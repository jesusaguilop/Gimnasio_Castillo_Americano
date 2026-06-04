<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/rank_helper.php";

$mensaje = "";
$tipo = "";

if (!isset($_GET["id"])) {
    header("Location: ver_asignaturas.php");
    exit;
}

$id = $_GET["id"];

$stmt = $conexion->prepare("SELECT id, nombre, area, nivel, grado FROM asignaturas WHERE id = :id");
$stmt->execute([":id" => $id]);
$asignatura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asignatura) {
    header("Location: ver_asignaturas.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $nivel = trim($_POST["nivel"] ?? "");
    $grado = trim($_POST["grado"] ?? "");

    if ($nombre === "" || $area === "" || $nivel === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "danger";
    } else {
        $grado = $grado ?: null;
        try {
            $sql = "UPDATE asignaturas SET nombre = :nombre, area = :area, nivel = :nivel, grado = :grado WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([":nombre" => $nombre, ":area" => $area, ":nivel" => $nivel, ":grado" => $grado, ":id" => $id]);

            $_SESSION["success_msg"] = "Asignatura actualizada correctamente.";
            header("Location: ver_asignaturas.php");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la asignatura.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Editar Asignatura";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Editar Asignatura</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Editar Asignatura</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Editar Asignatura</h4>
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
                                <label class="form-label fw-medium">Área</label>
                                <select name="area" class="form-select" required>
                                    <option value="">Seleccione el área</option>
                                    <?php
                                        $areas = ['MATEMÁTICAS', 'CASTELLANO', 'CIENCIAS SOCIALES', 'CIENCIAS NATURALES', 'LENGUAS EXTRANJERAS', 'ARTISTICA'];
                                        $currentArea = $_POST["area"] ?? $asignatura["area"];
                                        foreach ($areas as $a):
                                    ?>
                                        <option value="<?= $a ?>" <?= $currentArea === $a ? 'selected' : '' ?>><?= ucfirst(mb_strtolower($a)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Nivel</label>
                                <select name="nivel" class="form-select" required>
                                    <option value="">Seleccione el nivel</option>
                                    <?php $currentNivel = $_POST["nivel"] ?? $asignatura["nivel"]; ?>
                                    <option value="preescolar" <?= $currentNivel === "preescolar" ? "selected" : "" ?>>Preescolar</option>
                                    <option value="primaria" <?= $currentNivel === "primaria" ? "selected" : "" ?>>Primaria</option>
                                    <option value="secundaria" <?= $currentNivel === "secundaria" ? "selected" : "" ?>>Secundaria</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Grado (opcional)</label>
                                <select name="grado" class="form-select">
                                    <?php $currentGrado = $_POST["grado"] ?? $asignatura["grado"] ?? ""; ?>
                                    <option value="">Todos los grados</option>
                                    <optgroup label="Preescolar">
                                        <option value="maternal" <?= $currentGrado === "maternal" ? "selected" : "" ?>>Maternal</option>
                                        <option value="prejardin" <?= $currentGrado === "prejardin" ? "selected" : "" ?>>Pre-Jardín</option>
                                        <option value="jardin" <?= $currentGrado === "jardin" ? "selected" : "" ?>>Jardín</option>
                                        <option value="transicion" <?= $currentGrado === "transicion" ? "selected" : "" ?>>Transición</option>
                                    </optgroup>
                                    <optgroup label="Primaria">
                                        <option value="primero" <?= $currentGrado === "primero" ? "selected" : "" ?>>Primero</option>
                                        <option value="segundo" <?= $currentGrado === "segundo" ? "selected" : "" ?>>Segundo</option>
                                        <option value="tercero" <?= $currentGrado === "tercero" ? "selected" : "" ?>>Tercero</option>
                                        <option value="cuarto" <?= $currentGrado === "cuarto" ? "selected" : "" ?>>Cuarto</option>
                                        <option value="quinto" <?= $currentGrado === "quinto" ? "selected" : "" ?>>Quinto</option>
                                    </optgroup>
                                    <optgroup label="Secundaria">
                                        <option value="sexto" <?= $currentGrado === "sexto" ? "selected" : "" ?>>Sexto</option>
                                        <option value="septimo" <?= $currentGrado === "septimo" ? "selected" : "" ?>>Séptimo</option>
                                        <option value="octavo" <?= $currentGrado === "octavo" ? "selected" : "" ?>>Octavo</option>
                                        <option value="noveno" <?= $currentGrado === "noveno" ? "selected" : "" ?>>Noveno</option>
                                        <option value="decimo" <?= $currentGrado === "decimo" ? "selected" : "" ?>>Décimo</option>
                                        <option value="once" <?= $currentGrado === "once" ? "selected" : "" ?>>Once</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Nombre de la asignatura</label>
                                <input type="text" name="nombre" class="form-control"
                                    value="<?= htmlspecialchars($_POST["nombre"] ?? $asignatura["nombre"]) ?>" required
                                    placeholder="Ej. Matemáticas">
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                            </button>

                            <a href="ver_asignaturas.php" class="btn-outline-gca w-100 justify-content-center mt-2">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
