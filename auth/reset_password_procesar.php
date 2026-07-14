<?php
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$token = trim($_POST["token"] ?? "");
$passwordPlano = $_POST["password"] ?? "";

if ($token === "" || strlen($passwordPlano) < 6) {
    die("Datos inválidos. La contraseña debe tener al menos 6 caracteres.");
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
    die("Token inválido");
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
