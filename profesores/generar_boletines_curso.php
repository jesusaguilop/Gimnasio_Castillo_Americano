
<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/boletin_generator.php";

@set_time_limit(300); // generar muchos PDFs puede tardar; evita timeout

$profesor_id = userId();
$curso_id = (int)($_GET['curso'] ?? 0);
$periodo  = $_GET['periodo'] ?? (getConfig('periodo_activo') ?? '1');
$anio     = (int)($_GET['anio'] ?? (getConfig('anio_activo') ?? date('Y')));
$template = ($_GET['template'] ?? '') === 'v1' ? 'v1' : 'v2';

/* Solo el director de grupo del curso puede generar en masa */
$chk = $conexion->prepare("
    SELECT c.id, c.grado, c.nombre AS curso_nombre, c.nivel
    FROM directores_grupo dg
    JOIN cursos c ON dg.curso_id = c.id
    WHERE dg.curso_id = ? AND dg.profesor_id = ?
");
$chk->execute([$curso_id, $profesor_id]);
$curso = $chk->fetch(PDO::FETCH_ASSOC);

$resultados = [];
$generados = 0;
$omitidos = 0;

if ($curso) {
    $ests = $conexion->prepare("SELECT id, nombre, documento FROM estudiantes WHERE curso_id = ? ORDER BY nombre");
    $ests->execute([$curso_id]);
    foreach ($ests->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $r = generarBoletinEstudiante($conexion, (int)$e['id'], $periodo, (int)$profesor_id, $template, $anio);
        if (!empty($r['ok'])) { $generados++; } else { $omitidos++; }
        $resultados[] = [
            'nombre'   => $e['nombre'],
            'documento'=> $e['documento'],
            'ok'       => !empty($r['ok']),
            'motivo'   => $r['motivo'] ?? null,
            'ruta'     => $r['ruta'] ?? null,
            'promedio' => $r['promedio'] ?? null,
            'puesto'   => $r['puesto'] ?? null,
        ];
    }
}

$pageTitle = "Generar Boletines";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-files"></i> Generar Boletines</h5>
                <p>Panel del Profesor &nbsp;/&nbsp; Dirección de Grupo</p>
            </div>
        </div>

        <div class="content-area">

            <?php if (!$curso): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-shield-lock" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin permiso</h4>
                    <p class="text-muted mb-0">No eres director de grupo de este curso.</p>
                    <a href="direccion_grupo.php" class="btn-gca btn-gca-sm mt-3"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            <?php else: ?>

                <!-- Resumen -->
                <div class="gca-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h5 style="font-family:'Cormorant Garamond',serif;font-weight:700;margin:0;">
                                <i class="bi bi-collection-fill" style="color:var(--gold);"></i>
                                Boletines · <?= htmlspecialchars(ucfirst($curso['nivel'] ?? '') . ' - ' . $curso['grado'] . ' ' . $curso['curso_nombre']) ?>
                            </h5>
                            <p class="text-muted small mb-0">Período <?= htmlspecialchars($periodo) ?></p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge rounded-pill px-3 py-2" style="background:#e8f5e9;color:#2e7d32;font-size:12px;">
                                <i class="bi bi-check-circle-fill"></i> <?= $generados ?> generados
                            </span>
                            <?php if ($omitidos > 0): ?>
                                <span class="badge rounded-pill px-3 py-2" style="background:#fff3e0;color:#e65100;font-size:12px;">
                                    <i class="bi bi-exclamation-circle-fill"></i> <?= $omitidos ?> sin notas
                                </span>
                            <?php endif; ?>
                            <a href="direccion_grupo.php" class="btn-outline-gca btn-gca-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                        </div>
                    </div>
                </div>

                <!-- Detalle por estudiante -->
                <div class="gca-card">
                    <div class="table-responsive">
                        <table class="table gca-table mb-0">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th style="width:90px;text-align:center;">Promedio</th>
                                    <th style="width:90px;text-align:center;">Puesto</th>
                                    <th style="width:130px;text-align:center;">Estado</th>
                                    <th style="width:120px;text-align:center;">Boletín</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $r): ?>
                                    <tr>
                                        <td class="fw-medium"><?= htmlspecialchars($r['nombre']) ?></td>
                                        <td style="text-align:center;">
                                            <?php if ($r['ok'] && $r['promedio'] !== null):
                                                $p = (float)$r['promedio'];
                                                $bg = $p >= 60 ? '#1b5e20' : ($p >= 40 ? '#e65100' : '#b71c1c'); ?>
                                                <span class="badge rounded-pill px-2 py-1" style="background:<?= $bg ?>;color:#fff;font-size:12px;"><?= $p ?></span>
                                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                        </td>
                                        <td style="text-align:center;"><?= $r['ok'] && $r['puesto'] ? htmlspecialchars($r['puesto']) : '<span class="text-muted">—</span>' ?></td>
                                        <td style="text-align:center;">
                                            <?php if ($r['ok']): ?>
                                                <span style="color:#2e7d32;font-size:12px;font-weight:600;"><i class="bi bi-check-circle-fill"></i> Generado</span>
                                            <?php else: ?>
                                                <span style="color:#e65100;font-size:12px;"><i class="bi bi-dash-circle"></i> <?= $r['motivo'] === 'Sin notas' ? 'Sin notas' : htmlspecialchars($r['motivo'] ?? 'Omitido') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if ($r['ok'] && $r['ruta']): ?>
                                                <a href="../<?= htmlspecialchars($r['ruta']) ?>" target="_blank" class="btn-action btn-edit" title="Ver PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 p-3 gca-card" style="background:#fcfbfa;">
                    <i class="bi bi-info-circle-fill" style="color:var(--gold);font-size:20px;"></i>
                    <p class="mb-0 small">
                        Los boletines se guardaron con su <strong>promedio</strong> y <strong>puesto</strong>. Los estudiantes
                        <strong>sin notas</strong> se omiten: registra sus calificaciones y vuelve a generar.
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
