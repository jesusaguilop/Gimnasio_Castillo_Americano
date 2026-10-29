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
    // Se imprime en los paneles de profesores: solo 1-4, nada de texto libre
    $periodo = in_array($_POST['periodo_activo'] ?? '', ['1', '2', '3', '4'], true) ? $_POST['periodo_activo'] : '1';
    $apertura = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha_apertura'] ?? '') ? $_POST['fecha_apertura'] : '';
    $cierre = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha_cierre'] ?? '') ? $_POST['fecha_cierre'] : '';
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

/* ── Handle: escala de desempeño del año lectivo (rank_helper → escalaDesempeno) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_escala'])) {
    if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        $error = "Error de seguridad. Recargue la página e intente de nuevo.";
    } else {
        $anioActual = (int)(getConfig('anio_activo') ?? date('Y'));
        $anioEscala = (int)($_POST['escala_anio'] ?? 0);
        $escala = [
            'superior' => trim($_POST['escala_superior'] ?? ''),
            'alto'     => trim($_POST['escala_alto'] ?? ''),
            'basico'   => trim($_POST['escala_basico'] ?? ''),
        ];
        // Solo el año activo o el siguiente: los años cerrados conservan su escala
        if (!in_array($anioEscala, [$anioActual, $anioActual + 1], true)) {
            $error = "Solo se puede definir la escala del año lectivo activo o del siguiente.";
        } elseif ($msg = validarEscala($escala)) {
            $error = $msg;
        } else {
            $clave = 'escala_desempeno_' . $anioEscala;
            $valor = json_encode(array_map('intval', $escala));
            try {
                $up = $conexion->prepare("UPDATE configuraciones SET valor = ? WHERE clave = ?");
                $up->execute([$valor, $clave]);
                if ($up->rowCount() === 0) {
                    $conexion->prepare("INSERT INTO configuraciones (clave, valor) VALUES (?, ?)")->execute([$clave, $valor]);
                }
                $mensaje = "Escala de desempeño del año {$anioEscala} guardada.";
            } catch (PDOException $e) {
                error_log("[plataforma escala] " . $e->getMessage());
                $error = "Error al guardar la escala.";
            }
        }
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

/* ── Escala de desempeño: la del año activo y la del siguiente ── */
$escalaActual    = escalaDesempeno($anio_activo);
$escalaSiguiente = escalaDesempeno($anio_activo + 1);
$notaMinima      = $escalaActual['basico'];
$bolStmt = $conexion->prepare("SELECT COUNT(*) FROM boletines_pdf WHERE year = ?");
$bolStmt->execute([$anio_activo]);
$boletinesAnio = (int) $bolStmt->fetchColumn();

