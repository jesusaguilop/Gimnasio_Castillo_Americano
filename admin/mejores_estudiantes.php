<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');
require_once "../lib/rank_helper.php";

/* ── Get active period + year and available periods ── */
$periodo_activo = getConfig('periodo_activo') ?? '1';
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));

$stmtPd = $conexion->prepare("
    SELECT periodo FROM notas WHERE anio = ? GROUP BY periodo ORDER BY
        CASE periodo
            WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4
            WHEN '1er Periodo' THEN 1 WHEN '2do Periodo' THEN 2 WHEN '3er Periodo' THEN 3 WHEN '4to Periodo' THEN 4
            ELSE 5
        END
");
$stmtPd->execute([$anio_activo]);
$periodos_disponibles = $stmtPd->fetchAll(PDO::FETCH_COLUMN);

$selected_periodo = $_GET['periodo'] ?? $periodo_activo;
$limite = min(50, max(1, (int)($_GET['limite'] ?? 5)));

$mejores = calcularMejoresPorGrado($conexion, $selected_periodo, $limite, $anio_activo);

$pageTitle = "Mejores Estudiantes por Grado";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">

            <!-- Filtros -->
            <form method="GET" class="filter-bar no-print" aria-label="Opciones del reporte">
                <div class="ff">
                    <label class="form-label" for="f_periodo">Período</label>
                    <select id="f_periodo" name="periodo" class="form-select">
                        <?php foreach ($periodos_disponibles as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= $p === $selected_periodo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ff">
                    <label class="form-label" for="f_limite">Mostrar</label>
                    <select id="f_limite" name="limite" class="form-select">
                        <?php foreach ([3, 5, 10, 15, 20] as $l): ?>
                            <option value="<?= $l ?>" <?= $l === $limite ? 'selected' : '' ?>>Top <?= $l ?> por grado</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ff-actions">
                    <button type="submit" class="btn-gca">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i> Generar reporte
                    </button>
                    <button type="button" onclick="window.print()" class="btn-outline-gca">
                        <i class="bi bi-printer" aria-hidden="true"></i> Imprimir
                    </button>
                </div>
            </form>

            <?php if (empty($mejores)): ?>
                <div class="gca-card empty-state">
                    <i class="bi bi-trophy" aria-hidden="true"></i>
                    <h3>Sin datos</h3>
                    <p>No hay calificaciones registradas para el período seleccionado.</p>
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
                <div class="alert alert-gca alert-icon mb-4" role="note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <div>
                        <strong>Período <?= htmlspecialchars($selected_periodo) ?></strong> ·
                        Los <?= $limite ?> mejores estudiantes de cada grado. El promedio se calcula sobre todas las asignaturas cursadas en el período.
                    </div>
                </div>

                <?php foreach ($niveles_orden as $nivel): ?>
                    <?php if (!isset($agrupado[$nivel])) continue; ?>
                    <section class="gca-card p-4 mb-4" aria-labelledby="niv-<?= htmlspecialchars($nivel) ?>">
                        <div class="section-header">
                            <h4 id="niv-<?= htmlspecialchars($nivel) ?>"><i class="bi bi-trophy" aria-hidden="true"></i> <?= $nivel ?></h4>
                        </div>

                        <div class="row g-4">
                        <?php foreach ($agrupado[$nivel] as $grado => $estudiantes): ?>
                            <div class="col-xxl-6">
                                <h5 class="level-head">
                                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                                    Grado <?= htmlspecialchars(ucfirst($grado)) ?>
                                    <span class="gca-badge is-gold">Top <?= $limite ?></span>
                                </h5>

                                <div class="table-responsive">
                                    <table class="table gca-table align-middle" data-no-filter>
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Estudiante</th>
                                                <th scope="col">Curso</th>
                                                <th scope="col" class="text-center">Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $pos = 1; ?>
                                            <?php foreach ($estudiantes as $est):
                                                $prom = (float) $est['promedio'];
                                                if ($prom >= 60) $badgeClass = 'ok';
                                                elseif ($prom >= 40) $badgeClass = 'warn';
                                                else $badgeClass = 'bad';
                                            ?>
                                                <tr>
                                                    <td class="rank-pos<?= $pos === 1 ? ' top1' : '' ?>"><?= $pos === 1 ? '<i class="bi bi-trophy-fill me-1" aria-hidden="true"></i>' : '' ?>#<?= $pos++ ?></td>
                                                    <td class="cell-strong"><?= htmlspecialchars($est['nombre']) ?></td>
                                                    <td class="text-nowrap"><?= htmlspecialchars(ucfirst($est['grado']) . ' ' . $est['curso_nombre']) ?></td>
                                                    <td class="text-center">
                                                        <span class="nota <?= $badgeClass ?>"><?= $prom ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <?php include "includes/footer.php"; ?>
