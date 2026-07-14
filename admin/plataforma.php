<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');
require_once "../lib/rank_helper.php";
require_once "../lib/csrf_helper.php";

$mensaje = "";
$error = "";

$db = db();

/* ── Handle AJAX toggle ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        http_response_code(403);
        echo "csrf";
        exit;
    }
    $estado = $_POST['estado'] === 'true' ? '1' : '0';
    $conexion->prepare("UPDATE configuraciones SET valor = ? WHERE clave = 'plataforma_activa'")->execute([$estado]);
    echo "ok";
    exit;
}

/* ── Handle form save ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])
    && !validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    $error = "Error de seguridad. Recargue la página e intente de nuevo.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $periodo = $_POST['periodo_activo'] ?? '1';
    $apertura = $_POST['fecha_apertura'] ?? '';
    $cierre = $_POST['fecha_cierre'] ?? '';
    $anio = (int)($_POST['anio_activo'] ?? date('Y'));
    if ($anio < 2000 || $anio > 2100) $anio = (int)date('Y');

    try {
        $db->update('configuraciones', ['valor' => $periodo], "clave = 'periodo_activo'");
        $db->update('configuraciones', ['valor' => (string)$anio], "clave = 'anio_activo'");
        $db->update('configuraciones', ['valor' => $apertura], "clave = 'fecha_apertura'");
        $db->update('configuraciones', ['valor' => $cierre], "clave = 'fecha_cierre'");
        $mensaje = "Configuración guardada correctamente.";
    } catch (PDOException $e) {
        $error = "Error al guardar la configuración.";
    }
}

/* ── Handle: reiniciar período (borra notas/logros/boletines de un período) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reiniciar_periodo'])) {
    if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        $error = "Error de seguridad. Recargue la página e intente de nuevo.";
    } else {
        $per = $_POST['periodo_reset'] ?? '';
        $confirma = trim($_POST['confirmacion'] ?? '');
        if (!in_array($per, ['1', '2', '3', '4'], true)) {
            $error = "Período inválido.";
        } elseif ($confirma !== 'REINICIAR') {
            $error = "Debes escribir REINICIAR para confirmar el borrado.";
        } else {
            $anioReset = (int)(getConfig('anio_activo') ?? date('Y'));
            try {
                $conexion->beginTransaction();
                // Borrar los PDF del disco antes de borrar los registros (del período + año activo)
                $bs = $conexion->prepare("SELECT ruta_pdf FROM boletines_pdf WHERE periodo = ? AND year = ?");
                $bs->execute([$per, $anioReset]);
                foreach ($bs->fetchAll(PDO::FETCH_COLUMN) as $ruta) {
                    $abs = realpath(__DIR__ . '/../' . $ruta);
                    if ($abs && is_file($abs)) { @unlink($abs); }
                }
                $d1 = $conexion->prepare("DELETE FROM notas WHERE periodo = ? AND anio = ?");        $d1->execute([$per, $anioReset]); $cN = $d1->rowCount();
                $d2 = $conexion->prepare("DELETE FROM logros WHERE periodo = ? AND anio = ?");       $d2->execute([$per, $anioReset]); $cL = $d2->rowCount();
                $d3 = $conexion->prepare("DELETE FROM boletines_pdf WHERE periodo = ? AND year = ?"); $d3->execute([$per, $anioReset]); $cB = $d3->rowCount();
                $conexion->commit();
                $mensaje = "Período {$per} del año {$anioReset} reiniciado: se eliminaron {$cN} nota(s), {$cL} logro(s) y {$cB} boletín(es).";
            } catch (Throwable $e) {
                $conexion->rollBack();
                error_log("[plataforma reiniciar] " . $e->getMessage());
                $error = "Error al reiniciar el período.";
            }
        }
    }
}

/* ── Load current config (cached) ── */
$plataforma_activa = (getConfig('plataforma_activa') ?? '0') === '1';
$periodo_activo = getConfig('periodo_activo') ?? '1';
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));
$fecha_apertura = getConfig('fecha_apertura') ?? '';
$fecha_cierre = getConfig('fecha_cierre') ?? '';

