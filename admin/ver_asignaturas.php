<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
if (isset($_SESSION["success_msg"])) {
    $mensaje = $_SESSION["success_msg"];
    unset($_SESSION["success_msg"]);
}

if (isset($_GET["id"]) && isset($_GET["confirmar"])) {
    $id = $_GET["id"];
    try {
        $stmt = $conexion->prepare("DELETE FROM asignaturas WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $mensaje = "Asignatura eliminada correctamente";
    } catch (PDOException $e) {
        $mensaje = "No se puede eliminar la asignatura porque tiene dependencias.";
    }
}

$asignaturas = $conexion
    ->query("SELECT id, nombre, area, nivel, grado FROM asignaturas ORDER BY FIELD(nivel,'preescolar','primaria','secundaria'), area, nombre")
    ->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Asignaturas";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Asignaturas</h5>
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
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table gca-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th>Nombre</th>
                                <th>Nivel</th>
                                <th>Grado</th>
                                <th class="text-end">Acciones</th>
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
                                    <td><span class="text-muted" style="font-size:13px;"><?= $a["grado"] ? htmlspecialchars(ucfirst($a["grado"])) : '—' ?></span></td>
                                    <td class="text-end">
                                        <a href="editar_asignatura.php?id=<?= $a["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="ver_asignaturas.php?id=<?= $a["id"] ?>&confirmar=1"
                                            class="btn-action btn-delete" title="Eliminar"
                                            onclick="event.preventDefault();showConfirm('¿Seguro que deseas eliminar esta asignatura?',()=>window.location.href=this.href);">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($asignaturas) === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No hay asignaturas registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
