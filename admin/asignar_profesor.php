<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");

require_once "../lib/csrf_helper.php";

$mensaje = "";
$error = "";

$profesores = $conexion->query(
    "SELECT id, nombre FROM usuarios WHERE rol = 'profesor' ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$cursos = $conexion->query(
    "SELECT id, grado, nombre, nivel FROM cursos ORDER BY
        CASE nivel WHEN 'preescolar' THEN 1 WHEN 'primaria' THEN 2 WHEN 'secundaria' THEN 3 ELSE 4 END,
        CASE grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
            WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END, nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$asignaturas = $conexion->query(
    "SELECT a.id, a.nombre, a.nivel, COALESCE(ar.nombre, a.area) AS area,
            (SELECT STRING_AGG(ag.grado, ',' ORDER BY ag.grado) FROM asignatura_grado ag WHERE ag.asignatura_id = a.id) AS grados
     FROM asignaturas a
     LEFT JOIN areas ar ON a.area_id = ar.id
     ORDER BY
        CASE a.nivel WHEN 'preescolar' THEN 1 WHEN 'primaria' THEN 2 WHEN 'secundaria' THEN 3 ELSE 4 END,
        COALESCE(ar.nombre, a.area), a.nombre"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $error = "Error de seguridad. Intente de nuevo.";
    } elseif (isset($_POST["eliminar"])) {
        try {
            $stmt = $conexion->prepare("DELETE FROM profesor_curso_asignatura WHERE id = ?");
            $stmt->execute([$_POST["eliminar"]]);
            $mensaje = "Asignación eliminada correctamente.";
        } catch (PDOException $e) {
            error_log("[asignar_profesor] " . $e->getMessage());
            $error = "Error al eliminar la asignación.";
        }
    } else {
        $profesor = $_POST["profesor"];
        $curso = $_POST["curso"];
        $asignatura = $_POST["asignatura"];
        $porcentaje = (int)($_POST["porcentaje"] ?? 100);
        if ($porcentaje < 1 || $porcentaje > 100) $porcentaje = 100;

        $sql = "INSERT INTO profesor_curso_asignatura (profesor_id, curso_id, asignatura_id, porcentaje)
                VALUES (:profesor, :curso, :asignatura, :porcentaje)
                ON CONFLICT (profesor_id, curso_id, asignatura_id) DO NOTHING";

        $stmt = $conexion->prepare($sql);

        try {
            $stmt->execute([
                ":profesor" => $profesor,
                ":curso" => $curso,
                ":asignatura" => $asignatura,
                ":porcentaje" => $porcentaje,
            ]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "Asignación creada correctamente.";
            } else {
                $error = "Esta asignación ya existe.";
            }
        } catch (PDOException $e) {
            $error = "Error al asignar.";
        }
    }
}

