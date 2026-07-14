-- ============================================================
-- MIGRACIÓN CORRECTIVA — PostgreSQL / Supabase
-- ============================================================
-- Reescribe en sintaxis PostgreSQL lo que las migraciones
-- v4/v5/v6 (escritas en MySQL) nunca pudieron aplicar:
--   • Tabla `areas` + columna `asignaturas.area_id`
--   • Tabla `asignatura_grado`
--   • Columna `profesor_curso_asignatura.porcentaje`
--   • Restricción única en profesor_curso_asignatura
--   • `estudiantes.padre_id` pasa a ser NULLABLE (se asigna después)
--
-- Es IDEMPOTENTE: se puede ejecutar varias veces sin error,
-- exista o no cada objeto previamente.
-- Ejecutar en el SQL Editor de Supabase.
-- ============================================================

BEGIN;

-- ------------------------------------------------------------
-- 1. Tabla de ÁREAS (normalización de asignaturas.area)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS areas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Poblar `areas` desde los valores reales existentes en asignaturas
-- (garantiza que los nombres coincidan aunque tengan/omitan tildes).
INSERT INTO areas (nombre)
SELECT DISTINCT area FROM asignaturas
WHERE area IS NOT NULL AND area <> ''
ON CONFLICT (nombre) DO NOTHING;

-- Columna area_id en asignaturas
ALTER TABLE asignaturas ADD COLUMN IF NOT EXISTS area_id INTEGER;

-- Poblar area_id por coincidencia de nombre
UPDATE asignaturas a
SET area_id = ar.id
FROM areas ar
WHERE a.area = ar.nombre AND a.area_id IS DISTINCT FROM ar.id;

-- FK (solo si aún no existe)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'asignaturas_area_id_fkey'
    ) THEN
        ALTER TABLE asignaturas
            ADD CONSTRAINT asignaturas_area_id_fkey
            FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT;
    END IF;
END$$;

-- ------------------------------------------------------------
-- 2. Tabla ASIGNATURA_GRADO (relación asignatura ↔ grado + IH)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asignatura_grado (
    asignatura_id INTEGER NOT NULL REFERENCES asignaturas(id) ON DELETE CASCADE,
    grado VARCHAR(20) NOT NULL,
    intensidad_horaria INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (asignatura_id, grado)
);

-- Migrar asignaturas que ya tienen un grado específico
INSERT INTO asignatura_grado (asignatura_id, grado, intensidad_horaria)
SELECT id, grado, intensidad_horaria
FROM asignaturas
WHERE grado IS NOT NULL AND grado <> ''
ON CONFLICT (asignatura_id, grado) DO NOTHING;

-- Asignaturas sin grado específico → una fila por cada grado del nivel
INSERT INTO asignatura_grado (asignatura_id, grado, intensidad_horaria)
SELECT a.id, g.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (VALUES ('maternal'),('prejardin'),('jardin'),('transicion')) AS g(grado)
WHERE a.nivel = 'preescolar' AND (a.grado IS NULL OR a.grado = '')
ON CONFLICT (asignatura_id, grado) DO NOTHING;

INSERT INTO asignatura_grado (asignatura_id, grado, intensidad_horaria)
SELECT a.id, g.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (VALUES ('primero'),('segundo'),('tercero'),('cuarto'),('quinto')) AS g(grado)
WHERE a.nivel = 'primaria' AND (a.grado IS NULL OR a.grado = '')
ON CONFLICT (asignatura_id, grado) DO NOTHING;

INSERT INTO asignatura_grado (asignatura_id, grado, intensidad_horaria)
SELECT a.id, g.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (VALUES ('sexto'),('septimo'),('octavo'),('noveno'),('decimo'),('once')) AS g(grado)
WHERE a.nivel = 'secundaria' AND (a.grado IS NULL OR a.grado = '')
ON CONFLICT (asignatura_id, grado) DO NOTHING;

CREATE INDEX IF NOT EXISTS idx_asignatura_grado_grado ON asignatura_grado(grado);

-- ------------------------------------------------------------
-- 3. PORCENTAJE en profesor_curso_asignatura
-- ------------------------------------------------------------
ALTER TABLE profesor_curso_asignatura
    ADD COLUMN IF NOT EXISTS porcentaje INTEGER NOT NULL DEFAULT 100;

-- Evita duplicados profesor/curso/asignatura (necesario para ON CONFLICT)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'uq_profesor_curso_asignatura'
    ) THEN
        ALTER TABLE profesor_curso_asignatura
            ADD CONSTRAINT uq_profesor_curso_asignatura
            UNIQUE (profesor_id, curso_id, asignatura_id);
    END IF;
END$$;

-- ------------------------------------------------------------
-- 4. estudiantes.padre_id NULLABLE
--    (el padre se asigna después vía asignar_padre.php)
-- ------------------------------------------------------------
ALTER TABLE estudiantes ALTER COLUMN padre_id DROP NOT NULL;

COMMIT;
