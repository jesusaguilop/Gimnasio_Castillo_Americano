<?php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../lib/csrf_helper.php';
checkRole('admin');

use App\Base\Session;

$mensaje = Session::getSuccess() ?: "";
// Recién creado un estudiante: se ofrece ir directo a vincular su padre
$vincularGrado = Session::flash('vincular_padre_grado');
// Borrado por POST + token CSRF. Antes se disparaba con un GET
// (ver_*.php?id=N&confirmar=1): cualquier enlace o <img> que cargara
// el admin estando logueado podía eliminar registros.
$puedeEliminar = $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_id"]);
if ($puedeEliminar && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intenta de nuevo.";
    $puedeEliminar = false;
}
if ($puedeEliminar) {
    $id = (int)(int) $_POST["eliminar_id"];
    try {
        $est = db()->fetch("SELECT usuario_id FROM estudiantes WHERE id = ?", [$id]);

        db()->beginTransaction();
        db()->query("DELETE FROM logros WHERE estudiante_id = ?", [$id]);
        db()->query("DELETE FROM notas WHERE estudiante_id = ?", [$id]);
        db()->query("DELETE FROM boletines_pdf WHERE estudiante_id = ?", [$id]);

        if ($est && $est["usuario_id"]) {
            db()->query("DELETE FROM usuarios WHERE id = ? AND rol = 'estudiante'", [$est["usuario_id"]]);
        }
        db()->query("DELETE FROM estudiantes WHERE id = ?", [$id]);
        db()->commit();
        $mensaje = "Estudiante eliminado correctamente";
    } catch (PDOException $e) {
        db()->rollBack();
        $mensaje = "Error al eliminar el estudiante.";
    }
}

$search = trim($_GET["q"] ?? "");
$grado_filter = trim($_GET["grado"] ?? "");

$sql = "SELECT e.id, e.nombre, e.documento, e.fecha_nacimiento, e.curso_id,
               c.grado, c.nombre AS curso_nombre, c.nivel
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (e.nombre ILIKE ? OR e.documento ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($grado_filter !== "") {
    $sql .= " AND c.grado = ?";
    $params[] = $grado_filter;
}

$sql .= " ORDER BY
    CASE c.grado
        WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
        WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
        WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
        ELSE 16
    END, e.nombre";

$estudiantes = db()->fetchAll($sql, $params);

$grados = db()->fetchAll(
    // GROUP BY en vez de DISTINCT: PostgreSQL exige que la expresión del
    // ORDER BY esté en la lista del SELECT si se usa DISTINCT (con GROUP BY no).
    "SELECT grado FROM cursos GROUP BY grado ORDER BY
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END"
);
$grados = array_column($grados, 'grado');

$pageTitle = "Gestión de Estudiantes";
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
                    <h4><i class="bi bi-mortarboard" aria-hidden="true"></i> Estudiantes registrados</h4>
                    <a href="crear_estudiante.php" class="btn-gca">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo estudiante
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= str_starts_with($mensaje, "Error") ? "danger" : "success" ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <?php if ($vincularGrado !== null): ?>
                            <div class="mt-2">
                                <a href="asignar_padre.php<?= $vincularGrado !== '' ? '?grado=' . urlencode($vincularGrado) : '' ?>" class="btn-gca btn-gca-sm">
                                    <i class="bi bi-people-fill me-1" aria-hidden="true"></i> Vincular padre o acudiente
                                </a>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar estudiantes">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar por nombre o documento</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control"
                                placeholder="Escriba nombre o documento..."
                                value="<?= htmlspecialchars($search) ?>"
                                data-live-filter="#tablaEstudiantes" data-live-count="#cuentaEstudiantes" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff">
                        <label class="form-label" for="f_grado">Grado</label>
                        <select id="f_grado" name="grado" class="form-select">
                            <option value="">Todos los grados</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>" <?= $grado_filter === $g ? "selected" : "" ?>>
                                    <?= htmlspecialchars(ucfirst($g)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                        </button>
                        <?php if ($search !== "" || $grado_filter !== ""): ?>
                            <a href="ver_estudiantes.php" class="btn-outline-gca">
                                <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="table-count mb-2" id="cuentaEstudiantes" role="status" aria-live="polite"><?= count($estudiantes) ?> registros</p>

                <div class="table-responsive">
                        <table class="table gca-table align-middle" id="tablaEstudiantes">
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Documento</th>
                                    <th scope="col" class="d-none d-md-table-cell">Fecha nac.</th>
                                    <th scope="col">Curso</th>
                                    <th scope="col" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $e):
                                    $nivelClase = in_array($e['nivel'], ['preescolar', 'primaria'], true) ? 'lvl-' . $e['nivel'] : 'lvl-secundaria';
                                    $cursoLabel = $e['grado'] ? ucfirst($e['grado']) . ' - ' . $e['curso_nombre'] : '—';
                                ?>
                                    <tr>
                                        <td class="cell-strong"><?= htmlspecialchars($e["nombre"]) ?></td>
                                        <td><?= htmlspecialchars($e["documento"] ?? "—") ?></td>
                                        <td class="d-none d-md-table-cell text-nowrap"><?= htmlspecialchars($e["fecha_nacimiento"] ?? "—") ?></td>
                                        <td><?php if ($e["grado"]): ?><span class="gca-badge <?= $nivelClase ?>"><?= htmlspecialchars($cursoLabel) ?></span><?php else: ?><span class="gca-badge is-warn">Sin curso</span><?php endif; ?></td>
                                        <td class="text-end"><div class="row-actions">
                                            <a href="editar_estudiante.php?eid=<?= $e["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar a <?= htmlspecialchars($e["nombre"]) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                            <form method="POST" id="delForm<?= $e["id"] ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="eliminar_id" value="<?= $e["id"] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar a <?= htmlspecialchars($e["nombre"]) ?>"
                                                onclick="showConfirm('¿Eliminar este estudiante? También se borrarán sus notas, logros y boletines. No se puede deshacer.',()=>document.getElementById('delForm<?= $e["id"] ?>').submit());">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($estudiantes) === 0): ?>
                                    <tr><td colspan="5"><div class="empty-state empty-state-sm"><i class="bi bi-mortarboard" aria-hidden="true"></i><h3>No se encontraron estudiantes</h3><p>Pruebe con otro nombre, documento o grado.</p></div></td></tr>
                                <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
