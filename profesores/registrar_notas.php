<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/alertas_helper.php";
require_once "../lib/rank_helper.php";

$profesor_id = userId();

$plataforma_activa = (getConfig('plataforma_activa') ?? '0') === '1';
$periodo_activo = getConfig('periodo_activo') ?? '1';

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
$por_pagina = 10;

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $asignacion_seleccionada && isset($_POST['guardar_masivo'])) {
    $curso_id = $asignacion_seleccionada['curso_id'];
    $asignatura_id = $asignacion_seleccionada['asignatura_id'];

    $estudiantes_post = $_POST['estudiantes'] ?? [];

    try {
        $conexion->beginTransaction();

        $est_ids = array_keys($estudiantes_post);
        if (count($est_ids) === 0) throw new Exception("No hay estudiantes para guardar.");

        // Single bulk check: verify all students belong to this course
        $placeholders = implode(',', array_fill(0, count($est_ids), '?'));
        $checkStmt = $conexion->prepare("SELECT id FROM estudiantes WHERE id IN ($placeholders) AND curso_id = ?");
        $checkStmt->execute(array_merge($est_ids, [$curso_id]));
        $valid_ids = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        $valid_set = array_flip($valid_ids);

        // Bulk DELETE existing notas for all students
        $delNotas = $conexion->prepare("
            DELETE FROM notas
            WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ? AND curso_id = ?
        ");
        $delNotas->execute(array_merge($est_ids, [$asignatura_id, $periodo_activo, $curso_id]));

        // Bulk DELETE existing logros
        $delLogros = $conexion->prepare("
            DELETE FROM logros WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ?
        ");
        $delLogros->execute(array_merge($est_ids, [$asignatura_id, $periodo_activo]));

        // Batch INSERT notas + logros
        $insNota = $conexion->prepare("
            INSERT INTO notas (estudiante_id, profesor_id, asignatura_id, curso_id, periodo, nota)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insLogro = $conexion->prepare("
            INSERT INTO logros (estudiante_id, asignatura_id, periodo, logro)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($estudiantes_post as $est_id => $data) {
            if (!isset($valid_set[$est_id])) continue;

            $nota_field = 'nota_' . $periodo_activo;
            $nota_valor = isset($data[$nota_field]) && $data[$nota_field] !== '' ? (int)$data[$nota_field] : null;
            $logro_texto = $data['logro'] ?? '';

            if ($nota_valor !== null && $nota_valor >= 0 && $nota_valor <= 100) {
                $insNota->execute([$est_id, $profesor_id, $asignatura_id, $curso_id, $periodo_activo, $nota_valor]);
            }

            if (trim($logro_texto) !== '') {
                $insLogro->execute([$est_id, $asignatura_id, $periodo_activo, trim($logro_texto)]);
            }
        }

        $conexion->commit();

        // Check risk alerts for period 3
        $riesgos = 0;
        if ($periodo_activo === '3') {
            foreach ($estudiantes_post as $est_id => $data) {
                $alertas = verificarRiesgoAcademico($conexion, $est_id, $curso_id, $periodo_activo);
                $riesgos += count($alertas);
            }
        }

        $mensaje = "Notas y logros guardados correctamente.";
        if ($riesgos > 0) {
            $mensaje .= " Se generaron {$riesgos} alerta(s) de riesgo académico.";
        }
    } catch (Exception $e) {
        $conexion->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
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
            WHERE estudiante_id IN ($placeholders) AND asignatura_id = ?
        ");
        $stmtN->execute(array_merge($est_ids, [$asignacion_seleccionada['asignatura_id']]));
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
            WHERE estudiante_id IN ($placeholders) AND asignatura_id = ? AND periodo = ?
        ");
        $stmtL->execute(array_merge($est_ids, [$asignacion_seleccionada['asignatura_id'], $periodo_activo]));
        foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $logros_existentes[$row['estudiante_id']] = $row['logro'];
        }

        // Get ranking for the active period
        $ranking = obtenerRankingCurso($conexion, $asignacion_seleccionada['curso_id'], $periodo_activo);
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
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-pencil"></i>
                    <?= $asignacion_seleccionada ? 'Registro de Notas' : 'Seleccionar Curso' ?>
                </h5>
                <p>
                    Panel del Profesor &nbsp;/&nbsp;
                    <?php if ($asignacion_seleccionada): ?>
                        <a href="registrar_notas.php" style="color:#999;text-decoration:none;">Registrar Notas</a>
                        &nbsp;/&nbsp; <?= htmlspecialchars($asignacion_seleccionada['asignatura_nombre']) ?>
                    <?php else: ?>
                        Calificaciones
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="content-area">

            <?php if ($mensaje): ?>
                <div class="alert alert-success border-0 rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-check-circle-fill"></i> <?= $mensaje ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
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
                            Ingresa las notas y logros para cada estudiante, luego haz clic en "Guardar todo".
                            Usa el icono <i class="bi bi-filetype-pdf" style="color:var(--gold-dark);"></i> para generar el boletín PDF individual.
                        </p>
                    </div>
                </div>

                <form method="POST">
                    <div class="gca-card">
                        <div class="table-responsive">
                            <table class="table gca-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:36px;">#</th>
                                        <th>Estudiante</th>
                                        <th style="width:52px;font-size:11px;text-align:center;">P1</th>
                                        <th style="width:52px;font-size:11px;text-align:center;">P2</th>
                                        <th style="width:52px;font-size:11px;text-align:center;">P3</th>
                                        <th style="width:52px;font-size:11px;text-align:center;">P4</th>
                                        <th style="width:60px;font-size:11px;text-align:center;">Prom.</th>
                                        <th style="min-width:140px;">Logro</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($estudiantes) === 0): ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">No hay estudiantes en este curso.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $idx = $offset + 1; ?>
                                        <?php foreach ($estudiantes as $est): ?>
                                                        <tr>
                                                <td style="color:#999;font-size:12px;"><?= $idx++ ?></td>
                                                <td>
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <span class="fw-medium"><?= htmlspecialchars($est['nombre']) ?></span>
                                                        <?php if (isset($ranking_map[$est['id']])): ?>
                                                            <span style="font-size:10px;font-weight:700;color:var(--gold-dark);background:rgba(212,175,55,0.1);padding:1px 8px;border-radius:20px;white-space:nowrap;">
                                                                #<?= $ranking_map[$est['id']]['pos'] ?>/<?= $ranking_map[$est['id']]['total'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small style="color:#999;display:block;font-size:11px;"><?= htmlspecialchars($est['documento']) ?></small>
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
                                                                min="0" max="100"
                                                                value="<?= $n !== null ? (int)$n : '' ?>"
                                                                placeholder="–"
                                                                style="width:52px;text-align:center;font-size:12px;display:inline-block;padding:4px 2px;">
                                                        <?php elseif ($n !== null): ?>
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                  style="font-size:11px;font-weight:600;background:<?= $n >= 60 ? '#e8f5e9' : ($n >= 40 ? '#fff3e0' : '#ffebee') ?>;color:<?= $n >= 60 ? '#2e7d32' : ($n >= 40 ? '#e65100' : '#c62828') ?>;">
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
                                                              style="font-size:11px;font-weight:700;background:<?= $nota_promedio >= 60 ? '#1b5e20' : ($nota_promedio >= 40 ? '#e65100' : '#b71c1c') ?>;color:#fff;">
                                                            <?= $nota_promedio ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#ddd;font-size:11px;">–</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <textarea name="estudiantes[<?= $est['id'] ?>][logro]"
                                                        class="form-control form-control-sm"
                                                        rows="1"
                                                        placeholder="Logro u observación..."
                                                        style="resize:vertical;min-height:36px;"><?= htmlspecialchars($logros_existentes[$est['id']] ?? '') ?></textarea>
                                                </td>
                                                <td style="text-align:center;vertical-align:middle;">
                                                    <div style="display:flex;gap:6px;justify-content:center;">
                                                        <a href="#"
                                                           class="btn-action btn-edit btn-gen-boletin"
                                                           title="Generar boletín PDF (diseño actual)"
                                                           data-estudiante-id="<?= $est['id'] ?>"
                                                           data-estudiante-nombre="<?= htmlspecialchars($est['nombre'], ENT_QUOTES) ?>"
                                                           data-template="">
                                                            <i class="bi bi-filetype-pdf"></i>
                                                        </a>
                                                        <a href="#"
                                                           class="btn-action btn-edit btn-gen-boletin"
                                                           title="Generar boletín PDF (nuevo diseño)"
                                                           style="background:#C8A84B;border-color:#b8951f;color:#111;"
                                                           data-estudiante-id="<?= $est['id'] ?>"
                                                           data-estudiante-nombre="<?= htmlspecialchars($est['nombre'], ENT_QUOTES) ?>"
                                                           data-template="v2">
                                                            <i class="bi bi-layout-text-window-reverse"></i>
                                                        </a>
                                                    </div>
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
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                        <a class="page-link" href="?asignacion_id=<?= $asignacion_id ?>&page=<?= $i ?>"
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
                                if ($current_curso !== null) echo '</div></div></div>';
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
                                <span style="margin-left:auto;font-size:11px;color:#999;"><?= htmlspecialchars($asig['area']) ?></span>
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

<!-- ─── Paz y Salvo Modal ─── -->
<div class="modal fade" id="pazSalvoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--border-color);box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-body text-center py-5 px-4">
                <div class="mb-3" style="font-size:48px;color:var(--gold);">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="mb-2" style="font-weight:700;">¿Está a paz y salvo?</h5>
                <p id="pazSalvoMsg" class="mb-4" style="font-size:14px;color:var(--text-secondary);line-height:1.5;">
                    ¿El estudiante está a paz y salvo académica y administrativamente?
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn px-4 py-2" id="pazSalvoNo" style="border-radius:10px;border:1.5px solid var(--border-color);color:var(--text-secondary);font-weight:500;background:transparent;">
                        <i class="bi bi-x-circle me-1"></i>No
                    </button>
                    <button type="button" class="btn px-4 py-2" id="pazSalvoSi" style="border-radius:10px;border:none;background:#2e7d32;color:#fff;font-weight:600;">
                        <i class="bi bi-check-circle me-1"></i>Sí, está a paz y salvo
                    </button>
                </div>
                <small class="d-block mt-3 text-muted" style="font-size:11px;">
                    Si responde <b>Sí</b> se enviará el boletín al padre y al administrador.<br>
                    Si responde <b>No</b> solo se enviará al administrador.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('pazSalvoModal'), { backdrop: 'static', keyboard: false });
    let pendingUrl = '';

    document.querySelectorAll('.btn-gen-boletin').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const estudianteId = this.dataset.estudianteId;
            const estudianteNombre = this.dataset.estudianteNombre;
            const template = this.dataset.template;
            const periodo = <?= json_encode($periodo_activo) ?>;
            const baseUrl = 'generar_boletin.php?estudiante=' + estudianteId + '&periodo=' + periodo;
            pendingUrl = baseUrl + (template ? '&template=' + template : '');
            document.getElementById('pazSalvoMsg').textContent =
                '¿El estudiante <b>' + estudianteNombre + '</b> está a paz y salvo académica y administrativamente?';
            modal.show();
        });
    });

    document.getElementById('pazSalvoSi').addEventListener('click', function() {
        modal.hide();
        if (pendingUrl) {
            window.open(pendingUrl + '&paz_y_salvo=1', '_blank');
        }
    });

    document.getElementById('pazSalvoNo').addEventListener('click', function() {
        modal.hide();
        if (pendingUrl) {
            window.open(pendingUrl + '&paz_y_salvo=0', '_blank');
        }
    });
});
</script>

    <?php include "includes/footer.php"; ?>
