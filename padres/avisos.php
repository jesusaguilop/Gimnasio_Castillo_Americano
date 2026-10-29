<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');
require_once __DIR__ . '/../lib/csrf_helper.php';
require_once __DIR__ . '/../lib/alertas_helper.php';

$padre_id = userId();
$visible = "(para_usuario_id = ? OR (para_rol = 'padre' AND para_usuario_id IS NULL))";

/* ── Marcar uno o todos como leídos ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    if (isset($_POST['marcar_todas'])) {
        $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE leido = FALSE AND $visible")->execute([$padre_id]);
    } elseif (isset($_POST['alerta_id'])) {
        marcarAlertaLeida($conexion, (int) $_POST['alerta_id'], $padre_id, 'padre');
    }
    header('Location: avisos.php' . (($_POST['ver'] ?? '') === 'todos' ? '?ver=todos' : ''));
    exit;
}

$verTodos = ($_GET['ver'] ?? '') === 'todos';
$st = $conexion->prepare("
    SELECT id, tipo, titulo, mensaje, leido, created_at FROM alertas
    WHERE $visible" . ($verTodos ? '' : ' AND leido = FALSE') . "
    ORDER BY created_at DESC
    LIMIT 60
");
$st->execute([$padre_id]);
$avisos = $st->fetchAll(PDO::FETCH_ASSOC);

$c = $conexion->prepare("SELECT COUNT(*) FROM alertas WHERE leido = FALSE AND $visible");
$c->execute([$padre_id]);
$sinLeer = (int) $c->fetchColumn();

function ppTipo(string $t): string
{
    if (str_starts_with($t, 'riesgo')) return 'riesgo';
    if (str_starts_with($t, 'boletin')) return 'boletin';
    return 'sistema';
}
function ppCuando(string $ts): string
{
    $d = new DateTime($ts, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Bogota'));
    $hoy = new DateTime('today', new DateTimeZone('America/Bogota'));
    $m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    if ($d >= $hoy) return 'Hoy, ' . $d->format('g:i a');
    if ($d >= (clone $hoy)->modify('-1 day')) return 'Ayer, ' . $d->format('g:i a');
    return $d->format('j') . ' ' . $m[(int) $d->format('n') - 1] . ($d->format('Y') !== $hoy->format('Y') ? ' ' . $d->format('Y') : '');
}

$pageTitle = 'Avisos';
$ppActivo = 'avisos';
include 'includes/header.php';
?>

    <section class="pp-saludo">
        <p class="pp-fecha">Centro de avisos</p>
        <h1>Avisos del colegio</h1>
        <p>Aquí le avisamos cuando se publica un boletín o cuando hay algo importante sobre sus hijos.</p>
    </section>

    <div class="pp-sec-head" style="margin-top:8px">
        <nav class="pp-filtros" aria-label="Filtrar avisos">
            <a href="avisos.php"<?= $verTodos ? '' : ' aria-current="page"' ?>>Sin leer<?= $sinLeer ? " ($sinLeer)" : '' ?></a>
            <a href="avisos.php?ver=todos"<?= $verTodos ? ' aria-current="page"' : '' ?>>Todos</a>
        </nav>
        <?php if ($sinLeer): ?>
            <form method="POST" style="margin:0" data-no-loading>
                <?= campo_csrf() ?>
                <input type="hidden" name="ver" value="<?= $verTodos ? 'todos' : '' ?>">
                <button type="submit" name="marcar_todas" class="enlace">Marcar todos como leídos</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$avisos): ?>
        <div class="pp-card pp-vacio">
            <div class="ico" aria-hidden="true"><i class="bi bi-bell"></i></div>
            <h2><?= $verTodos ? 'No hay avisos todavía' : 'Está al día' ?></h2>
            <p><?= $verTodos ? 'Cuando el colegio publique un boletín o necesite informarle algo, lo verá aquí.' : 'No tiene avisos sin leer. Puede revisar los anteriores en «Todos».' ?></p>
        </div>
    <?php else: ?>
        <ul class="pp-avisos pp-card">
            <?php foreach ($avisos as $a):
                $tipo = ppTipo((string) $a['tipo']);
                [, $eid] = array_pad(explode(':', (string) $a['tipo'], 2), 2, null); ?>
                <li class="pp-aviso<?= $a['leido'] ? '' : ' nuevo' ?>" data-tipo="<?= $tipo ?>">
                    <span class="pp-aviso-ico" aria-hidden="true">
                        <i class="bi <?= $tipo === 'riesgo' ? 'bi-graph-down-arrow' : ($tipo === 'boletin' ? 'bi-file-earmark-check' : 'bi-info-circle') ?>"></i>
                    </span>
                    <div>
                        <h3><?= htmlspecialchars(trim(preg_replace('/^[\p{So}\p{Sk}\x{FE0F}\x{200D}\s]+/u', '', (string) $a['titulo']))) ?><?= $a['leido'] ? '' : '<span class="visually-hidden"> (sin leer)</span>' ?></h3>
                        <p><?= nl2br(htmlspecialchars($a['mensaje'])) ?></p>
                        <div class="pp-aviso-meta">
                            <time datetime="<?= htmlspecialchars((new DateTime($a['created_at'], new DateTimeZone('UTC')))->format('c')) ?>"><?= ppCuando($a['created_at']) ?></time>
                            <?php if ((int) $eid): ?>
                                <a href="boletin.php?estudiante=<?= (int) $eid ?>">Ver boletín</a>
                            <?php endif; ?>
                            <?php if (!$a['leido']): ?>
                                <form method="POST" style="margin:0" data-no-loading>
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="alerta_id" value="<?= (int) $a['id'] ?>">
                                    <input type="hidden" name="ver" value="<?= $verTodos ? 'todos' : '' ?>">
                                    <button type="submit">Marcar como leído</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php include 'includes/footer.php'; ?>
