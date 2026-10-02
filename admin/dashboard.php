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

<style>
    /* ─── Welcome banner ─── */
    .welcome-banner {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(600px 200px at 90% -20%, rgba(212,175,55,.22), transparent 70%),
            linear-gradient(135deg, #0d1b2a 0%, #1b2838 100%);
        border-radius: 22px;
        padding: 34px 38px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 28px;
        border: 1px solid rgba(212, 175, 55, 0.18);
        box-shadow: 0 12px 40px rgba(13, 27, 42, 0.18);
        animation: gcaFadeUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(212,175,55,.10) 1px, transparent 1px);
        background-size: 22px 22px;
        opacity: .5;
        pointer-events: none;
        mask-image: linear-gradient(90deg, transparent, #000 60%);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 60%);
    }
    .welcome-banner .wb-left { position: relative; z-index: 1; }
    .welcome-greet {
        display: inline-block;
        font-size: 12px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #d4af37;
        background: rgba(212,175,55,.1);
        border: 1px solid rgba(212,175,55,.22);
        padding: 4px 12px;
        border-radius: 30px;
        margin-bottom: 12px;
    }
    .welcome-banner h2 {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 700;
        color: #f0ede6;
        font-size: 30px;
        margin: 0 0 6px;
    }
    .welcome-banner h2 span {
        background: linear-gradient(90deg, #d4af37 20%, #f6e4a6 50%, #d4af37 80%);
        background-size: 200% auto;
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
        animation: wbShine 5s linear infinite;
    }
    @keyframes wbShine { to { background-position: 200% center; } }
    .welcome-banner p {
        color: rgba(240, 237, 230, 0.62);
        margin: 0;
        font-size: 14px;
    }
    .welcome-banner p i { color: #d4af37; margin-right: 6px; }
    .welcome-icon-wrap {
        position: relative; z-index: 1;
        width: 76px; height: 76px; flex-shrink: 0;
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(212,175,55,.12);
        border: 1px solid rgba(212,175,55,.25);
        box-shadow: inset 0 0 30px rgba(212,175,55,.1);
    }
    .welcome-icon-wrap i { font-size: 38px; color: #d4af37; animation: floaty 3.5s ease-in-out infinite; }
    @keyframes floaty { 0%,100%{ transform: translateY(0); } 50%{ transform: translateY(-6px); } }

    /* ─── Stat cards ─── */
    .stat-card {
        position: relative;
        overflow: hidden;
        background: var(--bg-card);
        border-radius: 16px;
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        border: 1px solid var(--border-color);
        transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s, border-color .25s, background .25s;
        animation: gcaFadeUp .5s cubic-bezier(.22,1,.36,1) both;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: -45%; right: -12%;
        width: 130px; height: 130px; border-radius: 50%;
        background: var(--accent, var(--gold));
        opacity: .06;
        transition: opacity .28s ease, transform .28s ease;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.10);
        border-color: var(--accent, var(--gold));
    }
    .stat-card:hover::before { opacity: .14; transform: scale(1.25); }

    .stat-icon {
        position: relative; z-index: 1;
        width: 52px; height: 52px;
        border-radius: 15px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1);
    }
    .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-4deg); }
    .stat-icon.users       { background: rgba(13, 27, 42, 0.09);  color: #0d1b2a; }
    .dark-mode .stat-icon.users { background: rgba(120,150,190,.12); color: #9db4d4; }
    .stat-icon.profesores  { background: rgba(212, 175, 55, 0.15); color: #b8962e; }
    .stat-icon.estudiantes { background: rgba(25, 135, 84, 0.12);  color: #198754; }
    .stat-icon.padres      { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
    .stat-icon.cursos      { background: rgba(111, 66, 193, 0.12); color: #6f42c1; }
    .stat-icon.asignaturas { background: rgba(253, 126, 20, 0.12); color: #fd7e14; }

    .stat-label {
        font-size: 12.5px; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: .5px; font-weight: 500;
    }
    .stat-value {
        font-family: 'Cormorant Garamond', serif;
        font-size: 32px; font-weight: 700; color: var(--text-primary); line-height: 1.1;
    }

    /* ─── Section titles ─── */
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 20px; font-weight: 700; color: var(--text-primary);
        margin: 0 0 16px; display: flex; align-items: center; gap: 10px;
    }
    .section-title i { color: var(--gold); font-size: 22px; }

    /* ─── Quick actions ─── */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(184px, 1fr));
        gap: 12px; margin-bottom: 32px;
    }
    .quick-action {
        position: relative; overflow: hidden;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 16px 18px;
        text-decoration: none;
        display: flex; align-items: center; gap: 14px;
        transition: all .22s cubic-bezier(.22,1,.36,1);
        color: var(--text-secondary);
    }
    .quick-action i {
        width: 40px; height: 40px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px; color: var(--gold);
        background: rgba(212,175,55,.1);
        border-radius: 11px;
        transition: all .22s ease;
    }
    .quick-action span { font-size: 14px; font-weight: 500; }
    .quick-action:hover {
        border-color: var(--gold);
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(212, 175, 55, 0.14);
        color: var(--text-primary);
    }
    .quick-action:hover i { background: var(--gold); color: #000; transform: rotate(-6deg) scale(1.05); }

    /* ─── Recent / summary cards ─── */
    .recent-card {
        background: var(--bg-card);
        border-radius: 16px;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    .recent-card .list-item {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; padding: 13px 20px;
        border-bottom: 1px solid var(--table-row-border);
        transition: background 0.15s;
    }
    .recent-card .list-item:last-child { border-bottom: none; }
    .recent-card .list-item:hover { background: var(--bg-card-hover); }
    .recent-card .li-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .stu-avatar {
        width: 40px; height: 40px; flex-shrink: 0;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 16px; color: #000;
        background: linear-gradient(135deg, var(--gold-light), var(--gold-dark));
        box-shadow: 0 4px 12px rgba(212,175,55,.28);
    }
    .recent-card .item-name { font-weight: 600; color: var(--text-primary); font-size: 14.5px; }
    .recent-card .item-email { font-size: 12.5px; color: var(--text-muted); }
    .date-badge {
        font-size: 12px; color: var(--gold-dark);
        background: rgba(212,175,55,.1);
        border: 1px solid rgba(212,175,55,.18);
        padding: 3px 10px; border-radius: 20px; white-space: nowrap;
    }
    .sum-value {
        font-weight: 700; color: var(--text-primary);
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        padding: 3px 12px; border-radius: 20px; font-size: 13px;
    }
    .recent-card .empty-message { padding: 32px; text-align: center; color: var(--text-muted); font-size: 14px; }

    @media (max-width: 768px) {
        .welcome-banner { flex-direction: column; text-align: center; padding: 26px 22px; }
        .welcome-icon-wrap { display: none; }
        .quick-actions { grid-template-columns: 1fr 1fr; }
        .welcome-banner h2 { font-size: 25px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .welcome-banner, .stat-card { animation: none; }
        .welcome-banner h2 span, .welcome-icon-wrap i { animation: none; }
    }
</style>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">

        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Panel Administrativo</h5>
                <p>Gimnasio Castillo Americano &nbsp;/&nbsp; Inicio</p>
            </div>
        </div>

        <div class="content-area">

            <div class="welcome-banner">
                <div class="wb-left">
                    <span class="welcome-greet"><?= $saludo ?></span>
                    <h2>Bienvenido, <span><?= $nombre_admin ?></span></h2>
                    <p><i class="bi bi-calendar3"></i><?= ucfirst($fechaLarga) ?> &middot; Panel de control académico</p>
                </div>
                <div class="welcome-icon-wrap">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="stat-card" style="--accent:#4a6fa5; animation-delay:.04s">
                        <div class="stat-icon users"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-label">Cuentas</div>
                            <div class="stat-value" data-count="<?= (int)$total_usuarios ?>">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <a href="ver_profesores.php" class="stat-link">
                    <div class="stat-card" style="--accent:#d4af37; animation-delay:.10s">
                        <div class="stat-icon profesores"><i class="bi bi-person-workspace"></i></div>
                        <div>
                            <div class="stat-label">Profesores</div>
                            <div class="stat-value" data-count="<?= (int)$total_profesores ?>">0</div>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <a href="ver_estudiantes.php" class="stat-link">
                    <div class="stat-card" style="--accent:#198754; animation-delay:.16s">
                        <div class="stat-icon estudiantes"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="stat-label">Estudiantes</div>
                            <div class="stat-value" data-count="<?= (int)$total_estudiantes ?>">0</div>
                            <div style="font-size:11px;color:var(--text-muted);"><?= (int)$total_estudiantes_usuarios ?> con cuenta</div>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <a href="ver_padres.php" class="stat-link">
                    <div class="stat-card" style="--accent:#0d6efd; animation-delay:.22s">
                        <div class="stat-icon padres"><i class="bi bi-person-heart"></i></div>
                        <div>
                            <div class="stat-label">Padres</div>
                            <div class="stat-value" data-count="<?= (int)$total_padres ?>">0</div>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <a href="ver_cursos.php" class="stat-link">
                    <div class="stat-card" style="--accent:#6f42c1; animation-delay:.28s">
                        <div class="stat-icon cursos"><i class="bi bi-journal-check"></i></div>
                        <div>
                            <div class="stat-label">Cursos</div>
                            <div class="stat-value" data-count="<?= (int)$total_cursos ?>">0</div>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <a href="ver_asignaturas.php" class="stat-link">
                    <div class="stat-card" style="--accent:#fd7e14; animation-delay:.34s">
                        <div class="stat-icon asignaturas"><i class="bi bi-book-fill"></i></div>
                        <div>
                            <div class="stat-label">Asignaturas</div>
                            <div class="stat-value" data-count="<?= (int)$total_asignaturas ?>">0</div>
                        </div>
                    </div>
                    </a>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-lightning-fill"></i> Acciones rápidas</div>
            <div class="quick-actions">
                <a href="crear_admin.php" class="quick-action"><i class="bi bi-shield-plus"></i><span>Nuevo Admin</span></a>
                <a href="crear_profesor.php" class="quick-action"><i class="bi bi-person-plus"></i><span>Nuevo Profesor</span></a>
                <a href="crear_estudiante.php" class="quick-action"><i class="bi bi-mortarboard"></i><span>Nuevo Estudiante</span></a>
                <a href="crear_padre.php" class="quick-action"><i class="bi bi-heart"></i><span>Nuevo Padre</span></a>
                <a href="crear_curso.php" class="quick-action"><i class="bi bi-plus-square"></i><span>Nuevo Curso</span></a>
                <a href="crear_asignatura.php" class="quick-action"><i class="bi bi-bookmark-plus"></i><span>Nueva Asignatura</span></a>
                <a href="asignar_profesor.php" class="quick-action"><i class="bi bi-link-45deg"></i><span>Asignar materias a profesores</span></a>
                <a href="asignar_padre.php" class="quick-action"><i class="bi bi-people"></i><span>Vincular padre con estudiante</span></a>
                <a href="enviar_boletin.php" class="quick-action"><i class="bi bi-file-earmark-check"></i><span>Boletines</span></a>
                <a href="crear_noticia.php" class="quick-action"><i class="bi bi-newspaper"></i><span>Nueva Noticia</span></a>
                <a href="plataforma.php" class="quick-action"><i class="bi bi-display"></i><span>Periodo y notas</span></a>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="section-title"><i class="bi bi-clock-history"></i> Últimos estudiantes registrados</div>
                    <div class="recent-card">
                        <?php if (count($ultimos_estudiantes) > 0): ?>
                            <?php foreach ($ultimos_estudiantes as $est):
                                $cursoStr = $est['grado'] ? ucfirst($est['grado']) . ' - ' . $est['curso_nombre'] : '—';
                                $inicial = strtoupper(mb_substr(trim($est['nombre']), 0, 1));
                            ?>
                                <div class="list-item">
                                    <div class="li-left">
                                        <div class="stu-avatar"><?= htmlspecialchars($inicial) ?></div>
                                        <div style="min-width:0;">
                                            <div class="item-name"><?= htmlspecialchars($est["nombre"]) ?></div>
                                            <div class="item-email">Doc: <?= htmlspecialchars($est["documento"]) ?> &middot; <?= htmlspecialchars($cursoStr) ?></div>
                                        </div>
                                    </div>
                                    <span class="date-badge"><?= date("d/m/Y", strtotime($est["creado_en"])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-message">No hay estudiantes registrados aún.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="section-title"><i class="bi bi-list-check"></i> Pendientes</div>
                    <div class="recent-card">
                        <?php
                        // Lo que el administrador tiene que resolver, con el enlace para hacerlo
                        $pendientes = [
                            ['Boletines por publicar a los padres', (int) $stats['bol_pendientes'], 'enviar_boletin.php'],
                            ['Estudiantes sin padre o acudiente vinculado', (int) $stats['sin_padre'], 'asignar_padre.php'],
                            ['Estudiantes sin curso asignado', (int) $stats['sin_curso'], 'asignar_estudiante.php'],
                            ['Alertas sin leer', (int) $stats['alertas_sin_leer'], 'alertas.php'],
                        ];
                        foreach ($pendientes as [$texto, $cantidad, $url]): ?>
                            <a href="<?= $url ?>" class="list-item" style="text-decoration:none;color:inherit;">
                                <span class="item-name"><?= $texto ?></span>
                                <span class="sum-value" style="<?= $cantidad > 0 ? '' : 'color:var(--text-muted);' ?>">
                                    <?= $cantidad > 0 ? $cantidad : '<i class="bi bi-check-circle" aria-hidden="true"></i><span class="visually-hidden">Ninguno</span>' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
    (function () {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.stat-value[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (reduce || target === 0) { el.textContent = target; return; }
            var dur = 1100, start = null;
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
