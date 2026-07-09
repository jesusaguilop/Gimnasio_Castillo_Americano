<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');

$padre_id = userId();
$estudiante_id = $_GET["estudiante"] ?? null;

/* ── Verify this student belongs to this parent ── */
$estudiante = null;
if ($estudiante_id) {
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ? AND e.padre_id = ?
    ");
    $stmt->execute([$estudiante_id, $padre_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$estudiante) {
    header("Location: dashboard.php");
    exit;
}

/* ── Get available periods ── */
$periodos = $conexion->prepare("
    SELECT DISTINCT n.periodo
    FROM notas n
    WHERE n.estudiante_id = ?
    ORDER BY
        CASE n.periodo
            WHEN '1er Periodo' THEN 1 WHEN '2do Periodo' THEN 2 WHEN '3er Periodo' THEN 3 WHEN '4to Periodo' THEN 4
            ELSE 5
        END
");
$periodos->execute([$estudiante_id]);
$periodos = $periodos->fetchAll(PDO::FETCH_COLUMN);

$selected_periodo = $_GET["periodo"] ?? ($periodos[0] ?? null);

/* ── Get grades for selected period ── */
$notas = [];
$promedio = null;
if ($selected_periodo) {
    $stmt = $conexion->prepare("
        SELECT a.nombre AS asignatura, n.nota
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        WHERE n.estudiante_id = ? AND n.periodo = ?
        ORDER BY a.nombre
    ");
    $stmt->execute([$estudiante_id, $selected_periodo]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($notas) > 0) {
        $sum = array_sum(array_column($notas, 'nota'));
        $promedio = round($sum / count($notas), 1);
    }
}

/* ── Check available PDF boletines ── */
$boletines_pdf = $conexion->prepare("
    SELECT periodo, year, ruta_pdf
    FROM boletines_pdf
    WHERE estudiante_id = ?
    ORDER BY year, periodo
");
$boletines_pdf->execute([$estudiante_id]);
$boletines_pdf = $boletines_pdf->fetchAll(PDO::FETCH_ASSOC);
$boletines_por_periodo = [];
foreach ($boletines_pdf as $bp) {
    $boletines_por_periodo[$bp['periodo']] = $bp;
}

function gradeClass($nota) {
    if ($nota >= 60) return 'high';
    if ($nota >= 40) return 'medium';
    return 'low';
}

$pageTitle = $estudiante['nombre'];
include "includes/header.php";
?>

<body>

<header class="app-header">
    <a href="dashboard.php" class="app-header-brand">
        <i class="bi bi-arrow-left" style="color:var(--gold);font-size:20px;margin-right:4px;"></i>
        <span>Boletín</span>
    </a>
    <div class="header-actions">
        <a href="#" class="btn-header" id="dmToggle" title="Modo oscuro" onclick="document.body.classList.toggle('dark-mode');document.documentElement.setAttribute('data-bs-theme',document.body.classList.contains('dark-mode')?'dark':'');localStorage.setItem('gca-dark-mode',document.body.classList.contains('dark-mode'));this.querySelector('i').className=document.body.classList.contains('dark-mode')?'bi bi-sun':'bi bi-moon-stars';">
            <i class="bi bi-moon-stars"></i>
        </a>
        <a href="../auth/logout.php" class="btn-header logout">
            <i class="bi bi-box-arrow-right"></i> Salir
        </a>
    </div>
</header>

<main class="app-content">

    <div class="student-header">
        <div class="big-avatar">
            <?= strtoupper(substr($estudiante['nombre'], 0, 2)) ?>
        </div>
        <h4><?= htmlspecialchars($estudiante['nombre']) ?></h4>
        <p>
            <i class="bi bi-book-fill" style="color:var(--gold-dark);font-size:12px;"></i>
            Curso <?= htmlspecialchars($estudiante['grado'] . ' ' . $estudiante['curso_nombre']) ?>
        </p>
    </div>

    <?php if (count($periodos) > 0): ?>
        <div class="period-tabs mb-4">
            <?php foreach ($periodos as $p): ?>
                <a href="boletin.php?estudiante=<?= $estudiante_id ?>&periodo=<?= urlencode($p) ?>"
                   class="period-tab <?= $p === $selected_periodo ? 'active' : '' ?>">
                    <i class="bi bi-calendar3"></i> <?= htmlspecialchars($p) ?>
                </a>
                <?php if (isset($boletines_por_periodo[$p])): ?>
                    <a href="../<?= $boletines_por_periodo[$p]['ruta_pdf'] ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:3px;font-size:10px;color:#2e7d32;background:#e8f5e9;padding:3px 8px;border-radius:20px;text-decoration:none;font-weight:600;white-space:nowrap;">
                        <i class="bi bi-file-pdf-fill"></i> PDF
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$selected_periodo): ?>
        <div class="app-card">
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <h5>Sin calificaciones</h5>
                <p>Este estudiante aún no tiene notas registradas.</p>
            </div>
        </div>

    <?php elseif (count($notas) === 0): ?>
        <div class="app-card">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Sin notas en <?= htmlspecialchars($selected_periodo) ?></h5>
                <p>Las calificaciones de este período aún no han sido publicadas.</p>
            </div>
        </div>

    <?php else: ?>
        <div class="app-card mb-4">
            <div class="app-card-header">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:600;font-size:14px;color:var(--text-primary);">
                        <i class="bi bi-journal-text" style="color:var(--gold);margin-right:6px;"></i>
                        Calificaciones
                    </span>
                    <span style="font-size:12px;color:var(--text-muted);">
                        <?= count($notas) ?> materia(s)
                    </span>
                </div>
            </div>
            <div class="app-card-body" style="padding:0;">
                <?php foreach ($notas as $n): ?>
                    <div class="grade-card">
                        <span class="subject-name"><?= htmlspecialchars($n['asignatura']) ?></span>
                        <div class="grade-badge <?= gradeClass($n['nota']) ?>">
                            <?= (int) $n['nota'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-body" style="text-align:center;">
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">
                    <i class="bi bi-star-fill" style="color:var(--gold);"></i> Promedio General
                </p>
                <div class="avg-pill <?= gradeClass($promedio) ?>">
                    <?= $promedio ?>
                </div>
                <?php if ($promedio >= 60): ?>
                    <p style="font-size:13px;color:#198754;margin-top:10px;font-weight:500;">
                        <i class="bi bi-emoji-smile-fill"></i> ¡Excelente rendimiento!
                    </p>
                <?php elseif ($promedio >= 40): ?>
                    <p style="font-size:13px;color:#997404;margin-top:10px;font-weight:500;">
                        <i class="bi bi-emoji-neutral-fill"></i> Puedes mejorar
                    </p>
                <?php else: ?>
                    <p style="font-size:13px;color:#dc3545;margin-top:10px;font-weight:500;">
                        <i class="bi bi-emoji-frown-fill"></i> Necesita refuerzo
                    </p>
                <?php endif; ?>
                <?php
                require_once __DIR__ . '/../lib/rank_helper.php';
                $puesto = calcularPuestoCurso($conexion, $estudiante_id, $selected_periodo);
                if ($puesto):
                ?>
                    <p style="font-size:13px;color:var(--gold-dark);margin-top:10px;margin-bottom:0;font-weight:700;">
                        <i class="bi bi-trophy-fill"></i> Puesto en el curso: <?= $puesto ?>
                    </p>
                <?php endif; ?>
                <p style="font-size:11px;color:var(--text-muted);margin-top:8px;margin-bottom:0;">
                    Escala de calificación: 0 a 100
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($selected_periodo && isset($boletines_por_periodo[$selected_periodo])): ?>
        <div style="text-align:center;margin-top:16px;">
            <a href="../<?= $boletines_por_periodo[$selected_periodo]['ruta_pdf'] ?>" target="_blank"
               class="btn-gca-pdf">
                <i class="bi bi-file-pdf-fill"></i> Descargar Boletín PDF
            </a>
        </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
        <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);font-size:13px;text-decoration:none;padding:8px 16px;border:1px solid var(--border);border-radius:50px;transition:all.2s;">
            <i class="bi bi-arrow-left"></i> Volver a mis hijos
        </a>
    </div>

    <div style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid var(--border);">
        <p style="font-size:11px;color:var(--text-muted);margin:0;">
            <i class="bi bi-shield-check me-1"></i>
            Gimnasio Castillo Americano &middot; Boletín Académico
        </p>
    </div>

</main>

<?php include "includes/footer.php"; ?>
