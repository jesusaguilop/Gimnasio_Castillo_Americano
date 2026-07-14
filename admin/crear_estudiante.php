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
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END, nombre"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? "");
    $curso_id = $_POST["curso_id"] ?? "";

    if ($nombre === "" || $documento === "" || $fecha_nacimiento === "" || $curso_id === "") {
        $mensaje = "Todos los campos obligatorios deben estar diligenciados.";
        $tipo = "danger";
    } else {
        try {
            $db = db();

            $db->insert('estudiantes', [
                'nombre' => $nombre,
                'documento' => $documento,
                'fecha_nacimiento' => $fecha_nacimiento,
                'curso_id' => $curso_id,
            ]);

            Session::setSuccess("Estudiante creado correctamente.");
            header("Location: ver_estudiantes.php");
            exit;
        } catch (\PDOException $e) {
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

                            <div class="mb-4">
                                <label class="form-label fw-medium">Fecha de nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="form-control"
                                    value="<?= htmlspecialchars($_POST["fecha_nacimiento"] ?? "") ?>" required>
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