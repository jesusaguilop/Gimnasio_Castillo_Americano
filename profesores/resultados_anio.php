<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/rank_helper.php";

$profesor_id = userId();
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));
$NOTA_MINIMA = 60;

/* Cursos que dirige */
$cursos = $conexion->prepare("
    SELECT c.id, c.grado, c.nombre AS curso_nombre, c.nivel
    FROM directores_grupo dg JOIN cursos c ON dg.curso_id = c.id
    WHERE dg.profesor_id = ?
    ORDER BY c.nivel, c.grado, c.nombre
");
$cursos->execute([$profesor_id]);
$cursos = $cursos->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Resultados del Año";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-award"></i> Resultados del Año</h5>
                <p>Panel del Profesor &nbsp;/&nbsp; Dirección de Grupo</p>
            </div>
        </div>

        <div class="content-area">
            <?php if (count($cursos) === 0): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-people" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin grupo a cargo</h4>
                    <p class="text-muted mb-0">No diriges ningún curso.</p>
                </div>
            <?php else: ?>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                    <div class="section-header mb-0"><h4><i class="bi bi-award"></i> Consolidado Anual</h4></div>
                    <span class="badge bg-dark rounded-pill px-3 py-1" style="color:var(--gold);font-size:11px;">
                        <i class="bi bi-calendar3"></i> Año <?= $anio_activo ?> &middot; aprueba con <?= $NOTA_MINIMA ?>
                    </span>
                </div>

                <?php foreach ($cursos as $curso):
                    $ranking = rankingAnualCurso($conexion, (int)$curso['id'], $anio_activo, $NOTA_MINIMA);
                    $conNotas = array_filter($ranking, fn($r) => $r['promedio'] !== null);
                    $aprob = count(array_filter($conNotas, fn($r) => $r['aprobo']));
                    $reprob = count($conNotas) - $aprob;
                ?>
                    <div class="gca-card mb-4">
                        <div class="p-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom:1px solid #f0ede8;background:#fcfbfa;">
                            <h5 style="font-family:'Cormorant Garamond',serif;font-weight:700;margin:0;">
                                <i class="bi bi-book-fill" style="color:var(--gold);"></i>
                                <?= htmlspecialchars(ucfirst($curso['nivel'] ?? '') . ' - ' . $curso['grado'] . ' ' . $curso['curso_nombre']) ?>
                            </h5>
                            <div class="d-flex gap-2">
                                <span class="badge rounded-pill px-3 py-2" style="background:#e8f5e9;color:#2e7d32;font-size:12px;"><i class="bi bi-check-circle-fill"></i> <?= $aprob ?> aprobados</span>
                                <span class="badge rounded-pill px-3 py-2" style="background:#ffebee;color:#c62828;font-size:12px;"><i class="bi bi-x-circle-fill"></i> <?= $reprob ?> reprobados</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table gca-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">Puesto</th>
                                        <th>Estudiante</th>
                                        <th style="width:110px;text-align:center;">Promedio año</th>
                                        <th style="width:110px;text-align:center;">Áreas perdidas</th>
                                        <th style="width:130px;text-align:center;">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $pos = 1; foreach ($ranking as $r): ?>
                                        <tr>
                                            <td style="text-align:center;font-weight:700;color:var(--gold-dark);">
                                                <?= $r['promedio'] !== null ? '#' . $pos : '—' ?>
                                            </td>
                                            <td class="fw-medium"><?= htmlspecialchars($r['nombre']) ?></td>
                                            <td style="text-align:center;">
                                                <?php if ($r['promedio'] !== null):
                                                    $p = (float)$r['promedio'];
                                                    $bg = $p >= 60 ? '#1b5e20' : ($p >= 40 ? '#e65100' : '#b71c1c'); ?>
                                                    <span class="badge rounded-pill px-3 py-1" style="background:<?= $bg ?>;color:#fff;font-size:12px;"><?= $p ?></span>
                                                <?php else: ?><span class="text-muted">Sin notas</span><?php endif; ?>
                                            </td>
                                            <td style="text-align:center;"><?= $r['promedio'] !== null ? (int)$r['areas_perdidas'] : '—' ?></td>
                                            <td style="text-align:center;">
                                                <?php if ($r['promedio'] === null): ?>
                                                    <span class="text-muted">—</span>
                                                <?php elseif ($r['aprobo']): ?>
                                                    <span style="color:#2e7d32;font-weight:700;font-size:12px;"><i class="bi bi-check-circle-fill"></i> APROBÓ</span>
                                                <?php else: ?>
                                                    <span style="color:#c62828;font-weight:700;font-size:12px;"><i class="bi bi-x-circle-fill"></i> REPROBÓ</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if ($r['promedio'] !== null) $pos++; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex align-items-center gap-3 p-3 gca-card" style="background:#fcfbfa;">
                    <i class="bi bi-info-circle-fill" style="color:var(--gold);font-size:20px;"></i>
                    <p class="mb-0 small">
                        El <strong>promedio del año</strong> consolida los 4 períodos (definitiva anual por materia → área → general).
                        <strong>Aprueba</strong> quien tiene promedio general del año &ge; <?= $NOTA_MINIMA ?>.
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
