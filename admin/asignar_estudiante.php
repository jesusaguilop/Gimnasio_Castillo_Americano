<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/csrf_helper.php";

$mensaje = "";

/* Primero se elige el GRADO actual (o "sin curso"): con cientos de estudiantes
   la lista plana era inservible, y TomSelect solo muestra 100 opciones. */
$grado = trim($_POST["grado"] ?? $_GET["grado"] ?? "");

/* Grados con estudiantes, en el orden del colegio (+ cuántos sin curso) */
$grados = $conexion->query("
    SELECT c.grado, COUNT(e.id) AS total
    FROM cursos c
    LEFT JOIN estudiantes e ON e.curso_id = c.id
    GROUP BY c.grado
    ORDER BY
        CASE c.grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8
            WHEN 'quinto' THEN 9 WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12
            WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END
")->fetchAll(PDO::FETCH_ASSOC);
$sinCurso = (int) $conexion->query("SELECT COUNT(*) FROM estudiantes WHERE curso_id IS NULL")->fetchColumn();

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
    $estudiante_id = (int) ($_POST["estudiante_id"] ?? 0);
    $curso_id = (int) ($_POST["curso_id"] ?? 0);

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

/* Estudiantes del grado elegido, con su curso actual. Se consulta DESPUÉS de
   guardar para que la lista ya muestre el curso nuevo. */
$estudiantes = [];
if ($grado === "_sin") {
    $estudiantes = $conexion->query(
        "SELECT e.id, e.nombre, NULL AS grado, NULL AS curso_nombre
         FROM estudiantes e WHERE e.curso_id IS NULL ORDER BY e.nombre"
    )->fetchAll(PDO::FETCH_ASSOC);
} elseif ($grado !== "") {
    $stmtE = $conexion->prepare(
        "SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre
         FROM estudiantes e
         JOIN cursos c ON e.curso_id = c.id
         WHERE c.grado = ?
         ORDER BY c.nombre, e.nombre"
    );
    $stmtE->execute([$grado]);
    $estudiantes = $stmtE->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = "Asignar Estudiante";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Asignar Estudiante</h5>
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
                            <div class="alert alert-<?= str_starts_with($mensaje, "Error") ? "danger" : "success" ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Paso 1: grado actual del estudiante -->
                        <form method="GET" class="mb-3">
                            <label class="form-label fw-medium" for="f_grado">1. Grado actual del estudiante</label>
                            <div class="d-flex gap-2">
                                <select id="f_grado" name="grado" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Elija un grado --</option>
                                    <?php if ($sinCurso > 0): ?>
                                        <option value="_sin" <?= $grado === "_sin" ? "selected" : "" ?>>Sin curso asignado (<?= $sinCurso ?>)</option>
                                    <?php endif; ?>
                                    <?php foreach ($grados as $g): ?>
                                        <option value="<?= htmlspecialchars($g["grado"]) ?>" <?= $grado === $g["grado"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars(ucfirst($g["grado"])) ?> (<?= (int) $g["total"] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <noscript><button type="submit" class="btn-outline-gca btn-gca-sm">Ver</button></noscript>
                            </div>
                        </form>

                        <?php if ($grado === ""): ?>
                            <p class="small text-muted mb-0">Elija primero el grado para ver sus estudiantes.</p>
                        <?php elseif (count($estudiantes) === 0): ?>
                            <p class="small text-muted mb-0">No hay estudiantes en este grado.</p>
                        <?php else: ?>
                        <form method="POST">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="grado" value="<?= htmlspecialchars($grado) ?>">
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_estudiante_id">2. Estudiante <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="f_estudiante_id" name="estudiante_id" class="form-select" required>
                                    <option value="">-- Elija un estudiante --</option>
                                    <?php foreach ($estudiantes as $e):
                                        $actual = $e['grado'] ? ucfirst($e['grado']) . ' ' . $e['curso_nombre'] : 'sin curso';
                                    ?>
                                        <option value="<?= (int) $e["id"] ?>"><?= htmlspecialchars($e["nombre"] . '  —  (' . $actual . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_curso_id">3. Nuevo curso <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="f_curso_id" name="curso_id" class="form-select" required>
                                    <option value="">-- Elija un curso --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c["id"] ?>">
                                            <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"] . ' · ' . ucfirst($c["nivel"])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-journal-check me-2" aria-hidden="true"></i>Asignar a Curso
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>