<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/alertas_helper.php";
require_once "../lib/rank_helper.php";
require_once "../lib/csrf_helper.php";

$profesor_id = userId();

$periodo_activo = (string) (getConfig('periodo_activo') ?? '1');
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));

/* ── ¿Se puede registrar? plataforma activa + ventana de fechas (Configuración) ── */
$hoy = date('Y-m-d');
$fecha_apertura = trim((string) (getConfig('fecha_apertura') ?? ''));
$fecha_cierre   = trim((string) (getConfig('fecha_cierre') ?? ''));
$motivo_cierre = null;
if ((getConfig('plataforma_activa') ?? '0') !== '1') {
    $motivo_cierre = 'El registro de notas no está habilitado. Consulta con administración.';
} elseif ($fecha_apertura !== '' && $hoy < $fecha_apertura) {
    $motivo_cierre = 'El registro de notas del período ' . $periodo_activo . ' abre el ' . date('d/m/Y', strtotime($fecha_apertura)) . '.';
} elseif ($fecha_cierre !== '' && $hoy > $fecha_cierre) {
    $motivo_cierre = 'El registro de notas del período ' . $periodo_activo . ' cerró el ' . date('d/m/Y', strtotime($fecha_cierre)) . '. Consulta con administración.';
}
$plataforma_activa = ($motivo_cierre === null);

$mensaje = "";
$error = "";

/* ── Asignaciones del profesor: SOLO estas se pueden calificar (control IDOR) ── */
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
$asignacion_id = (int) ($_GET['asignacion_id'] ?? 0);
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 60; // un curso completo por página: menos riesgo de perder notas al paginar

$asignacion_seleccionada = null;
foreach ($asignaciones as $a) {
    if ((int) $a['id'] === $asignacion_id) {
        $asignacion_seleccionada = $a;
        break;
    }
}

/* ── Handle save ── */
$flash = \App\Base\Session::getSuccess();
if ($flash) $mensaje = $flash;

// Valores escritos por el profesor cuando el guardado se rechaza: se vuelven a
// mostrar en vez de los de la BD para que no tenga que digitarlos otra vez.
$valores_post = null;
$invalidos_ids = [];

