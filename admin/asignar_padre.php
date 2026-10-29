<?php

require_once __DIR__ . "/../includes/init.php";
checkRole("admin");
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

/* ── Vincular / desvincular ──
   Un estudiante puede tener VARIOS acudientes (papá y mamá): el vínculo vive
   en estudiante_acudientes. estudiantes.padre_id queda como el acudiente
   principal (el primero vinculado) para lo que todavía lo lee. */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["estudiante_id"])) {
    $padre_id      = (int) ($_POST["padre_id"] ?? 0);
    $estudiante_id = (int) ($_POST["estudiante_id"] ?? 0);
    $quitar        = isset($_POST["desvincular"]);

    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intenta de nuevo.";
        $tipoMensaje = "danger";
    } elseif ($padre_id <= 0 || $estudiante_id <= 0) {
        $mensaje = "Elige un padre y un estudiante.";
        $tipoMensaje = "danger";
    } else {
        try {
            $conexion->beginTransaction();
            if ($quitar) {
                $conexion->prepare("DELETE FROM estudiante_acudientes WHERE estudiante_id = ? AND padre_id = ?")
                         ->execute([$estudiante_id, $padre_id]);
                // Si era el principal, pasa a serlo otro acudiente (o ninguno)
                $conexion->prepare("
                    UPDATE estudiantes SET padre_id = (
                        SELECT ea.padre_id FROM estudiante_acudientes ea
                        WHERE ea.estudiante_id = :e ORDER BY ea.creado_en LIMIT 1)
                    WHERE id = :e2 AND padre_id = :p
                ")->execute([":e" => $estudiante_id, ":e2" => $estudiante_id, ":p" => $padre_id]);
            } else {
                $esPadre = $conexion->prepare("SELECT 1 FROM usuarios WHERE id = ? AND rol = 'padre'");
                $esPadre->execute([$padre_id]);
                if (!$esPadre->fetchColumn()) {
                    throw new RuntimeException("La cuenta elegida no es de un padre.");
                }
                $conexion->prepare("
                    INSERT INTO estudiante_acudientes (estudiante_id, padre_id) VALUES (?, ?)
                    ON CONFLICT DO NOTHING
                ")->execute([$estudiante_id, $padre_id]);
                $conexion->prepare("UPDATE estudiantes SET padre_id = COALESCE(padre_id, ?) WHERE id = ?")
                         ->execute([$padre_id, $estudiante_id]);
            }
            $conexion->commit();

            $datos = $conexion->prepare(
                "SELECT e.nombre AS estudiante, u.nombre AS padre
                 FROM estudiantes e, usuarios u WHERE e.id = ? AND u.id = ?"
            );
            $datos->execute([$estudiante_id, $padre_id]);
            $d = $datos->fetch(PDO::FETCH_ASSOC) ?: ["estudiante" => "El estudiante", "padre" => "el padre"];

            $mensaje = $quitar
                ? "Se quitó el vínculo de {$d['estudiante']} con {$d['padre']}."
                : "{$d['estudiante']} quedó vinculado a {$d['padre']}.";
            $tipoMensaje = "success";
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("[asignar_padre] " . $e->getMessage());
            $mensaje = $e instanceof RuntimeException ? $e->getMessage() : "Error al realizar la asignación.";
            $tipoMensaje = "danger";
        }
    }
}

/* ── Estudiantes del grado elegido (se consulta DESPUÉS de procesar,
      para que el vínculo recién hecho ya se vea reflejado) ──
   `acudientes` = [{id, nombre}, ...] para listarlos y poder quitar vínculos. */
$estudiantes = [];
if ($grado !== "") {
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento, c.grado, c.nombre AS curso,
               string_agg(u.nombre, ', ' ORDER BY ea.creado_en) AS padre_actual,
               COALESCE(json_agg(json_build_object('id', u.id, 'nombre', u.nombre)
                        ORDER BY ea.creado_en) FILTER (WHERE u.id IS NOT NULL), '[]') AS acudientes
        FROM estudiantes e
        JOIN cursos c ON c.id = e.curso_id
        LEFT JOIN estudiante_acudientes ea ON ea.estudiante_id = e.id
        LEFT JOIN usuarios u ON u.id = ea.padre_id
        WHERE c.grado = ?
        GROUP BY e.id, c.grado, c.nombre
        ORDER BY e.nombre
    ");
    $stmt->execute([$grado]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($estudiantes as &$e) {
        $e["acudientes"] = json_decode($e["acudientes"], true) ?: [];
    }
    unset($e);
}

$sinVincular = count(array_filter($estudiantes, fn($e) => $e['padre_actual'] === null));

$pageTitle = "Asignar Padre";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-7">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            <div>
                                <h4>Asignar Padre a Estudiante</h4>
                                <span>Vincule a un estudiante con su padre o acudiente.</span>
                            </div>
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
                                    <span class="step-num" aria-hidden="true">1</span> Grado
                                </label>
                                <select name="grado" id="selGrado" class="form-select">
                                    <option value="">Elija un grado…</option>
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
                            <div class="empty-state empty-state-sm">
                                <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                <p>Selecciona un grado arriba para continuar.</p>
                            </div>
                        <?php elseif (count($estudiantes) === 0): ?>
                            <div class="alert alert-warning alert-icon" role="alert">
                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                No hay estudiantes en <?= htmlspecialchars(ucfirst($grado)) ?>.
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <?= campo_csrf() ?>
                                <input type="hidden" name="grado" value="<?= htmlspecialchars($grado) ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-medium" for="f_estudiante_id">
                                        <span class="step-num" aria-hidden="true">2</span> Estudiante
                                        <small class="text-muted">
                                            &middot; <?= count($estudiantes) ?> en <?= htmlspecialchars(ucfirst($grado)) ?>,
                                            <?= $sinVincular ?> sin padre
                                        </small>
                                    </label>
                                    <select id="f_estudiante_id" name="estudiante_id" class="form-select" required>
                                        <option value="">Elija un estudiante…</option>
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
                                        Un estudiante puede tener varios acudientes (papá y mamá): vincular uno nuevo no quita a los anteriores.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-medium" for="f_padre_id">
                                        <span class="step-num" aria-hidden="true">3</span> Padre o acudiente
                                    </label>
                                    <select id="f_padre_id" name="padre_id" class="form-select" required>
                                        <option value="">Elija un padre o acudiente…</option>
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
                                            No hay cuentas de padre. <a href="crear_padre.php">Crea una aquí</a>.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn-gca w-100">
                                    <i class="bi bi-link-45deg" aria-hidden="true"></i> Vincular acudiente
                                </button>
                            </form>

                            <?php $vinculados = array_filter($estudiantes, fn($e) => $e["acudientes"]); ?>
                            <?php if ($vinculados): ?>
                                <div class="form-section mt-4">
                                <h2 class="form-section-title">Vínculos actuales en <?= htmlspecialchars(ucfirst($grado)) ?> <span class="count-pill ms-1"><?= array_sum(array_map(fn($x) => count($x["acudientes"]), $vinculados)) ?></span></h2>
                                <ul class="link-list">
                                    <?php foreach ($vinculados as $e): ?>
                                        <?php foreach ($e["acudientes"] as $a): ?>
                                            <li class="link-item">
                                                <span class="avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr(trim($e["nombre"]), 0, 1))) ?></span>
                                                <span class="min-w-0 flex-grow-1">
                                                    <strong class="d-block text-truncate"><?= htmlspecialchars($e["nombre"]) ?></strong>
                                                    <small class="text-muted d-block"><i class="bi bi-link-45deg" aria-hidden="true"></i> Acudiente: <?= htmlspecialchars($a["nombre"]) ?></small>
                                                </span>
                                                <form method="POST" data-confirm="¿Quitar este vínculo? El acudiente dejará de ver los boletines de este estudiante." data-confirm-ok="Sí, quitar">
                                                    <?= campo_csrf() ?>
                                                    <input type="hidden" name="grado" value="<?= htmlspecialchars($grado) ?>">
                                                    <input type="hidden" name="estudiante_id" value="<?= (int) $e["id"] ?>">
                                                    <input type="hidden" name="padre_id" value="<?= (int) $a["id"] ?>">
                                                    <button type="submit" name="desvincular" value="1" class="btn-soft-danger"
                                                            aria-label="Quitar vínculo de <?= htmlspecialchars($e["nombre"]) ?> con <?= htmlspecialchars($a["nombre"]) ?>">
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i> Quitar
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </ul>
                                </div>
                            <?php endif; ?>
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
