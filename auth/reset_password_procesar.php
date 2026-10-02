<?php
require_once "../config/database.php";

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
if (strlen($passwordPlano) < 6) {
    volverAlFormulario($token, "corta");
}
if (!hash_equals($passwordPlano, $confirmacion)) {
    volverAlFormulario($token, "distintas");
}

$password = password_hash($passwordPlano, PASSWORD_DEFAULT);

/* Validar token */
$sql = "
    SELECT id 
    FROM usuarios 
    WHERE reset_token = :token 
    AND reset_expires > NOW()
";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(":token", $token);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    // reset_password.php muestra "El enlace es inválido o ha expirado"
    header("Location: ../reset_password.php?token=" . urlencode($token));
    exit;
}

/* Actualizar contraseña */
$sql = "
    UPDATE usuarios 
    SET password = :password,
        reset_token = NULL,
        reset_expires = NULL
    WHERE id = :id
";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(":password", $password);
$stmt->bindParam(":id", $usuario["id"]);
$stmt->execute();

header("Location: ../login.php?reset=1");
exit;
