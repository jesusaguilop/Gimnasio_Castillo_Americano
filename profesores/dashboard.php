<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');

$profesor_id = userId();
$nombre = $_SESSION["nombre"] ?? "Profesor";

/* ── Stats ── */
$totalEstudiantes = $conexion->prepare("
    SELECT COUNT(DISTINCT e.id)
    FROM estudiantes e
    JOIN profesor_curso_asignatura pca ON e.curso_id = pca.curso_id
    WHERE pca.profesor_id = ?
");
$totalEstudiantes->execute([$profesor_id]);
$totalEstudiantes = (int) $totalEstudiantes->fetchColumn();

$totalCursos = $conexion->prepare("
    SELECT COUNT(DISTINCT pca.curso_id)
    FROM profesor_curso_asignatura pca
    WHERE pca.profesor_id = ?
");
$totalCursos->execute([$profesor_id]);
$totalCursos = (int) $totalCursos->fetchColumn();

$totalAsignaturas = $conexion->prepare("
    SELECT COUNT(DISTINCT pca.asignatura_id)
    FROM profesor_curso_asignatura pca
    WHERE pca.profesor_id = ?
");
$totalAsignaturas->execute([$profesor_id]);
$totalAsignaturas = (int) $totalAsignaturas->fetchColumn();

$totalNotas = $conexion->prepare("
    SELECT COUNT(*) FROM notas WHERE profesor_id = ?
");
$totalNotas->execute([$profesor_id]);
$totalNotas = (int) $totalNotas->fetchColumn();

/* ── Recent grades ── */
$recientes = $conexion->prepare("
    SELECT n.nota, n.periodo, n.creado_en,
           e.nombre AS estudiante, a.nombre AS asignatura,
           c.grado, c.nombre AS curso
    FROM notas n
    JOIN estudiantes e ON n.estudiante_id = e.id
    JOIN asignaturas a ON n.asignatura_id = a.id
    JOIN cursos c ON n.curso_id = c.id
    WHERE n.profesor_id = ?
    ORDER BY n.creado_en DESC
    LIMIT 8
");
$recientes->execute([$profesor_id]);
$recientes = $recientes->fetchAll(PDO::FETCH_ASSOC);

/* Saludo por hora + fecha en español */
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$dias  = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$fechaLarga = $dias[date('l')] . ', ' . (int)date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

$pageTitle = "Dashboard";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-layout-dashboard"></i> Dashboard</h5>
                <p>Panel del Profesor &nbsp;/&nbsp; Resumen Académico</p>
            </div>
        </div>

        <div class="content-area">
            <!-- Welcome banner -->
            <div class="prof-welcome mb-4">
                <div class="pw-left">
                    <span class="pw-greet"><?= $saludo ?></span>
                    <h3>Bienvenido, <span><?= htmlspecialchars($nombre) ?></span></h3>
                    <p><i class="bi bi-calendar3"></i><?= ucfirst($fechaLarga) ?> &middot; Gestión de calificaciones</p>
                </div>
                <div class="pw-icon"><i class="bi bi-person-workspace"></i></div>
            </div>
            <style>
                .prof-welcome {
                    position: relative; overflow: hidden;
                    background:
                        radial-gradient(560px 200px at 88% -20%, rgba(212,175,55,.22), transparent 70%),
                        linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
                    border: 1px solid rgba(212,175,55,0.2);
                    border-radius: 20px; padding: 30px 34px;
                    display: flex; align-items: center; justify-content: space-between; gap: 18px;
                    box-shadow: 0 12px 36px rgba(0,0,0,.16);
                }
                .prof-welcome::after {
                    content:''; position:absolute; inset:0;
                    background-image: radial-gradient(rgba(212,175,55,.10) 1px, transparent 1px);
                    background-size: 22px 22px; opacity:.5; pointer-events:none;
                    -webkit-mask-image: linear-gradient(90deg, transparent, #000 60%);
                    mask-image: linear-gradient(90deg, transparent, #000 60%);
                }
                .pw-left { position: relative; z-index: 1; }
                .pw-greet {
                    display:inline-block; font-size:11px; letter-spacing:1.5px; text-transform:uppercase;
                    color:var(--gold); background:rgba(212,175,55,.1); border:1px solid rgba(212,175,55,.22);
                    padding:3px 11px; border-radius:30px; margin-bottom:10px;
                }
                .prof-welcome h3 { font-family:'Cormorant Garamond',serif; color:#f0ede6; margin:0 0 4px; font-size:26px; font-weight:700; }
                .prof-welcome h3 span {
                    background:linear-gradient(90deg,#d4af37 20%,#f6e4a6 50%,#d4af37 80%); background-size:200% auto;
                    -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; color:transparent;
                    animation: wbShine 5s linear infinite;
                }
                @keyframes wbShine { to { background-position:200% center; } }
                .prof-welcome p { color:rgba(240,237,230,.6); margin:0; font-size:13px; }
                .prof-welcome p i { color:var(--gold); margin-right:6px; }
                .pw-icon {
                    position:relative; z-index:1; width:68px; height:68px; flex-shrink:0; border-radius:18px;
                    display:flex; align-items:center; justify-content:center;
                    background:rgba(212,175,55,.12); border:1px solid rgba(212,175,55,.25);
                }
                .pw-icon i { font-size:32px; color:var(--gold); animation: floaty 3.5s ease-in-out infinite; }
                @keyframes floaty { 0%,100%{ transform:translateY(0);} 50%{ transform:translateY(-6px);} }
                @media (max-width:768px){ .prof-welcome{ flex-direction:column; text-align:center; padding:24px 20px; } .pw-icon{ display:none; } }
                @media (prefers-reduced-motion: reduce){ .prof-welcome h3 span, .pw-icon i { animation:none; } }
            </style>

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-info">
                            <h3 data-count="<?= (int)$totalEstudiantes ?>">0</h3>
                            <p>Estudiantes</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-book-fill"></i></div>
                        <div class="stat-info">
                            <h3 data-count="<?= (int)$totalCursos ?>">0</h3>
                            <p>Cursos</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-journal-text"></i></div>
                        <div class="stat-info">
                            <h3 data-count="<?= (int)$totalAsignaturas ?>">0</h3>
                            <p>Asignaturas</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="bi bi-star-fill"></i></div>
                        <div class="stat-info">
                            <h3 data-count="<?= (int)$totalNotas ?>">0</h3>
                            <p>Notas Registradas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick actions + Recent grades -->
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="section-header"><h4><i class="bi bi-lightning-fill"></i> Acceso Rápido</h4></div>
                    <div class="d-flex flex-column gap-3">
                        <a href="registrar_notas.php" class="quick-card">
                            <i class="bi bi-pencil-square"></i>
                            <h6>Registrar Notas</h6>
                            <p>Ingresa las calificaciones de tus estudiantes</p>
                        </a>
                        <a href="ver_promedio.php" class="quick-card">
                            <i class="bi bi-bar-chart-line"></i>
                            <h6>Ver Promedios</h6>
                            <p>Consulta el rendimiento académico</p>
                        </a>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="section-header">
                        <h4><i class="bi bi-clock-history"></i> Notas Recientes</h4>
                    </div>
                    <div class="gca-card">
                        <?php if (count($recientes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table gca-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Asignatura</th>
                                            <th>Curso</th>
                                            <th>Periodo</th>
                                            <th>Nota</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recientes as $r): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r["estudiante"]) ?></td>
                                                <td><?= htmlspecialchars($r["asignatura"]) ?></td>
                                                <td><?= htmlspecialchars($r["grado"] . ' ' . $r["curso"]) ?></td>
                                                <td><?= htmlspecialchars($r["periodo"]) ?></td>
                                                <td>
                                                    <span class="badge <?= $r["nota"] >= 60 ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 py-1">
                                                        <?= (int) $r["nota"] ?>
                                                    </span>
                                                </td>
                                                <td style="font-size:12px;color:var(--text-muted);"><?= date("d/m/Y", strtotime($r["creado_en"])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">Aún no has registrado ninguna nota.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    (function () {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.stat-info h3[data-count]').forEach(function (el) {
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
