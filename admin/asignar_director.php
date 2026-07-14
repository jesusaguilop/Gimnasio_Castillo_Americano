<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/csrf_helper.php";

$mensaje = "";
$error = "";

/* ── Handle AJAX delete FIRST (antes de imprimir nada) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'eliminar') {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        http_response_code(403);
        echo "csrf";
        exit;
    }
    $stmt = $conexion->prepare("DELETE FROM directores_grupo WHERE profesor_id = ? AND curso_id = ?");
    $stmt->execute([$_POST['profesor_id'] ?? null, $_POST['curso_id'] ?? null]);
    echo "ok";
    exit;
}

/* ── Get profesores ── */
$profesores = $conexion->query(
    "SELECT id, nombre FROM usuarios WHERE rol = 'profesor' ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── Get cursos ── */
$cursos = $conexion->query(
    "SELECT id, grado, nombre, nivel FROM cursos ORDER BY nivel, grado, nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── Handle form (alta de director) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['action'])) {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $error = "Error de seguridad. Intente de nuevo.";
    } else {
        $profesor_id = $_POST["profesor_id"] ?? null;
        $curso_id = $_POST["curso_id"] ?? null;

        try {
            $stmt = $conexion->prepare("
                INSERT INTO directores_grupo (profesor_id, curso_id) VALUES (?, ?)
                ON CONFLICT (profesor_id, curso_id) DO NOTHING
            ");
            $stmt->execute([$profesor_id, $curso_id]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "Director de grupo asignado correctamente.";
            } else {
                $error = "Este profesor ya está asignado como director de ese curso.";
            }
        } catch (PDOException $e) {
            error_log("[asignar_director] " . $e->getMessage());
            $error = "Error al realizar la asignación.";
        }
    }
}

/* ── Get current assignments ── */
$asignaciones = $conexion->query("
    SELECT dg.profesor_id, dg.curso_id, u.nombre AS profesor, c.grado, c.nombre AS curso, c.nivel
    FROM directores_grupo dg
    JOIN usuarios u ON dg.profesor_id = u.id
    JOIN cursos c ON dg.curso_id = c.id
    ORDER BY c.nivel, c.grado, u.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Asignar Director de Grupo";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-user-star"></i> Asignar Director de Grupo</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Asignaciones</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card card-form p-4 p-md-5">
                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Asignar Director</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-success border-0 rounded-3"><?= $mensaje ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 rounded-3"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Profesor</label>
                                <select name="profesor_id" class="form-select" required>
                                    <option value="">— Seleccione un profesor —</option>
                                    <?php foreach ($profesores as $p): ?>
                                        <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Curso</label>
                                <select name="curso_id" class="form-select" required>
                                    <option value="">— Seleccione un curso —</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c["id"] ?>">
                                            <?= htmlspecialchars(ucfirst($c['nivel'] ?? '') . ' - ' . $c['grado'] . ' ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-gca w-100 justify-content-center">
                                <i class="bi bi-plus-circle"></i> Asignar Director
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="gca-card p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2" style="color:var(--gold);"></i>Directores Asignados</h6>
                        <?php if (count($asignaciones) === 0): ?>
                            <div class="empty-state py-4">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">No hay directores asignados aún.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table gca-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Profesor</th>
                                            <th>Curso</th>
                                            <th style="width:50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignaciones as $a): ?>
                                            <tr>
                                                <td class="fw-medium"><?= htmlspecialchars($a['profesor']) ?></td>
                                                <td><?= htmlspecialchars(ucfirst($a['nivel'] ?? '') . ' - ' . $a['grado'] . ' ' . $a['curso']) ?></td>
                                                <td>
                                                    <a href="javascript:void(0)" onclick="eliminarDirector(<?= $a['profesor_id'] ?>, <?= $a['curso_id'] ?>)"
                                                       class="btn-action btn-delete" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
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

    <script>
        function eliminarDirector(profesorId, cursoId) {
            showConfirm('¿Eliminar esta asignación de director de grupo?', function() {
                const fd = new FormData();
                fd.append('action', 'eliminar');
                fd.append('profesor_id', profesorId);
                fd.append('curso_id', cursoId);
                fd.append('_csrf_token', <?= json_encode(generar_token_csrf()) ?>);
                fetch('asignar_director.php', { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(res => { if (res === 'ok') location.reload(); });
            });
        }
    </script>

    <?php include "includes/footer.php"; ?>
