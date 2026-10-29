<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');
require_once __DIR__ . '/../lib/csrf_helper.php';
require_once __DIR__ . '/../lib/alertas_helper.php';
require_once __DIR__ . '/../lib/rank_helper.php';

$padre_id = userId();
$anio_activo = (int) (getConfig('anio_activo') ?? date('Y'));
$periodo_activo = (string) (getConfig('periodo_activo') ?? '1');

/* ── Marcar un aviso como leído (solo los dirigidos a este padre) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida'])) {
    if (validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        marcarAlertaLeida($conexion, (int) ($_POST['alerta_id'] ?? 0), $padre_id, 'padre');
    }
    header('Location: dashboard.php');
    exit;
}

/* ── Hijos vinculados ── */
$hijos = $conexion->prepare("
    SELECT e.id, e.nombre, e.curso_id, c.grado, c.nombre AS curso_nombre
    FROM estudiante_acudientes ea
    JOIN estudiantes e ON e.id = ea.estudiante_id
    LEFT JOIN cursos c ON e.curso_id = c.id
    WHERE ea.padre_id = ?
    ORDER BY e.nombre
");
$hijos->execute([$padre_id]);
$hijos = $hijos->fetchAll(PDO::FETCH_ASSOC);

/* ── Avisos sin leer (los 3 más recientes) ── */
$avisos = $conexion->prepare("
    SELECT id, tipo, titulo, mensaje, created_at FROM alertas
    WHERE (para_usuario_id = ? OR (para_rol = 'padre' AND para_usuario_id IS NULL)) AND leido = FALSE
    ORDER BY created_at DESC
    LIMIT 3
");
$avisos->execute([$padre_id]);
$avisos = $avisos->fetchAll(PDO::FETCH_ASSOC);

/* ── Boletines publicados del año y el resumen del último de cada hijo ── */
$publicados = [];
if ($hijos) {
    $ids = array_column($hijos, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $conexion->prepare("
        SELECT estudiante_id, periodo, publicado_en FROM boletines_pdf
        WHERE estudiante_id IN ($ph) AND year = ? AND publicado = TRUE
        ORDER BY periodo
    ");
    $st->execute(array_merge($ids, [$anio_activo]));
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $publicados[(int) $b['estudiante_id']][(string) $b['periodo']] = $b['publicado_en'];
    }
}
$resumen = [];
foreach ($hijos as $h) {
    $per = array_keys($publicados[(int) $h['id']] ?? []);
    if (!$per) continue;
    $ultimo = (string) end($per);
    $notas = obtenerNotasPonderadas($conexion, (int) $h['id'], $ultimo, $anio_activo);
    $prom = $notas ? calcularDefinitivas($notas)['general'] : null;
    $resumen[(int) $h['id']] = [
        'periodo'  => $ultimo,
        'promedio' => $prom,
        'puesto'   => $notas && $prom !== null ? calcularPuestoCurso($conexion, (int) $h['id'], $ultimo, $anio_activo, (int) $h['curso_id']) : null,
        'fecha'    => $publicados[(int) $h['id']][$ultimo],
    ];
}

/** Clase del chip de desempeño */
function ppChip(?float $nota, int $anio): array
{
    $d = desempenoNota($nota, $anio);
    return [$d, ['Superior' => 'sup', 'Alto' => 'alto', 'Básico' => 'bas', 'Bajo' => 'bajo'][$d]];
}
function ppTipoAviso(string $tipo): string
{
    if (str_starts_with($tipo, 'riesgo')) return 'riesgo';
    if (str_starts_with($tipo, 'boletin')) return 'boletin';
    return 'sistema';
}
function ppFecha(?string $ts): string
{
    if (!$ts) return '';
    $d = new DateTime($ts, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Bogota'));
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return $d->format('j') . ' ' . $meses[(int) $d->format('n') - 1];
}

$hora = (int) date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$mesesL = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaHoy = $dias[(int) date('w')] . ' ' . date('j') . ' de ' . $mesesL[(int) date('n') - 1];

$pageTitle = 'Mis hijos';
$ppActivo = 'inicio';
include 'includes/header.php';
?>

    <section class="pp-saludo">
        <p class="pp-fecha"><?= $fechaHoy ?></p>
        <h1><?= $saludo ?>, <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></h1>
        <p>
            <?php if (count($hijos) === 1): ?>
                Aquí encuentra los boletines de <?= htmlspecialchars($hijos[0]['nombre']) ?> y los avisos del colegio.
            <?php else: ?>
                Aquí encuentra los boletines de sus hijos y los avisos del colegio.
            <?php endif; ?>
        </p>
    </section>

    <?php if ($avisos): ?>
    <section class="pp-sec" aria-labelledby="tAvisos">
        <div class="pp-sec-head">
            <h2 id="tAvisos">Avisos sin leer</h2>
            <a href="avisos.php">Ver todos</a>
        </div>
        <ul class="pp-avisos pp-card">
            <?php foreach ($avisos as $a):
                [$base, $eid] = array_pad(explode(':', (string) $a['tipo'], 2), 2, null); ?>
                <li class="pp-aviso nuevo" data-tipo="<?= ppTipoAviso((string) $a['tipo']) ?>">
                    <span class="pp-aviso-ico" aria-hidden="true">
                        <i class="bi <?= ppTipoAviso((string) $a['tipo']) === 'riesgo' ? 'bi-graph-down-arrow' : (ppTipoAviso((string) $a['tipo']) === 'boletin' ? 'bi-file-earmark-check' : 'bi-info-circle') ?>"></i>
                    </span>
                    <div>
                        <h3><?= htmlspecialchars(trim(preg_replace('/^[\p{So}\p{Sk}\x{FE0F}\x{200D}\s]+/u', '', (string) $a['titulo']))) ?></h3>
                        <p><?= nl2br(htmlspecialchars($a['mensaje'])) ?></p>
                        <div class="pp-aviso-meta">
                            <span><?= ppFecha($a['created_at']) ?></span>
                            <?php if ((int) $eid): ?>
                                <a href="boletin.php?estudiante=<?= (int) $eid ?>">Ver boletín</a>
                            <?php endif; ?>
                            <form method="POST" style="margin:0" data-no-loading>
                                <?= campo_csrf() ?>
                                <input type="hidden" name="alerta_id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" name="marcar_leida">Marcar como leído</button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="pp-sec" aria-labelledby="tHijos">
        <div class="pp-sec-head">
            <h2 id="tHijos"><?= count($hijos) === 1 ? 'Su hijo' : 'Sus hijos' ?></h2>
            <span style="font-size:13px;color:var(--pp-muted)">Año lectivo <?= $anio_activo ?></span>
        </div>

        <?php if (!$hijos): ?>
            <div class="pp-card pp-vacio">
                <div class="ico" aria-hidden="true"><i class="bi bi-people"></i></div>
                <h2>Aún no hay estudiantes vinculados</h2>
                <p>Su cuenta no tiene hijos asociados todavía. Comuníquese con la administración del colegio para vincularlos.</p>
            </div>
        <?php else: ?>
            <div class="pp-hijos">
                <?php foreach ($hijos as $h):
                    $hid = (int) $h['id'];
                    $pubs = $publicados[$hid] ?? [];
                    $r = $resumen[$hid] ?? null;
                    $ini = mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($h['nombre'])), 0, 2))));
                ?>
                <article class="pp-card pp-hijo">
                    <div class="pp-hijo-top">
                        <span class="pp-hijo-ini" aria-hidden="true"><?= htmlspecialchars($ini) ?></span>
                        <div>
                            <h3><?= htmlspecialchars($h['nombre']) ?></h3>
                            <p><?= $h['grado'] ? 'Curso ' . htmlspecialchars($h['grado'] . ' ' . $h['curso_nombre']) : 'Sin curso asignado' ?></p>
                        </div>
                    </div>

                    <div class="pp-periodos" role="list" aria-label="Boletines del año por período">
                        <?php foreach (['1', '2', '3', '4'] as $p):
                            if (isset($pubs[$p])): ?>
                                <a role="listitem" class="pp-per ok" href="boletin.php?estudiante=<?= $hid ?>&amp;periodo=<?= $p ?>"
                                   aria-label="Período <?= $p ?>: boletín publicado. Ver"><b>P<?= $p ?></b>Publicado</a>
                            <?php elseif ($p === $periodo_activo): ?>
                                <span role="listitem" class="pp-per curso" aria-label="Período <?= $p ?>: en curso"><b>P<?= $p ?></b>En curso</span>
                            <?php else: ?>
                                <span role="listitem" class="pp-per" aria-label="Período <?= $p ?>: sin boletín"><b>P<?= $p ?></b>&mdash;</span>
                            <?php endif;
                        endforeach; ?>
                    </div>

                    <?php if ($r): ?>
                        <div class="pp-ultimo">
                            <?php if ($r['promedio'] !== null): [$dNom, $dCls] = ppChip((float) $r['promedio'], $anio_activo); ?>
                                <span class="pp-prom"><?= number_format((float) $r['promedio'], 1) ?></span>
                            <?php endif; ?>
                            <div class="pp-ultimo-txt">
                                <small>Último boletín · período <?= htmlspecialchars($r['periodo']) ?><?= $r['fecha'] ? ' · ' . ppFecha($r['fecha']) : '' ?></small>
                                <div class="linea">
                                    <?php if ($r['promedio'] !== null): ?>
                                        <span class="pp-chip <?= $dCls ?>"><?= $dNom ?></span>
                                    <?php endif; ?>
                                    <?php if ($r['puesto']): ?>
                                        <span>Puesto <?= htmlspecialchars(str_replace('/', ' de ', $r['puesto'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <a class="pp-btn" href="boletin.php?estudiante=<?= $hid ?>">Ver boletín <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    <?php else: ?>
                        <p class="pp-vacio-txt">Todavía no hay boletines publicados este año. Le avisaremos aquí cuando el colegio publique el primero.</p>
                        <a class="pp-btn sec" href="boletin.php?estudiante=<?= $hid ?>">Ver detalle</a>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="pp-ayuda" aria-label="Contacto del colegio">
        <p><strong>¿Tiene dudas sobre un boletín?</strong> La administración del colegio le ayuda.</p>
        <div>
            <a href="tel:+573216548235"><i class="bi bi-telephone" aria-hidden="true"></i> 321 654 8235</a>
            <a href="mailto:gimnasiocastilloamericano@gmail.com"><i class="bi bi-envelope" aria-hidden="true"></i> Escribir</a>
        </div>
    </aside>

<?php include 'includes/footer.php'; ?>
