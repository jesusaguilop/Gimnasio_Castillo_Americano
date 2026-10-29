<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("profesor");
require_once "../lib/boletin_generator.php";
require_once "../lib/mail_helper.php";
require_once "../lib/csrf_helper.php";
require_once "../lib/alertas_helper.php";
require_once "../lib/limite_helper.php";

// Generar un PDF es pesado para el servidor: 40 cada 10 minutos por profesor
if (limiteSuperado('generar', 'u' . userId(), 40, 10)) {
    http_response_code(429);
    exit('Demasiados boletines generados seguidos. Espera unos minutos e intenta de nuevo.');
}

/**
 * Muestra una página de mensaje con estilo (en vez de un die() en texto plano)
 * y termina la ejecución. El boletín se abre en pestaña nueva, así que ofrece "Volver".
 */
function boletinMensaje(string $titulo, string $mensaje, string $icono = 'bi-info-circle'): void
{
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Boletín · GCA</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">'
        . '<style>*{margin:0;box-sizing:border-box}'
        . 'body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;'
        . 'font-family:system-ui,"Segoe UI",sans-serif;background:linear-gradient(135deg,#0b1622,#1b2a45)}'
        . '.card{position:relative;overflow:hidden;background:#fff;border-radius:20px;max-width:430px;width:100%;padding:40px 32px;text-align:center;'
        . 'box-shadow:0 30px 80px rgba(0,0,0,.4);animation:in .5s cubic-bezier(.22,1,.36,1)}'
        . '.card::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;background:#c9a24d}'
        . '@keyframes in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}'
        . '.ic{width:72px;height:72px;border-radius:50%;background:rgba(201,162,77,.12);color:#b8860b;'
        . 'display:flex;align-items:center;justify-content:center;font-size:34px;margin:0 auto 18px}'
        . 'h1{font-size:20px;color:#1a2233;margin-bottom:10px;font-weight:700}'
        . 'p{color:#5a6472;font-size:14px;line-height:1.65;margin-bottom:24px}'
        . '.btn{display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;'
        . 'background:linear-gradient(135deg,#c9a24d,#b8922e);color:#0d1b2a;font-weight:700;'
        . 'padding:12px 24px;border-radius:12px;text-decoration:none;font-size:14px;transition:transform .15s}'
        . '.btn:hover{transform:translateY(-1px)}</style></head><body><div class="card">'
        . '<div class="ic"><i class="bi ' . htmlspecialchars($icono) . '"></i></div>'
        . '<h1>' . htmlspecialchars($titulo) . '</h1>'
        . '<p>' . htmlspecialchars($mensaje) . '</p>'
        . '<button class="btn" onclick="window.close(); history.back();"><i class="bi bi-arrow-left"></i> Volver</button>'
        . '</div></body></html>';
    exit;
}

// Generar cambia datos (y despublica el boletín), así que solo por POST con token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    boletinMensaje('Usa el botón "Generar boletín"', 'Los boletines se generan desde "Dirección de Grupo", con el botón de cada estudiante.', 'bi-info-circle');
}
if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    boletinMensaje('Sesión vencida', 'Recarga la página de Dirección de Grupo e inténtalo de nuevo.', 'bi-shield-exclamation');
}

$profesor_id = $_SESSION["id"];
$estudiante_id = (int) ($_POST['estudiante'] ?? 0);
$periodo = (string) ($_POST['periodo'] ?? '');

if (!$estudiante_id || !in_array($periodo, ['1', '2', '3', '4'], true)) {
    boletinMensaje('Faltan datos', 'No se recibió el estudiante o el período. Vuelve a intentarlo desde la lista de estudiantes.', 'bi-exclamation-triangle');
}

// Solo el DIRECTOR DE GRUPO del curso del estudiante puede generar boletines.
$check = $conexion->prepare("
    SELECT e.id, e.nombre
    FROM estudiantes e
    JOIN directores_grupo dg ON dg.curso_id = e.curso_id
    WHERE e.id = ? AND dg.profesor_id = ?
");
$check->execute([$estudiante_id, $profesor_id]);
$estudiante = $check->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    boletinMensaje('Solo dirección de grupo', 'Los boletines los genera el director de grupo del curso, desde la sección "Dirección de Grupo".', 'bi-shield-lock');
}

// Generar el boletín (lógica compartida: guarda en disco + registra + calcula promedio/puesto)
$template = ($_POST['template'] ?? '') === 'v1' ? 'v1' : 'v2';
$anio = (int)($_POST['anio'] ?? date('Y'));
$pazBool = (($_POST['paz_y_salvo'] ?? '') === '1');
$res = generarBoletinEstudiante($conexion, (int)$estudiante_id, $periodo, (int)$profesor_id, $template, $anio, $pazBool);

if (!$res['ok']) {
    if (($res['motivo'] ?? '') === 'Sin notas') {
        boletinMensaje(
            'Aún no hay notas',
            'Este estudiante no tiene calificaciones registradas en este período. Registra y guarda sus notas primero, y luego genera el boletín.',
            'bi-journal-x'
        );
    }
    boletinMensaje('No se pudo generar', $res['motivo'] ?? 'Ocurrió un error inesperado.', 'bi-exclamation-triangle');
}

// El boletín queda PENDIENTE de publicación: NO se envía al padre aquí.
// El administrador decide el paso al padre desde "Gestión de Boletines".
// El paz y salvo (marcado por el director) queda registrado como referencia para el admin.

// Administración recibe el aviso de que hay un boletín por publicar
try {
    $cid = $conexion->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
    $cid->execute([(int) $estudiante_id]);
    alertarBoletinesListos($conexion, (int) $cid->fetchColumn(), $periodo, $anio, 1);
} catch (\Throwable $e) {
    error_log("[generar_boletin] aviso a admin: " . $e->getMessage());
}

// Transmitir el PDF al navegador (pestaña nueva)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="boletin_' . $estudiante_id . '_' . $periodo . '.pdf"');
header('Content-Length: ' . strlen($res['pdf']));
echo $res['pdf'];
exit;
