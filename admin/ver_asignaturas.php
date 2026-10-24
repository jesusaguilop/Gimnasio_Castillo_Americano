<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");
require_once "../lib/csrf_helper.php";

$mensaje = "";
if (isset($_SESSION["success_msg"])) {
    $mensaje = $_SESSION["success_msg"];
    unset($_SESSION["success_msg"]);
}

$tipo = "success";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_id"])) {
    $id = (int) $_POST["eliminar_id"];
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intente de nuevo.";
        $tipo = "danger";
    } else {
        // Las llaves foráneas borran EN CASCADA notas y logros de la
        // asignatura: si hay calificaciones, se bloquea en vez de perderlas.
        $q = $conexion->prepare("SELECT (SELECT COUNT(*) FROM notas WHERE asignatura_id = ?)
                                      + (SELECT COUNT(*) FROM logros WHERE asignatura_id = ?)");
        $q->execute([$id, $id]);
        if ((int) $q->fetchColumn() > 0) {
            $mensaje = "No se eliminó: esta asignatura ya tiene notas o logros registrados. Eliminarla borraría esas calificaciones.";
            $tipo = "warning";
        } else {
            try {
                $stmt = $conexion->prepare("DELETE FROM asignaturas WHERE id = :id");
                $stmt->execute([":id" => $id]);
                $mensaje = $stmt->rowCount() ? "Asignatura eliminada correctamente" : "No se encontró el registro.";
                $tipo = $stmt->rowCount() ? "success" : "warning";
            } catch (PDOException $e) {
                error_log("[ver_asignaturas] " . $e->getMessage());
                $mensaje = "No se pudo eliminar la asignatura.";
                $tipo = "danger";
            }
        }
    }
}

$asignaturas = $conexion
    ->query("SELECT a.id, a.nombre, a.area, a.nivel, a.intensidad_horaria,
                    (SELECT STRING_AGG(ag.grado, ', ' ORDER BY ag.grado)
                       FROM asignatura_grado ag WHERE ag.asignatura_id = a.id) AS grados
             FROM asignaturas a
             ORDER BY
                CASE a.nivel WHEN 'preescolar' THEN 1 WHEN 'primaria' THEN 2 WHEN 'secundaria' THEN 3 ELSE 4 END,
                a.area, a.nombre")
    ->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Asignaturas";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-book" aria-hidden="true"></i> Asignaturas <span class="count-pill"><?= count($asignaturas) ?></span></h4>
                    <a href="crear_asignatura.php" class="btn-gca">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva asignatura
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table gca-table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Área</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Nivel</th>
                                <th scope="col">Grados</th>
                                <th scope="col">Int. horaria</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaturas as $a):
                                $nivelClase = match($a["nivel"]) {
                                    'preescolar' => 'lvl-preescolar',
                                    'primaria'   => 'lvl-primaria',
                                    default      => 'lvl-secundaria',
                                };
                                $nivelLabel = match($a["nivel"]) {
                                    'preescolar' => 'Preescolar',
                                    'primaria'   => 'Primaria',
                                    default      => 'Secundaria',
                                };
                            ?>
                                <tr>
                                    <td><span class="gca-badge is-gold"><?= htmlspecialchars($a["area"]) ?></span></td>
                                    <td class="cell-strong"><?= htmlspecialchars($a["nombre"]) ?></td>
                                    <td><span class="gca-badge <?= $nivelClase ?>"><?= $nivelLabel ?></span></td>
                                    <td class="text-muted small"><?= $a["grados"] ? htmlspecialchars($a["grados"]) : '—' ?></td>
                                    <td class="text-nowrap"><?= (int)$a["intensidad_horaria"] ?> h</td>
                                    <td class="text-end"><div class="row-actions">
                                        <a href="editar_asignatura.php?id=<?= $a["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar <?= htmlspecialchars($a["nombre"]) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" id="deleteForm<?= $a["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $a["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar <?= htmlspecialchars($a["nombre"]) ?>"
                                                onclick="showConfirm('¿Eliminar esta asignatura? Si ya tiene notas registradas no se podrá eliminar.',()=>document.getElementById('deleteForm<?= $a["id"] ?>').submit());">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($asignaturas) === 0): ?>
                                <tr><td colspan="6"><div class="empty-state empty-state-sm"><i class="bi bi-book" aria-hidden="true"></i><h3>No hay asignaturas registradas</h3><p>Cree las asignaturas para poder asignarlas a los profesores.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
