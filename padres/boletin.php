<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');
require_once __DIR__ . '/../lib/rank_helper.php';

$padre_id = userId();
$anio_activo = (int) (getConfig('anio_activo') ?? date('Y'));
$periodo_activo = (string) (getConfig('periodo_activo') ?? '1');
$estudiante_id = (int) ($_GET['estudiante'] ?? 0);

/* ── El estudiante tiene que ser de este acudiente ── */
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
    header('Location: dashboard.php');
    exit;
}

/* ── Endpoint en vivo: períodos con boletín PUBLICADO por el admin ── */
if (isset($_GET['poll'])) {
    header('Content-Type: application/json');
    $q = $conexion->prepare("SELECT id, periodo FROM boletines_pdf WHERE estudiante_id = ? AND year = ? AND publicado = TRUE ORDER BY periodo");
    $q->execute([$estudiante_id, $anio_activo]);
    echo json_encode(['boletines' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

/* ── Boletines PUBLICADOS por el admin ──
 * Es la compuerta: el padre no ve notas de un período hasta que el colegio
 * publica su boletín (nada de calificaciones a medio digitar). */
$st = $conexion->prepare("
    SELECT id, periodo, publicado_en FROM boletines_pdf
    WHERE estudiante_id = ? AND year = ? AND publicado = TRUE
    ORDER BY periodo
");
$st->execute([$estudiante_id, $anio_activo]);
$boletines_por_periodo = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $bp) {
    $boletines_por_periodo[(string) $bp['periodo']] = $bp;
}
// Las claves "1".."4" PHP las vuelve enteros: se comparan como texto
$periodos = array_map('strval', array_keys($boletines_por_periodo));

// Por defecto, el período publicado más reciente
$selected_periodo = isset($_GET['periodo']) ? (string) $_GET['periodo'] : (end($periodos) ?: null);
if ($selected_periodo !== null && !isset($boletines_por_periodo[$selected_periodo])) {
    $selected_periodo = end($periodos) ?: null;
}

/* ── Notas del período (mismo cálculo que el boletín: rank_helper) ── */
$notas = [];
$promedio = null;
$puesto = null;
$definitivas = [];
if ($selected_periodo !== null) {
    $notas = obtenerNotasPonderadas($conexion, $estudiante_id, $selected_periodo, $anio_activo);
    if ($notas) {
        $calc = calcularDefinitivas($notas);
        $promedio = $calc['general'];            // null si solo hay Comportamiento
        $definitivas = $calc['areas'];
        $puesto = calcularPuestoCurso($conexion, $estudiante_id, $selected_periodo, $anio_activo, (int) $notas[0]['curso_id']);
    }
}
// Asignaturas agrupadas por área, en el orden del boletín
$porArea = [];
foreach ($notas as $n) {
    $porArea[(string) $n['area']][] = $n;
}

$escala = escalaDesempeno($anio_activo);
function ppDes($nota): array
{
    global $anio_activo;
    $d = desempenoNota($nota, $anio_activo);
    return [$d, ['Superior' => 'sup', 'Alto' => 'alto', 'Básico' => 'bas', 'Bajo' => 'bajo'][$d]];
}
function ppFechaLarga(?string $ts): string
{
    if (!$ts) return '';
    $d = new DateTime($ts, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Bogota'));
    $m = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $d->format('j') . ' de ' . $m[(int) $d->format('n') - 1];
}

$ini = mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($estudiante['nombre'])), 0, 2))));
$pageTitle = $estudiante['nombre'];
$ppActivo = 'inicio';
include 'includes/header.php';
?>

    <a href="dashboard.php" class="pp-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Mis hijos</a>

    <header class="pp-est">
        <span class="pp-hijo-ini" aria-hidden="true"><?= htmlspecialchars($ini) ?></span>
        <div>
            <h1><?= htmlspecialchars($estudiante['nombre']) ?></h1>
            <p><?= $estudiante['grado'] ? 'Curso ' . htmlspecialchars($estudiante['grado'] . ' ' . $estudiante['curso_nombre']) . ' · ' : '' ?>Año lectivo <?= $anio_activo ?></p>
        </div>
    </header>

    <nav class="pp-seg" aria-label="Períodos">
        <?php foreach (['1', '2', '3', '4'] as $p):
            if (isset($boletines_por_periodo[$p])): ?>
                <a href="boletin.php?estudiante=<?= $estudiante_id ?>&amp;periodo=<?= $p ?>"<?= $p === $selected_periodo ? ' aria-current="page"' : '' ?>>
                    Período <?= $p ?><small>Publicado</small>
                </a>
            <?php else: ?>
                <span aria-disabled="true">Período <?= $p ?><small><?= $p === $periodo_activo ? 'En curso' : 'Sin boletín' ?></small></span>
            <?php endif;
        endforeach; ?>
    </nav>

    <?php if ($selected_periodo === null): ?>
        <div class="pp-card pp-vacio">
            <div class="ico" aria-hidden="true"><i class="bi bi-hourglass-split"></i></div>
            <h2>Aún no hay boletines publicados</h2>
            <p>Cuando el colegio publique el boletín de un período, aparecerá aquí con las calificaciones y el PDF oficial. Le llegará un aviso.</p>
        </div>

    <?php else:
        $bol = $boletines_por_periodo[$selected_periodo];
        // Vista previa y descarga por boletin_descargar.php, que valida que sea un
        // hijo de este acudiente y que el boletín esté publicado.
        $urlBoletin = '../boletin_descargar.php?id=' . (int) $bol['id'];
    ?>

        <?php if (!$notas): ?>
            <div class="pp-card pp-vacio">
                <div class="ico" aria-hidden="true"><i class="bi bi-inbox"></i></div>
                <h2>Sin calificaciones en el período <?= htmlspecialchars($selected_periodo) ?></h2>
                <p>El boletín está publicado, pero no tiene calificaciones registradas. Puede verlo en el PDF de abajo.</p>
            </div>
        <?php else: ?>
            <section aria-labelledby="tResumen">
                <h2 id="tResumen" class="visually-hidden">Resumen del período <?= htmlspecialchars($selected_periodo) ?></h2>
                <div class="pp-card pp-resumen">
                    <div>
                        <small>Promedio del período</small>
                        <?php if ($promedio !== null): [$dNom, $dCls] = ppDes($promedio); ?>
                            <div class="grande"><?= number_format((float) $promedio, 1) ?></div>
                            <span class="pp-chip <?= $dCls ?>">Desempeño <?= $dNom ?></span>
                        <?php else: ?>
                            <div class="grande">&mdash;</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <small>Puesto en el curso</small>
                        <div class="medio"><?= $puesto ? htmlspecialchars(explode('/', $puesto)[0]) . ' <span>de ' . htmlspecialchars(explode('/', $puesto)[1] ?? '') . '</span>' : '&mdash;' ?></div>
                    </div>
                    <div>
                        <small>Asignaturas</small>
                        <div class="medio"><?= count($notas) ?> <span>en <?= count($porArea) ?> áreas</span></div>
                    </div>
                </div>
                <p class="pp-escala">Escala 0 a 100 · <?= htmlspecialchars(escalaTexto($anio_activo)) ?>. La línea dorada de cada barra marca dónde empieza Básico.</p>
            </section>

            <section class="pp-sec" aria-labelledby="tAreas">
                <div class="pp-sec-head">
                    <h2 id="tAreas">Por áreas</h2>
                    <button type="button" class="enlace" id="ppAbrirTodo">Abrir todas</button>
                </div>
                <div class="pp-card pp-areas">
                    <?php foreach ($porArea as $area => $asigs):
                        $def = $definitivas[$area] ?? null;
                        [$aNom, $aCls] = $def !== null ? ppDes($def) : ['', '']; ?>
                        <details class="pp-area">
                            <summary>
                                <span class="pp-area-nom">
                                    <strong><?= htmlspecialchars($area) ?></strong>
                                    <?php if ($def !== null): ?>
                                        <span class="pp-barra <?= $aCls === 'bajo' ? 'bajo' : '' ?>" aria-hidden="true">
                                            <i style="width:<?= max(0, min(100, (float) $def)) ?>%"></i>
                                            <b style="left:<?= (int) $escala['basico'] ?>%"></b>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($def !== null): ?>
                                    <span class="pp-chip <?= $aCls ?>"><?= $aNom ?></span>
                                    <span class="pp-area-nota"><?= number_format((float) $def, 1) ?><span class="visually-hidden"> de definitiva</span></span>
                                <?php else: ?>
                                    <span></span><span class="pp-area-nota">&mdash;</span>
                                <?php endif; ?>
                            </summary>
                            <ul class="pp-asigs">
                                <?php foreach ($asigs as $n): [$nNom, $nCls] = ppDes($n['nota']); ?>
                                    <li class="pp-asig">
                                        <span class="pp-asig-nom">
                                            <?= htmlspecialchars($n['asignatura']) ?>
                                            <?php if ($n['examen'] !== null && $n['examen'] !== ''): ?>
                                                <small>Examen final: <?= (int) $n['examen'] ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <span class="pp-asig-nota">
                                            <span class="pp-chip <?= $nCls ?>"><?= $nNom ?></span>
                                            <strong><?= (int) $n['nota'] ?></strong>
                                        </span>
                                        <?php if (trim((string) $n['logro']) !== ''): ?>
                                            <p class="pp-logro"><?= nl2br(htmlspecialchars($n['logro'])) ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="pp-sec">
            <div class="pp-card pp-pdf">
                <div class="pp-pdf-head">
                    <div>
                        <h2>Boletín oficial · período <?= htmlspecialchars($selected_periodo) ?></h2>
                        <p>Documento oficial del colegio<?= $bol['publicado_en'] ? ' · publicado el ' . ppFechaLarga($bol['publicado_en']) : '' ?>.</p>
                    </div>
                    <div class="pp-pdf-acc">
                        <a class="pp-btn sec" href="<?= $urlBoletin ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir</a>
                        <a class="pp-btn" href="<?= $urlBoletin ?>&amp;descargar=1"><i class="bi bi-download" aria-hidden="true"></i> Descargar</a>
                    </div>
                </div>
                <div class="pp-visor" id="ppVisor">
                    <div class="pp-visor-sk" aria-hidden="true">
                        <div class="cab"><span class="gca-sk gca-sk-circ"></span><span style="flex:1"><span class="gca-sk gca-sk-titulo"></span><span class="gca-sk gca-sk-linea m"></span></span></div>
                        <span class="gca-sk fila"></span><span class="gca-sk fila"></span><span class="gca-sk fila"></span>
                        <span class="gca-sk gca-sk-bloque"></span><span class="gca-sk fila"></span><span class="gca-sk fila"></span>
                    </div>
                    <iframe src="<?= $urlBoletin ?>#view=FitH" title="Boletín del período <?= htmlspecialchars($selected_periodo) ?> en PDF" loading="lazy"
                            onload="this.parentNode.classList.add('listo')"></iframe>
                </div>
            </div>
        </section>
    <?php endif; ?>

