<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$mensaje = "";
$error = "";

$profesores = $conexion->query(
    "SELECT id, nombre FROM usuarios WHERE rol = 'profesor' ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$cursos = $conexion->query(
    "SELECT id, grado, nombre, nivel FROM cursos ORDER BY FIELD(nivel,'preescolar','primaria','secundaria'), FIELD(grado,
        'maternal','prejardin','jardin','transicion',
        'primero','segundo','tercero','cuarto','quinto',
        'sexto','septimo','octavo','noveno','decimo','undecimo'), nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$asignaturas = $conexion->query(
    "SELECT id, nombre, nivel FROM asignaturas ORDER BY nivel, nombre"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["eliminar"])) {
        $stmt = $conexion->prepare("DELETE FROM profesor_curso_asignatura WHERE id = ?");
        $stmt->execute([$_POST["eliminar"]]);
        $mensaje = "Asignación eliminada correctamente.";
    } else {
        $profesor = $_POST["profesor"];
        $curso = $_POST["curso"];
        $asignatura = $_POST["asignatura"];

        $sql = "INSERT IGNORE INTO profesor_curso_asignatura (profesor_id, curso_id, asignatura_id)
                VALUES (:profesor, :curso, :asignatura)";

        $stmt = $conexion->prepare($sql);

        try {
            $stmt->execute([
                ":profesor" => $profesor,
                ":curso" => $curso,
                ":asignatura" => $asignatura,
            ]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "Asignación creada correctamente.";
            } else {
                $error = "Esta asignación ya existe.";
            }
        } catch (PDOException $e) {
            $error = "Error al asignar.";
        }
    }
}

$asignaciones = $conexion->query("
    SELECT pca.id, u.nombre AS profesor_nombre, 
           CONCAT(c.grado, ' ', c.nombre) AS curso_nombre, c.nivel AS curso_nivel,
           a.nombre AS asignatura_nombre, a.nivel AS asignatura_nivel
    FROM profesor_curso_asignatura pca
    JOIN usuarios u ON pca.profesor_id = u.id
    JOIN cursos c ON pca.curso_id = c.id
    JOIN asignaturas a ON pca.asignatura_id = a.id
    ORDER BY u.nombre, c.grado, a.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Asignar Materias";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Asignar Materias</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Asignación Académica</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Nueva Asignación</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Profesor</label>
                                <select name="profesor" class="form-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($profesores as $p): ?>
                                        <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Curso</label>
                                <select name="curso" class="form-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <?php
                                            $nivelLabel = match($c['nivel']) {
                                                'preescolar' => 'Preescolar',
                                                'primaria'   => 'Primaria',
                                                default      => 'Secundaria',
                                            };
                                        ?>
                                        <option value="<?= $c["id"] ?>">
                                            <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"] . ' (' . $nivelLabel . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Asignatura</label>
                                <select name="asignatura" class="form-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($asignaturas as $a): ?>
                                        <option value="<?= $a["id"] ?>" data-nivel="<?= $a["nivel"] ?>">
                                            <?= htmlspecialchars($a["nombre"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-plus-circle me-2"></i>Asignar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="gca-card p-4">
                        <h5 style="font-family:'Cormorant Garamond',serif;font-weight:700;margin:0 0 16px;">
                            <i class="bi bi-list-check" style="color:var(--gold);margin-right:8px;"></i>
                            Asignaciones Actuales
                        </h5>

                        <?php if (count($asignaciones) === 0): ?>
                            <div class="empty-state p-4">
                                <i class="bi bi-inbox" style="font-size:40px;color:#ddd;"></i>
                                <p class="text-muted mb-0">No hay asignaciones registradas.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table gca-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Profesor</th>
                                            <th>Curso</th>
                                            <th>Materia</th>
                                            <th style="width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignaciones as $a): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($a['profesor_nombre']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars(ucfirst($a['curso_nombre'])) ?>
                                                    <small class="text-muted d-block" style="font-size:10px;">
                                                        <?= match($a['curso_nivel']) {
                                                            'preescolar' => 'Preescolar',
                                                            'primaria'   => 'Primaria',
                                                            default      => 'Secundaria',
                                                        } ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($a['asignatura_nombre']) ?>
                                                </td>
                                                <td>
                                                    <form method="POST" id="deleteForm<?= $a['id'] ?>">
                                                        <input type="hidden" name="eliminar" value="<?= $a['id'] ?>">
                                                        <button type="button" class="btn btn-sm btn-outline-danger border-0"
                                                                onclick="showConfirm('¿Eliminar esta asignación?',()=>document.getElementById('deleteForm<?= $a['id'] ?>').submit());"
                                                                style="font-size:16px;padding:2px 6px;">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
