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
    // Borrar un curso borraba en cascada todas sus notas
    $deps = contarDependencias($conexion, [
        "estudiante(s)"         => "SELECT COUNT(*) FROM estudiantes WHERE curso_id = ?",
        "nota(s) registrada(s)" => "SELECT COUNT(*) FROM notas WHERE curso_id = ?",
    ], $id);
    if ($deps) {
        $mensaje = "No se eliminó: este curso tiene " . implode(", ", $deps)
                 . ". Primero pase a los estudiantes a otro curso en «Asignar estudiante a curso».";
        $tipo = "warning";
    } else {
        try {
            $stmt = $conexion->prepare("DELETE FROM cursos WHERE id = :id");
            $stmt->execute([":id" => $id]);
            $mensaje = $stmt->rowCount() ? "Curso eliminado correctamente" : "No se encontró el registro.";
            $tipo = $stmt->rowCount() ? "success" : "warning";
        } catch (PDOException $e) {
            error_log("[ver_cursos] " . $e->getMessage());
            $mensaje = "No se pudo eliminar el curso.";
            $tipo = "danger";
        }
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
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-collection" aria-hidden="true"></i> Cursos <span class="count-pill"><?= count($cursos) ?></span></h4>
                    <a href="crear_curso.php" class="btn-gca">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo curso
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
                                <th scope="col">Nivel</th>
                                <th scope="col">Grado</th>
                                <th scope="col">Curso</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos as $c):
                                $nivelClase = in_array($c['nivel'], ['preescolar', 'primaria'], true) ? 'lvl-' . $c['nivel'] : 'lvl-secundaria';
                                $nivelLabel = $c['nivel'] ? ucfirst($c['nivel']) : '—';
                            ?>
                                <tr>
                                    <td><span class="gca-badge <?= $nivelClase ?>"><?= htmlspecialchars($nivelLabel) ?></span></td>
                                    <td class="cell-strong"><?= htmlspecialchars(ucfirst($c["grado"])) ?></td>
                                    <td>Curso <?= htmlspecialchars($c["nombre"]) ?></td>
                                    <td class="text-end"><div class="row-actions">
                                        <a href="editar_curso.php?id=<?= $c["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar el curso <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"]) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" id="delForm<?= $c["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $c["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar el curso <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"]) ?>"
                                                onclick="showConfirm('¿Eliminar este curso? Si tiene estudiantes o notas no se podrá eliminar.',()=>document.getElementById('delForm<?= $c["id"] ?>').submit());">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($cursos) === 0): ?>
                                <tr><td colspan="4"><div class="empty-state empty-state-sm"><i class="bi bi-collection" aria-hidden="true"></i><h3>No hay cursos registrados</h3><p>Cree el primer curso para empezar a matricular estudiantes.</p><a href="crear_curso.php" class="btn-gca btn-gca-sm"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo curso</a></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
