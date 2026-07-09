<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$mensaje = Session::getSuccess() ?: "";
if (isset($_GET["id"]) && isset($_GET["confirmar"])) {
    $id = (int)$_GET["id"];
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

$sql = "SELECT e.id, e.nombre, e.documento, e.curso_id, e.usuario_id,
               u.email, c.grado, c.nombre AS curso_nombre, c.nivel
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (e.nombre LIKE ? OR e.documento LIKE ?)";
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
        WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'undecimo' THEN 15
        ELSE 16
    END, e.nombre";

$estudiantes = db()->fetchAll($sql, $params);

$grados = db()->fetchAll(
    "SELECT DISTINCT grado FROM cursos ORDER BY
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'undecimo' THEN 15
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
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Estudiantes</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Estudiantes</p>
            </div>
        </div>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-mortarboard-fill"></i> Estudiantes registrados</h4>
                    <a href="crear_estudiante.php" class="btn-gca btn-gca-sm">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Estudiante
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-medium small mb-1">Buscar por nombre o documento</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Escriba nombre o documento..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium small mb-1">Filtrar por grado</label>
                        <select name="grado" class="form-select form-select-sm">
                            <option value="">Todos los grados</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>" <?= $grado_filter === $g ? "selected" : "" ?>>
                                    <?= htmlspecialchars(ucfirst($g)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-gca btn-gca-sm flex-fill">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="ver_estudiantes.php" class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                        <table class="table gca-table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Curso</th>
                                    <th>Email</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $e):
                                    $nivelColor = $e['nivel'] === 'preescolar' ? '#7b1fa2' : ($e['nivel'] === 'primaria' ? '#1565c0' : '#2e7d32');
                                    $cursoLabel = $e['grado'] ? ucfirst($e['grado']) . ' - ' . $e['curso_nombre'] : '—';
                                ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($e["nombre"]) ?></span></td>
                                        <td><?= htmlspecialchars($e["documento"] ?? "—") ?></td>
                                        <td><?php if ($e["grado"]): ?><span style="background:<?= $nivelColor ?>;font-size:11px;" class="badge fw-normal px-2 py-1"><?= htmlspecialchars($cursoLabel) ?></span><?php else: ?>—<?php endif; ?></td>
                                        <td><?= htmlspecialchars($e["email"] ?? "—") ?></td>
                                        <td class="text-end">
                                            <a href="editar_estudiante.php?eid=<?= $e["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="ver_estudiantes.php?id=<?= $e["id"] ?>&confirmar=1"
                                                class="btn-action btn-delete" title="Eliminar"
                                                onclick="event.preventDefault();showConfirm('¿Seguro que deseas eliminar este estudiante?',()=>window.location.href=this.href);">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($estudiantes) === 0): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No se encontraron estudiantes.</td></tr>
                                <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
