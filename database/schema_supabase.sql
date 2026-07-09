-- ============================================================
-- ESQUEMA COMPLETO PARA SUPABASE (PostgreSQL)
-- Sistema de Gestión de Boletines - GCA
-- ============================================================
-- Este script crea todas las tablas necesarias con la
-- sintaxis compatible con PostgreSQL / Supabase.
-- ============================================================

-- Extensión para generar UUIDs (opcional, usamos SERIAL)
-- CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- TABLAS BASE (del dump original + migraciones)
-- ============================================================

-- 1. USUARIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires TIMESTAMP DEFAULT NULL
);

-- 2. CURSOS
CREATE TABLE IF NOT EXISTS cursos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    grado VARCHAR(20) NOT NULL,
    nivel VARCHAR(20) NOT NULL DEFAULT 'secundaria',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. ASIGNATURAS
CREATE TABLE IF NOT EXISTS asignaturas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL DEFAULT '',
    nivel VARCHAR(20) NOT NULL DEFAULT 'secundaria',
    intensidad_horaria INTEGER NOT NULL DEFAULT 0,
    grado VARCHAR(20) DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 4. ESTUDIANTES
CREATE TABLE IF NOT EXISTS estudiantes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(30) NOT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    padre_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    curso_id INTEGER DEFAULT NULL REFERENCES cursos(id) ON DELETE SET NULL,
    usuario_id INTEGER DEFAULT NULL REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 5. NOTAS
