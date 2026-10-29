-- ============================================================
--  Seguridad de la base de datos (Neon) — 2026-10
--  Mínimo privilegio + Row Level Security (RLS)
-- ------------------------------------------------------------
--  HOY la app se conecta como neondb_owner (dueño de todo): si esa clave
--  se filtra, quien la tenga puede borrar tablas enteras (DROP/TRUNCATE).
--  Con este script:
--    1. La app usa su propio usuario, gca_app, que SOLO puede leer y
--       escribir filas (SELECT/INSERT/UPDATE/DELETE). No puede crear,
--       alterar, vaciar ni borrar tablas.
--    2. RLS queda ACTIVO en todas las tablas: solo gca_app (con su
--       política) y el dueño ven filas. Cualquier otro rol que exista o
--       se cree después (por ejemplo los de la Data API de Neon,
--       "authenticated"/"anonymous") no ve nada aunque tenga permisos.
--    3. A PUBLIC se le quita todo en el esquema public.
--
--  CÓMO APLICARLO (lo hace el usuario; Claude no escribe en producción):
--    a) En la consola de Neon → Roles → "New role" → nombre: gca_app.
--       Neon genera la contraseña. Guárdala en un lugar seguro.
--    b) Ejecuta ESTE archivo en el SQL Editor de Neon, conectado como
--       neondb_owner, sobre la base neondb.
--    c) En Railway cambia DATABASE_URL para que use gca_app:
--         postgresql://gca_app:<clave nueva>@<host-pooler>/neondb?sslmode=require
--       y redespliega.
--    d) Las migraciones que cambian tablas (ALTER/CREATE) se siguen
--       corriendo con neondb_owner, nunca con gca_app.
--
--  Es idempotente: se puede volver a correr sin romper nada.
--  Para deshacerlo: ver el bloque "MARCHA ATRÁS" al final.
-- ============================================================

BEGIN;

-- 0. El rol tiene que existir (créalo en la consola de Neon, paso a)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'gca_app') THEN
        RAISE EXCEPTION 'Primero crea el rol gca_app en la consola de Neon (Roles → New role).';
    END IF;
END $$;

-- 1. Nadie más que los roles explícitos usa el esquema
REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM PUBLIC;
REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO gca_app;

-- 2. gca_app: solo datos (sin TRUNCATE, REFERENCES, TRIGGER ni DDL)
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO gca_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO gca_app;
-- Las tablas y secuencias que el dueño cree en el futuro, igual
ALTER DEFAULT PRIVILEGES FOR ROLE neondb_owner IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO gca_app;
ALTER DEFAULT PRIVILEGES FOR ROLE neondb_owner IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO gca_app;

-- 3. RLS en todas las tablas del esquema, con una política para gca_app.
--    El dueño (neondb_owner) no queda sujeto a RLS (no se usa FORCE), así
--    las migraciones siguen funcionando.
DO $$
DECLARE t record;
BEGIN
    FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', t.tablename);
        IF NOT EXISTS (SELECT 1 FROM pg_policies
                       WHERE schemaname = 'public' AND tablename = t.tablename AND policyname = 'gca_app_datos') THEN
            EXECUTE format('CREATE POLICY gca_app_datos ON public.%I FOR ALL TO gca_app USING (true) WITH CHECK (true)', t.tablename);
        END IF;
    END LOOP;
END $$;

-- 4. Comprobación: todas las tablas con RLS y con su política
DO $$
DECLARE sin_rls int; sin_politica int;
BEGIN
    SELECT count(*) INTO sin_rls FROM pg_tables WHERE schemaname = 'public' AND NOT rowsecurity;
    SELECT count(*) INTO sin_politica FROM pg_tables t
     WHERE schemaname = 'public'
       AND NOT EXISTS (SELECT 1 FROM pg_policies p WHERE p.schemaname = 'public' AND p.tablename = t.tablename AND p.policyname = 'gca_app_datos');
    IF sin_rls > 0 OR sin_politica > 0 THEN
        RAISE EXCEPTION 'Quedaron % tablas sin RLS y % sin política', sin_rls, sin_politica;
    END IF;
    RAISE NOTICE 'Listo: RLS activo y política gca_app_datos en todas las tablas de public.';
END $$;

COMMIT;

-- ============================================================
--  MARCHA ATRÁS (solo si hiciera falta volver a como estaba):
--
--  DO $$ DECLARE t record; BEGIN
--    FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
--      EXECUTE format('DROP POLICY IF EXISTS gca_app_datos ON public.%I', t.tablename);
--      EXECUTE format('ALTER TABLE public.%I DISABLE ROW LEVEL SECURITY', t.tablename);
--    END LOOP;
--  END $$;
--  -- y en Railway DATABASE_URL vuelve a neondb_owner.
-- ============================================================
