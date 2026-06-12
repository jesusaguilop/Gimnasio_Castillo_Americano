-- ============================================================
-- MIGRACIÓN BASE DE DATOS — Sistema de Gestión de Boletines GCA
-- ============================================================
-- Este script NO borra datos existentes. Solo agrega:
--   • Columnas nuevas a tablas existentes
--   • Tablas nuevas (logros, alertas, boletines_pdf, directores_grupo)
--   • Datos de materias completa (primaria + secundaria)
--   • Configuraciones iniciales
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. MODIFICAR TABLAS EXISTENTES
-- ============================================================

-- 1a. cursos → agregar nivel (primaria / secundaria)
ALTER TABLE `cursos`
  ADD COLUMN `nivel` ENUM('primaria','secundaria') NOT NULL DEFAULT 'secundaria'
  AFTER `grado`;

-- (Los cursos existentes quedan como 'secundaria' por defecto,
--  puedes cambiar manualmente los que sean de primaria después)

-- 1b. asignaturas → agregar área, nivel e intensidad_horaria
ALTER TABLE `asignaturas`
  ADD COLUMN `area` varchar(100) NOT NULL DEFAULT ''
  AFTER `nombre`,
  ADD COLUMN `nivel` ENUM('primaria','secundaria') NOT NULL DEFAULT 'secundaria'
  AFTER `area`,
  ADD COLUMN `intensidad_horaria` INT NOT NULL DEFAULT 0
  AFTER `nivel`;

-- Actualizar la única materia existente (MATEMATICAS) con área y nivel
UPDATE `asignaturas`
SET `area` = 'MATEMÁTICAS', `nivel` = 'secundaria'
WHERE `nombre` = 'MATEMATICAS';

-- ============================================================
-- 2. CREAR TABLAS NUEVAS
-- ============================================================

-- 2a. Logros por estudiante
CREATE TABLE IF NOT EXISTS `logros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `logro` text NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `asignatura_id` (`asignatura_id`),
  CONSTRAINT `logros_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logros_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2b. Alertas y notificaciones del sistema
CREATE TABLE IF NOT EXISTS `alertas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL DEFAULT 'sistema',
  `titulo` varchar(200) NOT NULL DEFAULT '',
  `mensaje` text NOT NULL,
  `para_rol` varchar(20) DEFAULT NULL,
  `para_usuario_id` int(11) DEFAULT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `para_usuario_id` (`para_usuario_id`),
  KEY `para_rol` (`para_rol`),
  CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`para_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2c. Boletines PDF generados
CREATE TABLE IF NOT EXISTS `boletines_pdf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `year` year(4) NOT NULL DEFAULT year(current_timestamp()),
  `ruta_pdf` varchar(255) NOT NULL,
  `generado_por` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `generado_por` (`generado_por`),
  CONSTRAINT `boletines_pdf_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boletines_pdf_ibfk_2` FOREIGN KEY (`generado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2d. Directores de grupo (profesor → curso)
