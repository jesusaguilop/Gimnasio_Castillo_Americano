<?php
require_once "config/database.php";

$token = $_GET["token"] ?? "";

$sql = "
    SELECT id 
    FROM usuarios 
    WHERE reset_token = :token 
    AND reset_expires > NOW()
";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(":token", $token);
$stmt->execute();

if (!$stmt->fetch()) {
    die("Enlace inválido o expirado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding: 20px; }
    .card-reset-pass { max-width: 420px; width: 100%; }
    @media (max-width: 480px) {
        .card-reset-pass { padding: 20px !important; }
    }
</style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">

<div class="card p-4 card-reset-pass">
    <h4 class="text-center mb-3">Nueva contraseña</h4>

    <form action="auth/reset_password_procesar.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label>Nueva contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Guardar contraseña</button>
    </form>
</div>

</body>
</html>

