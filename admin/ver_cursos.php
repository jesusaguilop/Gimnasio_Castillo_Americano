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
        $stmt = $conexion->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $mensaje = "Curso eliminado correctamente";
    } catch (PDOException $e) {
        $mensaje = "No se puede eliminar el curso porque tiene dependencias.";
    }
}

$cursos = $conexion
    ->query("SELECT id, nombre, grado, nivel FROM cursos ORDER BY
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END, nombre")
    ->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Cursos";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Cursos</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Cursos</p>
            </div>
        </div>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-journal-check"></i> Cursos registrados</h4>
                    <a href="crear_curso.php" class="btn-gca btn-gca-sm">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Curso
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
                                <th>Nivel</th>
                                <th>Grado</th>
                                <th>Curso</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos as $c):
                                $nivelColor = $c['nivel'] === 'preescolar' ? '#7b1fa2' : ($c['nivel'] === 'primaria' ? '#1565c0' : '#2e7d32');
                                $nivelLabel = $c['nivel'] ? ucfirst($c['nivel']) : '—';
                            ?>
                                <tr>
                                    <td><span style="background:<?= $nivelColor ?>;font-size:11px;" class="badge fw-normal px-3 py-2"><?= htmlspecialchars($nivelLabel) ?></span></td>
                                    <td><span class="badge bg-dark fw-normal px-3 py-2"><?= htmlspecialchars(ucfirst($c["grado"])) ?></span></td>
                                    <td><span class="fw-semibold">Curso <?= htmlspecialchars($c["nombre"]) ?></span></td>
                                    <td class="text-end">
                                        <a href="editar_curso.php?id=<?= $c["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="ver_cursos.php?id=<?= $c["id"] ?>&confirmar=1"
                                            class="btn-action btn-delete" title="Eliminar"
                                            onclick="event.preventDefault();showConfirm('¿Seguro que deseas eliminar este curso?',()=>window.location.href=this.href);">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($cursos) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No hay cursos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
