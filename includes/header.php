<?php
// Compatibilidad: header global para páginas antiguas que incluyen `includes/header.php`
// Calcula dinámicamente la URL base donde existe la carpeta `assets` para que
// rutas relativas como `assets/img/...` funcionen desde subcarpetas.

$docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']), '/');
$script = '/' . trim(str_replace('\\','/', $_SERVER['SCRIPT_NAME']), '/');
$parts = $script === '/' ? [] : explode('/', trim($script, '/'));
$siteBase = '/';
for ($i = count($parts); $i >= 0; $i--) {
    $candidate = '/' . implode('/', array_slice($parts, 0, $i));
    if ($candidate === '//') $candidate = '/';
    // Comprueba si existe la carpeta assets en el candidato
    if (is_dir($docRoot . rtrim($candidate, '/') . '/assets')) {
        $siteBase = rtrim($candidate, '/') . '/';
        break;
    }
}
// Asegurar que termina con '/'
if ($siteBase === '') $siteBase = '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars($siteBase, ENT_QUOTES, 'UTF-8') ?>">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | GCA' : 'Gimnasio Castillo Americano'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php
// Incluir la barra y navbar del sitio público (partial centralizado)
$partial = __DIR__ . '/../resources/views/partials/public/header.php';
if (file_exists($partial)) {
    include $partial;
}
?>
