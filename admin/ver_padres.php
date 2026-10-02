<?php
session_start();
require_once "../config/database.php";
require_once __DIR__ . "/../lib/csrf_helper.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
if (isset($_SESSION["success_msg"])) {
    $mensaje = $_SESSION["success_msg"];
    unset($_SESSION["success_msg"]);
}

// Borrado por POST + token CSRF. Antes se disparaba con un GET
// (ver_*.php?id=N&confirmar=1): cualquier enlace o <img> que cargara
// el admin estando logueado podía eliminar registros.
$puedeEliminar = $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_id"]);

/**
 * Cuenta filas que dependen del registro. Las llaves foráneas borran EN
 * CASCADA (notas, logros, boletines…), así que antes de eliminar se revisa
 * y, si hay datos, se bloquea con un mensaje en vez de perderlos en silencio.
 */
function contarDependencias(PDO $conexion, array $consultas, int $id): array
{
    $encontradas = [];
    foreach ($consultas as $etiqueta => $sql) {
        $q = $conexion->prepare($sql);
        $q->execute([$id]);
        $n = (int) $q->fetchColumn();
        if ($n > 0) {
            $encontradas[] = "$n $etiqueta";
        }
    }
    return $encontradas;
}

$tipo = "success";
if ($puedeEliminar && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intenta de nuevo.";
    $tipo = "danger";
    $puedeEliminar = false;
}
if ($puedeEliminar) {
    $id = (int) $_POST["eliminar_id"];
    // Borrar un padre borraba también a sus hijos (y sus notas y boletines)
    $deps = contarDependencias($conexion, [
        "estudiante(s) vinculado(s)" => "SELECT COUNT(*) FROM estudiantes WHERE padre_id = ?",
    ], $id);
    if ($deps) {
        $mensaje = "No se eliminó: este padre tiene " . implode(", ", $deps)
                 . ". Primero vincule a sus hijos con otro padre o acudiente en «Vincular padre con estudiante».";
        $tipo = "warning";
    } else {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'padre'");
        $stmt->execute([":id" => $id]);
        $mensaje = $stmt->rowCount() ? "Padre eliminado correctamente" : "No se encontró el registro.";
        $tipo = $stmt->rowCount() ? "success" : "warning";
    }
}

/* Búsqueda por nombre o correo (sin distinguir mayúsculas) */
$busqueda = trim($_GET["q"] ?? "");
$sqlLista = "SELECT id, nombre, email FROM usuarios WHERE rol = 'padre'";
$paramsLista = [];
if ($busqueda !== "") {
    $sqlLista .= " AND (nombre ILIKE ? OR email ILIKE ?)";
    $like = "%" . $busqueda . "%";
    $paramsLista = [$like, $like];
}
$stmtLista = $conexion->prepare($sqlLista . " ORDER BY nombre");
$stmtLista->execute($paramsLista);
$padres = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Padres";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Padres de Familia</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Padres</p>
            </div>
        </div>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-person-heart"></i> Padres registrados</h4>
                    <a href="crear_padre.php" class="btn-gca btn-gca-sm">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Padre
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="row g-2 mb-3 align-items-end" role="search">
                    <div class="col-md-6">
                        <label class="form-label fw-medium small mb-1" for="f_q">Buscar por nombre o correo</label>
                        <input id="f_q" type="search" name="q" class="form-control form-control-sm"
                            placeholder="Escriba un nombre o correo..."
                            value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-gca btn-gca-sm flex-fill">
                            <i class="bi bi-search" aria-hidden="true"></i> Buscar
                        </button>
                        <?php if ($busqueda !== ""): ?>
                            <a href="ver_padres.php" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="small text-muted mb-2" role="status">
                    <?= count($padres) ?> resultado(s)<?= $busqueda !== "" ? ' para «' . htmlspecialchars($busqueda) . '»' : '' ?>
                </p>

                <div class="table-responsive">
                    <table class="table gca-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Email</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($padres as $p): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($p["nombre"]) ?></span></td>
                                    <td><?= htmlspecialchars($p["email"]) ?></td>
                                    <td class="text-end">
                                        <a href="editar_padre.php?id=<?= $p["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form method="POST" style="display:inline" id="delForm<?= $p["id"] ?>">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="eliminar_id" value="<?= $p["id"] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="showConfirm('¿Eliminar este padre? Se borrará su cuenta de acceso. Si tiene hijos vinculados no se podrá eliminar.',()=>document.getElementById('delForm<?= $p["id"] ?>').submit());">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($padres) === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4"><?= $busqueda !== "" ? "No se encontraron padres con esa búsqueda." : "No hay padres registrados." ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
