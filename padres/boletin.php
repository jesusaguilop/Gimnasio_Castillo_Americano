<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');

$padre_id = userId();
$anio_activo = (int)(getConfig('anio_activo') ?? date('Y'));
$estudiante_id = $_GET["estudiante"] ?? null;

/* ── Verify this student belongs to this parent ── */
$estudiante = null;
if ($estudiante_id) {
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
          AND EXISTS (SELECT 1 FROM estudiante_acudientes ea
                      WHERE ea.estudiante_id = e.id AND ea.padre_id = ?)
    ");
    $stmt->execute([$estudiante_id, $padre_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$estudiante) {
    header("Location: dashboard.php");
    exit;
}

/* ── Endpoint en vivo: períodos con boletín PUBLICADO por el admin ── */
if (isset($_GET["poll"])) {
    header("Content-Type: application/json");
    $q = $conexion->prepare("SELECT id, periodo FROM boletines_pdf WHERE estudiante_id = ? AND year = ? AND publicado = TRUE ORDER BY periodo");
    $q->execute([$estudiante_id, $anio_activo]);
    echo json_encode(["boletines" => $q->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

/* ── Boletines PUBLICADOS por el admin ──
 * Es la compuerta: el padre no ve notas de un período hasta que el colegio
 * publica su boletín. Antes las notas salían de `notas` directamente y el
 * padre veía calificaciones a medio digitar. */
$boletines_pdf = $conexion->prepare("
    SELECT id, periodo, year
    FROM boletines_pdf
    WHERE estudiante_id = ? AND year = ? AND publicado = TRUE
    ORDER BY periodo
");
$boletines_pdf->execute([$estudiante_id, $anio_activo]);
$boletines_pdf = $boletines_pdf->fetchAll(PDO::FETCH_ASSOC);
$boletines_por_periodo = [];
foreach ($boletines_pdf as $bp) {
    $boletines_por_periodo[(string) $bp['periodo']] = $bp;
}
$periodos = array_keys($boletines_por_periodo);

// Por defecto, el período publicado más reciente
$selected_periodo = isset($_GET["periodo"]) ? (string) $_GET["periodo"] : (end($periodos) ?: null);
if ($selected_periodo !== null && !isset($boletines_por_periodo[$selected_periodo])) {
    $selected_periodo = end($periodos) ?: null;
}

/* ── Notas del período seleccionado (solo si está publicado) ── */
$notas = [];
$promedio = null;
$puesto = null;
if ($selected_periodo !== null) {
    // Notas + área + porcentaje del grado en UNA consulta; promedio y puesto con el
    // MISMO cálculo que el boletín (rank_helper). Antes eran 6 consultas.
    require_once __DIR__ . '/../lib/rank_helper.php';
    $notas = obtenerNotasPonderadas($conexion, $estudiante_id, $selected_periodo, $anio_activo);

    if (count($notas) > 0) {
        $promedio = calcularDefinitivas($notas)['general'];   // null si solo hay Comportamiento
        $puesto = calcularPuestoCurso($conexion, $estudiante_id, $selected_periodo, $anio_activo, (int) $notas[0]['curso_id']);
    }
}

/* Misma escala que el boletín: Superior ≥90, Alto 80-89, Básico 60-79, Bajo <60 */
function gradeClass($nota) {
    if ($nota >= 80) return 'high';
    if ($nota >= 60) return 'medium';
    return 'low';
}

function desempeno($nota) {
    if ($nota >= 90) return 'Superior';
    if ($nota >= 80) return 'Alto';
    if ($nota >= 60) return 'Básico';
    return 'Bajo';
}

$pageTitle = $estudiante['nombre'];
include "includes/header.php";
?>

<body>

<header class="app-header">
    <a href="dashboard.php" class="app-header-brand">
        <i class="bi bi-arrow-left" style="color:var(--gold);font-size:20px;margin-right:4px;" aria-hidden="true"></i>
        <span>Boletín</span><span class="visually-hidden"> — volver a mis hijos</span>
    </a>
    <div class="header-actions">
        <button type="button" class="btn-header" id="dmToggle" aria-label="Cambiar modo oscuro" aria-pressed="false" onclick="var on=document.body.classList.toggle('dark-mode');document.documentElement.setAttribute('data-bs-theme',on?'dark':'');localStorage.setItem('gca-dark-mode',on);this.setAttribute('aria-pressed',on);this.querySelector('i').className=on?'bi bi-sun':'bi bi-moon-stars';">
            <i class="bi bi-moon-stars" aria-hidden="true"></i>
        </button>
        <a href="../auth/logout.php" class="btn-header logout">
            <i class="bi bi-box-arrow-right"></i> Salir
        </a>
    </div>
</header>

<main class="app-content">

    <div class="student-header">
        <div class="big-avatar">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($estudiante['nombre'], 0, 2))) ?>
        </div>
        <h4><?= htmlspecialchars($estudiante['nombre']) ?></h4>
        <p>
            <i class="bi bi-book-fill" style="color:var(--gold-dark);font-size:12px;"></i>
            Curso <?= htmlspecialchars($estudiante['grado'] . ' ' . $estudiante['curso_nombre']) ?>
        </p>
    </div>

    <?php if (count($periodos) > 0): ?>
        <nav class="period-tabs mb-4" aria-label="Períodos publicados">
            <?php foreach ($periodos as $p): ?>
                <a href="boletin.php?estudiante=<?= (int) $estudiante_id ?>&periodo=<?= urlencode($p) ?>"
                   class="period-tab <?= $p === $selected_periodo ? 'active' : '' ?>"
                   <?= $p === $selected_periodo ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-calendar3" aria-hidden="true"></i> Período <?= htmlspecialchars($p) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($selected_periodo === null): ?>
        <div class="app-card">
            <div class="empty-state">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                <h2 class="h5">Aún no hay boletines publicados</h2>
                <p>Cuando el colegio publique el boletín de un período, aparecerá aquí con sus calificaciones.</p>
            </div>
        </div>

    <?php elseif (count($notas) === 0): ?>
        <div class="app-card">
            <div class="empty-state">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <h2 class="h5">Sin notas en el período <?= htmlspecialchars($selected_periodo) ?></h2>
                <p>El boletín está publicado, pero no tiene calificaciones registradas. Puedes verlo abajo.</p>
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
                        <div class="grade-badge <?= gradeClass($n['nota']) ?>" title="Desempeño <?= desempeno($n['nota']) ?>">
                            <?= (int) $n['nota'] ?><span class="visually-hidden"> — desempeño <?= desempeno($n['nota']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($promedio !== null): ?>
        <div class="app-card">
            <div class="app-card-body" style="text-align:center;">
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">
                    <i class="bi bi-star-fill" style="color:var(--gold);"></i> Promedio General
                </p>
                <div class="avg-pill <?= gradeClass($promedio) ?>">
                    <?= number_format((float) $promedio, 1) ?>
                </div>
                <p class="desempeno-msg <?= gradeClass($promedio) ?>">
                    <?php if ($promedio >= 80): ?>
                        <i class="bi bi-emoji-smile-fill" aria-hidden="true"></i> Desempeño <?= desempeno($promedio) ?>. ¡Muy bien!
                    <?php elseif ($promedio >= 60): ?>
                        <i class="bi bi-emoji-neutral-fill" aria-hidden="true"></i> Desempeño Básico. Puede mejorar.
                    <?php else: ?>
                        <i class="bi bi-emoji-frown-fill" aria-hidden="true"></i> Desempeño Bajo. Necesita refuerzo.
                    <?php endif; ?>
                </p>
                <?php if ($puesto): ?>
                    <p style="font-size:13px;color:var(--gold-dark);margin-top:10px;margin-bottom:0;font-weight:700;">
                        <i class="bi bi-trophy-fill" aria-hidden="true"></i> Puesto en el curso: <?= htmlspecialchars(str_replace('/', ' de ', $puesto)) ?>
                    </p>
                <?php endif; ?>
                <p style="font-size:11px;color:var(--text-muted);margin-top:8px;margin-bottom:0;">
                    Escala 0 a 100 · Superior 90+ · Alto 80-89 · Básico 60-79 · Bajo menos de 60
                </p>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($selected_periodo && isset($boletines_por_periodo[$selected_periodo])):
        // Un solo origen para la vista previa y la descarga: boletin_descargar.php
        // valida que el boletín sea de un hijo de este padre Y que el admin ya lo
        // haya publicado. Si no, responde 403 y aquí no se ve nada.
        $urlBoletin = '../boletin_descargar.php?id=' . (int) $boletines_por_periodo[$selected_periodo]['id'];
    ?>
        <div class="boletin-preview">
            <div class="boletin-preview-head">
                <span>
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    Boletín del período <?= htmlspecialchars($selected_periodo) ?>
                </span>
                <div class="boletin-preview-acciones">
                    <a href="<?= $urlBoletin ?>" target="_blank" rel="noopener">
                        <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ver en pantalla completa
                    </a>
                    <a href="<?= $urlBoletin ?>&amp;descargar=1" class="btn-gca-pdf">
                        <i class="bi bi-download" aria-hidden="true"></i> Descargar
                    </a>
                </div>
            </div>

            <iframe class="boletin-visor"
                    src="<?= $urlBoletin ?>#view=FitH"
                    title="Boletín del período <?= htmlspecialchars($selected_periodo) ?>"
                    loading="lazy">
            </iframe>

            <!-- Algunos navegadores móviles no muestran PDF dentro de un marco -->
            <p class="boletin-preview-nota">
                <i class="bi bi-info-circle"></i>
                ¿No se ve el boletín aquí? Ábrelo con
                <a href="<?= $urlBoletin ?>" target="_blank" rel="noopener">Pantalla completa</a>.
            </p>
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

<div id="nuevoBoletin" class="nuevo-boletin" role="status" hidden>
    Hay cambios en los boletines publicados.
    <button type="button" onclick="location.reload()">Actualizar</button>
</div>

<script>
(function () {
    // Si el admin publica u oculta un boletín se avisa, en vez de recargar
    // la página mientras el padre está leyendo.
    var initial = <?= json_encode(array_map('strval', array_keys($boletines_por_periodo))) ?>;
    var pollUrl = 'boletin.php?estudiante=<?= (int) $estudiante_id ?>&poll=1';
    var baseKey = initial.slice().sort().join('|');
    var aviso = document.getElementById('nuevoBoletin');
    setInterval(function () {
        if (document.hidden || !aviso.hidden) return;
        fetch(pollUrl, { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var now = (d.boletines || []).map(function (b) { return String(b.periodo); }).sort().join('|');
                if (now !== baseKey) aviso.hidden = false;
            })
            .catch(function () {});
    }, 15000);
})();
</script>

<?php include "includes/footer.php"; ?>