$es_guardado = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_masivo']);
if ($es_guardado && !$asignacion_seleccionada) {
    $error = "No tienes asignada esa asignatura en ese curso.";
} elseif ($es_guardado && !validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    $error = "Error de seguridad. Recargue la página e intente de nuevo.";
} elseif ($es_guardado && !$plataforma_activa) {
    // El formulario se oculta con la plataforma cerrada, pero el POST también se bloquea
    $error = $motivo_cierre;
} elseif ($es_guardado) {
    $curso_id = (int) $asignacion_seleccionada['curso_id'];
    $asignatura_id = (int) $asignacion_seleccionada['asignatura_id'];
    $nota_field = 'nota_' . $periodo_activo;

    $estudiantes_post = is_array($_POST['estudiantes'] ?? null) ? $_POST['estudiantes'] : [];
    $valores_post = [];
    foreach ($estudiantes_post as $eid => $d) {
        $valores_post[(int) $eid] = [
            'nota'   => is_array($d) ? (string) ($d[$nota_field] ?? '') : '',
            'examen' => is_array($d) ? (string) ($d['examen'] ?? '') : '',
            'logro'  => is_array($d) ? (string) ($d['logro'] ?? '') : '',
        ];
    }

    try {
        $est_ids = array_values(array_filter(array_keys($valores_post)));
        if (count($est_ids) === 0) throw new RuntimeException("No hay estudiantes para guardar.");

        // Solo estudiantes que de verdad son de este curso (1 consulta)
        $placeholders = implode(',', array_fill(0, count($est_ids), '?'));
        $checkStmt = $conexion->prepare("SELECT id, nombre FROM estudiantes WHERE id IN ($placeholders) AND curso_id = ?");
        $checkStmt->execute(array_merge($est_ids, [$curso_id]));
        $nombres = $checkStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $valid_ids = array_map('intval', array_keys($nombres));
        if (count($valid_ids) === 0) throw new RuntimeException("Ningún estudiante pertenece a este curso.");

        // Validar TODO antes de tocar la BD: una nota inválida no borra la anterior
        $notas_ok = [];   // est_id => int|null
        $examenes_ok = []; // est_id => int|null
        $invalidos = [];
        foreach ($valid_ids as $eid) {
            [$valida, $valor] = parsearNota($valores_post[$eid]['nota']);
            if (!$valida) {
                $invalidos[] = $nombres[$eid] . ' ("' . trim($valores_post[$eid]['nota']) . '")';
                $invalidos_ids[$eid] = true;
            }
            $notas_ok[$eid] = $valor;

            // Examen final del período: SOLO informativo (se muestra en el boletín).
            // La nota de arriba ya es la definitiva; el examen no entra al cálculo.
            [$validaEx, $valorEx] = parsearNota($valores_post[$eid]['examen']);
            if (!$validaEx) {
                $invalidos[] = $nombres[$eid] . ' (examen "' . trim($valores_post[$eid]['examen']) . '")';
                $invalidos_ids[$eid] = true;
            } elseif ($valorEx !== null && $valor === null && $valida) {
                $invalidos[] = $nombres[$eid] . ' (tiene examen pero falta la nota del período)';
                $invalidos_ids[$eid] = true;
            }
            $examenes_ok[$eid] = $valorEx;
        }

        if ($invalidos) {
            $error = "Revise estas notas (números enteros de 0 a 100; se acepta 85 o 85,0): "
                   . implode(', ', $invalidos) . ". No se guardó ningún cambio.";
        } else {
            // UNA sola sentencia: borra las notas/logros previos de estos estudiantes en
            // esta asignatura/período/año y escribe los nuevos. Es atómica por sí misma
            // (todo o nada) y cuesta un único viaje a la BD sin importar el tamaño del curso.
            // Las sub-sentencias ven la misma foto de la tabla: los DELETE no tocan las
            // filas que insertan los INSERT.
            // (Sin filtrar por curso_id al borrar: un estudiante tiene UNA nota por
            // asignatura/período/año aunque haya cambiado de curso; antes quedaban duplicadas.)
            $phIds = implode(',', array_fill(0, count($valid_ids), '?'));
            $params = [];
            $sql = "WITH del_n AS (
                        DELETE FROM notas
                        WHERE estudiante_id IN ($phIds) AND asignatura_id = ? AND periodo = ? AND anio = ?
                    ), del_l AS (
                        DELETE FROM logros
                        WHERE estudiante_id IN ($phIds) AND asignatura_id = ? AND periodo = ? AND anio = ?
                    )";
            array_push($params, ...$valid_ids);
            array_push($params, $asignatura_id, $periodo_activo, $anio_activo);
            array_push($params, ...$valid_ids);
            array_push($params, $asignatura_id, $periodo_activo, $anio_activo);

            $filasN = [];
            $filasL = [];
            $paramsN = [];
            $paramsL = [];
            foreach ($valid_ids as $eid) {
                if ($notas_ok[$eid] !== null) {
                    $filasN[] = '(?, ?, ?, ?, ?, ?, ?, ?::smallint)';
                    array_push($paramsN, $eid, $profesor_id, $asignatura_id, $curso_id, $periodo_activo, $notas_ok[$eid], $anio_activo, $examenes_ok[$eid]);
                }
                $logro_texto = trim($valores_post[$eid]['logro']);
                if ($logro_texto !== '') {
                    $filasL[] = '(?, ?, ?, ?, ?)';
                    array_push($paramsL, $eid, $asignatura_id, $periodo_activo, $logro_texto, $anio_activo);
                }
            }
            if ($filasN) {
                $sql .= ", ins_n AS (
                        INSERT INTO notas (estudiante_id, profesor_id, asignatura_id, curso_id, periodo, nota, anio, examen)
                        VALUES " . implode(', ', $filasN) . " RETURNING 1
                    )";
                array_push($params, ...$paramsN);
            }
            if ($filasL) {
                $sql .= ", ins_l AS (
                        INSERT INTO logros (estudiante_id, asignatura_id, periodo, logro, anio)
                        VALUES " . implode(', ', $filasL) . " RETURNING 1
                    )";
                array_push($params, ...$paramsL);
            }
            $sql .= " SELECT 1";
            $conexion->prepare($sql)->execute($params);

            $con_nota = count($filasN);
            $ok = "Notas y logros guardados correctamente ({$con_nota} nota(s) de " . count($valid_ids) . " estudiante(s)).";

            // Alertas de riesgo académico (período 3): todo el curso con ≤ 3 consultas.
            // Si fallan, las notas YA quedaron guardadas: no se reporta como error de guardado.
            if ($periodo_activo === '3') {
                try {
                    $riesgos = count(verificarRiesgoAcademicoCurso($conexion, $valid_ids, $periodo_activo, $anio_activo));
                    if ($riesgos > 0) $ok .= " Se generaron {$riesgos} alerta(s) de riesgo académico.";
                } catch (Throwable $e) {
                    error_log("[registrar_notas] alertas de riesgo: " . $e->getMessage());
                }
            }
            // Post/Redirect/Get: recargar la página no vuelve a enviar el formulario
            \App\Base\Session::setSuccess($ok);
            header("Location: registrar_notas.php?asignacion_id=" . (int) $asignacion_id . "&page=" . (int) $pagina);
            exit;
        }
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        error_log("[registrar_notas] " . $e->getMessage());
        $error = "No se pudo guardar. Intente de nuevo; si el problema sigue, avise a administración.";
    }
}

