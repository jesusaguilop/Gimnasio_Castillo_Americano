<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

$nombre_admin = htmlspecialchars(userName() ?? "Administrador");

$stats = $conexion->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor') AS total_profesores,
        (SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante') AS total_estudiantes_usuarios,
        (SELECT COUNT(*) FROM estudiantes) AS total_estudiantes,
        (SELECT COUNT(*) FROM usuarios WHERE rol = 'padre') AS total_padres,
        (SELECT COUNT(*) FROM cursos) AS total_cursos,
        (SELECT COUNT(*) FROM asignaturas) AS total_asignaturas,
        (SELECT COUNT(*) FROM boletines_pdf WHERE publicado = FALSE) AS bol_pendientes,
        (SELECT COUNT(*) FROM estudiantes WHERE padre_id IS NULL) AS sin_padre,
        (SELECT COUNT(*) FROM estudiantes WHERE curso_id IS NULL) AS sin_curso,
        (SELECT COUNT(*) FROM alertas WHERE para_rol = 'admin' AND leido = FALSE) AS alertas_sin_leer
")->fetch(PDO::FETCH_ASSOC);
$total_usuarios = $stats['total_usuarios'];
$total_profesores = $stats['total_profesores'];
$total_estudiantes_usuarios = $stats['total_estudiantes_usuarios'];
$total_estudiantes = $stats['total_estudiantes'];
$total_padres = $stats['total_padres'];
$total_cursos = $stats['total_cursos'];
$total_asignaturas = $stats['total_asignaturas'];

$ultimos_estudiantes = $conexion
    ->query("SELECT e.id, e.nombre, e.documento, c.grado, c.nombre AS curso_nombre, e.creado_en
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        ORDER BY e.creado_en DESC
        LIMIT 8")
    ->fetchAll(PDO::FETCH_ASSOC);

/* Saludo por hora + fecha en español */
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$dias  = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$fechaLarga = $dias[date('l')] . ', ' . (int)date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

$pageTitle = "Panel Administrativo";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">

        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <?php
            // Todo sale de la consulta agregada de arriba ($stats) y de
            // $ultimos_estudiantes: el panel no añade consultas.
            $kBolPend  = (int) ($stats['bol_pendientes'] ?? 0);
            $kSinPadre = (int) ($stats['sin_padre'] ?? 0);
            $kSinCurso = (int) ($stats['sin_curso'] ?? 0);
            $kAlertas  = (int) ($num_alertas_admin ?? ($stats['alertas_sin_leer'] ?? 0));
            $kEst      = (int) $total_estudiantes;
            $kVinc     = $kEst > 0 ? max(0, $kEst - $kSinPadre) : 0;
            $kPctVinc  = $kEst > 0 ? (int) round($kVinc * 100 / $kEst) : 0;
            $totalPend = ($kBolPend > 0) + ($kSinPadre > 0) + ($kSinCurso > 0) + ($kAlertas > 0);
            ?>

            <section class="dash-hero" aria-label="Bienvenida">
                <div>
                    <span class="dash-kicker"><?= $saludo ?> · <?= ucfirst($fechaLarga) ?></span>
                    <h2>Hola, <span><?= $nombre_admin ?></span></h2>
                    <p>
                        <?php if ($totalPend > 0): ?>
                            Tiene <?= $totalPend ?> <?= $totalPend === 1 ? 'tarea pendiente' : 'tareas pendientes' ?> en el panel. Las encontrará abajo.
                        <?php else: ?>
                            Todo está al día. No hay tareas pendientes.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="dash-hero-actions">
                    <a href="enviar_boletin.php" class="btn-gca"><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Publicar boletines</a>
                    <a href="crear_estudiante.php" class="btn-outline-gca"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo estudiante</a>
                </div>
            </section>

            <h2 class="visually-hidden">Indicadores</h2>
            <div class="kpi-grid">
                <a href="ver_estudiantes.php" class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Estudiantes</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span></div>
                    <div class="kpi-value" data-count="<?= $kEst ?>"><?= $kEst ?></div>
                    <div class="kpi-foot"><?= $kPctVinc ?>% con acudiente vinculado</div>
                </a>
                <a href="ver_profesores.php" class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Profesores</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-person-workspace"></i></span></div>
                    <div class="kpi-value" data-count="<?= (int) $total_profesores ?>"><?= (int) $total_profesores ?></div>
                    <div class="kpi-foot">Cuentas docentes</div>
                </a>
                <a href="ver_padres.php" class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Acudientes</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-people"></i></span></div>
                    <div class="kpi-value" data-count="<?= (int) $total_padres ?>"><?= (int) $total_padres ?></div>
                    <div class="kpi-foot">Padres con acceso al portal</div>
                </a>
                <a href="ver_cursos.php" class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Cursos</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-collection"></i></span></div>
                    <div class="kpi-value" data-count="<?= (int) $total_cursos ?>"><?= (int) $total_cursos ?></div>
                    <div class="kpi-foot">De preescolar a once</div>
                </a>
                <a href="ver_asignaturas.php" class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Asignaturas</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-book"></i></span></div>
                    <div class="kpi-value" data-count="<?= (int) $total_asignaturas ?>"><?= (int) $total_asignaturas ?></div>
                    <div class="kpi-foot">En el plan de estudios</div>
                </a>
                <div class="kpi">
                    <div class="kpi-top"><span class="kpi-label">Cuentas</span><span class="kpi-icon" aria-hidden="true"><i class="bi bi-person-lock"></i></span></div>
                    <div class="kpi-value" data-count="<?= (int) $total_usuarios ?>"><?= (int) $total_usuarios ?></div>
                    <div class="kpi-foot">Usuarios de todos los roles</div>
                </div>
            </div>

            <div class="dash-grid">
                <div class="stack-col">
                <section class="panel" aria-labelledby="h-pend">
                    <div class="panel-head">
                        <h3 id="h-pend"><i class="bi bi-list-check" aria-hidden="true"></i> Pendientes</h3>
                        <?php if ($totalPend === 0): ?><span class="gca-badge is-ok"><i class="bi bi-check2" aria-hidden="true"></i> Al día</span><?php endif; ?>
                    </div>
                    <ul class="todo-list">
                        <?php
                        // Lo que el administrador tiene que resolver, con el enlace para hacerlo
                        $pendientes = [
                            ['Boletines por publicar', 'Generados por los directores; el padre aún no los ve.', $kBolPend, 'enviar_boletin.php', 'bi-file-earmark-check'],
                            ['Estudiantes sin acudiente', 'Sin padre o acudiente vinculado: nadie ve sus boletines.', $kSinPadre, 'asignar_padre.php', 'bi-link-45deg'],
                            ['Estudiantes sin curso', 'No aparecen en las listas de notas de los profesores.', $kSinCurso, 'asignar_estudiante.php', 'bi-diagram-3'],
                            ['Alertas sin leer', 'Avisos del sistema, como riesgo académico.', $kAlertas, 'alertas.php', 'bi-bell'],
                        ];
                        foreach ($pendientes as [$texto, $detalle, $cantidad, $url, $icono]): ?>
                            <li>
                                <a href="<?= $url ?>" class="todo-item <?= $cantidad > 0 ? 'is-pending' : 'is-done' ?>">
                                    <span class="todo-ico" aria-hidden="true"><i class="bi <?= $cantidad > 0 ? $icono : 'bi-check2' ?>"></i></span>
                                    <span class="todo-text">
                                        <strong><?= $texto ?></strong>
                                        <small><?= $detalle ?></small>
                                    </span>
                                    <?php if ($cantidad > 0): ?>
                                        <span class="todo-num"><?= $cantidad ?></span>
                                    <?php else: ?>
                                        <span class="todo-num">Ninguno</span>
                                    <?php endif; ?>
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="panel" aria-labelledby="h-acc">
                    <div class="panel-head"><h3 id="h-acc"><i class="bi bi-lightning-charge" aria-hidden="true"></i> Accesos rápidos</h3></div>
                    <div class="quick-grid panel-body">
                        <a href="crear_estudiante.php" class="quick-action"><i class="bi bi-mortarboard" aria-hidden="true"></i><span>Nuevo estudiante<small>Matrícula</small></span></a>
                        <a href="asignar_padre.php" class="quick-action"><i class="bi bi-link-45deg" aria-hidden="true"></i><span>Vincular acudiente<small>Padre ↔ estudiante</small></span></a>
                        <a href="crear_padre.php" class="quick-action"><i class="bi bi-person-heart" aria-hidden="true"></i><span>Nuevo padre<small>Cuenta del portal</small></span></a>
                        <a href="crear_profesor.php" class="quick-action"><i class="bi bi-person-workspace" aria-hidden="true"></i><span>Nuevo profesor<small>Cuenta docente</small></span></a>
                        <a href="asignar_profesor.php" class="quick-action"><i class="bi bi-journal-arrow-up" aria-hidden="true"></i><span>Asignar materias<small>Profesor ↔ curso</small></span></a>
                        <a href="plataforma.php" class="quick-action"><i class="bi bi-sliders" aria-hidden="true"></i><span>Periodo y notas<small>Abrir o cerrar registro</small></span></a>
                        <a href="mejores_estudiantes.php" class="quick-action"><i class="bi bi-trophy" aria-hidden="true"></i><span>Mejores estudiantes<small>Reporte por grado</small></span></a>
                        <a href="crear_noticia.php" class="quick-action"><i class="bi bi-newspaper" aria-hidden="true"></i><span>Nueva noticia<small>Sitio web</small></span></a>
                    </div>
                </section>
                </div>

                <section class="panel" aria-labelledby="h-rec">
                    <div class="panel-head">
                        <h3 id="h-rec"><i class="bi bi-clock-history" aria-hidden="true"></i> Últimos estudiantes registrados</h3>
                        <a href="ver_estudiantes.php">Ver todos</a>
                    </div>
                    <?php if (count($ultimos_estudiantes) > 0): ?>
                        <ul class="recent-list">
                            <?php foreach ($ultimos_estudiantes as $est):
                                $cursoStr = $est['grado'] ? ucfirst($est['grado']) . ' - ' . $est['curso_nombre'] : 'Sin curso';
                                $inicial = strtoupper(mb_substr(trim($est['nombre']), 0, 1));
                            ?>
                                <li class="recent-item">
                                    <span class="avatar" aria-hidden="true"><?= htmlspecialchars($inicial) ?></span>
                                    <span class="recent-main">
                                        <strong><?= htmlspecialchars($est["nombre"]) ?></strong>
                                        <small>Doc. <?= htmlspecialchars($est["documento"]) ?> · <?= htmlspecialchars($cursoStr) ?></small>
                                    </span>
                                    <span class="recent-date"><?= date("d/m/Y", strtotime($est["creado_en"])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state empty-state-sm">
                            <i class="bi bi-mortarboard" aria-hidden="true"></i>
                            <p>No hay estudiantes registrados aún.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>


        </div>
    </main>

    <script>
    (function () {
        // Cuenta ascendente de los indicadores (el número final ya está en el HTML)
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;
        document.querySelectorAll('.kpi-value[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (target === 0) return;
            var dur = 900, start = null;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                el.textContent = Math.floor(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(step); else el.textContent = target;
            }
            requestAnimationFrame(step);
        });
    })();
    </script>

    <?php include "includes/footer.php"; ?>
