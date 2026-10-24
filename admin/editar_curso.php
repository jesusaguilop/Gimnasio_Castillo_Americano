<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");

require_once __DIR__ . "/../lib/csrf_helper.php";
require_once "../lib/rank_helper.php";

$mensaje = "";
$tipo = "";

if (!isset($_GET["id"])) {
    header("Location: ver_cursos.php");
    exit;
}

$id = $_GET["id"];

$stmt = $conexion->prepare("SELECT id, nombre, grado FROM cursos WHERE id = :id");
$stmt->execute([":id" => $id]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    header("Location: ver_cursos.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $grado = trim($_POST["grado"] ?? "");

    if ($nombre === "" || $grado === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "danger";
    } else {
        $nivel = obtenerNivelGrado($grado);
        try {
            $sql = "UPDATE cursos SET nombre = :nombre, grado = :grado, nivel = :nivel WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([":nombre" => $nombre, ":grado" => $grado, ":nivel" => $nivel, ":id" => $id]);

            $_SESSION["success_msg"] = "Curso actualizado correctamente.";
            header("Location: ver_cursos.php");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el curso.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Editar Curso";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <a href="ver_cursos.php" class="form-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Cursos</a>
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <i class="bi bi-collection" aria-hidden="true"></i>
                            <div>
                                <h4>Editar Curso</h4>
                                <span>Actualice el grado o el nombre del curso.</span>
                            </div>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <?= campo_csrf() ?>
                            <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_grado">Grado <span class="req" aria-hidden="true">*</span></label>
                                <select id="f_grado" name="grado" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <optgroup label="Preescolar">
                                        <option value="maternal" <?= ($_POST["grado"] ?? $curso["grado"]) == "maternal" ? "selected" : "" ?>>Maternal</option>
                                        <option value="prejardin" <?= ($_POST["grado"] ?? $curso["grado"]) == "prejardin" ? "selected" : "" ?>>Pre-Jardín</option>
                                        <option value="jardin" <?= ($_POST["grado"] ?? $curso["grado"]) == "jardin" ? "selected" : "" ?>>Jardín</option>
                                        <option value="transicion" <?= ($_POST["grado"] ?? $curso["grado"]) == "transicion" ? "selected" : "" ?>>Transición</option>
                                    </optgroup>
                                    <optgroup label="Primaria">
                                        <option value="primero" <?= ($_POST["grado"] ?? $curso["grado"]) == "primero" ? "selected" : "" ?>>Primero</option>
                                        <option value="segundo" <?= ($_POST["grado"] ?? $curso["grado"]) == "segundo" ? "selected" : "" ?>>Segundo</option>
                                        <option value="tercero" <?= ($_POST["grado"] ?? $curso["grado"]) == "tercero" ? "selected" : "" ?>>Tercero</option>
                                        <option value="cuarto" <?= ($_POST["grado"] ?? $curso["grado"]) == "cuarto" ? "selected" : "" ?>>Cuarto</option>
                                        <option value="quinto" <?= ($_POST["grado"] ?? $curso["grado"]) == "quinto" ? "selected" : "" ?>>Quinto</option>
                                    </optgroup>
                                    <optgroup label="Secundaria">
                                        <option value="sexto" <?= ($_POST["grado"] ?? $curso["grado"]) == "sexto" ? "selected" : "" ?>>Sexto</option>
                                        <option value="septimo" <?= ($_POST["grado"] ?? $curso["grado"]) == "septimo" ? "selected" : "" ?>>Séptimo</option>
                                        <option value="octavo" <?= ($_POST["grado"] ?? $curso["grado"]) == "octavo" ? "selected" : "" ?>>Octavo</option>
                                        <option value="noveno" <?= ($_POST["grado"] ?? $curso["grado"]) == "noveno" ? "selected" : "" ?>>Noveno</option>
                                        <option value="decimo" <?= ($_POST["grado"] ?? $curso["grado"]) == "decimo" ? "selected" : "" ?>>Décimo</option>
                                        <option value="once" <?= ($_POST["grado"] ?? $curso["grado"]) == "once" ? "selected" : "" ?>>Once</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_nombre">Nombre del curso (ej. A, B) <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_nombre" type="text" name="nombre" class="form-control"
                                    value="<?= htmlspecialchars($_POST["nombre"] ?? $curso["nombre"]) ?>" required
                                    placeholder="Ej. A">
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                            </button>

                            <a href="ver_cursos.php" class="btn-outline-gca w-100 justify-content-center mt-2">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