/* ── Estudiantes del curso + sus notas de la asignatura (todos los períodos) +
 *    logro del período, en UNA consulta (antes eran 4) ── */
$estudiantes = [];
$total_estudiantes = 0;
$total_paginas = 0;
$offset = 0;
$notas_por_periodo = [];   // [est_id][periodo] => nota
$promedios = [];           // [est_id] => promedio de la asignatura en el año
$logros_existentes = [];
$examenes_existentes = [];   // [est_id] => examen del período activo
$ranking_map = [];

if ($asignacion_seleccionada) {
    $offset = ($pagina - 1) * $por_pagina;
    $asig = (int) $asignacion_seleccionada['asignatura_id'];
    $stmtE = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento,
               COUNT(*) OVER () AS total,
               (SELECT json_object_agg(n.periodo, n.nota) FROM notas n
                 WHERE n.estudiante_id = e.id AND n.asignatura_id = ? AND n.anio = ?) AS notas,
               (SELECT n.examen FROM notas n
                 WHERE n.estudiante_id = e.id AND n.asignatura_id = ? AND n.periodo = ? AND n.anio = ?
                 LIMIT 1) AS examen,
               (SELECT l.logro FROM logros l
                 WHERE l.estudiante_id = e.id AND l.asignatura_id = ? AND l.periodo = ? AND l.anio = ?
                 ORDER BY l.id DESC LIMIT 1) AS logro
        FROM estudiantes e
        WHERE e.curso_id = ?
        ORDER BY e.nombre
        LIMIT ? OFFSET ?
    ");
    $stmtE->bindValue(1, $asig, PDO::PARAM_INT);
    $stmtE->bindValue(2, $anio_activo, PDO::PARAM_INT);
    $stmtE->bindValue(3, $asig, PDO::PARAM_INT);
    $stmtE->bindValue(4, $periodo_activo, PDO::PARAM_STR);
    $stmtE->bindValue(5, $anio_activo, PDO::PARAM_INT);
    $stmtE->bindValue(6, $asig, PDO::PARAM_INT);
    $stmtE->bindValue(7, $periodo_activo, PDO::PARAM_STR);
    $stmtE->bindValue(8, $anio_activo, PDO::PARAM_INT);
    $stmtE->bindValue(9, (int) $asignacion_seleccionada['curso_id'], PDO::PARAM_INT);
    $stmtE->bindValue(10, $por_pagina, PDO::PARAM_INT);
    $stmtE->bindValue(11, $offset, PDO::PARAM_INT);
    $stmtE->execute();
    $estudiantes = $stmtE->fetchAll(PDO::FETCH_ASSOC);
    $total_estudiantes = $estudiantes ? (int) $estudiantes[0]['total'] : 0;
    $total_paginas = max(1, (int) ceil($total_estudiantes / $por_pagina));

    foreach ($estudiantes as $row) {
        $eid = (int) $row['id'];
        $notas_por_periodo[$eid] = $row['notas'] ? (json_decode($row['notas'], true) ?: []) : [];
        $vals = array_values($notas_por_periodo[$eid]);
        // Definitiva parcial de la asignatura en el año = promedio de los períodos con nota
        $promedios[$eid] = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
        if ($row['logro'] !== null) $logros_existentes[$eid] = $row['logro'];
        if ($row['examen'] !== null) $examenes_existentes[$eid] = (int) $row['examen'];
    }

    // Puesto del período activo (1 consulta; mismo cálculo que el boletín)
    if ($estudiantes) {
        foreach (obtenerRankingCurso($conexion, $asignacion_seleccionada['curso_id'], $periodo_activo, $anio_activo) as $r) {
            $ranking_map[$r['id']] = ['pos' => $r['puesto'], 'total' => $r['total']];
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
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Registro de notas cerrado</h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($motivo_cierre) ?></p>
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
                                &middot; <strong>Período <?= htmlspecialchars($periodo_activo) ?></strong>
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
                            Escribe la nota definitiva (0 a 100), el examen final del período (opcional, solo se muestra en el boletín) y el logro de cada estudiante y pulsa <strong>"Guardar todo"</strong> al final.
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
                                        <th scope="col" style="width:64px;font-size:11px;text-align:center;"><abbr title="Examen final del período <?= htmlspecialchars($periodo_activo) ?> (solo se muestra en el boletín; no cambia la nota)">Examen P<?= htmlspecialchars($periodo_activo) ?></abbr></th>
                                        <th scope="col" style="width:60px;font-size:11px;text-align:center;"><abbr title="Promedio">Prom.</abbr></th>
                                        <th scope="col" style="min-width:140px;">Logro</th>
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
                                                             <?php
                                                                // Tras un rechazo se muestra lo que el profesor escribió, no lo de la BD
                                                                $valor_input = isset($valores_post[(int) $est['id']])
                                                                    ? trim($valores_post[(int) $est['id']]['nota'])
                                                                    : ($n !== null ? (string) (int) $n : '');
                                                                $invalida = isset($invalidos_ids[(int) $est['id']]);
                                                             ?>
                                                             <input type="text" name="estudiantes[<?= $est['id'] ?>][nota_<?= $p ?>]"
                                                                class="form-control form-control-sm grade-input<?= $invalida ? ' is-invalid' : '' ?>"
                                                                inputmode="numeric" maxlength="6" autocomplete="off"
                                                                pattern="\s*(100|[0-9]{1,2})([.,]0+)?\s*"
                                                                title="Nota entera de 0 a 100"
                                                                <?= $invalida ? 'aria-invalid="true"' : '' ?>
                                                                aria-label="Nota del período <?= $p ?> de <?= htmlspecialchars($est['nombre']) ?>"
                                                                value="<?= htmlspecialchars($valor_input) ?>"
                                                                placeholder="–"
                                                                style="width:52px;text-align:center;font-size:12px;display:inline-block;padding:4px 2px;">
                                                        <?php elseif ($n !== null): ?>
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                  title="Desempeño <?= desempenoNota($n) ?>" style="font-size:11px;font-weight:600;<?= desempenoEstilo($n) ?>">
                                                                <?= (int)$n ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="color:#ddd;font-size:11px;">–</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endfor; ?>
                                                <td style="text-align:center;vertical-align:middle;padding:4px 2px;">
                                                    <?php
                                                        $examen_input = isset($valores_post[(int) $est['id']])
                                                            ? trim($valores_post[(int) $est['id']]['examen'])
                                                            : (string) ($examenes_existentes[(int) $est['id']] ?? '');
                                                    ?>
                                                    <input type="text" name="estudiantes[<?= $est['id'] ?>][examen]"
                                                        class="form-control form-control-sm grade-input"
                                                        inputmode="numeric" maxlength="6" autocomplete="off"
                                                        pattern="\s*(100|[0-9]{1,2})([.,]0+)?\s*"
                                                        title="Examen final del período: entero de 0 a 100 (opcional)"
                                                        aria-label="Examen final del período <?= htmlspecialchars($periodo_activo) ?> de <?= htmlspecialchars($est['nombre']) ?>"
                                                        value="<?= htmlspecialchars($examen_input) ?>"
                                                        placeholder="–"
                                                        style="width:52px;text-align:center;font-size:12px;display:inline-block;padding:4px 2px;border-style:dashed;">
                                                </td>
                                                <td style="text-align:center;vertical-align:middle;">
                                                    <?php if ($nota_promedio !== null): ?>
                                                        <span class="badge rounded-pill px-2 py-1"
                                                              title="Promedio de la asignatura en el año · Desempeño <?= desempenoNota($nota_promedio) ?>" style="font-size:11px;font-weight:700;<?= desempenoEstilo($nota_promedio) ?>">
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
                                                        style="resize:vertical;min-height:36px;"><?= htmlspecialchars(isset($valores_post[(int) $est['id']]) ? $valores_post[(int) $est['id']]['logro'] : ($logros_existentes[$est['id']] ?? '')) ?></textarea>
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
                            Período <?= htmlspecialchars($periodo_activo) ?>
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
                                <strong>Período activo: <?= htmlspecialchars($periodo_activo) ?></strong> &middot;
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
        form.addEventListener('submit', function (e) {
            if (form.dataset.enviando) { e.preventDefault(); return; }   // evita doble envío
            form.dataset.enviando = '1';
            cambios = false;
            var b = form.querySelector('button[name="guardar_masivo"]');
            if (b) { b.setAttribute('aria-busy', 'true'); b.style.opacity = '.6'; }
        });
        window.addEventListener('beforeunload', function (e) {
            if (cambios) { e.preventDefault(); e.returnValue = ''; }
        });
        var msg = document.getElementById('msgGuardado');
        if (msg) msg.scrollIntoView({ block: 'center' });
    })();
    </script>
