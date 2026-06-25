-- ============================================================
-- MIGRACIÓN v4 — Normalizar áreas + agregar CSRF + logging
-- ============================================================
-- Ejecutar en phpMyAdmin o MySQL Workbench
-- ============================================================

-- 1. Crear tabla de áreas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Insertar todas las áreas existentes
INSERT IGNORE INTO `areas` (`nombre`) VALUES
('MATEMÁTICAS'),
('HUMANIDADES'),
('LENGUAS EXTRANJERAS'),
('CIENCIAS SOCIALES'),
('CIENCIAS NATURALES'),
('EDUCACIÓN ARTÍSTICA'),
('EDUCACIÓN RELIGIOSA'),
('ÉTICA Y VALORES'),
('TECNOLOGÍA'),
('EDUCACIÓN FÍSICA'),
('COMPORTAMIENTO');

-- 3. Agregar columna area_id a asignaturas
ALTER TABLE `asignaturas`
  ADD COLUMN `area_id` int(11) DEFAULT NULL AFTER `area`,
  ADD KEY `area_id` (`area_id`);

-- 4. Poblar area_id desde el nombre del área existente
UPDATE `asignaturas` a
  JOIN `areas` ar ON a.`area` = ar.`nombre`
  SET a.`area_id` = ar.`id`;

-- 5. Agregar FK (después de poblar)
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `asignaturas_ibfk_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE RESTRICT;

-- 6. Crear tabla para sesiones / CSRF tokens
CREATE TABLE IF NOT EXISTS `csrf_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Agregar columna intensidad_horaria si no existe (por si se perdió en alguna migración)
-- La columna ya fue agregada en migration v1, esto es solo safety
