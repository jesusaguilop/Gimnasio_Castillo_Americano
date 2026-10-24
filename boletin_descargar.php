<?php

/**
 * Entrega un boletín en PDF VALIDANDO quién lo pide.
 *
 * Antes los boletines se enlazaban como archivo estático
 * (assets/boletines/AÑO/PERIODO/boletin_ID_PERIODO_AÑO.pdf), así que
 * cualquiera podía descargar el de cualquier estudiante adivinando la URL.
 * Ahora se pide por el id del registro en boletines_pdf y aquí se comprueba:
 *
 *   - padre     → solo hijos suyos, y solo si el admin ya lo publicó
 *   - profesor  → solo si es director de grupo del curso del estudiante
 *   - admin     → cualquiera
 *
 * Se recibe un id numérico (nunca una ruta), así que no hay path traversal.
 */

require_once __DIR__ . '/includes/init.php';

// Misma validez de sesión que el resto del sitio (usuario borrado,
// rol cambiado, clave restablecida o sesión inactiva → fuera).
if (App\Base\Auth::check() && !App\Base\Auth::sesionValida()) {
    App\Base\Auth::logout();
}

$rol = userRole();
$uid = userId();
$id  = (int) ($_GET['id'] ?? 0);

function negar(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

if (!$rol || !$uid) {
    negar(403, 'Necesitas iniciar sesión para ver un boletín.');
}
if ($id <= 0) {
    negar(400, 'Falta el identificador del boletín.');
}

$boletin = db()->fetch(
    "SELECT b.id, b.ruta_pdf, b.publicado, b.periodo, b.year,
            e.id AS estudiante_id, e.nombre AS estudiante, e.padre_id, e.curso_id
     FROM boletines_pdf b
     JOIN estudiantes e ON e.id = b.estudiante_id
     WHERE b.id = ?",
    [$id]
);

if (!$boletin) {
    negar(404, 'El boletín no existe.');
}

// ── Autorización por rol ──
$permitido = false;

if ($rol === 'admin') {
    $permitido = true;
} elseif ($rol === 'padre') {
    // Solo sus hijos (cualquiera de sus acudientes), y solo lo que el admin haya publicado
    $esAcudiente = db()->fetch(
        "SELECT 1 FROM estudiante_acudientes WHERE estudiante_id = ? AND padre_id = ?",
        [$boletin['estudiante_id'], $uid]
    );
    $permitido = $esAcudiente && $boletin['publicado'];
} elseif ($rol === 'profesor') {
    // Solo el director de grupo del curso al que pertenece el estudiante
    $esDirector = db()->fetch(
        "SELECT 1 FROM directores_grupo WHERE profesor_id = ? AND curso_id = ?",
        [$uid, $boletin['curso_id']]
    );
    $permitido = (bool) $esDirector;
}

if (!$permitido) {
    negar(403, 'No tienes permiso para ver este boletín.');
}

// ── Entregar el archivo ──
$ruta = __DIR__ . '/' . ltrim($boletin['ruta_pdf'], '/');

if (!is_file($ruta)) {
    // Pasa cuando el contenedor se redespliega: el registro sigue en la BD
    // pero el archivo se fue con el sistema de archivos efímero.
    negar(404, 'El archivo del boletín ya no está en el servidor. Hay que volver a generarlo.');
}

$nombre = sprintf(
    'Boletin_%s_P%s_%s.pdf',
    preg_replace('/[^A-Za-z0-9]+/', '-', $boletin['estudiante']),
    $boletin['periodo'],
    $boletin['year']
);

header('Content-Type: application/pdf');
// ?descargar=1 baja el archivo; sin él se abre en el visor del navegador
$disposicion = isset($_GET['descargar']) ? 'attachment' : 'inline';
header('Content-Disposition: ' . $disposicion . '; filename="' . $nombre . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($ruta);
