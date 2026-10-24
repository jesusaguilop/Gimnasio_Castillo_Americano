<?php
/**
 * Mapa de navegación del panel de administración.
 *
 * Una sola fuente para la barra lateral (grupos y enlaces) y para la barra
 * superior (título de la página y migas de pan). Las páginas que no son de
 * menú (crear_*, editar_*) declaran a qué entrada del menú pertenecen.
 */

if (!function_exists('gca_admin_nav')) {

    /** Grupos del menú lateral: [etiqueta, [[archivo, texto, icono], ...]] */
    function gca_admin_nav(): array
    {
        return [
            ['Inicio', [
                ['dashboard.php', 'Resumen', 'bi-grid-1x2'],
                ['alertas.php', 'Alertas', 'bi-bell'],
            ]],
            ['Académico', [
                ['ver_estudiantes.php', 'Estudiantes', 'bi-mortarboard'],
                ['ver_cursos.php', 'Cursos', 'bi-collection'],
                ['ver_asignaturas.php', 'Asignaturas', 'bi-book'],
                ['asignar_estudiante.php', 'Estudiantes por curso', 'bi-diagram-3'],
                ['asignar_profesor.php', 'Materias por profesor', 'bi-journal-arrow-up'],
                ['asignar_director.php', 'Directores de grupo', 'bi-person-badge'],
            ]],
            ['Personas', [
                ['ver_profesores.php', 'Profesores', 'bi-person-workspace'],
                ['ver_padres.php', 'Padres y acudientes', 'bi-people'],
                ['asignar_padre.php', 'Vincular acudientes', 'bi-link-45deg'],
                ['ver_admins.php', 'Administradores', 'bi-shield-lock'],
            ]],
            ['Boletines', [
                ['enviar_boletin.php', 'Publicar boletines', 'bi-file-earmark-check'],
                ['mejores_estudiantes.php', 'Mejores estudiantes', 'bi-trophy'],
            ]],
            ['Sitio web', [
                ['ver_noticias.php', 'Noticias', 'bi-newspaper'],
                ['ver_eventos.php', 'Eventos', 'bi-calendar-event'],
                ['ver_docentes.php', 'Docentes del sitio', 'bi-person-vcard'],
            ]],
            ['Plataforma', [
                ['plataforma.php', 'Periodo y notas', 'bi-sliders'],
            ]],
        ];
    }

    /**
     * Metadatos de cada página: [título, entrada del menú a la que pertenece].
     * La entrada define el grupo (para las migas) y el enlace marcado como activo.
     */
    function gca_admin_paginas(): array
    {
        return [
            'dashboard.php'          => ['Resumen', 'dashboard.php'],
            'alertas.php'            => ['Alertas del sistema', 'alertas.php'],

            'ver_estudiantes.php'    => ['Estudiantes', 'ver_estudiantes.php'],
            'crear_estudiante.php'   => ['Nuevo estudiante', 'ver_estudiantes.php'],
            'editar_estudiante.php'  => ['Editar estudiante', 'ver_estudiantes.php'],
            'ver_cursos.php'         => ['Cursos', 'ver_cursos.php'],
            'crear_curso.php'        => ['Nuevo curso', 'ver_cursos.php'],
            'editar_curso.php'       => ['Editar curso', 'ver_cursos.php'],
            'ver_asignaturas.php'    => ['Asignaturas', 'ver_asignaturas.php'],
            'crear_asignatura.php'   => ['Nueva asignatura', 'ver_asignaturas.php'],
            'editar_asignatura.php'  => ['Editar asignatura', 'ver_asignaturas.php'],
            'asignar_estudiante.php' => ['Asignar estudiante a curso', 'asignar_estudiante.php'],
            'asignar_profesor.php'   => ['Materias por profesor', 'asignar_profesor.php'],
            'asignar_director.php'   => ['Directores de grupo', 'asignar_director.php'],

            'ver_profesores.php'     => ['Profesores', 'ver_profesores.php'],
            'crear_profesor.php'     => ['Nuevo profesor', 'ver_profesores.php'],
            'editar_profesor.php'    => ['Editar profesor', 'ver_profesores.php'],
            'ver_padres.php'         => ['Padres y acudientes', 'ver_padres.php'],
            'crear_padre.php'        => ['Nuevo padre o acudiente', 'ver_padres.php'],
            'editar_padre.php'       => ['Editar padre o acudiente', 'ver_padres.php'],
            'asignar_padre.php'      => ['Vincular acudientes', 'asignar_padre.php'],
            'ver_admins.php'         => ['Administradores', 'ver_admins.php'],
            'crear_admin.php'        => ['Nuevo administrador', 'ver_admins.php'],
            'editar_admin.php'       => ['Editar administrador', 'ver_admins.php'],

            'enviar_boletin.php'     => ['Publicar boletines', 'enviar_boletin.php'],
            'mejores_estudiantes.php'=> ['Mejores estudiantes por grado', 'mejores_estudiantes.php'],

            'ver_noticias.php'       => ['Noticias', 'ver_noticias.php'],
            'crear_noticia.php'      => ['Nueva noticia', 'ver_noticias.php'],
            'editar_noticia.php'     => ['Editar noticia', 'ver_noticias.php'],
            'ver_eventos.php'        => ['Eventos', 'ver_eventos.php'],
            'crear_evento.php'       => ['Nuevo evento', 'ver_eventos.php'],
            'editar_evento.php'      => ['Editar evento', 'ver_eventos.php'],
            'ver_docentes.php'       => ['Docentes del sitio web', 'ver_docentes.php'],
            'crear_docente.php'      => ['Nuevo docente del sitio', 'ver_docentes.php'],
            'editar_docente.php'     => ['Editar docente del sitio', 'ver_docentes.php'],

            'plataforma.php'         => ['Periodo y notas', 'plataforma.php'],
        ];
    }

    /**
     * Datos de la página actual para la barra superior:
     * ['titulo', 'activo' => archivo del menú, 'migas' => [[texto, url|null], ...]]
     */
    function gca_admin_pagina_actual(?string $fallbackTitulo = null): array
    {
        $archivo = basename($_SERVER['PHP_SELF'] ?? '');
        $paginas = gca_admin_paginas();
        [$titulo, $activo] = $paginas[$archivo] ?? [$fallbackTitulo ?: 'Panel', $archivo];

        $migas = [['Inicio', 'dashboard.php']];
        if ($archivo !== 'dashboard.php') {
            foreach (gca_admin_nav() as [$grupo, $items]) {
                foreach ($items as [$href, $texto]) {
                    if ($href === $activo) {
                        if ($grupo !== 'Inicio') {
                            $migas[] = [$grupo, null];
                        }
                        if ($activo !== $archivo) {
                            $migas[] = [$texto, $href];
                        }
                        break 2;
                    }
                }
            }
            $migas[] = [$titulo, null];
        }

        return ['titulo' => $titulo, 'activo' => $activo, 'migas' => $migas];
    }
}
