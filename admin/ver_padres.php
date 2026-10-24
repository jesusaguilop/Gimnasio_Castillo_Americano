<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");
require_once __DIR__ . "/../lib/csrf_helper.php";

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
        "estudiante(s) vinculado(s)" => "SELECT COUNT(*) FROM estudiante_acudientes WHERE padre_id = ?",
    ], $id);
    if ($deps) {
        $mensaje = "No se eliminó: este padre tiene " . implode(", ", $deps)
                 . ". Primero quite esos vínculos en «Vincular acudientes».";
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
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-people" aria-hidden="true"></i> Padres y acudientes <span class="count-pill"><?= count($padres) ?></span></h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="asignar_padre.php" class="btn-outline-gca">
                            <i class="bi bi-link-45deg" aria-hidden="true"></i> Vincular a estudiante
                        </a>
                        <a href="crear_padre.php" class="btn-gca">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo padre
                        </a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar padres">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar por nombre o correo</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control"
                                placeholder="Escriba un nombre o correo..."
                                value="<?= htmlspecialchars($busqueda) ?>" data-live-filter="#tablaPadres" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($busqueda !== ""): ?>
                            <a href="ver_padres.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="table-count mb-2" role="status">
                    <?= count($padres) ?> resultado(s)<?= $busqueda !== "" ? ' para «' . htmlspecialchars($busqueda) . '»' : '' ?>
                </p>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaPadres">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($padres as $p): ?>
                                <tr>
                                    <td class="cell-strong"><?= htmlspecialchars($p["nombre"]) ?></td>
                                    <td><?= htmlspecialchars($p["email"]) ?></td>
                                    <td class="text-end"><div class="row-actions">
                                        <a href="editar_padre.php?id=<?= $p["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar a <?= htmlspecialchars($p["nombre"]) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" id="delForm<?= $p["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $p["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar a <?= htmlspecialchars($p["nombre"]) ?>"
                                                onclick="showConfirm('¿Eliminar este padre? Se borrará su cuenta de acceso. Si tiene hijos vinculados no se podrá eliminar.',()=>document.getElementById('delForm<?= $p["id"] ?>').submit());">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($padres) === 0): ?>
                                <tr><td colspan="3"><div class="empty-state empty-state-sm"><i class="bi bi-people" aria-hidden="true"></i><p><?= $busqueda !== "" ? "No se encontraron padres con esa búsqueda." : "No hay padres registrados." ?></p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
