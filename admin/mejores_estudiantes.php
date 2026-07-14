<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');
require_once "../lib/rank_helper.php";

/* ── Get active period + year and available periods ── */
$periodo_activo = getConfig('periodo_activo') ?? '1';
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));

$stmtPd = $conexion->prepare("
    SELECT DISTINCT periodo FROM notas WHERE anio = ? ORDER BY
        CASE periodo
            WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4
            WHEN '1er Periodo' THEN 1 WHEN '2do Periodo' THEN 2 WHEN '3er Periodo' THEN 3 WHEN '4to Periodo' THEN 4
            ELSE 5
        END
");
$stmtPd->execute([$anio_activo]);
$periodos_disponibles = $stmtPd->fetchAll(PDO::FETCH_COLUMN);

$selected_periodo = $_GET['periodo'] ?? $periodo_activo;
$limite = max(1, (int)($_GET['limite'] ?? 5));

$mejores = calcularMejoresPorGrado($conexion, $selected_periodo, $limite, $anio_activo);

$pageTitle = "Mejores Estudiantes por Grado";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-trophy"></i> Mejores Estudiantes por Grado</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Reportes &nbsp;/&nbsp; Rendimiento</p>
            </div>
        </div>

        <div class="content-area">

            <!-- Filters -->
            <div class="gca-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium"><i class="bi bi-calendar3 me-1" style="color:var(--gold);"></i> Período</label>
                        <select name="periodo" class="form-select">
                            <?php foreach ($periodos_disponibles as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $p === $selected_periodo ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium"><i class="bi bi-sort-numeric-up me-1" style="color:var(--gold);"></i> Top</label>
                        <select name="limite" class="form-select">
                            <?php foreach ([3, 5, 10, 15, 20] as $l): ?>
                                <option value="<?= $l ?>" <?= $l === $limite ? 'selected' : '' ?>><?= $l ?> estudiantes</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn-gca w-100 justify-content-center">
                            <i class="bi bi-search"></i> Generar Reporte
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" onclick="window.print()" class="btn-outline-gca w-100 justify-content-center">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </form>
            </div>

            <?php if (empty($mejores)): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-inbox" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin datos</h4>
                    <p class="text-muted mb-0">No hay calificaciones registradas para el período seleccionado.</p>
                </div>
            <?php else:
                $niveles_orden = ['Preescolar', 'Primaria', 'Secundaria'];
                $agrupado = [];
                foreach ($mejores as $grado => $estudiantes) {
                    if (count($estudiantes) === 0) continue;
                    $nivel = obtenerNivelGrado($grado);
                    $agrupado[$nivel][$grado] = $estudiantes;
                }
                ?>
                <?php foreach ($niveles_orden as $nivel): ?>
                    <?php if (!isset($agrupado[$nivel])) continue; ?>
                    <div class="gca-card p-4 mb-4">
                        <div class="section-header mb-3 pb-3" style="border-bottom:2px solid var(--gold);">
                            <h4>
                                <i class="bi bi-trophy-fill" style="color:var(--gold);"></i>
                                <?= $nivel ?>
                            </h4>
                        </div>

                        <?php foreach ($agrupado[$nivel] as $grado => $estudiantes): ?>
                            <div class="mb-4">
                                <h5 style="font-family:'Cormorant Garamond',serif;font-weight:700;color:#444;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                    <i class="bi bi-book-fill" style="color:var(--gold-dark);font-size:16px;"></i>
                                    Grado <?= htmlspecialchars(ucfirst($grado)) ?>
                                    <span class="badge bg-dark rounded-pill px-3 py-1" style="color:var(--gold);font-size:10px;margin-left:auto;">
                                        <i class="bi bi-star-fill"></i> Top <?= $limite ?>
                                    </span>
                                </h5>

                                <div class="table-responsive">
                                    <table class="table gca-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:50px;">#</th>
                                                <th>Estudiante</th>
                                                <th>Curso</th>
                                                <th style="width:120px;text-align:center;">Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $pos = 1; ?>
                                            <?php foreach ($estudiantes as $est):
                                                $prom = (float) $est['promedio'];
                                                if ($prom >= 60) $badgeClass = 'bg-success';
                                                elseif ($prom >= 40) $badgeClass = 'bg-warning text-dark';
                                                else $badgeClass = 'bg-danger';
                                            ?>
                                                <tr>
                                                    <td style="font-weight:700;color:var(--gold-dark);">#<?= $pos++ ?></td>
                                                    <td class="fw-medium"><?= htmlspecialchars($est['nombre']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($est['grado']) . ' ' . $est['curso_nombre']) ?></td>
                                                    <td style="text-align:center;">
                                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1 fs-6 fw-semibold">
                                                            <?= $prom ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex align-items-center gap-3 p-3 gca-card" style="background:#fcfbfa;">
                    <i class="bi bi-info-circle-fill" style="color:var(--gold);font-size:20px;"></i>
                    <div>
                        <p class="mb-0 small">
                            <strong>Reporte de Mejores Estudiantes</strong> &middot;
                            Período <?= htmlspecialchars($selected_periodo) ?> &middot;
                            Mostrando los <?= $limite ?> mejores estudiantes por grado.
                            Los promedios se calculan sobre todas las asignaturas cursadas en el período.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include "includes/footer.php"; ?>
