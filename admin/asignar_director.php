<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");

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
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card card-form p-4">
                        <div class="header-box">
                            <i class="bi bi-person-badge" aria-hidden="true"></i>
                            <div>
                                <h4>Asignar Director</h4>
                                <span>Elija el curso y el profesor que será su director de grupo.</span>
                            </div>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-success" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_profesor_id">Profesor</label>
                                <select id="f_profesor_id" name="profesor_id" class="form-select" required>
                                    <option value="">— Seleccione un profesor —</option>
                                    <?php foreach ($profesores as $p): ?>
                                        <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_curso_id">Curso</label>
                                <select id="f_curso_id" name="curso_id" class="form-select" required>
                                    <option value="">— Seleccione un curso —</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c["id"] ?>">
                                            <?= htmlspecialchars(ucfirst($c['nivel'] ?? '') . ' - ' . $c['grado'] . ' ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-gca w-100 justify-content-center">
                                <i class="bi bi-check2" aria-hidden="true"></i> Asignar director
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="gca-card p-4">
                        <div class="section-header">
                            <h4><i class="bi bi-list-check" aria-hidden="true"></i> Directores asignados <span class="count-pill"><?= count($asignaciones) ?></span></h4>
                        </div>
                        <?php if (count($asignaciones) === 0): ?>
                            <div class="empty-state">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <h3>Sin directores de grupo</h3>
                                <p>No hay directores asignados aún.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table gca-table mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Profesor</th>
                                            <th scope="col">Curso</th>
                                            <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignaciones as $a): ?>
                                            <tr>
                                                <td class="cell-strong"><?= htmlspecialchars($a['profesor']) ?></td>
                                                <td><?= htmlspecialchars(ucfirst($a['nivel'] ?? '') . ' - ' . $a['grado'] . ' ' . $a['curso']) ?></td>
                                                <td class="text-end">
                                                    <button type="button" onclick="eliminarDirector(<?= (int) $a['profesor_id'] ?>, <?= (int) $a['curso_id'] ?>)"
                                                       class="btn-action btn-delete" title="Quitar director" aria-label="Quitar a <?= htmlspecialchars($a['profesor']) ?> como director de grupo">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
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
            showConfirm('¿Quitar a este profesor como director de grupo? El curso quedará sin director.', function() {
                const fd = new FormData();
                fd.append('action', 'eliminar');
                fd.append('profesor_id', profesorId);
                fd.append('curso_id', cursoId);
                fd.append('_csrf_token', <?= json_encode(generar_token_csrf()) ?>);
                fetch('asignar_director.php', { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(res => {
                        if (res === 'ok') location.reload();
                        else GCA.toast('No se pudo eliminar la asignación. Intente de nuevo.', { tipo: 'bad' });
                    })
                    .catch(() => GCA.toast('No se pudo conectar con el servidor. Intente de nuevo.', { tipo: 'bad' }));
            });
        }
    </script>

    <?php include "includes/footer.php"; ?>