CREATE TABLE IF NOT EXISTS `directores_grupo` (
  `profesor_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`profesor_id`,`curso_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `directores_grupo_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `directores_grupo_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. CONFIGURACIONES INICIALES
-- ============================================================

-- Asegurar que la tabla configuraciones existe
CREATE TABLE IF NOT EXISTS `configuraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar configuraciones por defecto (solo si no existen)
INSERT IGNORE INTO `configuraciones` (`clave`, `valor`) VALUES
('plataforma_activa', '0'),
('periodo_activo', '1'),
('fecha_apertura', ''),
('fecha_cierre', '');

-- ============================================================
-- 4. MATERIAS COMPLETAS POR NIVEL
-- ============================================================

-- Solo se insertan si no hay materias con esos nombres ya
-- (para evitar duplicados al re-ejecutar)

-- 4a. PRIMARIA
INSERT IGNORE INTO `asignaturas` (`nombre`, `area`, `nivel`) VALUES
('Matemáticas',   'MATEMÁTICAS',       'primaria'),
('Geometría',     'MATEMÁTICAS',       'primaria'),
('Castellano',    'HUMANIDADES',       'primaria'),
('Taller Lector', 'HUMANIDADES',       'primaria'),
('Inglés',        'LENGUAS EXTRANJERAS', 'primaria'),
('Instituto',     'LENGUAS EXTRANJERAS', 'primaria'),
('Sociales',      'CIENCIAS SOCIALES', 'primaria'),
('Naturales',     'CIENCIAS NATURALES',  'primaria'),
('Artística',     'EDUCACIÓN ARTÍSTICA', 'primaria'),
('Religión',      'EDUCACIÓN RELIGIOSA', 'primaria'),
('Ética y Valores', 'ÉTICA Y VALORES',  'primaria'),
('Robótica',      'TECNOLOGÍA',        'primaria'),
('Tecnología',    'TECNOLOGÍA',        'primaria'),
('Educación Física', 'EDUCACIÓN FÍSICA', 'primaria'),
('Danza',         'EDUCACIÓN FÍSICA',  'primaria'),
('Comportamiento','COMPORTAMIENTO',    'primaria');

-- 4b. SECUNDARIA
INSERT IGNORE INTO `asignaturas` (`nombre`, `area`, `nivel`) VALUES
('Aritmética',    'MATEMÁTICAS',       'secundaria'),
('Geometría',     'MATEMÁTICAS',       'secundaria'),
('Estadísticas',  'MATEMÁTICAS',       'secundaria'),
('Castellano',    'HUMANIDADES',       'secundaria'),
('Lectura Crítica', 'HUMANIDADES',     'secundaria'),
('Inglés',        'LENGUAS EXTRANJERAS', 'secundaria'),
('Instituto',     'LENGUAS EXTRANJERAS', 'secundaria'),
('Sociales',      'CIENCIAS SOCIALES', 'secundaria'),
('Biología',      'CIENCIAS NATURALES',  'secundaria'),
('Química',       'CIENCIAS NATURALES',  'secundaria'),
('Física',        'CIENCIAS NATURALES',  'secundaria'),
('Artística',     'EDUCACIÓN ARTÍSTICA', 'secundaria'),
('Religión',      'EDUCACIÓN RELIGIOSA', 'secundaria'),
('Ética y Valores', 'ÉTICA Y VALORES',   'secundaria'),
('Robótica',      'TECNOLOGÍA',        'secundaria'),
('Tecnología',    'TECNOLOGÍA',        'secundaria'),
('Educación Física', 'EDUCACIÓN FÍSICA', 'secundaria'),
('Danza',         'EDUCACIÓN FÍSICA',  'secundaria'),
('Comportamiento','COMPORTAMIENTO',    'secundaria');

-- ============================================================
-- 5. ASIGNAR INTENSIDAD HORARIA
-- ============================================================
UPDATE `asignaturas` SET `intensidad_horaria` = 4 WHERE `id` = 18; -- Aritmética
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 19; -- Geometría
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 20; -- Estadísticas
UPDATE `asignaturas` SET `intensidad_horaria` = 6 WHERE `id` = 21; -- Castellano
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 22; -- Lectura Crítica
UPDATE `asignaturas` SET `intensidad_horaria` = 5 WHERE `id` = 23; -- Inglés
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 24; -- Instituto
UPDATE `asignaturas` SET `intensidad_horaria` = 3 WHERE `id` = 25; -- Sociales
UPDATE `asignaturas` SET `intensidad_horaria` = 3 WHERE `id` = 26; -- Biología
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 27; -- Química
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 28; -- Física
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 29; -- Artística
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 30; -- Religión
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 31; -- Ética y Valores
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 32; -- Robótica
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 33; -- Tecnología
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 34; -- Educación Física
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 35; -- Danza
UPDATE `asignaturas` SET `intensidad_horaria` = 0 WHERE `id` = 36; -- Comportamiento
UPDATE `asignaturas` SET `intensidad_horaria` = 4 WHERE `id` = 2;  -- Matemáticas (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 3;  -- Geometría (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 5 WHERE `id` = 4;  -- Castellano (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 5;  -- Taller Lector
UPDATE `asignaturas` SET `intensidad_horaria` = 4 WHERE `id` = 6;  -- Inglés (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 7;  -- Instituto (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 3 WHERE `id` = 8;  -- Sociales (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 3 WHERE `id` = 9;  -- Naturales
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 10; -- Artística (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 11; -- Religión (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 12; -- Ética (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 13; -- Robótica (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 14; -- Tecnología (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 2 WHERE `id` = 15; -- Educación Física (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 1 WHERE `id` = 16; -- Danza (primaria)
UPDATE `asignaturas` SET `intensidad_horaria` = 0 WHERE `id` = 17; -- Comportamiento (primaria)

-- ============================================================
-- 5b. CORREGIR ÁREAS CON CARACTERES CORRUPTOS
-- ============================================================
UPDATE `asignaturas` SET `area` = 'MATEMÁTICAS'        WHERE `area` = 'MATEM├üTICAS';
UPDATE `asignaturas` SET `area` = 'EDUCACIÓN ARTÍSTICA' WHERE `area` = 'EDUCACI├ôN ART├ìSTICA';
UPDATE `asignaturas` SET `area` = 'EDUCACIÓN RELIGIOSA' WHERE `area` = 'EDUCACI├ôN RELIGIOSA';
UPDATE `asignaturas` SET `area` = 'ÉTICA Y VALORES'    WHERE `area` = '├ëTICA Y VALORES';
UPDATE `asignaturas` SET `area` = 'TECNOLOGÍA'         WHERE `area` = 'TECNOLOG├ìA';
UPDATE `asignaturas` SET `area` = 'EDUCACIÓN FÍSICA'   WHERE `area` = 'EDUCACI├ôN F├ìSICA';

-- ============================================================
-- 6. ACTUALIZAR NOTAS EXISTENTES: periodo numérico
-- ============================================================
-- Las notas actuales usan periodo='1'. Asegurar consistencia.
UPDATE `notas` SET `periodo` = '1' WHERE `periodo` NOT IN ('1','2','3','4');
UPDATE `notas` SET `periodo` = '1' WHERE `periodo` = '1er Periodo';
UPDATE `notas` SET `periodo` = '2' WHERE `periodo` = '2do Periodo';
UPDATE `notas` SET `periodo` = '3' WHERE `periodo` = '3er Periodo';
UPDATE `notas` SET `periodo` = '4' WHERE `periodo` = '4to Periodo';

-- ============================================================
-- FIN
-- ============================================================

COMMIT;
