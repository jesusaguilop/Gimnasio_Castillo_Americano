-- ============================================================
--  Varios acudientes por estudiante (2026-10-23)
-- ============================================================
--  Antes cada estudiante tenía UN solo padre (estudiantes.padre_id).
--  El formulario de registro de padres trae estudiantes con papá y
--  mamá, así que el vínculo pasa a una tabla propia. padre_id se
--  conserva como "acudiente principal" (el primero vinculado).
--
--  Además: la FK de padre_id era ON DELETE CASCADE — borrar la cuenta
--  de un padre BORRABA a sus hijos, con notas, logros y boletines.
--  Pasa a ON DELETE SET NULL.
--
--  Idempotente: se puede volver a aplicar sin efectos.
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS estudiante_acudientes (
    estudiante_id INTEGER     NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
    padre_id      INTEGER     NOT NULL REFERENCES usuarios(id)    ON DELETE CASCADE,
    parentesco    VARCHAR(30),
    creado_en     TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (estudiante_id, padre_id)
);

CREATE INDEX IF NOT EXISTS idx_acudientes_padre ON estudiante_acudientes (padre_id);

INSERT INTO estudiante_acudientes (estudiante_id, padre_id)
SELECT id, padre_id FROM estudiantes WHERE padre_id IS NOT NULL
ON CONFLICT DO NOTHING;

ALTER TABLE estudiantes DROP CONSTRAINT IF EXISTS fk_estudiantes_padre;
ALTER TABLE estudiantes
    ADD CONSTRAINT fk_estudiantes_padre
    FOREIGN KEY (padre_id) REFERENCES usuarios(id) ON DELETE SET NULL;

COMMIT;