/* ── Cursos para vista de notas ── */
$cursos = $conexion->query("
    SELECT * FROM cursos ORDER BY nivel, grado, nombre
")->fetchAll(PDO::FETCH_ASSOC);

function obtenerNotasEstudiantesCurso($conexion, $curso_id, $anio) {
    $stmt = $conexion->prepare("
        SELECT n.estudiante_id, n.periodo, n.nota, a.nombre as asignatura, a.area, a.id as asignatura_id
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        WHERE n.curso_id = ? AND n.anio = ?
        ORDER BY a.area, a.nombre, n.periodo
    ");
    $stmt->execute([$curso_id, $anio]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $notas_por_estudiante = [];
    foreach ($rows as $r) {
        $eid = $r['estudiante_id'];
        if (!isset($notas_por_estudiante[$eid])) {
            $notas_por_estudiante[$eid] = [];
        }
        $key = $r['asignatura_id'] . '_' . $r['asignatura'];
        if (!isset($notas_por_estudiante[$eid][$key])) {
            $notas_por_estudiante[$eid][$key] = [
                'area' => $r['area'],
                'asignatura' => $r['asignatura'],
                'periodos' => [],
                'sum' => 0,
                'count' => 0,
            ];
        }
        $notas_por_estudiante[$eid][$key]['periodos'][$r['periodo']] = $r['nota'];
        $notas_por_estudiante[$eid][$key]['sum'] += $r['nota'];
        $notas_por_estudiante[$eid][$key]['count']++;
    }

    $resultado = [];
    foreach ($notas_por_estudiante as $eid => $materias) {
        $total_sum = 0;
        $total_count = 0;
        foreach ($materias as &$m) {
            $m['promedio'] = $m['count'] > 0 ? round($m['sum'] / $m['count'], 1) : null;
            $total_sum += $m['sum'];
            $total_count += $m['count'];
        }
        unset($m);
        $resultado[$eid] = [
            'materias' => $materias,
            'promedio_general' => $total_count > 0 ? round($total_sum / $total_count, 1) : null,
        ];
    }
    return $resultado;
}

$pageTitle = "Configuración de Plataforma";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-settings"></i> Configuración de Plataforma</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Sistema</p>
            </div>
        </div>

        <div class="content-area">

            <?php if ($mensaje): ?>
                <div class="alert alert-success border-0 rounded-3 d-flex align-items-center gap-2 shadow-sm mb-4"><?= $mensaje ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 shadow-sm mb-4"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- ─── COL IZQUIERDA: CONFIGURACIÓN ─── -->
                <div class="col-lg-5">

                    <!-- Toggle plataforma -->
                    <div class="gca-card p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1"><i class="bi bi-toggle-on me-2" style="color:var(--gold);"></i>Estado de la Plataforma</h6>
                                <p class="text-muted small mb-0">Habilita o deshabilita el registro de notas y la vista de boletines.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="platformSwitch"
                                    style="width:3.2rem;height:1.6rem;cursor:pointer;"
                                    <?= $plataforma_activa ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center gap-2">
                            <span class="badge rounded-pill px-3 py-1 <?= $plataforma_activa ? 'bg-success' : 'bg-secondary' ?>">
                                <i class="bi <?= $plataforma_activa ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                <?= $plataforma_activa ? 'Activa' : 'Inactiva' ?>
                            </span>
                            <span class="small text-muted">
                                <?php if ($plataforma_activa): ?>
                                    Periodo activo: <strong><?= $periodo_activo ?></strong>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Configuración de periodo y fechas -->
                    <div class="gca-card p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-calendar-range me-2" style="color:var(--gold);"></i>Ventana de Registro</h6>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Año escolar</label>
                                <input type="number" name="anio_activo" class="form-select" value="<?= (int)$anio_activo ?>" min="2000" max="2100">
                                <div class="form-text small">Año del calendario escolar. Las notas se guardan y consultan por año.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Período Activo</label>
                                <select name="periodo_activo" class="form-select">
                                    <option value="1" <?= $periodo_activo === '1' ? 'selected' : '' ?>>1er Período</option>
                                    <option value="2" <?= $periodo_activo === '2' ? 'selected' : '' ?>>2do Período</option>
                                    <option value="3" <?= $periodo_activo === '3' ? 'selected' : '' ?>>3er Período</option>
                                    <option value="4" <?= $periodo_activo === '4' ? 'selected' : '' ?>>4to Período</option>
                                </select>
                                <div class="form-text small">Selecciona el período académico en el que se están registrando notas actualmente.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Fecha de Apertura</label>
                                    <input type="date" name="fecha_apertura" class="form-control" value="<?= htmlspecialchars($fecha_apertura) ?>">
                                    <div class="form-text small">Desde esta fecha los profesores pueden registrar notas.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Fecha de Cierre</label>
                                    <input type="date" name="fecha_cierre" class="form-control" value="<?= htmlspecialchars($fecha_cierre) ?>">
                                    <div class="form-text small">Hasta esta fecha los profesores pueden registrar notas.</div>
                                </div>
                            </div>

                            <button type="submit" name="guardar_config" class="btn-gca w-100 justify-content-center">
                                <i class="bi bi-check-lg"></i> Guardar Configuración
                            </button>
                        </form>
                    </div>

                    <!-- ── Zona de mantenimiento: reiniciar período (pruebas) ── -->
                    <div class="gca-card p-4 mt-4" style="border:1px solid rgba(220,53,69,.28);">
                        <h6 class="fw-bold mb-1" style="color:#c0392b;">
                            <i class="bi bi-exclamation-octagon me-2"></i>Reiniciar período
                        </h6>
                        <p class="text-muted small mb-3">
                            Borra <strong>todas las notas, logros y boletines</strong> del período seleccionado.
                            Acción <strong>irreversible</strong> — pensada para pruebas.
                        </p>
                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-5">
                                    <label class="form-label fw-medium small">Período a borrar</label>
                                    <select name="periodo_reset" class="form-select">
                                        <option value="1">1er Período</option>
                                        <option value="2">2do Período</option>
                                        <option value="3">3er Período</option>
                                        <option value="4">4to Período</option>
                                    </select>
                                </div>
                                <div class="col-sm-7">
                                    <label class="form-label fw-medium small">Escribe <b>REINICIAR</b> para confirmar</label>
                                    <input type="text" name="confirmacion" class="form-control" placeholder="REINICIAR" autocomplete="off">
                                </div>
                            </div>
                            <button type="submit" name="reiniciar_periodo" class="w-100"
                                style="background:#c0392b;color:#fff;border:none;border-radius:10px;padding:11px;font-weight:600;cursor:pointer;"
                                onclick="return confirm('¿Seguro? Se borrarán TODAS las notas, logros y boletines de este período. No se puede deshacer.');">
                                <i class="bi bi-trash3"></i> Reiniciar período
                            </button>
                        </form>
                    </div>

                </div>

                <!-- ─── COL DERECHA: VISTA DE NOTAS (BACKUP) ─── -->
                <div class="col-lg-7">
                    <div class="gca-card p-4">
                        <div class="section-header mb-0 pb-3" style="border-bottom:1px solid #ece8e0;">
                            <h4><i class="bi bi-database"></i> Backup de Notas</h4>
                            <span class="badge bg-dark rounded-pill px-3 py-1" style="color:var(--gold);font-size:11px;">
                                <i class="bi bi-eye"></i> Vista general
                            </span>
                        </div>

                        <?php if (!$plataforma_activa): ?>
                            <div class="empty-state py-4">
                                <i class="bi bi-journal-x"></i>
                                <p class="mb-0">Activa la plataforma para visualizar las notas registradas.</p>
                            </div>
                        <?php elseif (count($cursos) === 0): ?>
                            <div class="empty-state py-4">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">No hay cursos registrados en el sistema.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion mt-3" id="accordionCursos">
                                <?php foreach ($cursos as $curso):
                                    $stmt = $conexion->prepare("SELECT * FROM estudiantes WHERE curso_id = ? ORDER BY nombre");
                                    $stmt->execute([$curso['id']]);
                                    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $ranking = obtenerRankingCurso($conexion, $curso['id'], $periodo_activo, $anio_activo);
                                    $ranking_map = [];
                                    foreach ($ranking as $i => $r) {
                                        $ranking_map[$r['id']] = ['pos' => $i + 1, 'total' => count($ranking)];
                                    }
                                    $notas_curso = count($estudiantes) > 0 ? obtenerNotasEstudiantesCurso($conexion, $curso['id'], $anio_activo) : [];
                                ?>
                                    <div class="accordion-item" style="border-radius:12px!important;margin-bottom:10px;border:1px solid #ece8e0;overflow:hidden;">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseCurso<?= $curso['id'] ?>"
                                                style="font-weight:600;font-size:14px;background:#fcfbfa;">
                                                <i class="bi bi-book-fill me-2" style="color:var(--gold);"></i>
                                                <?= htmlspecialchars(ucfirst($curso['nivel'] ?? '') . ' - ' . $curso['grado'] . ' ' . $curso['nombre']) ?>
                                                <span class="badge bg-secondary ms-2 rounded-pill"><?= count($estudiantes) ?> est.</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCurso<?= $curso['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionCursos">
                                            <div class="accordion-body p-0">
                                                <?php if (count($estudiantes) > 0): ?>
                                                    <div class="accordion student-accordion" id="accordionEstudiantes<?= $curso['id'] ?>">
                                                        <?php foreach ($estudiantes as $estudiante):
                                                            $notasData = $notas_curso[$estudiante['id']] ?? ['materias' => [], 'promedio_general' => null];
                                                            $materias = $notasData['materias'];
                                                            $promedio_gral = $notasData['promedio_general'];
                                                        ?>
                                                            <div class="accordion-item" style="border-left:3px solid var(--gold);border-radius:0;">
                                                                <h2 class="accordion-header">
                                                                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#collapseEst<?= $estudiante['id'] ?>"
                                                                        style="font-size:13px;background:#fff;">
                                                                        <i class="bi bi-person-fill me-2" style="color:var(--gold-dark);"></i>
                                                                        <?= htmlspecialchars($estudiante['nombre']) ?>
                                                                        <?php if (isset($ranking_map[$estudiante['id']])): ?>
                                                                            <span style="margin-left:12px;font-size:10px;font-weight:700;color:var(--gold-dark);background:rgba(212,175,55,0.1);padding:1px 8px;border-radius:20px;white-space:nowrap;">
                                                                                #<?= $ranking_map[$estudiante['id']]['pos'] ?>/<?= $ranking_map[$estudiante['id']]['total'] ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </button>
                                                                </h2>
                                                                <div id="collapseEst<?= $estudiante['id'] ?>" class="accordion-collapse collapse"
                                                                    data-bs-parent="#accordionEstudiantes<?= $curso['id'] ?>">
                                                                    <div class="accordion-body p-0">
                                                                        <?php if (count($materias) > 0): ?>
                                                                            <div class="table-responsive">
                                                                                <table class="table gca-table mb-0" style="font-size:12px;">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>Área</th>
                                                                                            <th>Asignatura</th>
                                                                                            <th style="text-align:center;width:40px;">P1</th>
                                                                                            <th style="text-align:center;width:40px;">P2</th>
                                                                                            <th style="text-align:center;width:40px;">P3</th>
                                                                                            <th style="text-align:center;width:40px;">P4</th>
                                                                                            <th style="text-align:center;width:50px;">Prom.</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($materias as $m): ?>
                                                                                            <tr>
                                                                                                <td style="color:#888;"><?= htmlspecialchars($m['area']) ?></td>
                                                                                                <td><?= htmlspecialchars($m['asignatura']) ?></td>
                                                                                                <?php for ($p = 1; $p <= 4; $p++):
                                                                                                    $n = $m['periodos'][(string)$p] ?? null;
                                                                                                ?>
                                                                                                    <td style="text-align:center;">
                                                                                                        <?php if ($n !== null): ?>
                                                                                                            <span class="badge rounded-pill px-2 py-1"
                                                                                                                  style="font-size:10px;font-weight:600;background:<?= $n >= 60 ? '#e8f5e9' : ($n >= 40 ? '#fff3e0' : '#ffebee') ?>;color:<?= $n >= 60 ? '#2e7d32' : ($n >= 40 ? '#e65100' : '#c62828') ?>;">
                                                                                                                <?= (int)$n ?>
                                                                                                            </span>
                                                                                                        <?php else: ?>
                                                                                                            <span style="color:#ddd;">–</span>
                                                                                                        <?php endif; ?>
                                                                                                    </td>
                                                                                                <?php endfor; ?>
                                                                                                <td style="text-align:center;">
                                                                                                    <?php if ($m['promedio'] !== null): ?>
                                                                                                        <span class="badge rounded-pill px-2 py-1"
                                                                                                              style="font-size:10px;font-weight:700;background:<?= $m['promedio'] >= 60 ? '#1b5e20' : ($m['promedio'] >= 40 ? '#e65100' : '#b71c1c') ?>;color:#fff;">
                                                                                                            <?= $m['promedio'] ?>
                                                                                                        </span>
                                                                                                    <?php else: ?>
                                                                                                        <span style="color:#ddd;">–</span>
                                                                                                    <?php endif; ?>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                    <tfoot>
                                                                                        <tr style="border-top:2px solid var(--gold);background:#fcfbfa;">
                                                                                            <td colspan="6" style="text-align:right;font-weight:700;font-size:13px;">
                                                                                                <i class="bi bi-star-fill me-1" style="color:var(--gold);"></i>Promedio General
                                                                                            </td>
                                                                                            <td style="text-align:center;">
                                                                                                <?php if ($promedio_gral !== null): ?>
                                                                                                    <span class="badge rounded-pill px-3 py-1"
                                                                                                          style="font-size:12px;font-weight:700;background:<?= $promedio_gral >= 60 ? '#1b5e20' : ($promedio_gral >= 40 ? '#e65100' : '#b71c1c') ?>;color:#fff;">
                                                                                                        <?= $promedio_gral ?>
                                                                                                    </span>
                                                                                                <?php else: ?>
                                                                                                    <span style="color:#ddd;">–</span>
                                                                                                <?php endif; ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tfoot>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="p-3 text-center text-muted small">Sin notas registradas.</div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="p-3 text-center text-muted small">No hay estudiantes en este curso.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.getElementById('platformSwitch')?.addEventListener('change', function() {
            const isActive = this.checked;
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('estado', isActive);
            formData.append('_csrf_token', <?= json_encode(generar_token_csrf()) ?>);
            fetch('plataforma.php', { method: 'POST', body: formData })
                .then(r => r.text())
                .then(res => { if (res === 'ok') location.reload(); });
        });
    </script>

    <?php include "includes/footer.php"; ?>
