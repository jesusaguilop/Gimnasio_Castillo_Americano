-- ============================================================
-- MIGRACIÓN v2 — Agregar nivel Preescolar
-- ============================================================
-- Ejecutar en phpMyAdmin o MySQL Workbench
-- ============================================================

-- 1. Cambiar el ENUM de cursos.nivel para incluir 'preescolar'
ALTER TABLE `cursos`
  MODIFY COLUMN `nivel` ENUM('preescolar','primaria','secundaria') NOT NULL DEFAULT 'secundaria';

-- 2. Actualizar cursos existentes de preescolar
UPDATE `cursos` SET `nivel` = 'preescolar'
WHERE LOWER(grado) IN ('maternal', 'prejardin', 'jardin', 'transicion');

-- 3. Cambiar el ENUM de asignaturas.nivel para incluir 'preescolar'
ALTER TABLE `asignaturas`
  MODIFY COLUMN `nivel` ENUM('preescolar','primaria','secundaria') NOT NULL DEFAULT 'secundaria';

-- 4. Agregar columna grado a asignaturas (opcional, para materias de un grado específico)
ALTER TABLE `asignaturas`
  ADD COLUMN `grado` VARCHAR(20) NULL DEFAULT NULL
  AFTER `nivel`;

-- 5. Verificar resultados
SELECT id, grado, nombre, nivel FROM cursos ORDER BY FIELD(grado,
  'maternal','prejardin','jardin','transicion',
  'primero','segundo','tercero','cuarto','quinto',
  'sexto','septimo','octavo','noveno','decimo','undecimo'), nombre;
