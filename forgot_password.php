<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Sistema Académico</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d6efd, #6f42c1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        .card-reset {
            background: #ffffff;
            border-radius: 22px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
            animation: fadeIn .7s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-primary {
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            border: none;
        }

        .btn-primary:hover {
            opacity: .9;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        @media (max-width: 480px) {
            .card-reset {
                padding: 28px 20px;
                border-radius: 16px;
            }
            body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<div class="card-reset">

    <div class="text-center mb-4">
        <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
        <h3 class="mt-3 fw-bold">Recuperar contraseña</h3>
        <p class="text-muted">
            Ingresa tu correo y te enviaremos un enlace para restablecerla
        </p>
    </div>

    <?php if (isset($_GET["ok"])): ?>
        <div class="alert alert-success text-center">
            Si el correo existe, se enviará un enlace de recuperación.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-danger text-center">
            Ocurrió un error. Intenta nuevamente.
        </div>
    <?php endif; ?>

    <!-- 🔴 AQUÍ ESTABA EL ERROR -->
    <form action="auth/forgot_password_procesar.php" method="POST">

        <div class="mb-3">
            <label class="form-label fw-semibold">Correo electrónico</label>
            <input 
                type="email" 
                name="email" 
                class="form-control" 
                placeholder="usuario@correo.com"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-envelope-check me-1"></i>
            Enviar enlace de recuperación
        </button>

    </form>

    <div class="text-center mt-4">
        <a href="login.php" class="text-decoration-none">
            ← Volver al inicio de sesión
        </a>
    </div>

</div>

</body>
</html>
