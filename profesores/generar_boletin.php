<?php
session_start();
require_once "../config/database.php";
require_once "../lib/boletin_generator.php";
require_once "../lib/mail_helper.php";

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

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "profesor") {
    header("Location: ../login.php");
    exit;
}

$profesor_id = $_SESSION["id"];
$estudiante_id = $_GET['estudiante'] ?? null;
$periodo = $_GET['periodo'] ?? null;

if (!$estudiante_id || !$periodo) {
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
$template = ($_GET['template'] ?? '') === 'v2' ? 'v2' : 'v1';
$anio = (int)($_GET['anio'] ?? date('Y'));
$res = generarBoletinEstudiante($conexion, (int)$estudiante_id, $periodo, (int)$profesor_id, $template, $anio);

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

// ─── Envío de correos según paz y salvo ───
$paz_y_salvo = $_GET['paz_y_salvo'] ?? null;
$ruta_absoluta = realpath(__DIR__ . '/../' . $res['ruta']);

$admin_email = config('mail.admin', '');
if ($admin_email && $ruta_absoluta) {
    enviar_boletin_por_email($admin_email, 'Administrador', $ruta_absoluta, 'Nuevo boletín generado');
}
if ($paz_y_salvo === '1') {
    $stmtPadre = $conexion->prepare("
        SELECT u.email, u.nombre FROM estudiantes e
        JOIN usuarios u ON e.padre_id = u.id
        WHERE e.id = ? AND u.email IS NOT NULL AND u.email != ''
    ");
    $stmtPadre->execute([$estudiante_id]);
    $padre = $stmtPadre->fetch(PDO::FETCH_ASSOC);
    if ($padre && $ruta_absoluta) {
        enviar_boletin_por_email($padre['email'], $padre['nombre'], $ruta_absoluta, 'Boletín de ' . $estudiante['nombre']);
    }
}

// Transmitir el PDF al navegador (pestaña nueva)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="boletin_' . $estudiante_id . '_' . $periodo . '.pdf"');
header('Content-Length: ' . strlen($res['pdf']));
echo $res['pdf'];
exit;