<script>
(function () {
    var abrir = document.getElementById('ppAbrirTodo');
    if (abrir) {
        abrir.addEventListener('click', function () {
            var areas = document.querySelectorAll('.pp-area');
            var todas = Array.prototype.every.call(areas, function (d) { return d.open; });
            areas.forEach(function (d) { d.open = !todas; });
            abrir.textContent = todas ? 'Abrir todas' : 'Cerrar todas';
        });
    }
    // Si el colegio publica u oculta un boletín mientras se lee, se avisa
    // (sin recargar la página por sorpresa)
    var base = <?= json_encode(array_map('strval', array_keys($boletines_por_periodo))) ?>.sort().join('|');
    var url = 'boletin.php?estudiante=<?= $estudiante_id ?>&poll=1', avisado = false;
    setInterval(function () {
        if (document.hidden || avisado) return;
        fetch(url, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
            var ahora = (d.boletines || []).map(function (b) { return String(b.periodo); }).sort().join('|');
            if (ahora !== base) {
                avisado = true;
                GCA.toast('Hay cambios en los boletines publicados de este estudiante.', {
                    tipo: 'aviso', titulo: 'Boletines actualizados', ms: 0,
                    enlace: { texto: 'Actualizar', url: location.href }
                });
            }
        }).catch(function () {});
    }, 15000);
})();
</script>

<?php include 'includes/footer.php'; ?>