/* ── Cursos para vista de notas ── */
$cursos = $conexion->query("
    SELECT * FROM cursos ORDER BY nivel, grado, nombre
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Todo lo de la vista de notas en 4 consultas fijas ──
   Antes eran 3 consultas por curso (≈48) y un "promedio general" propio
   (media de todas las notas, sin áreas, sin % y con Comportamiento) que no
   coincidía con el del boletín. Ahora el promedio y el puesto salen de
   lib/rank_helper.php, igual que en el boletín y en los paneles. */
$curso_ids = array_map('intval', array_column($cursos, 'id'));

$estPorCurso = [];
foreach ($conexion->query("SELECT * FROM estudiantes WHERE curso_id IS NOT NULL ORDER BY nombre") as $e) {
    $estPorCurso[(int) $e['curso_id']][] = $e;
}

// Puesto del período activo y promedio del año (de los períodos con notas)
$rankings = obtenerRankingsCursos($conexion, $curso_ids, $periodo_activo, $anio_activo);
$promedioAnual = [];
foreach (rankingAnualCursos($conexion, $curso_ids, $anio_activo) as $lista) {
    foreach ($lista as $r) {
        $promedioAnual[(int) $r['id']] = $r['promedio'];
    }
}

// Notas por asignatura y período (solo para mostrarlas en la tabla)
$notasPorEst = [];
$stmt = $conexion->prepare("
    SELECT n.estudiante_id, n.periodo, n.nota, a.nombre AS asignatura, a.area, a.id AS asignatura_id
    FROM notas n
    JOIN asignaturas a ON n.asignatura_id = a.id
    WHERE n.anio = ?
    ORDER BY a.area, a.nombre, n.periodo
");
$stmt->execute([$anio_activo]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $m = &$notasPorEst[(int) $r['estudiante_id']][$r['asignatura_id'] . '_' . $r['asignatura']];
    $m ??= ['area' => $r['area'], 'asignatura' => $r['asignatura'], 'periodos' => [], 'sum' => 0, 'count' => 0];
    $m['periodos'][$r['periodo']] = $r['nota'];
    $m['sum'] += $r['nota'];
    $m['count']++;
    $m['promedio'] = round($m['sum'] / $m['count'], 1);
    unset($m);
}

$pageTitle = "Configuración de Plataforma";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-icon mb-4" role="alert"><i class="bi bi-check-circle" aria-hidden="true"></i><div><?= htmlspecialchars($mensaje) ?></div></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-icon mb-4" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><div><?= htmlspecialchars($error) ?></div></div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- ─── COL IZQUIERDA: CONFIGURACIÓN ─── -->
                <div class="col-lg-5">

                    <!-- Toggle plataforma -->
                    <div class="gca-card p-4 mb-4">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h4 class="section-title"><i class="bi bi-toggle-on" aria-hidden="true"></i> Registro de notas</h4>
                                <p class="section-sub mt-2" id="platformHelp">Habilita o deshabilita el registro de notas y la vista de boletines.</p>
                            </div>
                            <div class="form-check form-switch switch-lg">
                                <input class="form-check-input" type="checkbox" id="platformSwitch" role="switch"
                                    aria-label="Plataforma de notas activa" aria-describedby="platformHelp"
                                    <?= $plataforma_activa ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                            <span class="gca-badge <?= $plataforma_activa ? 'is-ok' : '' ?>">
                                <i class="bi <?= $plataforma_activa ? 'bi-check-circle' : 'bi-x-circle' ?>" aria-hidden="true"></i>
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
                        <div class="section-header">
                            <h4><i class="bi bi-calendar-range" aria-hidden="true"></i> Periodo y ventana de registro</h4>
                        </div>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_anio_activo">Año escolar</label>
                                <input type="number" id="f_anio_activo" name="anio_activo" class="form-control" value="<?= (int)$anio_activo ?>" min="2000" max="2100">
                                <div class="form-text small">Año del calendario escolar. Las notas se guardan y consultan por año.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_periodo_activo">Período Activo</label>
                                <select id="f_periodo_activo" name="periodo_activo" class="form-select">
                                    <option value="1" <?= $periodo_activo === '1' ? 'selected' : '' ?>>1er Período</option>
                                    <option value="2" <?= $periodo_activo === '2' ? 'selected' : '' ?>>2do Período</option>
                                    <option value="3" <?= $periodo_activo === '3' ? 'selected' : '' ?>>3er Período</option>
                                    <option value="4" <?= $periodo_activo === '4' ? 'selected' : '' ?>>4to Período</option>
                                </select>
                                <div class="form-text small">Selecciona el período académico en el que se están registrando notas actualmente.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium" for="f_fecha_apertura">Fecha de Apertura</label>
                                    <input id="f_fecha_apertura" type="date" name="fecha_apertura" class="form-control" value="<?= htmlspecialchars($fecha_apertura) ?>">
                                    <div class="form-text small">Desde esta fecha los profesores pueden registrar notas.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium" for="f_fecha_cierre">Fecha de Cierre</label>
                                    <input id="f_fecha_cierre" type="date" name="fecha_cierre" class="form-control" value="<?= htmlspecialchars($fecha_cierre) ?>">
                                    <div class="form-text small">Hasta esta fecha los profesores pueden registrar notas.</div>
                                </div>
                            </div>

                            <button type="submit" name="guardar_config" class="btn-gca w-100">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Guardar configuración
                            </button>
                        </form>
                    </div>

                    <!-- Escala de desempeño del año lectivo -->
                    <div class="gca-card p-4 mt-4 scroll-target" id="escala">
                        <div class="section-header">
                            <div>
                                <h4><i class="bi bi-bar-chart-steps" aria-hidden="true"></i> Escala de desempeño</h4>
                                <p class="section-sub">Dónde empieza cada nivel, según el SIEE del colegio. Los nombres son los del Decreto 1290.</p>
                            </div>
                        </div>

                        <p class="small mb-3">
                            Vigente en <?= (int) $anio_activo ?>:
                            <strong><?= htmlspecialchars(escalaTexto($anio_activo)) ?></strong>.
                            Aprueba con <strong><?= (int) $notaMinima ?></strong>.
                            <?php if ($escalaActual['desde'] === null): ?>
                                <span class="text-muted">(escala por defecto)</span>
                            <?php elseif ($escalaActual['desde'] !== $anio_activo): ?>
                                <span class="text-muted">(fijada en <?= (int) $escalaActual['desde'] ?>)</span>
                            <?php endif; ?>
                        </p>

                        <form method="POST" id="formEscala">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_escala_anio">Año lectivo</label>
                                <select id="f_escala_anio" name="escala_anio" class="form-select">
                                    <option value="<?= (int) $anio_activo ?>" data-escala="<?= htmlspecialchars(json_encode($escalaActual)) ?>"><?= (int) $anio_activo ?> (año activo)</option>
                                    <option value="<?= (int) $anio_activo + 1 ?>" data-escala="<?= htmlspecialchars(json_encode($escalaSiguiente)) ?>"><?= (int) $anio_activo + 1 ?> (próximo año)</option>
                                </select>
                                <div class="form-text small">Cada año lectivo conserva su escala. Si un año no tiene una propia, usa la del año anterior.</div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="form-label fw-medium small" for="f_escala_superior">Superior desde</label>
                                    <input id="f_escala_superior" type="number" name="escala_superior" class="form-control" min="1" max="100" step="1" required value="<?= (int) $escalaActual['superior'] ?>">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-medium small" for="f_escala_alto">Alto desde</label>
                                    <input id="f_escala_alto" type="number" name="escala_alto" class="form-control" min="1" max="100" step="1" required value="<?= (int) $escalaActual['alto'] ?>">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-medium small" for="f_escala_basico">Básico desde</label>
                                    <input id="f_escala_basico" type="number" name="escala_basico" class="form-control" min="1" max="100" step="1" required value="<?= (int) $escalaActual['basico'] ?>">
                                </div>
                            </div>
                            <div class="escala-preview mb-3" id="escalaPreview" aria-live="polite"></div>
                            <?php if ($boletinesAnio > 0): ?>
                            <div class="alert alert-warning small py-2 mb-3" id="escalaAviso" role="alert">
                                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                Ya hay <?= $boletinesAnio ?> boletín(es) generados en <?= (int) $anio_activo ?>. Esos PDF conservan la escala con que se hicieron;
                                las pantallas y los boletines nuevos usarán la nueva. Lo ideal es fijar la escala antes de empezar el año.
                            </div>
                            <?php endif; ?>
                            <button type="submit" name="guardar_escala" class="btn-gca w-100" id="btnEscala">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Guardar escala
                            </button>
                        </form>
                    </div>

                    <!-- ── Zona de mantenimiento: reiniciar período (pruebas) ── -->
                    <div class="gca-card danger-zone p-4 mt-4">
                        <h3 class="h6 fw-bold mb-1">
                            <i class="bi bi-exclamation-octagon me-2" aria-hidden="true"></i>Zona de riesgo: reiniciar período
                        </h3>
                        <p class="text-muted small mb-3">
                            Borra <strong>todas las notas, logros y boletines</strong> del período seleccionado.
                            Acción <strong>irreversible</strong> — pensada para pruebas.
                        </p>
                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-5">
                                    <label class="form-label fw-medium small" for="f_periodo_reset">Período a borrar</label>
                                    <select id="f_periodo_reset" name="periodo_reset" class="form-select">
                                        <option value="1">1er Período</option>
                                        <option value="2">2do Período</option>
                                        <option value="3">3er Período</option>
                                        <option value="4">4to Período</option>
                                    </select>
                                </div>
                                <div class="col-sm-7">
                                    <label class="form-label fw-medium small" for="f_confirmacion">Escribe <b>REINICIAR</b> para confirmar</label>
                                    <input id="f_confirmacion" type="text" name="confirmacion" class="form-control" placeholder="REINICIAR" autocomplete="off">
                                </div>
                            </div>
                            <button type="submit" name="reiniciar_periodo" class="btn-danger-gca w-100"
                                onclick="event.preventDefault(); var b=this, f=b.form; if (!f.reportValidity()) return; showConfirm('¿Seguro? Se borrarán TODAS las notas, logros y boletines de este período. No se puede deshacer.', function(){ var h=document.createElement('input'); h.type='hidden'; h.name='reiniciar_periodo'; h.value='1'; f.appendChild(h); f.submit(); }, 'Sí, borrar');">
                                <i class="bi bi-trash3" aria-hidden="true"></i> Reiniciar período
                            </button>
                        </form>
                    </div>

                </div>

                <!-- ─── COL DERECHA: VISTA DE NOTAS (BACKUP) ─── -->
                <div class="col-lg-7">
                    <div class="gca-card p-4 scroll-target" id="notas">
                        <div class="section-header">
                            <div>
                                <h4><i class="bi bi-database" aria-hidden="true"></i> Notas por curso</h4>
                                <p class="section-sub">Notas del año <?= (int) $anio_activo ?> por curso y estudiante (solo lectura).</p>
                            </div>
                            <span class="gca-badge"><i class="bi bi-eye" aria-hidden="true"></i> Vista general</span>
                        </div>

                        <?php if (!$plataforma_activa): ?>
                            <div class="empty-state">
                                <i class="bi bi-journal-x" aria-hidden="true"></i>
                                <h3>Registro de notas cerrado</h3>
                                <p>Activa la plataforma para visualizar las notas registradas.</p>
                            </div>
                        <?php elseif (count($cursos) === 0): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <p>No hay cursos registrados en el sistema.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="accordionCursos">
                                <?php foreach ($cursos as $curso):
                                    $estudiantes = $estPorCurso[(int) $curso['id']] ?? [];
                                    $ranking_map = [];
                                    foreach ($rankings[(int) $curso['id']] ?? [] as $r) {
                                        if ($r['puesto'] !== null) {
                                            $ranking_map[$r['id']] = ['pos' => $r['puesto'], 'total' => $r['total']];
                                        }
                                    }
                                ?>
                                    <div class="accordion-item acc-curso">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseCurso<?= $curso['id'] ?>" aria-expanded="false" aria-controls="collapseCurso<?= $curso['id'] ?>">
                                                <i class="bi bi-collection acc-ico" aria-hidden="true"></i>
                                                <?= htmlspecialchars(ucfirst($curso['nivel'] ?? '') . ' - ' . $curso['grado'] . ' ' . $curso['nombre']) ?>
                                                <span class="gca-badge acc-rank"><?= count($estudiantes) ?> est.</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCurso<?= $curso['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionCursos">
                                            <div class="accordion-body p-0">
                                                <?php if (count($estudiantes) > 0): ?>
                                                    <div class="accordion student-accordion" id="accordionEstudiantes<?= $curso['id'] ?>">
                                                        <?php foreach ($estudiantes as $estudiante):
                                                            $materias = $notasPorEst[(int) $estudiante['id']] ?? [];
                                                            $promedio_gral = $promedioAnual[(int) $estudiante['id']] ?? null;
                                                        ?>
                                                            <div class="accordion-item acc-est">
                                                                <h2 class="accordion-header">
                                                                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#collapseEst<?= $estudiante['id'] ?>" aria-expanded="false" aria-controls="collapseEst<?= $estudiante['id'] ?>">
                                                                        <i class="bi bi-person acc-ico" aria-hidden="true"></i>
                                                                        <?= htmlspecialchars($estudiante['nombre']) ?>
                                                                        <?php if (isset($ranking_map[$estudiante['id']])): ?>
                                                                            <span class="gca-badge is-gold acc-rank" title="Puesto en el curso">
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
                                                                                <table class="table gca-table gca-table-sm mb-0">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th scope="col">Área</th>
                                                                                            <th scope="col">Asignatura</th>
                                                                                            <th scope="col" class="text-center">P1</th>
                                                                                            <th scope="col" class="text-center">P2</th>
                                                                                            <th scope="col" class="text-center">P3</th>
                                                                                            <th scope="col" class="text-center">P4</th>
                                                                                            <th scope="col" class="text-center">Prom.</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($materias as $m): ?>
                                                                                            <tr>
                                                                                                <td class="text-muted"><?= htmlspecialchars($m['area']) ?></td>
                                                                                                <td><?= htmlspecialchars($m['asignatura']) ?></td>
                                                                                                <?php for ($p = 1; $p <= 4; $p++):
                                                                                                    $n = $m['periodos'][(string)$p] ?? null;
                                                                                                ?>
                                                                                                    <td class="text-center">
                                                                                                        <?php if ($n !== null): ?>
                                                                                                            <span class="nota <?= $n >= $notaMinima ? 'ok' : ($n >= 40 ? 'warn' : 'bad') ?>">
                                                                                                                <?= (int)$n ?>
                                                                                                            </span>
                                                                                                        <?php else: ?>
                                                                                                            <span class="nota-vacia" aria-label="Sin nota">–</span>
                                                                                                        <?php endif; ?>
                                                                                                    </td>
                                                                                                <?php endfor; ?>
                                                                                                <td class="text-center">
                                                                                                    <?php if ($m['promedio'] !== null): ?>
                                                                                                        <span class="nota solid <?= $m['promedio'] >= $notaMinima ? 'ok' : ($m['promedio'] >= 40 ? 'warn' : 'bad') ?>">
                                                                                                            <?= $m['promedio'] ?>
                                                                                                        </span>
                                                                                                    <?php else: ?>
                                                                                                        <span class="nota-vacia" aria-label="Sin nota">–</span>
                                                                                                    <?php endif; ?>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                    <tfoot>
                                                                                        <tr class="row-total">
                                                                                            <td colspan="6" class="text-end fw-semibold">
                                                                                                <i class="bi bi-star-fill me-1 text-gold" aria-hidden="true"></i>Promedio del año (como en el boletín)
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                <?php if ($promedio_gral !== null): ?>
                                                                                                    <span class="nota solid <?= $promedio_gral >= $notaMinima ? 'ok' : ($promedio_gral >= 40 ? 'warn' : 'bad') ?>">
                                                                                                        <?= $promedio_gral ?>
                                                                                                    </span>
                                                                                                <?php else: ?>
                                                                                                    <span class="nota-vacia" aria-label="Sin nota">–</span>
                                                                                                <?php endif; ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tfoot>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="empty-state empty-state-sm"><p>Sin notas registradas.</p></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="empty-state empty-state-sm"><p>No hay estudiantes en este curso.</p></div>
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
        /* Escala de desempeño: vista previa de los cuatro rangos y validación en vivo */
        (function () {
            const f = document.getElementById('formEscala');
            if (!f) return;
            const anio = document.getElementById('f_escala_anio');
            const campos = { superior: f.escala_superior, alto: f.escala_alto, basico: f.escala_basico };
            const prev = document.getElementById('escalaPreview');
            const aviso = document.getElementById('escalaAviso');
            const btn = document.getElementById('btnEscala');
            const activo = anio.options[0].value;
            function pinta() {
                const s = +campos.superior.value, a = +campos.alto.value, b = +campos.basico.value;
                const enteros = [s, a, b].every(v => Number.isInteger(v) && v >= 1 && v <= 100);
                if (!enteros || !(s > a && a > b)) {
                    prev.innerHTML = '<span class="escala-error"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Superior debe empezar más arriba que Alto, y Alto más arriba que Básico (enteros de 1 a 100).</span>';
                    btn.disabled = true;
                } else {
                    const r = [['Superior', s + '–100', 'sup'], ['Alto', a + '–' + (s - 1), 'alto'],
                               ['Básico', b + '–' + (a - 1), 'bas'], ['Bajo', '0–' + (b - 1), 'bajo']];
                    prev.innerHTML = r.map(x => '<span class="escala-nivel ' + x[2] + '"><b>' + x[0] + '</b> ' + x[1] + '</span>').join('')
                        + '<span class="escala-aprueba">Aprueba con ' + b + '</span>';
                    btn.disabled = false;
                }
                if (aviso) aviso.classList.toggle('d-none', anio.value !== activo);
            }
            anio.addEventListener('change', function () {
                const e = JSON.parse(anio.selectedOptions[0].dataset.escala);
                campos.superior.value = e.superior; campos.alto.value = e.alto; campos.basico.value = e.basico;
                pinta();
            });
            Object.values(campos).forEach(c => c.addEventListener('input', pinta));
            pinta();
        })();

        document.getElementById('platformSwitch')?.addEventListener('change', function() {
            const sw = this;
            const isActive = this.checked;
            const pregunta = isActive
                ? '¿Activar el registro de notas para todos los profesores?'
                : '¿Desactivar el registro de notas? Ningún profesor podrá guardar notas hasta que se vuelva a activar.';
            if (!confirm(pregunta)) { sw.checked = !isActive; return; }
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('estado', isActive);
            formData.append('_csrf_token', <?= json_encode(generar_token_csrf()) ?>);
            fetch('plataforma.php', { method: 'POST', body: formData })
                .then(r => r.text())
                .then(res => {
                    if (res === 'ok') location.reload();
                    else { alert('No se pudo guardar el cambio. Intente de nuevo.'); sw.checked = !isActive; }
                })
                .catch(() => { alert('No se pudo conectar con el servidor.'); sw.checked = !isActive; });
        });
    </script>

    <?php include "includes/footer.php"; ?>
