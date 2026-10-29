<?php
require_once __DIR__ . "/../includes/init.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$token = trim($_POST["token"] ?? "");
$passwordPlano = $_POST["password"] ?? "";
$confirmacion = $_POST["password_confirm"] ?? $passwordPlano;

// Los errores vuelven al formulario (con su diseño) en vez de una página en blanco
function volverAlFormulario(string $token, string $error): never
{
    header("Location: ../reset_password.php?token=" . urlencode($token) . "&error=" . $error);
    exit;
}

if ($token === "") {
    header("Location: ../forgot_password.php");
    exit;
}
// Límite por IP: 20 intentos por hora con enlaces de recuperación
require_once __DIR__ . "/../lib/limite_helper.php";
if (limiteSuperado('nueva-clave', '', 20, 60)) {
    header("Location: ../reset_password.php?token=" . urlencode($token));
    exit;
}
if (mb_strlen($passwordPlano) < 8) {
    volverAlFormulario($token, "corta");
}
if (!hash_equals($passwordPlano, $confirmacion)) {
    volverAlFormulario($token, "distintas");
}

/* Validar token (en la BD está su SHA-256) */
$usuario = db()->fetch(
    "SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()",
    [hash("sha256", $token)]
);

if (!$usuario) {
    // reset_password.php muestra "El enlace es inválido o ha expirado"
    header("Location: ../reset_password.php?token=" . urlencode($token));
    exit;
}

/* Actualizar contraseña. Al cambiar el hash, las sesiones abiertas con la
   clave anterior dejan de valer (Auth::sesionValida compara su huella). */
db()->query(
    "UPDATE usuarios SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
    [password_hash($passwordPlano, PASSWORD_BCRYPT, ["cost" => 12]), $usuario["id"]]
);

// Quien elige su propia clave ya no necesita cambiar la provisional.
// Aparte, para no romper el restablecimiento si la columna aún no existe.
try {
    db()->query("UPDATE usuarios SET debe_cambiar_clave = FALSE WHERE id = ?", [$usuario["id"]]);
} catch (Throwable $e) {
    error_log("[reset] " . $e->getMessage());
}

header("Location: ../login.php?reset=1");
exit;
