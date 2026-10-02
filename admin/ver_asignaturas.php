<?php
session_start();
require_once "../config/database.php";
require_once "../lib/csrf_helper.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

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
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Asignaturas</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Asignaturas</p>
            </div>
        </div>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-book-fill"></i> Asignaturas registradas</h4>
                    <a href="crear_asignatura.php" class="btn-gca btn-gca-sm">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Asignatura
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table gca-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Área</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Nivel</th>
                                <th scope="col">Grados</th>
                                <th scope="col" style="width:100px;">Int. Horaria</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaturas as $a):
                                $nivelColor = match($a["nivel"]) {
                                    'preescolar' => '#7b1fa2',
                                    'primaria'   => '#1565c0',
                                    default      => '#2e7d32',
                                };
                                $nivelLabel = match($a["nivel"]) {
                                    'preescolar' => 'Preescolar',
                                    'primaria'   => 'Primaria',
                                    default      => 'Secundaria',
                                };
                            ?>
                                <tr>
                                    <td><span class="badge fw-normal px-3 py-2" style="background:#c9a24d;font-size:11px;"><?= htmlspecialchars($a["area"]) ?></span></td>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($a["nombre"]) ?></span></td>
                                    <td><span class="badge fw-normal px-3 py-2" style="background:<?= $nivelColor ?>;font-size:11px;"><?= $nivelLabel ?></span></td>
                                    <td><span class="text-muted" style="font-size:13px;"><?= $a["grados"] ? htmlspecialchars($a["grados"]) : '—' ?></span></td>
                                    <td><span class="fw-semibold"><?= (int)$a["intensidad_horaria"] ?> h</span></td>
                                    <td class="text-end">
                                        <a href="editar_asignatura.php?id=<?= $a["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form method="POST" style="display:inline" id="deleteForm<?= $a["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $a["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar"
                                                onclick="showConfirm('¿Eliminar esta asignatura? Si ya tiene notas registradas no se podrá eliminar.',()=>document.getElementById('deleteForm<?= $a["id"] ?>').submit());">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($asignaturas) === 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No hay asignaturas registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
