<?php
/**
 * Ruta de un archivo de assets/ con su versión (?v=fecha de modificación).
 * El servidor pide a los navegadores guardar CSS, JS e imágenes 30 días
 * (.htaccess); con la versión en la URL, al cambiar el archivo se descarga
 * de nuevo en vez de usar la copia vieja con el HTML nuevo.
 *
 *   <link rel="stylesheet" href="<?= asset('assets/css/escudo.css') ?>">
 */
if (!function_exists('asset')) {
    function asset(string $ruta): string
    {
        $t = @filemtime(__DIR__ . '/../' . ltrim($ruta, '/'));
        return $ruta . ($t ? '?v=' . $t : '');
    }
}
