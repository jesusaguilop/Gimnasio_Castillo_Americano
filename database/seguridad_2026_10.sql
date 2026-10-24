-- ============================================================
--  Endurecimiento de cuentas (2026-10-23)
-- ============================================================
--  1. usuarios.debe_cambiar_clave: las cuentas creadas por el colegio
--     (p. ej. las de padres importadas del formulario) entran con una
--     clave provisional y el sistema obliga a cambiarla al entrar.
--  2. login_intentos: freno a la fuerza bruta guardado en la BD. El
--     anterior contaba los fallos en la sesión, y basta con borrar la
--     cookie para saltárselo.
--  3. Limpieza de la columna duplicada usuarios.reset_expira (el código
--     usa reset_expires).
--
--  Idempotente.
-- ============================================================

BEGIN;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS debe_cambiar_clave BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS login_intentos (
    id        BIGSERIAL   PRIMARY KEY,
    ip        VARCHAR(64) NOT NULL,
    email     VARCHAR(255) NOT NULL,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_login_intentos_ip    ON login_intentos (ip, creado_en);
CREATE INDEX IF NOT EXISTS idx_login_intentos_email ON login_intentos (email, creado_en);

ALTER TABLE usuarios DROP COLUMN IF EXISTS reset_expira;

-- 4. Examen final del período: se guarda junto a la nota y se muestra en el
--    boletín (columna EXAMEN). NO entra al cálculo: notas.nota ya es la
--    definitiva que registra el profesor.
ALTER TABLE notas ADD COLUMN IF NOT EXISTS examen SMALLINT
    CHECK (examen IS NULL OR examen BETWEEN 0 AND 100);

-- 5. Integridad de notas: una sola nota/logro por estudiante, asignatura,
--    período y año, y notas en 0-100. (Verificado 2026-10-23: no hay
--    duplicados ni valores fuera de rango, así que se puede aplicar tal cual.)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_notas_est_asig_per_anio') THEN
        ALTER TABLE notas ADD CONSTRAINT uq_notas_est_asig_per_anio
            UNIQUE (estudiante_id, asignatura_id, periodo, anio);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_logros_est_asig_per_anio') THEN
        ALTER TABLE logros ADD CONSTRAINT uq_logros_est_asig_per_anio
            UNIQUE (estudiante_id, asignatura_id, periodo, anio);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_notas_rango') THEN
        ALTER TABLE notas ADD CONSTRAINT ck_notas_rango CHECK (nota BETWEEN 0 AND 100);
    END IF;
END $$;

COMMIT;
