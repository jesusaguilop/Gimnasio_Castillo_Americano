-- Migración v3: Noticias, Eventos, Docentes

CREATE TABLE IF NOT EXISTS `noticias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `contenido` TEXT,
    `imagen` VARCHAR(255) DEFAULT NULL,
    `categoria` VARCHAR(100) DEFAULT 'General',
    `fecha_publicacion` DATE DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `eventos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT,
    `fecha_evento` DATE NOT NULL,
    `hora_evento` TIME DEFAULT NULL,
    `tipo` VARCHAR(50) DEFAULT 'General',
    `color` VARCHAR(7) DEFAULT '#c9a24d',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `docentes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(255) NOT NULL,
    `especialidad` VARCHAR(255) DEFAULT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `descripcion` TEXT,
    `email` VARCHAR(255) DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
