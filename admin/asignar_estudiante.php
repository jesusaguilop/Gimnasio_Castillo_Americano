<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/csrf_helper.php";

$mensaje = "";

/* Obtener estudiantes con su curso actual (para saber a quién mover) */
$estudiantes = $conexion->query(
    "SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre
     FROM estudiantes e
     LEFT JOIN cursos c ON e.curso_id = c.id
     ORDER BY e.nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* Obtener cursos (con grado y nivel para poder distinguirlos) */
$cursos = $conexion->query(
    "SELECT id, grado, nombre, nivel FROM cursos ORDER BY
        CASE nivel WHEN 'preescolar' THEN 1 WHEN 'primaria' THEN 2 WHEN 'secundaria' THEN 3 ELSE 4 END,
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14
            WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END, nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* Procesar formulario */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intente de nuevo.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $estudiante_id = $_POST["estudiante_id"];
    $curso_id = $_POST["curso_id"];

    try {
        $sql = "UPDATE estudiantes SET curso_id = :curso WHERE id = :estudiante";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":curso" => $curso_id,
            ":estudiante" => $estudiante_id
        ]);
        $mensaje = "Estudiante asignado al curso correctamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al realizar la asignación.";
    }
}

$pageTitle = "Asignar Estudiante";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Asignar Estudiante</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Matriculación Académica</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Asignar Estudiante a Curso</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Seleccionar Estudiante</label>
                                <select name="estudiante_id" class="form-select" required>
                                    <option value="">-- Elija un estudiante --</option>
                                    <?php foreach ($estudiantes as $e):
                                        $actual = $e['grado'] ? ucfirst($e['grado']) . ' ' . $e['curso_nombre'] : 'sin curso';
                                    ?>
                                        <option value="<?= $e["id"] ?>"><?= htmlspecialchars($e["nombre"] . '  —  (' . $actual . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Seleccionar Curso</label>
                                <select name="curso_id" class="form-select" required>
                                    <option value="">-- Elija un curso --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c["id"] ?>">
                                            <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"] . ' · ' . ucfirst($c["nivel"])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-journal-check me-2"></i>Asignar a Curso
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>