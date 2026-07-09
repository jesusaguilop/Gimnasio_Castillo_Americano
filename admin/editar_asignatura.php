<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/rank_helper.php";
require_once "../lib/csrf_helper.php";

$mensaje = "";
$tipo = "";

if (!isset($_GET["id"])) {
    header("Location: ver_asignaturas.php");
    exit;
}

$id = $_GET["id"];

$stmt = $conexion->prepare("SELECT a.id, a.nombre, a.area_id, a.area, a.nivel, a.intensidad_horaria FROM asignaturas a WHERE a.id = :id");
$stmt->execute([":id" => $id]);
$asignatura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asignatura) {
    header("Location: ver_asignaturas.php");
    exit;
}

$gradosAsignadosRaw = $conexion->prepare("SELECT grado, intensidad_horaria FROM asignatura_grado WHERE asignatura_id = :id");
$gradosAsignadosRaw->execute([":id" => $id]);
$gradosAsignadosIH = $gradosAsignadosRaw->fetchAll(PDO::FETCH_KEY_PAIR);
$gradosAsignados = $gradosAsignadosIH ? array_keys($gradosAsignadosIH) : [];

$areas = $conexion->query("SELECT id, nombre FROM areas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intente de nuevo.";
        $tipo = "danger";
    } else {
        $nombre = trim($_POST["nombre"] ?? "");
        $area_id = trim($_POST["area_id"] ?? "");
        $nivel = trim($_POST["nivel"] ?? "");
        $grados = $_POST["grados"] ?? [];
        $ih_por_grado = $_POST["ih"] ?? [];

        if ($nombre === "" || $area_id === "" || $nivel === "" || empty($grados)) {
            $mensaje = "Todos los campos son obligatorios y debe seleccionar al menos un grado.";
            $tipo = "danger";
        } else {
            try {
                $stmt = $conexion->prepare("SELECT nombre FROM areas WHERE id = :id");
                $stmt->execute([":id" => $area_id]);
                $area_nombre = $stmt->fetchColumn();

                $conexion->beginTransaction();

                $ih_default = (int)($ih_por_grado[$grados[0]] ?? 0);
                $sql = "UPDATE asignaturas SET nombre = :nombre, area = :area, area_id = :area_id, nivel = :nivel, intensidad_horaria = :intensidad_horaria WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ":nombre" => $nombre,
                    ":area" => $area_nombre,
                    ":area_id" => $area_id,
                    ":nivel" => $nivel,
                    ":intensidad_horaria" => $ih_default,
                    ":id" => $id,
                ]);

                $stmtDel = $conexion->prepare("DELETE FROM asignatura_grado WHERE asignatura_id = :id");
                $stmtDel->execute([":id" => $id]);

                $stmtGrado = $conexion->prepare("INSERT INTO asignatura_grado (asignatura_id, grado, intensidad_horaria) VALUES (:asignatura_id, :grado, :intensidad_horaria)");
                foreach ($grados as $g) {
                    $stmtGrado->execute([
                        ":asignatura_id" => $id,
                        ":grado" => $g,
                        ":intensidad_horaria" => (int)($ih_por_grado[$g] ?? 0),
                    ]);
                }

                $conexion->commit();

                $_SESSION["success_msg"] = "Asignatura actualizada correctamente.";
                header("Location: ver_asignaturas.php");
                exit;
            } catch (PDOException $e) {
                $conexion->rollBack();
                error_log("[editar_asignatura] " . $e->getMessage());
                $mensaje = "Error al actualizar la asignatura.";
                $tipo = "danger";
            }
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
                            <?= campo_csrf() ?>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Área</label>
                                <select name="area_id" class="form-select" required>
                                    <option value="">Seleccione el área</option>
                                    <?php $currentAreaId = $_POST["area_id"] ?? $asignatura["area_id"]; ?>
                                    <?php foreach ($areas as $a): ?>
                                        <option value="<?= $a["id"] ?>" <?= $currentAreaId == $a["id"] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst(mb_strtolower($a["nombre"]))) ?>
                                        </option>
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
                                <label class="form-label fw-medium">Grados donde se dicta</label>
                                <div id="gradosContainer">
                                    <div class="grado-group" data-nivel="preescolar" style="display:none;">
                                        <label class="form-label text-muted" style="font-size:12px;">Preescolar</label>
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <?php $gradosPost = $_POST["grados"] ?? $gradosAsignados; ?>
                                            <?php foreach (['maternal','prejardin','jardin','transicion'] as $g): ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="grados[]" value="<?= $g ?>" id="eg_<?= $g ?>"
                                                        <?= in_array($g, $gradosPost) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="eg_<?= $g ?>"><?= ucfirst($g) ?></label>
                                                    <input type="number" name="ih[<?= $g ?>]" class="form-control form-control-sm d-inline-block ms-1"
                                                        style="width:70px;" min="0" max="40"
                                                        value="<?= htmlspecialchars($_POST["ih"][$g] ?? $gradosAsignadosIH[$g] ?? 0) ?>"
                                                        placeholder="Horas">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="grado-group" data-nivel="primaria" style="display:none;">
                                        <label class="form-label text-muted" style="font-size:12px;">Primaria</label>
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <?php foreach (['primero','segundo','tercero','cuarto','quinto'] as $g): ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="grados[]" value="<?= $g ?>" id="eg_<?= $g ?>"
                                                        <?= in_array($g, $gradosPost) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="eg_<?= $g ?>"><?= ucfirst($g) ?></label>
                                                    <input type="number" name="ih[<?= $g ?>]" class="form-control form-control-sm d-inline-block ms-1"
                                                        style="width:70px;" min="0" max="40"
                                                        value="<?= htmlspecialchars($_POST["ih"][$g] ?? $gradosAsignadosIH[$g] ?? 0) ?>"
                                                        placeholder="Horas">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="grado-group" data-nivel="secundaria" style="display:none;">
                                        <label class="form-label text-muted" style="font-size:12px;">Secundaria</label>
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <?php foreach (['sexto','septimo','octavo','noveno','decimo','once'] as $g): ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="grados[]" value="<?= $g ?>" id="eg_<?= $g ?>"
                                                        <?= in_array($g, $gradosPost) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="eg_<?= $g ?>"><?= ucfirst($g) ?></label>
                                                    <input type="number" name="ih[<?= $g ?>]" class="form-control form-control-sm d-inline-block ms-1"
                                                        style="width:70px;" min="0" max="40"
                                                        value="<?= htmlspecialchars($_POST["ih"][$g] ?? $gradosAsignadosIH[$g] ?? 0) ?>"
                                                        placeholder="Horas">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text">Seleccione los grados en los que se dicta esta asignatura e ingrese la intensidad horaria semanal para cada grado.</div>
                            </div>

                            <div class="mb-3">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nivelSelect = document.querySelector('select[name="nivel"]');
    const gradoGroups = document.querySelectorAll('.grado-group');

    function mostrarGrados() {
        const nivel = nivelSelect.value;
        gradoGroups.forEach(g => {
            g.style.display = g.dataset.nivel === nivel ? 'block' : 'none';
        });
    }

    nivelSelect.addEventListener('change', mostrarGrados);
    mostrarGrados();
});
</script>

    <?php include "includes/footer.php"; ?>
