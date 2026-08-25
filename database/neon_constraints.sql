-- ============================================================
-- RESTAURAR INTEGRIDAD E ÍNDICES EN NEON  (2026-08-25)
-- ============================================================
-- La importación desde Supabase trajo tablas, datos, defaults y
-- NOT NULL, pero NINGUNA llave primaria, foránea, UNIQUE ni índice.
-- Este script los recrea según database/schema_supabase.sql.
--
-- Es idempotente: se puede volver a ejecutar sin efecto.
--   php database/neon_setup.php apply database/neon_constraints.sql
-- ============================================================

-- ---------- 1. PRIMARY KEY / UNIQUE / FOREIGN KEY ----------
DO $$
DECLARE
    r record;
BEGIN
    FOR r IN
        SELECT * FROM (VALUES
            -- llaves primarias
            ('usuarios',                  'usuarios_pkey',              'PRIMARY KEY (id)'),
            ('cursos',                    'cursos_pkey',                'PRIMARY KEY (id)'),
            ('areas',                     'areas_pkey',                 'PRIMARY KEY (id)'),
            ('asignaturas',               'asignaturas_pkey',           'PRIMARY KEY (id)'),
            ('estudiantes',               'estudiantes_pkey',           'PRIMARY KEY (id)'),
            ('notas',                     'notas_pkey',                 'PRIMARY KEY (id)'),
            ('profesor_curso_asignatura', 'prof_curso_asig_pkey',       'PRIMARY KEY (id)'),
            ('logros',                    'logros_pkey',                'PRIMARY KEY (id)'),
            ('alertas',                   'alertas_pkey',               'PRIMARY KEY (id)'),
            ('boletines_pdf',             'boletines_pdf_pkey',         'PRIMARY KEY (id)'),
            ('configuraciones',           'configuraciones_pkey',       'PRIMARY KEY (id)'),
            ('noticias',                  'noticias_pkey',              'PRIMARY KEY (id)'),
            ('eventos',                   'eventos_pkey',               'PRIMARY KEY (id)'),
            ('docentes',                  'docentes_pkey',              'PRIMARY KEY (id)'),
            ('asignatura_grado',          'asignatura_grado_pkey',      'PRIMARY KEY (asignatura_id, grado)'),
            ('directores_grupo',          'directores_grupo_pkey',      'PRIMARY KEY (profesor_id, curso_id)'),

            -- unicidad de negocio
            ('usuarios',                  'usuarios_email_key',         'UNIQUE (email)'),
            ('areas',                     'areas_nombre_key',           'UNIQUE (nombre)'),
            ('configuraciones',           'configuraciones_clave_key',  'UNIQUE (clave)'),
            ('profesor_curso_asignatura', 'prof_curso_asig_unico',      'UNIQUE (profesor_id, curso_id, asignatura_id)'),
            ('boletines_pdf',             'boletines_pdf_unico',        'UNIQUE (estudiante_id, periodo, "year")'),

            -- llaves foráneas
            ('asignaturas',               'fk_asignaturas_area',        'FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT'),
            ('asignatura_grado',          'fk_asig_grado_asignatura',   'FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE'),
            ('estudiantes',               'fk_estudiantes_padre',       'FOREIGN KEY (padre_id) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('estudiantes',               'fk_estudiantes_curso',       'FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL'),
            ('estudiantes',               'fk_estudiantes_usuario',     'FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL'),
            ('notas',                     'fk_notas_estudiante',        'FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE'),
            ('notas',                     'fk_notas_profesor',          'FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('notas',                     'fk_notas_asignatura',        'FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE'),
            ('notas',                     'fk_notas_curso',             'FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE'),
            ('profesor_curso_asignatura', 'fk_pca_profesor',            'FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('profesor_curso_asignatura', 'fk_pca_curso',               'FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE'),
            ('profesor_curso_asignatura', 'fk_pca_asignatura',          'FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE'),
            ('logros',                    'fk_logros_estudiante',       'FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE'),
            ('logros',                    'fk_logros_asignatura',       'FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE'),
            ('alertas',                   'fk_alertas_usuario',         'FOREIGN KEY (para_usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('boletines_pdf',             'fk_boletines_estudiante',    'FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE'),
            ('boletines_pdf',             'fk_boletines_generado_por',  'FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('directores_grupo',          'fk_dir_grupo_profesor',      'FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE'),
            ('directores_grupo',          'fk_dir_grupo_curso',         'FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE'),
            ('cursos',                    'fk_cursos_director',         'FOREIGN KEY (director_id) REFERENCES usuarios(id) ON DELETE SET NULL')
        ) AS t(tabla, nombre, definicion)
    LOOP
        IF NOT EXISTS (
            SELECT 1 FROM pg_constraint
            WHERE conname = r.nombre AND connamespace = 'public'::regnamespace
        ) THEN
            EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I %s', r.tabla, r.nombre, r.definicion);
            RAISE NOTICE 'creado: % (%)', r.nombre, r.tabla;
        END IF;
    END LOOP;
END $$;

-- ---------- 2. ÍNDICES DE CONSULTA ----------
-- Solo los que NO cubre ya una PK o un UNIQUE, elegidos según las
-- consultas reales del código (los WHERE/JOIN más frecuentes).

-- estudiantes: listar por curso es la consulta más repetida del sistema
CREATE INDEX IF NOT EXISTS idx_estudiantes_curso   ON estudiantes(curso_id);
CREATE INDEX IF NOT EXISTS idx_estudiantes_padre   ON estudiantes(padre_id);
CREATE INDEX IF NOT EXISTS idx_estudiantes_usuario ON estudiantes(usuario_id);

-- notas: los boletines y las definitivas filtran por estudiante+año+periodo;
-- registrar_notas filtra por curso+asignatura+periodo+año
CREATE INDEX IF NOT EXISTS idx_notas_est_anio_per   ON notas(estudiante_id, anio, periodo);
CREATE INDEX IF NOT EXISTS idx_notas_curso_asig_per ON notas(curso_id, asignatura_id, periodo, anio);
CREATE INDEX IF NOT EXISTS idx_notas_profesor       ON notas(profesor_id);
CREATE INDEX IF NOT EXISTS idx_notas_asignatura     ON notas(asignatura_id);
CREATE INDEX IF NOT EXISTS idx_notas_anio_periodo   ON notas(anio, periodo);

-- logros
CREATE INDEX IF NOT EXISTS idx_logros_est_anio_per ON logros(estudiante_id, anio, periodo);
CREATE INDEX IF NOT EXISTS idx_logros_asignatura   ON logros(asignatura_id);

-- profesor_curso_asignatura (el UNIQUE ya cubre profesor_id como primera columna)
CREATE INDEX IF NOT EXISTS idx_pca_curso      ON profesor_curso_asignatura(curso_id);
CREATE INDEX IF NOT EXISTS idx_pca_asignatura ON profesor_curso_asignatura(asignatura_id);

-- alertas: la campana consulta "mis alertas no leídas"
CREATE INDEX IF NOT EXISTS idx_alertas_usuario_leido ON alertas(para_usuario_id, leido);
CREATE INDEX IF NOT EXISTS idx_alertas_rol_leido     ON alertas(para_rol, leido);

-- boletines: el padre filtra por estudiante+año+publicado; el admin por periodo+año
CREATE INDEX IF NOT EXISTS idx_boletines_est_anio_pub ON boletines_pdf(estudiante_id, "year", publicado);
CREATE INDEX IF NOT EXISTS idx_boletines_periodo_anio ON boletines_pdf(periodo, "year");
CREATE INDEX IF NOT EXISTS idx_boletines_generado_por ON boletines_pdf(generado_por);

-- currícula
CREATE INDEX IF NOT EXISTS idx_asignaturas_area  ON asignaturas(area_id);
CREATE INDEX IF NOT EXISTS idx_asignaturas_nivel ON asignaturas(nivel);
CREATE INDEX IF NOT EXISTS idx_asig_grado_grado  ON asignatura_grado(grado);

-- cursos / direcciones de grupo
CREATE INDEX IF NOT EXISTS idx_cursos_director ON cursos(director_id);
CREATE INDEX IF NOT EXISTS idx_dir_grupo_curso ON directores_grupo(curso_id);

-- web pública: solo se listan los activos, ordenados por fecha
CREATE INDEX IF NOT EXISTS idx_noticias_activo ON noticias(activo, fecha_publicacion DESC);
CREATE INDEX IF NOT EXISTS idx_eventos_activo  ON eventos(activo, fecha_evento);
CREATE INDEX IF NOT EXISTS idx_docentes_activo ON docentes(activo);

-- ---------- 3. Estadísticas para el planificador ----------
ANALYZE;
