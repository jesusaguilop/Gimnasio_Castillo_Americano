-- ============================================================
-- MIGRACIÓN v5 — Asignatura ↔ Grado (junction table)
-- ============================================================

-- 1. Crear tabla de relación asignatura ↔ grado
CREATE TABLE IF NOT EXISTS `asignatura_grado` (
  `asignatura_id` int(11) NOT NULL,
  `grado` varchar(20) NOT NULL,
  `intensidad_horaria` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`asignatura_id`, `grado`),
  KEY `grado` (`grado`),
  CONSTRAINT `ag_ibfk_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar registros existentes que tienen grado específico
INSERT IGNORE INTO `asignatura_grado` (`asignatura_id`, `grado`, `intensidad_horaria`)
SELECT `id`, `grado`, `intensidad_horaria` FROM `asignaturas` WHERE `grado` IS NOT NULL AND `grado` != '';

-- 3. Para registros con grado NULL (todos los grados del nivel),
--    crear una entrada por cada grado de ese nivel
INSERT IGNORE INTO `asignatura_grado` (`asignatura_id`, `grado`, `intensidad_horaria`)
SELECT a.id, c.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (
  SELECT 'maternal' AS grado UNION SELECT 'prejardin' UNION SELECT 'jardin' UNION SELECT 'transicion'
) c
WHERE a.nivel = 'preescolar' AND (a.grado IS NULL OR a.grado = '');

INSERT IGNORE INTO `asignatura_grado` (`asignatura_id`, `grado`, `intensidad_horaria`)
SELECT a.id, c.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (
  SELECT 'primero' AS grado UNION SELECT 'segundo' UNION SELECT 'tercero' UNION SELECT 'cuarto' UNION SELECT 'quinto'
) c
WHERE a.nivel = 'primaria' AND (a.grado IS NULL OR a.grado = '');

INSERT IGNORE INTO `asignatura_grado` (`asignatura_id`, `grado`, `intensidad_horaria`)
SELECT a.id, c.grado, a.intensidad_horaria
FROM asignaturas a
CROSS JOIN (
  SELECT 'sexto' AS grado UNION SELECT 'septimo' UNION SELECT 'octavo' UNION SELECT 'noveno' UNION SELECT 'decimo' UNION SELECT 'once'
) c
WHERE a.nivel = 'secundaria' AND (a.grado IS NULL OR a.grado = '');

-- 4. Eliminar columna grado de asignaturas (ya no se necesita)
ALTER TABLE `asignaturas` DROP COLUMN `grado`;
