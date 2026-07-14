<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/rank_helper.php";

$profesor_id = userId();

/* ── Get active period + year ── */
$periodo_activo = getConfig('periodo_activo') ?? '1';
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));

/* ── Get courses where this profesor is director ── */
$cursos_dirigidos = $conexion->prepare("
    SELECT c.id, c.grado, c.nombre AS curso_nombre, c.nivel
    FROM directores_grupo dg
    JOIN cursos c ON dg.curso_id = c.id
    WHERE dg.profesor_id = ?
    ORDER BY c.nivel, c.grado, c.nombre
");
$cursos_dirigidos->execute([$profesor_id]);
$cursos_dirigidos = $cursos_dirigidos->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Dirección de Grupo";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-users"></i> Dirección de Grupo</h5>
                <p>Panel del Profesor &nbsp;/&nbsp; Vista Consolidada</p>
            </div>
        </div>

        <div class="content-area">

            <?php if (count($cursos_dirigidos) === 0): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-people" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin grupo a cargo</h4>
                    <p class="text-muted mb-0">No tienes ningún curso asignado como director de grupo.</p>
                </div>
            <?php else: ?>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                    <div class="section-header mb-0">
                        <h4><i class="bi bi-eye"></i> Vista Consolidada</h4>
                    </div>
                    <span class="badge bg-dark rounded-pill px-3 py-1" style="color:var(--gold);font-size:11px;">
                        <i class="bi bi-calendar3"></i> Período <?= $periodo_activo ?>
                    </span>
                </div>

                <div class="accordion" id="accordionDirector">
                    <?php foreach ($cursos_dirigidos as $curso):
                        $estudiantes = $conexion->prepare("
                            SELECT id, nombre, documento FROM estudiantes WHERE curso_id = ? ORDER BY nombre
                        ");
                        $estudiantes->execute([$curso['id']]);
                        $estudiantes = $estudiantes->fetchAll(PDO::FETCH_ASSOC);

                        $materias_curso = $conexion->prepare("
                            SELECT DISTINCT a.id, a.nombre, a.area
                            FROM profesor_curso_asignatura pca
                            JOIN asignaturas a ON pca.asignatura_id = a.id
                            WHERE pca.curso_id = ?
                            ORDER BY a.area, a.nombre
                        ");
                        $materias_curso->execute([$curso['id']]);
                        $materias_curso = $materias_curso->fetchAll(PDO::FETCH_ASSOC);

                        $ranking = obtenerRankingCurso($conexion, $curso['id'], $periodo_activo, $anio_activo);
                        $ranking_map = [];
                        $prom_map = [];
                        foreach ($ranking as $i => $r) {
                            $ranking_map[$r['id']] = ['pos' => $i + 1, 'total' => count($ranking)];
                            $prom_map[$r['id']] = $r['promedio'];
                        }

                        // Eager-load all notas + logros for all students in this course (1 query)
                        $notas_map_por_est = [];
                        if (count($estudiantes) > 0) {
                            $est_ids = array_column($estudiantes, 'id');
                            $placeholders = implode(',', array_fill(0, count($est_ids), '?'));
                            $params = array_merge($est_ids, [$periodo_activo, $anio_activo]);
                            $notas_all = $conexion->prepare("
                                SELECT n.estudiante_id, n.asignatura_id, n.nota, n.profesor_id,
                                       u.nombre AS profesor_nombre, l.logro
                                FROM notas n
                                JOIN usuarios u ON n.profesor_id = u.id
                                LEFT JOIN logros l ON l.estudiante_id = n.estudiante_id
                                    AND l.asignatura_id = n.asignatura_id
                                    AND l.periodo = n.periodo AND l.anio = n.anio
                                WHERE n.estudiante_id IN ($placeholders) AND n.periodo = ? AND n.anio = ?
                            ");
                            $notas_all->execute($params);
                            foreach ($notas_all->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                $eid = $row['estudiante_id'];
                                $notas_map_por_est[$eid][$row['asignatura_id']] = $row;
                            }
                        }
                    ?>
                        <div class="accordion-item mb-3" style="border-radius:14px!important;border:1px solid #ece8e0;overflow:hidden;">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseDir<?= $curso['id'] ?>"
                                    style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:16px;color:#1a1a1a;background:#fcfbfa;">
                                    <i class="bi bi-book-fill me-2" style="color:var(--gold);"></i>
                                    <?= htmlspecialchars(ucfirst($curso['nivel'] ?? '') . ' - ' . $curso['grado'] . ' ' . $curso['curso_nombre']) ?>
                                    <span class="badge bg-secondary ms-2 rounded-pill"><?= count($estudiantes) ?> est.</span>
                                </button>
                            </h2>
                            <div id="collapseDir<?= $curso['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionDirector">
                                <div class="accordion-body p-0">

                                    <?php if (count($estudiantes) > 0 && count($materias_curso) > 0): ?>
                                        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #f0ede8;background:#fcfbfa;">
                                            <span class="small text-muted">
                                                <i class="bi bi-people-fill me-1" style="color:var(--gold-dark);"></i>
                                                <?= count($estudiantes) ?> estudiantes &middot; <?= count($materias_curso) ?> materias del plan
                                            </span>
                                            <a href="generar_boletines_curso.php?curso=<?= $curso['id'] ?>&periodo=<?= urlencode($periodo_activo) ?>&anio=<?= $anio_activo ?>&template=v2"
                                               class="btn-gca btn-gca-sm"
                                               onclick="return confirm('¿Generar los boletines de TODOS los estudiantes con notas de este curso?\nSe guardarán con su promedio y puesto.')">
                                                <i class="bi bi-collection"></i> Generar todos los boletines
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (count($estudiantes) === 0): ?>
                                        <div class="p-4 text-center text-muted">No hay estudiantes en este curso.</div>
                                    <?php elseif (count($materias_curso) === 0): ?>
                                        <div class="p-4 text-center text-muted">No hay materias asignadas a este curso.</div>
                                    <?php else: ?>
                                        <div class="accordion" id="accordionEstDir<?= $curso['id'] ?>">
                                            <?php foreach ($estudiantes as $est):
                                                $notas_map = $notas_map_por_est[$est['id']] ?? [];
                                            ?>
                                                <div class="accordion-item" style="border-left:3px solid var(--gold);border-radius:0;">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#collapseEstDir<?= $est['id'] ?>"
                                                            style="font-size:13px;background:#fff;">
                                                            <i class="bi bi-person-fill me-2" style="color:var(--gold-dark);"></i>
                                                            <?= htmlspecialchars($est['nombre']) ?>
                                                            <?php if (isset($ranking_map[$est['id']])): ?>
                                                                <span style="margin-left:12px;font-size:10px;font-weight:700;color:var(--gold-dark);background:rgba(212,175,55,0.1);padding:1px 8px;border-radius:20px;white-space:nowrap;">
                                                                    #<?= $ranking_map[$est['id']]['pos'] ?>/<?= $ranking_map[$est['id']]['total'] ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php
                                                                $reg = count($notas_map); $tot = count($materias_curso);
                                                                $completo = ($tot > 0 && $reg >= $tot);
                                                                $prom = $prom_map[$est['id']] ?? null;
                                                            ?>
                                                            <span style="margin-left:8px;font-size:10px;font-weight:700;padding:1px 8px;border-radius:20px;white-space:nowrap;<?= $completo ? 'color:#2e7d32;background:rgba(25,135,84,.12);' : 'color:#e65100;background:rgba(230,81,0,.10);' ?>">
                                                                <i class="bi bi-journal-check"></i> <?= $reg ?>/<?= $tot ?>
                                                            </span>
                                                            <?php if ($prom !== null): ?>
                                                                <span style="margin-left:6px;font-size:10px;font-weight:700;color:#1a1a1a;background:rgba(212,175,55,.15);padding:1px 8px;border-radius:20px;white-space:nowrap;">Prom <?= $prom ?></span>
                                                            <?php endif; ?>
                                                            <span style="margin-left:auto;font-size:11px;color:#999;"><?= htmlspecialchars($est['documento']) ?></span>
                                                        </button>
                                                    </h2>
                                                    <div id="collapseEstDir<?= $est['id'] ?>" class="accordion-collapse collapse"
                                                        data-bs-parent="#accordionEstDir<?= $curso['id'] ?>">
                                                        <div class="accordion-body p-0">
                                                            <div style="padding:10px 14px;display:flex;justify-content:flex-end;border-bottom:1px solid #f5f3ee;">
                                                                <a href="#" class="btn-gca btn-gca-sm btn-gen-boletin"
                                                                   data-estudiante-id="<?= $est['id'] ?>"
                                                                   data-estudiante-nombre="<?= htmlspecialchars($est['nombre'], ENT_QUOTES) ?>">
                                                                    <i class="bi bi-file-earmark-pdf"></i> Generar boletín
                                                                </a>
                                                            </div>
                                                            <div class="table-responsive">
                                                                <table class="table gca-table mb-0" style="font-size:12px;">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Área</th>
                                                                            <th>Asignatura</th>
                                                                            <th>Docente</th>
                                                                            <th style="width:70px;">Nota</th>
                                                                            <th>Logro</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($materias_curso as $mat):
                                                                            $tiene = $notas_map[$mat['id']] ?? null;
                                                                        ?>
                                                                            <tr>
                                                                                <td style="color:#888;"><?= htmlspecialchars($mat['area']) ?></td>
                                                                                <td class="fw-medium"><?= htmlspecialchars($mat['nombre']) ?></td>
                                                                                <td style="font-size:11px;color:#666;">
                                                                                    <?= $tiene ? htmlspecialchars($tiene['profesor_nombre']) : '—' ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($tiene): ?>
                                                                                        <span class="badge rounded-pill px-2 py-1
                                                                                            <?= $tiene['nota'] >= 60 ? 'bg-success' : ($tiene['nota'] >= 40 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                                                            <?= (int) $tiene['nota'] ?>
                                                                                        </span>
                                                                                    <?php else: ?>
                                                                                        <span style="color:#ccc;">—</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td style="font-size:11px;color:#666;max-width:200px;">
                                                                                    <?= $tiene && $tiene['logro'] ? htmlspecialchars($tiene['logro']) : '—' ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 p-3 gca-card" style="background:#fcfbfa;">
                    <i class="bi bi-info-circle-fill" style="color:var(--gold);font-size:20px;"></i>
                    <div>
                        <p class="mb-0 small">
                            Como director de grupo ves las notas de <strong>todas las materias</strong> de tu curso
                            (el avance <strong>X/Y</strong> indica cuántas materias tienen nota). Cuando estén completas,
                            genera el boletín de un estudiante con <strong>"Generar boletín"</strong>, o todos de una vez con
                            <strong>"Generar todos los boletines"</strong> — se guardan con <strong>promedio</strong> y <strong>puesto</strong>.
                            Tus propias asignaturas se califican en <a href="registrar_notas.php" style="color:var(--gold-dark);font-weight:500;">Registrar Notas</a>.
                        </p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>

<!-- ─── Modal Paz y Salvo ─── -->
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
                    Si responde <b>Sí</b> se envía el boletín al padre y al administrador.<br>
                    Si responde <b>No</b> solo se envía al administrador.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('pazSalvoModal');
    if (!el || typeof bootstrap === 'undefined') return;
    var modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
    var periodo = <?= json_encode($periodo_activo) ?>;
    var anio = <?= json_encode((string)$anio_activo) ?>;
    var pendingUrl = '';

    document.querySelectorAll('.btn-gen-boletin').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var id = this.dataset.estudianteId;
            var nombre = this.dataset.estudianteNombre;
            pendingUrl = 'generar_boletin.php?estudiante=' + id + '&periodo=' + encodeURIComponent(periodo) + '&anio=' + encodeURIComponent(anio) + '&template=v2';
            document.getElementById('pazSalvoMsg').innerHTML =
                '¿El estudiante <b>' + nombre + '</b> está a paz y salvo académica y administrativamente?';
            modal.show();
        });
    });

    document.getElementById('pazSalvoSi').addEventListener('click', function () {
        modal.hide();
        if (pendingUrl) window.open(pendingUrl + '&paz_y_salvo=1', '_blank');
    });
    document.getElementById('pazSalvoNo').addEventListener('click', function () {
        modal.hide();
        if (pendingUrl) window.open(pendingUrl + '&paz_y_salvo=0', '_blank');
    });
});
</script>

    <?php include "includes/footer.php"; ?>
