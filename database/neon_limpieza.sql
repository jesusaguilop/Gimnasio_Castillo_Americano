-- ============================================================
-- LIMPIEZA DE VESTIGIOS EN LA BASE  (2026-08-25)
-- ============================================================
-- Objetos que quedaron de la migración MySQL→PostgreSQL y que
-- NINGUNA parte del código usa. Verificado con grep sobre todo
-- el proyecto antes de escribir esto.
--
--   php database/neon_setup.php apply database/neon_limpieza.sql
-- ============================================================

-- 1. Tabla profesor_asignatura
--    La relación real es profesor_curso_asignatura (profesor+curso+materia).
--    Esta solo tenía profesor+materia, sin curso, y quedó con 1 fila huérfana
--    de mayo. Cero referencias en el código.
DROP TABLE IF EXISTS profesor_asignatura;

-- 2. Columna usuarios.reset_expira
--    Duplicado de reset_expires (esta última es la que usa el flujo de
--    recuperación de contraseña). Estaba vacía en las 23 filas.
ALTER TABLE usuarios DROP COLUMN IF EXISTS reset_expira;
