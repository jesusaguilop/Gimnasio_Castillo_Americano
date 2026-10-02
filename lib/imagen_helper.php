<?php

/**
 * Guardado de imágenes subidas desde el panel (fotos de docentes, imágenes
 * de noticias).
 *
 * Antes cada pantalla repetía el mismo bloque y tenía dos problemas:
 *
 *  1. Si PHP rechazaba el archivo (típicamente por pasar de
 *     upload_max_filesize, y cualquier foto de celular pasa de 2 MB), el
 *     código se saltaba la foto EN SILENCIO y guardaba el registro sin ella.
 *     El usuario montaba la imagen, no veía ningún error, y no se guardaba.
 *  2. No se comprobaba que move_uploaded_file hubiera funcionado, así que se
 *     podía guardar en la base la ruta de un archivo inexistente.
 *
 * Ahora cualquier fallo lanza una excepción con un mensaje entendible, y las
 * fotos grandes se reducen en vez de rechazarse.
 */

require_once __DIR__ . '/../config/env.php';

/**
 * Recibe un elemento de $_FILES y devuelve la ruta relativa donde quedó
 * guardada la imagen, lista para la base de datos.
 *
 * Devuelve '' si el usuario no eligió ningún archivo (no es un error).
 *
 * @param array  $archivo    p.ej. $_FILES['foto']
 * @param string $subcarpeta 'docentes' o 'noticias'
 * @param string $prefijo    prefijo del nombre, p.ej. 'docente'
 * @param int    $ladoMax    lado mayor en píxeles; más grande se reduce
 *
 * @throws Exception con un mensaje pensado para mostrarle al usuario
 */
function guardar_imagen_subida(
    array $archivo,
    string $subcarpeta,
    string $prefijo,
    int $ladoMax = 1200
): string {
    $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($codigo === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($codigo !== UPLOAD_ERR_OK) {
        throw new Exception(mensaje_error_subida($codigo));
    }

    $temporal = $archivo['tmp_name'] ?? '';
    if ($temporal === '' || !is_uploaded_file($temporal)) {
        throw new Exception('No se recibió la imagen. Vuelve a intentarlo.');
    }

    // Se comprueba el contenido real, no la extensión del nombre: así no
    // entra un archivo disfrazado, y de paso se detecta el caso del iPhone
    // (HEIC), que no es una imagen que se pueda procesar aquí.
    $info = @getimagesize($temporal);
    if ($info === false) {
        throw new Exception(
            'El archivo no es una imagen que podamos procesar. Si viene de un iPhone '
            . 'puede estar en formato HEIC: conviértelo a JPG y vuelve a subirlo.'
        );
    }

    [$ancho, $alto] = $info;
    $tipo = $info[2] ?? 0;

    $formatos = [
        IMAGETYPE_JPEG => ['jpg',  'imagecreatefromjpeg'],
        IMAGETYPE_PNG  => ['png',  'imagecreatefrompng'],
        IMAGETYPE_WEBP => ['webp', 'imagecreatefromwebp'],
    ];

    if (!isset($formatos[$tipo])) {
        throw new Exception('Formato no permitido. Usa JPG, PNG o WebP.');
    }

    [$extension, $abrir] = $formatos[$tipo];

    if (!function_exists($abrir)) {
        throw new Exception('El servidor no puede procesar imágenes ' . strtoupper($extension) . '.');
    }

    $imagen = @$abrir($temporal);
    if (!$imagen) {
        throw new Exception('La imagen está dañada o incompleta. Prueba con otra.');
    }

    // Reducir si es más grande de lo necesario. Una foto de celular puede
    // venir a 4000 px: se muestra en una tarjeta pequeña, así que guardarla
    // entera solo gasta ancho de banda de las familias.
    $ladoMayor = max($ancho, $alto);
    if ($ladoMayor > $ladoMax) {
        $escala = $ladoMax / $ladoMayor;
        $reducida = imagescale($imagen, (int) round($ancho * $escala), (int) round($alto * $escala));
        if ($reducida !== false) {
            imagedestroy($imagen);
            $imagen = $reducida;
        }
    }

    $carpeta = ruta_subidas($subcarpeta);
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        throw new Exception('No se pudo preparar la carpeta de imágenes en el servidor.');
    }
    if (!is_writable($carpeta)) {
        throw new Exception('La carpeta de imágenes del servidor no tiene permiso de escritura.');
    }

    $nombre  = $prefijo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destino = $carpeta . '/' . $nombre;

    $guardada = match ($tipo) {
        IMAGETYPE_JPEG => imagejpeg($imagen, $destino, 82),
        IMAGETYPE_PNG  => imagepng($imagen, $destino, 6),
        IMAGETYPE_WEBP => imagewebp($imagen, $destino, 82),
    };
    imagedestroy($imagen);

    if (!$guardada || !is_file($destino)) {
        throw new Exception('No se pudo guardar la imagen en el servidor.');
    }

    return url_subidas($subcarpeta) . '/' . $nombre;
}

/**
 * Traduce el código de error de PHP a algo que el usuario pueda accionar.
 */
function mensaje_error_subida(int $codigo): string
{
    $limite = ini_get('upload_max_filesize') ?: '2M';

    return match ($codigo) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            "La imagen es más grande de lo permitido (máximo $limite). "
            . 'Redúcela o usa una foto más liviana.',
        UPLOAD_ERR_PARTIAL =>
            'La subida se interrumpió antes de terminar. Vuelve a intentarlo.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE =>
            'El servidor no pudo guardar el archivo temporal. Avisa al administrador.',
        UPLOAD_ERR_EXTENSION =>
            'Una extensión de PHP bloqueó la subida. Avisa al administrador.',
        default =>
            'No se pudo subir la imagen (error ' . $codigo . ').',
    };
}

/**
 * Borra una imagen anterior al reemplazarla. Silencioso a propósito: si el
 * archivo ya no está, no hay nada que informar.
 */
function borrar_imagen_subida(?string $rutaRelativa): void
{
    if (!$rutaRelativa) {
        return;
    }
    $ruta = dirname(__DIR__) . '/' . ltrim($rutaRelativa, '/');
    if (is_file($ruta)) {
        @unlink($ruta);
    }
}
