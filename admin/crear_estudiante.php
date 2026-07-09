<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

$cursos = db()->fetchAll(
    "SELECT id, nombre, grado, nivel FROM cursos ORDER BY
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'undecimo' THEN 15
            ELSE 16
        END, nombre"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? "");
    $curso_id = $_POST["curso_id"] ?? "";
    $pass1 = $_POST["password"] ?? "";
    $pass2 = $_POST["password2"] ?? "";

    if ($nombre === "" || $email === "" || $documento === "" || $curso_id === "" || $pass1 === "" || $pass2 === "") {
        $mensaje = "Todos los campos obligatorios deben estar diligenciados.";
        $tipo = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido.";
        $tipo = "danger";
    } elseif ($pass1 !== $pass2) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo = "warning";
    } else {
        try {
            $db = db();
            $db->beginTransaction();

            $existing = $db->fetch("SELECT id FROM usuarios WHERE email = ? LIMIT 1", [$email]);

            if ($existing) {
                $mensaje = "Este correo ya se encuentra registrado.";
                $tipo = "warning";
                $db->rollBack();
            } else {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                $usuario_id = $db->insert('usuarios', [
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => $hash,
                    'rol' => 'estudiante',
                ]);

                $db->insert('estudiantes', [
                    'usuario_id' => $usuario_id,
                    'nombre' => $nombre,
                    'documento' => $documento ?: null,
                    'fecha_nacimiento' => $fecha_nacimiento ?: null,
                    'curso_id' => $curso_id,
                ]);

                $db->commit();

                Session::setSuccess("Estudiante creado correctamente.");
                header("Location: ver_estudiantes.php");
                exit;
            }
        } catch (\PDOException $e) {
            db()->rollBack();
            $mensaje = "Error al crear el estudiante.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Estudiante";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Crear Estudiante</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Nuevo Estudiante</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Registro de Estudiante</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Nombre completo</label>
                                <input type="text" name="nombre" class="form-control"
                                    value="<?= htmlspecialchars($_POST["nombre"] ?? "") ?>" required
                                    placeholder="Ej. Ana Sofía Torres">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Número de documento</label>
                                <input type="text" name="documento" class="form-control"
                                    value="<?= htmlspecialchars($_POST["documento"] ?? "") ?>" required
                                    placeholder="Ej. 1234567890">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Curso</label>
                                <select name="curso_id" class="form-select" required>
                                    <option value="">Seleccione un curso</option>
                                    <?php $lastNivel = ""; ?>
                                    <?php foreach ($cursos as $c):
                                        $nivelLabel = $c['nivel'] ? ucfirst($c['nivel']) : 'Otro';
                                        $gradoLabel = ucfirst($c['grado']);
                                    ?>
                                        <?php if ($c['nivel'] !== $lastNivel): ?>
                                            <?php $lastNivel = $c['nivel']; ?>
                                            <optgroup label="<?= htmlspecialchars($nivelLabel) ?>">
                                        <?php endif; ?>
                                        <option value="<?= $c['id'] ?>" <?= ($_POST["curso_id"] ?? "") == $c['id'] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($gradoLabel . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Fecha de nacimiento <small class="text-muted fw-normal">(opcional)</small></label>
                                <input type="date" name="fecha_nacimiento" class="form-control"
                                    value="<?= htmlspecialchars($_POST["fecha_nacimiento"] ?? "") ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Correo electrónico</label>
                                <input type="email" name="email" class="form-control"
                                    placeholder="estudiante@gca.edu.co"
                                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Confirmar contraseña</label>
                                <input type="password" name="password2" class="form-control" required>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-check-circle me-2"></i>Registrar Estudiante
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>