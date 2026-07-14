<?php
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);

    // Buscar usuario por email
    $sql = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        // Generar token seguro
        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Guardar token
        $sqlUpdate = "
            UPDATE usuarios 
            SET reset_token = :token, reset_expires = :expira 
            WHERE id = :id
        ";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->bindParam(":token", $token);
        $stmtUpdate->bindParam(":expira", $expira);
        $stmtUpdate->bindParam(":id", $usuario["id"]);
        $stmtUpdate->execute();

        // Redirigir (luego aquí se envía correo real)
        header("Location: ../forgot_password.php?ok=1");
        exit;
    }

    // Si no existe el correo
    header("Location: ../forgot_password.php?error=1");
    exit;
}