$asignaciones = $conexion->query("
    SELECT pca.id, pca.porcentaje, u.nombre AS profesor_nombre, 
           CONCAT(c.grado, ' ', c.nombre) AS curso_nombre, c.nivel AS curso_nivel,
           a.nombre AS asignatura_nombre, a.nivel AS asignatura_nivel
    FROM profesor_curso_asignatura pca
    JOIN usuarios u ON pca.profesor_id = u.id
    JOIN cursos c ON pca.curso_id = c.id
    JOIN asignaturas a ON pca.asignatura_id = a.id
    ORDER BY u.nombre, c.grado, a.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Asignar Materias";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="row">
                <div class="col-xl-5 mb-4">
                    <div class="card card-form p-4">

                        <div class="header-box">
                            <i class="bi bi-journal-arrow-up" aria-hidden="true"></i>
                            <div>
                                <h4>Nueva Asignación</h4>
                                <span>Asigne a un profesor la asignatura que dicta en un curso.</span>
                            </div>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_profesor">Profesor</label>
                                <select id="f_profesor" name="profesor" class="form-select" required>
                                    <option value="">Seleccionar…</option>
                                    <?php foreach ($profesores as $p): ?>
                                        <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="selectCurso">Curso</label>
                                <select name="curso" id="selectCurso" class="form-select" required>
                                    <option value="">Seleccionar…</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <?php
                                            $nivelLabel = match($c['nivel']) {
                                                'preescolar' => 'Preescolar',
                                                'primaria'   => 'Primaria',
                                                default      => 'Secundaria',
                                            };
                                        ?>
                                        <option value="<?= $c["id"] ?>" data-nivel="<?= $c["nivel"] ?>" data-grado="<?= $c["grado"] ?>">
                                            <?= htmlspecialchars(ucfirst($c["grado"]) . ' ' . $c["nombre"] . ' (' . $nivelLabel . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="selectAsignatura">Asignatura</label>
                                <select name="asignatura" id="selectAsignatura" class="form-select" required>
                                    <option value="">Seleccionar…</option>
                                    <?php foreach ($asignaturas as $a):
                                        $gradoLabel = $a["grados"] ? ' (' . $a["grados"] . ')' : '';
                                    ?>
                                        <option value="<?= $a["id"] ?>" data-nivel="<?= $a["nivel"] ?>" data-grados="<?= $a["grados"] ?? '' ?>">
                                            <?= htmlspecialchars($a["nombre"] . ($a["area"] ? ' — ' . ucfirst(mb_strtolower($a["area"])) : '') . $gradoLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_porcentaje">Peso % en el área</label>
                                <input id="f_porcentaje" type="number" name="porcentaje" class="form-control" value="100" min="1" max="100"
                                    placeholder="Ej. 100">
                                <div class="form-text">Porcentaje de aporte de esta asignatura al promedio global del área.</div>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-plus-circle me-2"></i>Asignar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="gca-card p-4">
                        <div class="section-header">
                            <h4><i class="bi bi-list-check" aria-hidden="true"></i> Asignaciones actuales <span class="count-pill"><?= count($asignaciones) ?></span></h4>
                        </div>

                        <?php if (count($asignaciones) === 0): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <h3>Sin asignaciones</h3>
                                <p>Use el formulario para asignar la primera materia a un profesor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table gca-table mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Profesor</th>
                                            <th scope="col">Curso</th>
                                            <th scope="col">Materia</th>
                                            <th scope="col" class="text-center">%</th>
                                            <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignaciones as $a): ?>
                                            <tr>
                                                <td class="cell-strong"><?= htmlspecialchars($a['profesor_nombre']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars(ucfirst($a['curso_nombre'])) ?>
                                                    <small class="cell-sub">
                                                        <?= match($a['curso_nivel']) {
                                                            'preescolar' => 'Preescolar',
                                                            'primaria'   => 'Primaria',
                                                            default      => 'Secundaria',
                                                        } ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($a['asignatura_nombre']) ?>
                                                </td>
                                                <td class="text-center"><span class="gca-badge"><?= (int)$a['porcentaje'] ?>%</span></td>
                                                <td class="text-end">
                                                    <form method="POST" id="deleteForm<?= $a['id'] ?>">
                                                        <?= campo_csrf() ?>
                                                        <input type="hidden" name="eliminar" value="<?= $a['id'] ?>">
                                                        <button type="button" class="btn-action btn-delete" title="Eliminar"
                                                                onclick="showConfirm('¿Eliminar esta asignación?',()=>document.getElementById('deleteForm<?= $a['id'] ?>').submit());"
                                                                aria-label="Eliminar asignación de <?= htmlspecialchars($a['asignatura_nombre']) ?> a <?= htmlspecialchars($a['profesor_nombre']) ?>">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cursoSelect = document.getElementById('selectCurso');
    const asignaturaSelect = document.getElementById('selectAsignatura');

    if (!cursoSelect || !asignaturaSelect) return;

    const allOptions = Array.from(asignaturaSelect.options).slice(1);

    function filtrarAsignaturas() {
        const selectedOption = cursoSelect.options[cursoSelect.selectedIndex];
        const cursoNivel = selectedOption?.getAttribute('data-nivel') || '';
        const cursoGrado = selectedOption?.getAttribute('data-grado') || '';

        const ts = asignaturaSelect.tomselect;
        if (!ts) return;

        const idsAMostrar = [];

        allOptions.forEach(opt => {
            const optNivel = opt.getAttribute('data-nivel') || '';
            const optGrados = (opt.getAttribute('data-grados') || '').split(',').map(s => s.trim()).filter(Boolean);
            const coincide = optNivel === cursoNivel && (optGrados.length === 0 || optGrados.includes(cursoGrado));
            if (coincide) {
                idsAMostrar.push(opt.value);
            }
        });

        ts.clearOptions();
        ts.clear();

        allOptions.forEach(opt => {
            if (idsAMostrar.includes(opt.value)) {
                ts.addOption({ value: opt.value, text: opt.text });
            }
        });

        if (idsAMostrar.length === 0) {
            ts.addOption({ value: '', text: '-- No hay materias disponibles --' });
        }

        ts.refreshOptions(false);
    }

    cursoSelect.addEventListener('change', filtrarAsignaturas);

    setTimeout(filtrarAsignaturas, 300);
});
</script>

    <?php include "includes/footer.php"; ?>
