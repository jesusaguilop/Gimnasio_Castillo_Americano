<?php
/**
 * Notificaciones del usuario conectado (admin, profesor o padre), en JSON.
 * La usa la campana de los tres paneles (assets/js/gca-ui.js).
 *
 *   GET  notificaciones.php?json=1          → {no_leidas, items[], csrf}
 *   POST accion=leer&id=N  | accion=leer_todas   (con _csrf_token)
 *
 * Ve lo dirigido a su usuario y lo dirigido a su rol en general (igual que
 * las páginas de alertas de cada panel). El tipo puede llevar un id al final
 * ("boletin_publicado:123") para enlazar directo al estudiante.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$uid = userId();
$rol = userRole();
if (!$uid || !in_array($rol, ['admin', 'profesor', 'padre'], true) || !App\Base\Auth::sesionValida()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'sesion']);
    exit;
}

// Lo que ve cada quien: lo suyo y lo de su rol en general
$visible = "(para_usuario_id = :uid OR (para_rol = :rol AND para_usuario_id IS NULL))";

function contarNoLeidas(PDO $c, string $visible, int $uid, string $rol): int
{
    $s = $c->prepare("SELECT COUNT(*) FROM alertas WHERE leido = FALSE AND $visible");
    $s->execute(['uid' => $uid, 'rol' => $rol]);
    return (int) $s->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/lib/limite_helper.php';
    if (limiteSuperado('notif', 'u' . $uid, 120, 10)) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'limite']);
        exit;
    }
    if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'csrf']);
        exit;
    }
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'leer') {
        $s = $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE id = :id AND $visible");
        $s->execute(['id' => (int) ($_POST['id'] ?? 0), 'uid' => $uid, 'rol' => $rol]);
    } elseif ($accion === 'leer_todas') {
        $s = $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE leido = FALSE AND $visible");
        $s->execute(['uid' => $uid, 'rol' => $rol]);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'accion']);
        exit;
    }
    unset($_SESSION['_sidebar_prof']);   // el contador del menú del profesor se recalcula
    echo json_encode(['ok' => true, 'no_leidas' => contarNoLeidas($conexion, $visible, $uid, $rol)]);
    exit;
}

$s = $conexion->prepare("
    SELECT id, tipo, titulo, mensaje, leido, created_at
    FROM alertas
    WHERE $visible
    ORDER BY leido ASC, created_at DESC
    LIMIT 15
");
$s->execute(['uid' => $uid, 'rol' => $rol]);

/** A dónde lleva cada aviso, según el rol (rutas desde la raíz del sitio). */
function enlaceAlerta(string $tipo, string $rol): ?string
{
    [$base, $id] = array_pad(explode(':', $tipo, 2), 2, null);
    $id = (int) $id;
    if ($rol === 'padre') {
        return $id ? "padres/boletin.php?estudiante=$id" : 'padres/avisos.php';
    }
    if ($rol === 'profesor') {
        return $base === 'riesgo_academico' ? 'profesores/direccion_grupo.php' : 'profesores/alertas.php';
    }
    return $base === 'boletin_listo' ? 'admin/enviar_boletin.php' : 'admin/alertas.php';
}

// Los avisos viejos traían emojis en el título; se muestran sin ellos
$sinEmoji = fn(string $t) => trim(preg_replace('/^[\p{So}\p{Sk}\x{FE0F}\x{200D}\s]+/u', '', $t));

$items = [];
foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $items[] = [
        'id'      => (int) $a['id'],
        'tipo'    => (string) $a['tipo'],
        'titulo'  => $sinEmoji((string) $a['titulo']),
        'mensaje' => (string) $a['mensaje'],
        'leido'   => (bool) $a['leido'],
        // created_at lo pone la BD (Neon) en UTC
        'fecha'   => (new DateTime((string) $a['created_at'], new DateTimeZone('UTC')))->format('c'),
        'url'     => enlaceAlerta((string) $a['tipo'], $rol),
    ];
}
// Las rutas se entregan relativas a la página que pregunta (../ desde los paneles)
$prefijo = isset($_GET['desde_panel']) ? '../' : '';
foreach ($items as &$it) {
    if ($it['url']) $it['url'] = $prefijo . $it['url'];
}
unset($it);

echo json_encode([
    'ok'        => true,
    'no_leidas' => contarNoLeidas($conexion, $visible, $uid, $rol),
    'items'     => $items,
    'csrf'      => generar_token_csrf(),
], JSON_UNESCAPED_UNICODE);
