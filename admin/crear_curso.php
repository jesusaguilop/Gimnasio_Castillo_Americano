<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");

require_once __DIR__ . "/../lib/csrf_helper.php";
require_once "../lib/rank_helper.php";

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre_curso = trim($_POST["nombre_curso"] ?? "");
    $grado = trim($_POST["grado"] ?? "");

    if (!in_array($grado, ['maternal','prejardin','jardin','transicion','primero','segundo','tercero','cuarto','quinto','sexto','septimo','octavo','noveno','decimo','once'], true)) {
        $grado = "";   // solo grados conocidos (se imprimen en atributos data- del panel)
    }
    if ($nombre_curso === "" || $grado === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "danger";
    } else {
        $nivel = mb_strtolower(obtenerNivelGrado($grado)); // 'preescolar' | 'primaria' | 'secundaria' (consistente con el resto)
        try {
            $sql = "INSERT INTO cursos (nombre, grado, nivel) VALUES (:nombre_curso, :grado, :nivel)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([":nombre_curso" => $nombre_curso, ":grado" => $grado, ":nivel" => $nivel]);

            $_SESSION["success_msg"] = "Curso creado correctamente.";
            header("Location: ver_cursos.php");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al crear el curso.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Curso";
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
                                <h4>Registro de Curso</h4>
                                <span>Registre un grado y el nombre del curso.</span>
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
                                        <option value="maternal" <?= ($_POST["grado"] ?? "") == "maternal" ? "selected" : "" ?>>Maternal</option>
                                        <option value="prejardin" <?= ($_POST["grado"] ?? "") == "prejardin" ? "selected" : "" ?>>Pre-Jardín</option>
                                        <option value="jardin" <?= ($_POST["grado"] ?? "") == "jardin" ? "selected" : "" ?>>Jardín</option>
                                        <option value="transicion" <?= ($_POST["grado"] ?? "") == "transicion" ? "selected" : "" ?>>Transición</option>
                                    </optgroup>
                                    <optgroup label="Primaria">
                                        <option value="primero" <?= ($_POST["grado"] ?? "") == "primero" ? "selected" : "" ?>>Primero</option>
                                        <option value="segundo" <?= ($_POST["grado"] ?? "") == "segundo" ? "selected" : "" ?>>Segundo</option>
                                        <option value="tercero" <?= ($_POST["grado"] ?? "") == "tercero" ? "selected" : "" ?>>Tercero</option>
                                        <option value="cuarto" <?= ($_POST["grado"] ?? "") == "cuarto" ? "selected" : "" ?>>Cuarto</option>
                                        <option value="quinto" <?= ($_POST["grado"] ?? "") == "quinto" ? "selected" : "" ?>>Quinto</option>
                                    </optgroup>
                                    <optgroup label="Secundaria">
                                        <option value="sexto" <?= ($_POST["grado"] ?? "") == "sexto" ? "selected" : "" ?>>Sexto</option>
                                        <option value="septimo" <?= ($_POST["grado"] ?? "") == "septimo" ? "selected" : "" ?>>Séptimo</option>
                                        <option value="octavo" <?= ($_POST["grado"] ?? "") == "octavo" ? "selected" : "" ?>>Octavo</option>
                                        <option value="noveno" <?= ($_POST["grado"] ?? "") == "noveno" ? "selected" : "" ?>>Noveno</option>
                                        <option value="decimo" <?= ($_POST["grado"] ?? "") == "decimo" ? "selected" : "" ?>>Décimo</option>
                                        <option value="once" <?= ($_POST["grado"] ?? "") == "once" ? "selected" : "" ?>>Once</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_nombre_curso">Nombre del curso (ej. A, B) <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_nombre_curso" type="text" name="nombre_curso" class="form-control"
                                    value="<?= htmlspecialchars($_POST["nombre_curso"] ?? "") ?>" required
                                    placeholder="Ej. A">
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-check-circle me-2"></i>Registrar Curso
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>