<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once __DIR__ . "/../lib/csrf_helper.php";

$mensaje = "";
$tipoMensaje = "info";

/* Primero se pide el GRADO. Con 245 estudiantes la lista plana era
   inservible, y además TomSelect está configurado con maxOptions:100,
   así que los últimos estudiantes no llegaban a aparecer. */
$grado = trim($_POST["grado"] ?? $_GET["grado"] ?? "");

/* ── Padres disponibles ── */
$padres = $conexion->query(
    "SELECT id, nombre, email FROM usuarios WHERE rol = 'padre' ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── Grados existentes, en el orden del colegio ──
   GROUP BY y no DISTINCT: PostgreSQL no admite que el CASE del ORDER BY
   esté fuera de la lista del SELECT cuando se usa DISTINCT. */
$grados = $conexion->query("
    SELECT c.grado, COUNT(e.id) AS total
    FROM cursos c
    LEFT JOIN estudiantes e ON e.curso_id = c.id
    GROUP BY c.grado
    ORDER BY
        CASE c.grado
            WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
            WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8
            WHEN 'quinto' THEN 9 WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12
            WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'once' THEN 15 WHEN 'undecimo' THEN 15
            ELSE 16
        END
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Procesar la vinculación ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["estudiante_id"])) {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intenta de nuevo.";
        $tipoMensaje = "danger";
    } else {
        $padre_id      = (int) ($_POST["padre_id"] ?? 0);
        $estudiante_id = (int) ($_POST["estudiante_id"] ?? 0);

        if ($padre_id <= 0 || $estudiante_id <= 0) {
            $mensaje = "Elige un padre y un estudiante.";
            $tipoMensaje = "danger";
        } else {
            try {
                $stmt = $conexion->prepare(
                    "UPDATE estudiantes SET padre_id = :padre WHERE id = :estudiante"
                );
                $stmt->execute([":padre" => $padre_id, ":estudiante" => $estudiante_id]);

                $datos = $conexion->prepare(
                    "SELECT e.nombre AS estudiante, u.nombre AS padre
                     FROM estudiantes e JOIN usuarios u ON u.id = e.padre_id
                     WHERE e.id = ?"
                );
                $datos->execute([$estudiante_id]);
                $d = $datos->fetch(PDO::FETCH_ASSOC);

                $mensaje = $d
                    ? "{$d['estudiante']} quedó vinculado a {$d['padre']}."
                    : "Padre asignado correctamente.";
                $tipoMensaje = "success";
            } catch (PDOException $e) {
                error_log("[asignar_padre] " . $e->getMessage());
                $mensaje = "Error al realizar la asignación.";
                $tipoMensaje = "danger";
            }
        }
    }
}

/* ── Estudiantes del grado elegido (se consulta DESPUÉS de procesar,
      para que el vínculo recién hecho ya se vea reflejado) ── */
$estudiantes = [];
if ($grado !== "") {
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento, c.grado, c.nombre AS curso,
               up.nombre AS padre_actual
        FROM estudiantes e
        JOIN cursos c ON c.id = e.curso_id
        LEFT JOIN usuarios up ON up.id = e.padre_id
        WHERE c.grado = ?
        ORDER BY e.nombre
    ");
    $stmt->execute([$grado]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sinVincular = count(array_filter($estudiantes, fn($e) => $e['padre_actual'] === null));

$pageTitle = "Asignar Padre";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Asignar Padre</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Vinculación Familiar</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Asignar Padre a Estudiante</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Paso 1: el grado. Recarga la página con solo ese grado. -->
                        <form method="GET" id="formGrado">
                            <div class="mb-4">
                                <label class="form-label fw-medium" for="selGrado">
                                    <span class="badge bg-dark me-1">1</span> Grado
                                </label>
                                <select name="grado" id="selGrado" class="form-select">
                                    <option value="">-- Elija un grado --</option>
                                    <?php foreach ($grados as $g): ?>
                                        <option value="<?= htmlspecialchars($g["grado"]) ?>"
                                            <?= $g["grado"] === $grado ? "selected" : "" ?>>
                                            <?= htmlspecialchars(ucfirst($g["grado"])) ?> (<?= (int) $g["total"] ?> estudiantes)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Elige el grado para ver solo sus estudiantes.</div>
                            </div>
                        </form>

                        <?php if ($grado === ""): ?>
                            <div class="alert alert-light border" role="alert">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona un grado arriba para continuar.
                            </div>
                        <?php elseif (count($estudiantes) === 0): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                No hay estudiantes en <?= htmlspecialchars(ucfirst($grado)) ?>.
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <?= campo_csrf() ?>
                                <input type="hidden" name="grado" value="<?= htmlspecialchars($grado) ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-medium" for="f_estudiante_id">
                                        <span class="badge bg-dark me-1">2</span> Estudiante
                                        <small class="text-muted">
                                            &middot; <?= count($estudiantes) ?> en <?= htmlspecialchars(ucfirst($grado)) ?>,
                                            <?= $sinVincular ?> sin padre
                                        </small>
                                    </label>
                                    <select id="f_estudiante_id" name="estudiante_id" class="form-select" required>
                                        <option value="">-- Elija un estudiante --</option>
                                        <?php foreach ($estudiantes as $e): ?>
                                            <option value="<?= (int) $e["id"] ?>">
                                                <?= htmlspecialchars($e["nombre"]) ?>
                                                &middot; <?= htmlspecialchars(ucfirst($e["grado"]) . " " . $e["curso"]) ?>
                                                <?php if ($e["documento"]): ?>
                                                    &middot; <?= htmlspecialchars($e["documento"]) ?>
                                                <?php endif; ?>
                                                <?php if ($e["padre_actual"]): ?>
                                                    &nbsp;— ya vinculado a <?= htmlspecialchars($e["padre_actual"]) ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        Los que ya tienen padre lo indican al lado: al asignar uno nuevo, se reemplaza.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-medium" for="f_padre_id">
                                        <span class="badge bg-dark me-1">3</span> Padre
                                    </label>
                                    <select id="f_padre_id" name="padre_id" class="form-select" required>
                                        <option value="">-- Elija un padre --</option>
                                        <?php foreach ($padres as $p): ?>
                                            <option value="<?= (int) $p["id"] ?>">
                                                <?= htmlspecialchars($p["nombre"]) ?>
                                                <?php if ($p["email"]): ?>
                                                    &middot; <?= htmlspecialchars($p["email"]) ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (count($padres) === 0): ?>
                                        <div class="form-text text-danger">
                                            No hay cuentas de padre. Crea una en Registros &rarr; Nuevo Padre.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn-gca w-100">
                                    <i class="bi bi-person-check me-2"></i>Vincular Padre
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // El grado recarga la lista solo. TomSelect envuelve el <select>,
        // así que se escucha el change del original (TomSelect lo propaga).
        document.addEventListener('DOMContentLoaded', function () {
            var sel = document.getElementById('selGrado');
            if (sel) {
                sel.addEventListener('change', function () {
                    document.getElementById('formGrado').submit();
                });
            }
        });
    </script>

    <?php include "includes/footer.php"; ?>
