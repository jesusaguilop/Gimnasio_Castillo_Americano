<?php

require_once __DIR__ . '/../includes/init.php';
checkRole('padre');

$padre_id = userId();
$nombre_padre = $_SESSION["nombre"] ?? "Padre de Familia";

/* ── Get children ── */
$hijos = $conexion->prepare("
    SELECT e.id, e.nombre, e.documento,
           c.grado, c.nombre AS curso_nombre
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    WHERE e.padre_id = ?
    ORDER BY e.nombre
");
$hijos->execute([$padre_id]);
$hijos = $hijos->fetchAll(PDO::FETCH_ASSOC);

/* ── Get unread alerts ── */
$alertas_padre = $conexion->prepare("
    SELECT * FROM alertas
    WHERE (para_usuario_id = ? OR (para_rol = 'padre' AND para_usuario_id IS NULL))
    AND leido = FALSE
    ORDER BY created_at DESC
    LIMIT 5
");
$alertas_padre->execute([$padre_id]);
$alertas_padre = $alertas_padre->fetchAll(PDO::FETCH_ASSOC);

/* ── Check for available boletines ── */
$hijos_ids = array_column($hijos, 'id');
$boletines_disponibles = [];
if (count($hijos_ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($hijos_ids), '?'));
    $stmtB = $conexion->prepare("
        SELECT DISTINCT bp.estudiante_id, bp.periodo, bp.year, e.nombre AS estudiante_nombre
        FROM boletines_pdf bp
        JOIN estudiantes e ON bp.estudiante_id = e.id
        WHERE bp.estudiante_id IN ($placeholders)
        ORDER BY bp.estudiante_id, bp.periodo
    ");
    $stmtB->execute($hijos_ids);
    foreach ($stmtB->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $boletines_disponibles[$b['estudiante_id']][] = $b;
    }
}

$h = (int)date('H');
$saludo = $h < 12 ? 'Buenos días' : ($h < 18 ? 'Buenas tardes' : 'Buenas noches');
$pageTitle = "Inicio";
include "includes/header.php";
?>

<body>

<header class="app-header">
    <a href="dashboard.php" class="app-header-brand">
        <img src="../assets/img/logo_gca.png" alt="GCA">
        <div>
            <span>Familia GCA</span>
            <small>Panel de Padres</small>
        </div>
    </a>
    <div class="header-actions">
        <a href="#" class="btn-header" id="dmToggle" title="Modo oscuro" onclick="document.body.classList.toggle('dark-mode');document.documentElement.setAttribute('data-bs-theme',document.body.classList.contains('dark-mode')?'dark':'');localStorage.setItem('gca-dark-mode',document.body.classList.contains('dark-mode'));this.querySelector('i').className=document.body.classList.contains('dark-mode')?'bi bi-sun':'bi bi-moon-stars';">
            <i class="bi bi-moon-stars"></i>
        </a>
        <a href="../auth/logout.php" class="btn-header logout">
            <i class="bi bi-box-arrow-right"></i> Salir
        </a>
    </div>
</header>

<main class="app-content">

    <div class="parent-welcome">
        <div class="pw-txt">
            <span class="pw-greet"><?= $saludo ?></span>
            <h4>Hola, <span><?= htmlspecialchars($nombre_padre) ?></span></h4>
            <p>Selecciona un hijo para ver su boletín</p>
        </div>
        <div class="pw-emoji"><i class="bi bi-house-heart-fill"></i></div>
    </div>
    <style>
        .parent-welcome {
            position: relative; overflow: hidden;
            background:
                radial-gradient(400px 160px at 90% -30%, rgba(212,175,55,.28), transparent 70%),
                linear-gradient(135deg, #0f1117 0%, #1c1f2b 100%);
            border: 1px solid rgba(212,175,55,.22);
            border-radius: var(--radius);
            padding: 20px 22px; margin-bottom: 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,.14);
        }
        .parent-welcome .pw-greet {
            display:inline-block; font-size:10px; letter-spacing:1.4px; text-transform:uppercase;
            color:var(--gold); background:rgba(212,175,55,.1); border:1px solid rgba(212,175,55,.22);
            padding:2px 10px; border-radius:20px; margin-bottom:8px;
        }
        .parent-welcome h4 { font-family:'Cormorant Garamond',serif; color:#f0ede6; margin:0 0 2px; font-size:22px; font-weight:700; }
        .parent-welcome h4 span {
            background:linear-gradient(90deg,#d4af37 20%,#f6e4a6 50%,#d4af37 80%); background-size:200% auto;
            -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; color:transparent;
            animation: pwShine 5s linear infinite;
        }
        @keyframes pwShine { to { background-position:200% center; } }
        .parent-welcome p { color:rgba(240,237,230,.6); margin:0; font-size:12.5px; }
        .parent-welcome .pw-emoji {
            width:54px; height:54px; flex-shrink:0; border-radius:16px;
            display:flex; align-items:center; justify-content:center;
            background:rgba(212,175,55,.12); border:1px solid rgba(212,175,55,.25);
        }
        .parent-welcome .pw-emoji i { font-size:26px; color:var(--gold); animation: floaty 3.5s ease-in-out infinite; }
        @keyframes floaty { 0%,100%{ transform:translateY(0);} 50%{ transform:translateY(-5px);} }
        @media (prefers-reduced-motion: reduce){ .parent-welcome h4 span, .parent-welcome .pw-emoji i { animation:none; } }
    </style>

    <h5 class="section-title" style="margin-bottom:12px;">
        <i class="bi bi-people-fill"></i>Mis Hijos
    </h5>

    <?php if (count($alertas_padre) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
            <?php foreach ($alertas_padre as $al): ?>
                <div style="background:var(--alert-bg);border-left:4px solid var(--alert-color);border-radius:10px;padding:10px 14px;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill" style="color:var(--alert-color);font-size:16px;margin-top:1px;"></i>
                        <div>
                            <strong style="font-size:13px;color:var(--text-primary);"><?= htmlspecialchars($al['titulo']) ?></strong>
                            <p style="font-size:12px;color:var(--text-secondary);margin:2px 0 0;"><?= nl2br(htmlspecialchars($al['mensaje'])) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($hijos) === 0): ?>
        <div class="app-card">
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h5>Sin estudiantes vinculados</h5>
                <p>No hay hijos asignados a tu cuenta. Consulta con la administración del colegio.</p>
            </div>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($hijos as $h):
                $h_boletines = $boletines_disponibles[$h['id']] ?? [];
                $nuevo = count($h_boletines) > 0;
            ?>
                <a href="boletin.php?estudiante=<?= $h['id'] ?>" class="student-card" style="position:relative;">
                    <?php if ($nuevo): ?>
                        <span style="position:absolute;top:-4px;right:-4px;width:20px;height:20px;background:#dc3545;color:#fff;border-radius:50%;font-size:10px;display:flex;align-items:center;justify-content:center;border:2px solid var(--surface);">
                            <i class="bi bi-file-pdf-fill" style="font-size:8px;"></i>
                        </span>
                    <?php endif; ?>
                    <div class="student-avatar">
                        <?= strtoupper(substr($h['nombre'], 0, 2)) ?>
                    </div>
                    <div class="student-info">
                        <h6><?= htmlspecialchars($h['nombre']) ?></h6>
                        <p>
                            <?php if ($h['grado']): ?>
                                Curso <?= htmlspecialchars($h['grado'] . ' ' . $h['curso_nombre']) ?>
                            <?php else: ?>
                                Sin curso asignado
                            <?php endif; ?>
                        </p>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <?php if ($h['grado']): ?>
                                <span class="badge-curso">
                                    <i class="bi bi-book-fill" style="font-size:9px;"></i>
                                    <?= htmlspecialchars($h['grado']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($nuevo): ?>
                                <span style="background:rgba(25,135,84,0.1);color:#198754;font-size:10px;font-weight:600;padding:2px 10px;border-radius:20px;">
                                    <i class="bi bi-file-pdf-fill"></i> Boletín
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right student-chevron"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:32px;padding-top:20px;border-top:1px solid var(--border);">
        <p style="font-size:11px;color:var(--text-muted);margin:0;">
            <i class="bi bi-shield-check me-1"></i>
            Gimnasio Castillo Americano &middot; Boletín Académico
        </p>
    </div>

</main>

<?php include "includes/footer.php"; ?>
