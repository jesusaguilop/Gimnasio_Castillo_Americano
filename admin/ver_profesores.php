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
    // Borrar un profesor borraba en cascada todas las notas que digitó
    $deps = contarDependencias($conexion, [
        "nota(s) registrada(s)"   => "SELECT COUNT(*) FROM notas WHERE profesor_id = ?",
        "materia(s) asignada(s)"  => "SELECT COUNT(*) FROM profesor_curso_asignatura WHERE profesor_id = ?",
        "dirección(es) de grupo"  => "SELECT COUNT(*) FROM directores_grupo WHERE profesor_id = ?",
        "boletín(es) generado(s)" => "SELECT COUNT(*) FROM boletines_pdf WHERE generado_por = ?",
    ], $id);
    if ($deps) {
        $mensaje = "No se eliminó: este profesor tiene " . implode(", ", $deps)
                 . ". Para no perder esa información, primero reasigne sus materias y su dirección de grupo a otro profesor.";
        $tipo = "warning";
    } else {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'profesor'");
        $stmt->execute([":id" => $id]);
        $mensaje = $stmt->rowCount() ? "Profesor eliminado correctamente" : "No se encontró el registro.";
        $tipo = $stmt->rowCount() ? "success" : "warning";
    }
}

/* Búsqueda por nombre o correo (sin distinguir mayúsculas) */
$busqueda = trim($_GET["q"] ?? "");
$sqlLista = "SELECT id, nombre, email FROM usuarios WHERE rol = 'profesor'";
$paramsLista = [];
if ($busqueda !== "") {
    $sqlLista .= " AND (nombre ILIKE ? OR email ILIKE ?)";
    $like = "%" . $busqueda . "%";
    $paramsLista = [$like, $like];
}
$stmtLista = $conexion->prepare($sqlLista . " ORDER BY nombre");
$stmtLista->execute($paramsLista);
$profesores = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Profesores";
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
                    <h4><i class="bi bi-person-workspace" aria-hidden="true"></i> Profesores <span class="count-pill"><?= count($profesores) ?></span></h4>
                    <a href="crear_profesor.php" class="btn-gca">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo profesor
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar profesores">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar por nombre o correo</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control"
                                placeholder="Escriba un nombre o correo..."
                                value="<?= htmlspecialchars($busqueda) ?>" data-live-filter="#tablaProfesores" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($busqueda !== ""): ?>
                            <a href="ver_profesores.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="table-count mb-2" role="status">
                    <?= count($profesores) ?> resultado(s)<?= $busqueda !== "" ? ' para «' . htmlspecialchars($busqueda) . '»' : '' ?>
                </p>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaProfesores">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profesores as $p): ?>
                                <tr>
                                    <td class="cell-strong"><?= htmlspecialchars($p["nombre"]) ?></td>
                                    <td><?= htmlspecialchars($p["email"]) ?></td>
                                    <td class="text-end"><div class="row-actions">
                                        <a href="editar_profesor.php?id=<?= $p["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar a <?= htmlspecialchars($p["nombre"]) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" id="delForm<?= $p["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $p["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar a <?= htmlspecialchars($p["nombre"]) ?>"
                                                onclick="showConfirm('¿Eliminar este profesor? Si ya tiene notas, materias o dirección de grupo no se podrá eliminar.',()=>document.getElementById('delForm<?= $p["id"] ?>').submit());">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($profesores) === 0): ?>
                                <tr><td colspan="3"><div class="empty-state empty-state-sm"><i class="bi bi-person-workspace" aria-hidden="true"></i><p><?= $busqueda !== "" ? "No se encontraron profesores con esa búsqueda." : "No hay profesores registrados." ?></p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