CREATE TABLE IF NOT EXISTS notas (
    id SERIAL PRIMARY KEY,
    estudiante_id INTEGER NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
    profesor_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    asignatura_id INTEGER NOT NULL REFERENCES asignaturas(id) ON DELETE CASCADE,
    curso_id INTEGER NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    periodo VARCHAR(20) NOT NULL,
    nota INTEGER NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 6. PROFESOR_CURSO_ASIGNATURA
CREATE TABLE IF NOT EXISTS profesor_curso_asignatura (
    id SERIAL PRIMARY KEY,
    profesor_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    curso_id INTEGER NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    asignatura_id INTEGER NOT NULL REFERENCES asignaturas(id) ON DELETE CASCADE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLAS DE MIGRACIÓN v1
-- ============================================================

-- 7. LOGROS
CREATE TABLE IF NOT EXISTS logros (
    id SERIAL PRIMARY KEY,
    estudiante_id INTEGER NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
    asignatura_id INTEGER NOT NULL REFERENCES asignaturas(id) ON DELETE CASCADE,
    periodo VARCHAR(20) NOT NULL,
    logro TEXT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 8. ALERTAS
CREATE TABLE IF NOT EXISTS alertas (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL DEFAULT 'sistema',
    titulo VARCHAR(200) NOT NULL DEFAULT '',
    mensaje TEXT NOT NULL,
    para_rol VARCHAR(20) DEFAULT NULL,
    para_usuario_id INTEGER DEFAULT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    leido SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 9. BOLETINES_PDF
CREATE TABLE IF NOT EXISTS boletines_pdf (
    id SERIAL PRIMARY KEY,
    estudiante_id INTEGER NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
    periodo VARCHAR(20) NOT NULL,
    year INTEGER NOT NULL DEFAULT EXTRACT(YEAR FROM CURRENT_TIMESTAMP),
    ruta_pdf VARCHAR(255) NOT NULL,
    generado_por INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (estudiante_id, periodo, year)
);

-- 10. DIRECTORES_GRUPO
CREATE TABLE IF NOT EXISTS directores_grupo (
    profesor_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    curso_id INTEGER NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (profesor_id, curso_id)
);

-- 11. CONFIGURACIONES
CREATE TABLE IF NOT EXISTS configuraciones (
    id SERIAL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLAS DE MIGRACIÓN v3
-- ============================================================

-- 12. NOTICIAS
CREATE TABLE IF NOT EXISTS noticias (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    imagen VARCHAR(255) DEFAULT NULL,
    categoria VARCHAR(100) DEFAULT 'General',
    fecha_publicacion DATE DEFAULT NULL,
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 13. EVENTOS
CREATE TABLE IF NOT EXISTS eventos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_evento DATE NOT NULL,
    hora_evento TIME DEFAULT NULL,
    tipo VARCHAR(50) DEFAULT 'General',
    color VARCHAR(7) DEFAULT '#c9a24d',
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 14. DOCENTES
CREATE TABLE IF NOT EXISTS docentes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    especialidad VARCHAR(255) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    descripcion TEXT,
    email VARCHAR(255) DEFAULT NULL,
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- ÍNDICES ADICIONALES
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_estudiantes_padre ON estudiantes(padre_id);
CREATE INDEX IF NOT EXISTS idx_estudiantes_curso ON estudiantes(curso_id);
CREATE INDEX IF NOT EXISTS idx_notas_estudiante ON notas(estudiante_id);
CREATE INDEX IF NOT EXISTS idx_notas_profesor ON notas(profesor_id);
CREATE INDEX IF NOT EXISTS idx_notas_asignatura ON notas(asignatura_id);
CREATE INDEX IF NOT EXISTS idx_notas_curso ON notas(curso_id);
CREATE INDEX IF NOT EXISTS idx_prof_curso_asig_profesor ON profesor_curso_asignatura(profesor_id);
CREATE INDEX IF NOT EXISTS idx_prof_curso_asig_curso ON profesor_curso_asignatura(curso_id);
CREATE INDEX IF NOT EXISTS idx_prof_curso_asig_asignatura ON profesor_curso_asignatura(asignatura_id);
CREATE INDEX IF NOT EXISTS idx_logros_estudiante ON logros(estudiante_id);
CREATE INDEX IF NOT EXISTS idx_logros_asignatura ON logros(asignatura_id);
CREATE INDEX IF NOT EXISTS idx_alertas_usuario ON alertas(para_usuario_id);
CREATE INDEX IF NOT EXISTS idx_alertas_rol ON alertas(para_rol);
CREATE INDEX IF NOT EXISTS idx_boletines_estudiante ON boletines_pdf(estudiante_id);
CREATE INDEX IF NOT EXISTS idx_boletines_generado_por ON boletines_pdf(generado_por);

-- ============================================================
-- TRIGGER: actualizar timestamp en configuraciones
-- ============================================================
CREATE OR REPLACE FUNCTION update_actualizado_en()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_configuraciones_actualizado ON configuraciones;
CREATE TRIGGER trg_configuraciones_actualizado
    BEFORE UPDATE ON configuraciones
    FOR EACH ROW
    EXECUTE FUNCTION update_actualizado_en();

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Insertar configuraciones por defecto
INSERT INTO configuraciones (clave, valor) VALUES
    ('plataforma_activa', '0'),
    ('periodo_activo', '1'),
    ('fecha_apertura', ''),
    ('fecha_cierre', '')
ON CONFLICT (clave) DO NOTHING;

-- Asignaturas de PRIMARIA
INSERT INTO asignaturas (nombre, area, nivel, intensidad_horaria) VALUES
    ('Matematicas',   'MATEMATICAS',       'primaria', 4),
    ('Geometria',     'MATEMATICAS',       'primaria', 2),
    ('Castellano',    'HUMANIDADES',       'primaria', 5),
    ('Taller Lector', 'HUMANIDADES',       'primaria', 2),
    ('Ingles',        'LENGUAS EXTRANJERAS', 'primaria', 4),
    ('Instituto',     'LENGUAS EXTRANJERAS', 'primaria', 2),
    ('Sociales',      'CIENCIAS SOCIALES', 'primaria', 3),
    ('Naturales',     'CIENCIAS NATURALES',  'primaria', 3),
    ('Artistica',     'EDUCACION ARTISTICA', 'primaria', 2),
    ('Religion',      'EDUCACION RELIGIOSA', 'primaria', 1),
    ('Etica y Valores', 'ETICA Y VALORES',  'primaria', 1),
    ('Robotica',      'TECNOLOGIA',        'primaria', 1),
    ('Tecnologia',    'TECNOLOGIA',        'primaria', 1),
    ('Educacion Fisica', 'EDUCACION FISICA', 'primaria', 2),
    ('Danza',         'EDUCACION FISICA',  'primaria', 1),
    ('Comportamiento','COMPORTAMIENTO',    'primaria', 0)
ON CONFLICT DO NOTHING;

-- Asignaturas de SECUNDARIA
INSERT INTO asignaturas (nombre, area, nivel, intensidad_horaria) VALUES
    ('Aritmetica',    'MATEMATICAS',       'secundaria', 4),
    ('Geometria',     'MATEMATICAS',       'secundaria', 2),
    ('Estadisticas',  'MATEMATICAS',       'secundaria', 2),
    ('Castellano',    'HUMANIDADES',       'secundaria', 6),
    ('Lectura Critica', 'HUMANIDADES',     'secundaria', 2),
    ('Ingles',        'LENGUAS EXTRANJERAS', 'secundaria', 5),
    ('Instituto',     'LENGUAS EXTRANJERAS', 'secundaria', 2),
    ('Sociales',      'CIENCIAS SOCIALES', 'secundaria', 3),
    ('Biologia',      'CIENCIAS NATURALES',  'secundaria', 3),
    ('Quimica',       'CIENCIAS NATURALES',  'secundaria', 1),
    ('Fisica',        'CIENCIAS NATURALES',  'secundaria', 1),
    ('Artistica',     'EDUCACION ARTISTICA', 'secundaria', 2),
    ('Religion',      'EDUCACION RELIGIOSA', 'secundaria', 1),
    ('Etica y Valores', 'ETICA Y VALORES',   'secundaria', 1),
    ('Robotica',      'TECNOLOGIA',        'secundaria', 1),
    ('Tecnologia',    'TECNOLOGIA',        'secundaria', 1),
    ('Educacion Fisica', 'EDUCACION FISICA', 'secundaria', 1),
    ('Danza',         'EDUCACION FISICA',  'secundaria', 1),
    ('Comportamiento','COMPORTAMIENTO',    'secundaria', 0)
ON CONFLICT DO NOTHING;
