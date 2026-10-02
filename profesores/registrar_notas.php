<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/alertas_helper.php";
require_once "../lib/rank_helper.php";
require_once "../lib/csrf_helper.php";

$profesor_id = userId();

$plataforma_activa = (getConfig('plataforma_activa') ?? '0') === '1';
$periodo_activo = getConfig('periodo_activo') ?? '1';
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));

$mensaje = "";
$error = "";

/* ── Get teacher's course-subject assignments ── */
$asignaciones = $conexion->prepare("
    SELECT pca.id, c.id AS curso_id, c.grado, c.nombre AS curso_nombre,
           c.nivel, a.id AS asignatura_id, a.nombre AS asignatura_nombre,
           a.area
    FROM profesor_curso_asignatura pca
    JOIN cursos c ON pca.curso_id = c.id
    JOIN asignaturas a ON pca.asignatura_id = a.id
    WHERE pca.profesor_id = ?
    ORDER BY c.nivel, c.grado, a.area, a.nombre
");
$asignaciones->execute([$profesor_id]);
$asignaciones = $asignaciones->fetchAll(PDO::FETCH_ASSOC);

/* ── Determine view ── */
$asignacion_id = $_GET['asignacion_id'] ?? null;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 60; // un curso completo por página: menos riesgo de perder notas al paginar

$asignacion_seleccionada = null;
if ($asignacion_id) {
    foreach ($asignaciones as $a) {
        if ($a['id'] == $asignacion_id) {
            $asignacion_seleccionada = $a;
            break;
        }
    }
}

/* ── Handle save ── */
$flash = \App\Base\Session::getSuccess();
if ($flash) $mensaje = $flash;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $asignacion_seleccionada && isset($_POST['guardar_masivo'])
    && !validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    $error = "Error de seguridad. Recargue la página e intente de nuevo.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $asignacion_seleccionada && isset($_POST['guardar_masivo']) && !$plataforma_activa) {
    // El formulario se oculta con la plataforma cerrada, pero el POST también se bloquea
    $error = "El registro de notas está cerrado. Consulte con administración.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $asignacion_seleccionada && isset($_POST['guardar_masivo'])) {
    $curso_id = $asignacion_seleccionada['curso_id'];
    $asignatura_id = $asignacion_seleccionada['asignatura_id'];
    $nota_field = 'nota_' . $periodo_activo;

    $estudiantes_post = $_POST['estudiantes'] ?? [];

    try {
        $est_ids = array_map('intval', array_keys($estudiantes_post));
        if (count($est_ids) === 0) throw new Exception("No hay estudiantes para guardar.");

        // Solo estudiantes que de verdad son de este curso
        $placeholders = implode(',', array_fill(0, count($est_ids), '?'));
        $checkStmt = $conexion->prepare("SELECT id, nombre FROM estudiantes WHERE id IN ($placeholders) AND curso_id = ?");
        $checkStmt->execute(array_merge($est_ids, [$curso_id]));
        $nombres = $checkStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $valid_ids = array_map('intval', array_keys($nombres));
        if (count($valid_ids) === 0) throw new Exception("Ningún estudiante pertenece a este curso.");

        // Validar ANTES de borrar nada: una nota inválida ya no borra la anterior en silencio
        $invalidos = [];
        foreach ($valid_ids as $eid) {
            $v = trim((string) ($estudiantes_post[$eid][$nota_field] ?? ''));
            if ($v !== '' && (!ctype_digit($v) || (int) $v > 100)) {
                $invalidos[] = $nombres[$eid];
            }
        }

        if ($invalidos) {
            $error = "Revise estas notas (deben ser números enteros de 0 a 100): " . implode(', ', $invalidos)
                   . ". No se guardó ningún cambio.";
        } else {
            $conexion->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));

            $delNotas = $conexion->prepare("
                DELETE FROM notas
                WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ? AND curso_id = ? AND anio = ?
            ");
            $delNotas->execute(array_merge($valid_ids, [$asignatura_id, $periodo_activo, $curso_id, $anio_activo]));

            $delLogros = $conexion->prepare("
                DELETE FROM logros WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ? AND anio = ?
            ");
            $delLogros->execute(array_merge($valid_ids, [$asignatura_id, $periodo_activo, $anio_activo]));

            $insNota = $conexion->prepare("
                INSERT INTO notas (estudiante_id, profesor_id, asignatura_id, curso_id, periodo, nota, anio)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insLogro = $conexion->prepare("
                INSERT INTO logros (estudiante_id, asignatura_id, periodo, logro, anio)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($valid_ids as $est_id) {
                $data = $estudiantes_post[$est_id] ?? [];
                $v = trim((string) ($data[$nota_field] ?? ''));
                if ($v !== '') {
                    $insNota->execute([$est_id, $profesor_id, $asignatura_id, $curso_id, $periodo_activo, (int) $v, $anio_activo]);
                }
                $logro_texto = trim((string) ($data['logro'] ?? ''));
                if ($logro_texto !== '') {
                    $insLogro->execute([$est_id, $asignatura_id, $periodo_activo, $logro_texto, $anio_activo]);
                }
            }

            $conexion->commit();

            // Alertas de riesgo académico (período 3), solo para estudiantes del curso
            $riesgos = 0;
            if ($periodo_activo === '3') {
                foreach ($valid_ids as $est_id) {
                    $riesgos += count(verificarRiesgoAcademico($conexion, $est_id, $curso_id, $periodo_activo, $anio_activo));
                }
            }

            $ok = "Notas y logros guardados correctamente (" . count($valid_ids) . " estudiante(s)).";
            if ($riesgos > 0) {
                $ok .= " Se generaron {$riesgos} alerta(s) de riesgo académico.";
            }
            // Post/Redirect/Get: recargar la página no vuelve a enviar el formulario
            \App\Base\Session::setSuccess($ok);
            header("Location: registrar_notas.php?asignacion_id=" . (int) $asignacion_id . "&page=" . (int) $pagina);
            exit;
        }
    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        error_log("[registrar_notas] " . $e->getMessage());
        $error = "No se pudo guardar. Intente de nuevo; si el problema sigue, avise a administración.";
    }
}

/* ── Get students for the selected course (paginated) ── */
$estudiantes = [];
$total_estudiantes = 0;
$total_paginas = 0;

if ($asignacion_seleccionada) {
    $countStmt = $conexion->prepare("SELECT COUNT(*) FROM estudiantes WHERE curso_id = ?");
    $countStmt->execute([$asignacion_seleccionada['curso_id']]);
    $total_estudiantes = (int)$countStmt->fetchColumn();
    $total_paginas = max(1, ceil($total_estudiantes / $por_pagina));
    $pagina = min($pagina, $total_paginas);
    $offset = ($pagina - 1) * $por_pagina;

    $stmtE = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento
        FROM estudiantes e
        WHERE e.curso_id = ?
        ORDER BY e.nombre
        LIMIT ? OFFSET ?
    ");
    $stmtE->bindValue(1, $asignacion_seleccionada['curso_id'], PDO::PARAM_INT);
    $stmtE->bindValue(2, $por_pagina, PDO::PARAM_INT);
    $stmtE->bindValue(3, $offset, PDO::PARAM_INT);
    $stmtE->execute();
    $estudiantes = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    // Pre-load existing notas for ALL periods and logros for these students
    $est_ids = array_column($estudiantes, 'id');
    $notas_por_periodo = []; // [est_id][periodo] => nota
    $promedios = []; // [est_id] => average
    $logros_existentes = [];

    if (count($est_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($est_ids), '?'));

        $stmtN = $conexion->prepare("
            SELECT estudiante_id, nota, periodo FROM notas
            WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND anio = ?
        ");
        $stmtN->execute(array_merge($est_ids, [$asignacion_seleccionada['asignatura_id'], $anio_activo]));
        foreach ($stmtN->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $notas_por_periodo[$row['estudiante_id']][$row['periodo']] = $row['nota'];
        }

        // Compute average per student across all periods
        foreach ($est_ids as $eid) {
            $vals = array_filter($notas_por_periodo[$eid] ?? [], fn($v) => $v !== null);
            $promedios[$eid] = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
        }

        $stmtL = $conexion->prepare("
            SELECT estudiante_id, logro FROM logros
            WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ? AND anio = ?
        ");
        $stmtL->execute(array_merge($est_ids, [$asignacion_seleccionada['asignatura_id'], $periodo_activo, $anio_activo]));
        foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $logros_existentes[$row['estudiante_id']] = $row['logro'];
        }

        // Get ranking for the active period/year
        $ranking = obtenerRankingCurso($conexion, $asignacion_seleccionada['curso_id'], $periodo_activo, $anio_activo);
        $ranking_map = [];
        foreach ($ranking as $i => $r) {
            $ranking_map[$r['id']] = ['pos' => $i + 1, 'total' => count($ranking)];
        }
    }
}

$pageTitle = $asignacion_seleccionada
    ? "Notas: {$asignacion_seleccionada['asignatura_nombre']} - {$asignacion_seleccionada['grado']} {$asignacion_seleccionada['curso_nombre']}"
    : "Registrar Notas";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-pencil"></i>
                    <?= $asignacion_seleccionada ? 'Registro de Notas' : 'Seleccionar Curso' ?>
                </h5>
                <p>
                    Panel del Profesor &nbsp;/&nbsp;
                    <?php if ($asignacion_seleccionada): ?>
                        <a href="registrar_notas.php" style="color:var(--text-muted);text-decoration:none;">Registrar Notas</a>
                        &nbsp;/&nbsp; <?= htmlspecialchars($asignacion_seleccionada['asignatura_nombre']) ?>
                    <?php else: ?>
                        Calificaciones
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="content-area">

            <?php if ($mensaje): ?>
                <div class="alert alert-success border-0 rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4" role="status" id="msgGuardado">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$plataforma_activa): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-lock-fill" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Plataforma Desactivada</h4>
                    <p class="text-muted mb-0">El registro de notas no está habilitado. Consulta con administración.</p>
                </div>

            <?php elseif ($asignacion_seleccionada): ?>
                <!-- ─── SPREADSHEET VIEW ─── -->
                <div class="gca-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h5 style="font-family:'Cormorant Garamond',serif;font-weight:700;margin:0;">
                                <i class="bi bi-book-fill" style="color:var(--gold);"></i>
                                <?= htmlspecialchars($asignacion_seleccionada['asignatura_nombre']) ?>
                            </h5>
                            <p class="text-muted small mb-0">
                                Curso <?= htmlspecialchars($asignacion_seleccionada['grado'] . ' ' . $asignacion_seleccionada['curso_nombre']) ?>
                                &middot; <?= htmlspecialchars($asignacion_seleccionada['area']) ?>
                                &middot; <strong>Período <?= $periodo_activo ?></strong>
                                &middot; <?= $total_estudiantes ?> estudiante(s)
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="registrar_notas.php" class="btn-outline-gca btn-gca-sm">
                                <i class="bi bi-arrow-left"></i> Cambiar curso
                            </a>
                        </div>
                    </div>
                    <div class="mt-3 pt-3" style="border-top:1px solid #f0ede8;">
                        <p class="small text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Escribe la nota (0 a 100) y el logro de cada estudiante y pulsa <strong>"Guardar todo"</strong> al final.
                            Si sales sin guardar, la página te avisará. Los boletines se generan desde <a href="direccion_grupo.php">Dirección de Grupo</a>.
                        </p>
                    </div>
                </div>

                <form method="POST" id="formNotas">
                    <?= campo_csrf() ?>
                    <div class="gca-card">
                        <div class="table-responsive">
                            <table class="table gca-table mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width:36px;">#</th>
                                        <th scope="col">Estudiante</th>
                                        <th scope="col" style="width:52px;font-size:11px;text-align:center;"><abbr title="Período 1">P1</abbr></th>
                                        <th scope="col" style="width:52px;font-size:11px;text-align:center;"><abbr title="Período 2">P2</abbr></th>
                                        <th scope="col" style="width:52px;font-size:11px;text-align:center;"><abbr title="Período 3">P3</abbr></th>
                                        <th scope="col" style="width:52px;font-size:11px;text-align:center;"><abbr title="Período 4">P4</abbr></th>
                                        <th scope="col" style="width:60px;font-size:11px;text-align:center;"><abbr title="Promedio">Prom.</abbr></th>
                                        <th scope="col" style="min-width:140px;">Logro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($estudiantes) === 0): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">No hay estudiantes en este curso.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $idx = $offset + 1; ?>
                                        <?php foreach ($estudiantes as $est): ?>
                                                        <tr>
                                                <td style="color:var(--text-muted);font-size:12px;"><?= $idx++ ?></td>
                                                <td>
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <span class="fw-medium"><?= htmlspecialchars($est['nombre']) ?></span>
                                                        <?php if (isset($ranking_map[$est['id']])): ?>
                                                            <span style="font-size:10px;font-weight:700;color:var(--gold-dark);background:rgba(212,175,55,0.1);padding:1px 8px;border-radius:20px;white-space:nowrap;">
                                                                #<?= $ranking_map[$est['id']]['pos'] ?>/<?= $ranking_map[$est['id']]['total'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small style="color:var(--text-muted);display:block;font-size:11px;"><?= htmlspecialchars($est['documento']) ?></small>
                                                </td>
                                                <?php $nota_promedio = $promedios[$est['id']] ?? null; ?>
                                                <?php for ($p = 1; $p <= 4; $p++):
                                                    $pStr = (string)$p;
                                                    $n = $notas_por_periodo[$est['id']][$pStr] ?? null;
                                                    $es_actual = $pStr === $periodo_activo;
                                                ?>
                                                    <td style="text-align:center;vertical-align:middle;padding:4px 2px;">
                                                        <?php if ($es_actual): ?>
                                                             <input type="number" name="estudiantes[<?= $est['id'] ?>][nota_<?= $p ?>]"
                                                                class="form-control form-control-sm grade-input"
                                                                min="0" max="100" step="1" inputmode="numeric"
                                                                aria-label="Nota del período <?= $p ?> de <?= htmlspecialchars($est['nombre']) ?>"
                                                                value="<?= $n !== null ? (int)$n : '' ?>"
                                                                placeholder="–"
                                                                style="width:52px;text-align:center;font-size:12px;display:inline-block;padding:4px 2px;">
                                                        <?php elseif ($n !== null): ?>
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                  style="font-size:11px;font-weight:600;background:<?= $n >= 80 ? '#e8f5e9' : ($n >= 60 ? '#fff3e0' : '#ffebee') ?>;color:<?= $n >= 80 ? '#1b5e20' : ($n >= 60 ? '#a33f00' : '#b71c1c') ?>;">
                                                                <?= (int)$n ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="color:#ddd;font-size:11px;">–</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endfor; ?>
                                                <td style="text-align:center;vertical-align:middle;">
                                                    <?php if ($nota_promedio !== null): ?>
                                                        <span class="badge rounded-pill px-2 py-1"
                                                              style="font-size:11px;font-weight:700;background:<?= $nota_promedio >= 80 ? '#1b5e20' : ($nota_promedio >= 60 ? '#a33f00' : '#b71c1c') ?>;color:#fff;">
                                                            <?= $nota_promedio ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#ddd;font-size:11px;">–</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <textarea name="estudiantes[<?= $est['id'] ?>][logro]"
                                                        class="form-control form-control-sm logro-input"
                                                        aria-label="Logro de <?= htmlspecialchars($est['nombre']) ?>"
                                                        rows="1"
                                                        placeholder="Logro u observación..."
                                                        style="resize:vertical;min-height:36px;"><?= htmlspecialchars($logros_existentes[$est['id']] ?? '') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination + Save -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4">
                        <nav aria-label="Páginas de estudiantes">
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                        <a class="page-link" href="?asignacion_id=<?= (int) $asignacion_id ?>&page=<?= $i ?>"
                                            style="<?= $i === $pagina ? 'background:var(--bg-dark);border-color:var(--gold);color:var(--gold);' : 'color:#555;' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php if (count($estudiantes) > 0): ?>
                            <button type="submit" name="guardar_masivo" class="btn-gca">
                                <i class="bi bi-save"></i> Guardar todo
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

            <?php else: ?>
                <!-- ─── COURSE CARDS VIEW ─── -->
                <?php if (count($asignaciones) === 0): ?>
                    <div class="gca-card empty-state p-5">
                        <i class="bi bi-inbox" style="font-size:52px;color:#ddd;"></i>
                        <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin asignaciones</h4>
                        <p class="text-muted mb-0">No tienes cursos ni asignaturas asignadas.</p>
                    </div>
                <?php else: ?>
                    <div class="section-header">
                        <h4><i class="bi bi-columns-gap"></i> Mis Cursos y Asignaturas</h4>
                        <span class="badge bg-dark rounded-pill px-3 py-1" style="color:var(--gold);font-size:11px;">
                            Período <?= $periodo_activo ?>
                        </span>
                    </div>

                    <div class="row g-3">
                        <?php
                        $current_curso = null;
                        foreach ($asignaciones as $asig):
                            $curso_key = $asig['curso_id'];
                            $is_new_curso = $curso_key !== $current_curso;
                            if ($is_new_curso) {
                                $current_curso = $curso_key;
                            }
                        ?>
                            <?php if ($is_new_curso): ?>
                                <div class="col-md-6">
                                    <div class="gca-card p-3">
                                        <div class="d-flex align-items-center gap-2 mb-2" style="border-bottom:1px solid #f0ede8;padding-bottom:10px;">
                                            <i class="bi bi-book-fill" style="color:var(--gold);"></i>
                                            <span class="fw-bold" style="font-size:14px;">
                                                <?= htmlspecialchars(ucfirst($asig['nivel'] ?? '') . ' - ' . $asig['grado'] . ' ' . $asig['curso_nombre']) ?>
                                            </span>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php endif; ?>

                            <a href="?asignacion_id=<?= $asig['id'] ?>"
                               style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;text-decoration:none;color:#444;transition:all.15s;font-size:13px;"
                               onmouseover="this.style.background='rgba(212,175,55,0.06)'"
                               onmouseout="this.style.background='transparent'">
                                <i class="bi bi-journal-text" style="color:var(--gold-dark);font-size:14px;"></i>
                                <span class="fw-medium"><?= htmlspecialchars($asig['asignatura_nombre']) ?></span>
                                <span style="margin-left:auto;font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($asig['area']) ?></span>
                                <i class="bi bi-chevron-right" style="color:#ccc;font-size:12px;"></i>
                            </a>

                            <?php
                            // Check if next iteration is a new course or end
                            $next_key = null;
                            $next_index = array_search($asig, $asignaciones, true) + 1;
                            if (isset($asignaciones[$next_index])) {
                                $next_key = $asignaciones[$next_index]['curso_id'];
                            }
                            if ($next_key !== $curso_key || $next_key === null):
                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Info banner -->
                    <div class="d-flex align-items-center gap-3 mt-4 p-3 gca-card" style="background:#fcfbfa;">
                        <i class="bi bi-info-circle-fill" style="color:var(--gold);font-size:20px;"></i>
                        <div>
                            <p class="mb-0 small">
                                <strong>Período activo: <?= $periodo_activo ?></strong> &middot;
                                Selecciona una asignatura para registrar las notas de tus estudiantes.
                                Los datos se guardan por período y quedan disponibles para consulta histórica.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
    <style>
        @media (max-width: 768px) {
            /* 16px evita que iOS haga zoom al tocar la casilla */
            .grade-input { font-size: 16px !important; width: 64px !important; }
        }
    </style>
    <script>
    (function () {
        var form = document.getElementById('formNotas');
        if (!form) return;
        var cambios = false;
        form.addEventListener('input', function (e) {
            if (e.target.matches('.grade-input, .logro-input')) cambios = true;
        });
        form.addEventListener('submit', function () { cambios = false; });
        window.addEventListener('beforeunload', function (e) {
            if (cambios) { e.preventDefault(); e.returnValue = ''; }
        });
        var msg = document.getElementById('msgGuardado');
        if (msg) msg.scrollIntoView({ block: 'center' });
    })();
    </script>
