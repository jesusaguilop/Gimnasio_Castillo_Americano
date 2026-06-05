<?php
require_once __DIR__ . '/includes/init.php';

$docentes = db()->fetchAll(
    "SELECT * FROM docentes WHERE activo = 1 ORDER BY nombre ASC"
);

include "header.php";
?>

<style>
    .teachers-hero {
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        color: #fff;
        padding: 80px 20px 56px;
        text-align: center;
        position: relative;
        isolation: isolate;
    }
    .teachers-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gca-gold), transparent);
    }
    .teachers-hero h1 { font-weight: 900; font-size: 2.4rem; }
    .teachers-hero h1::after {
        content: '';
        display: block;
        width: 70px; height: 3px;
        background: var(--gca-gold);
        margin: 14px auto 0;
        border-radius: 2px;
    }
    .teachers-hero p { color: #aaa; max-width: 560px; margin: 14px auto 0; font-size: 1.05rem; }
</style>

<div class="teachers-hero" data-aos="fade-down">
    <h1>Nuestros Docentes</h1>
    <p>Conoce al equipo de profesionales que hacen posible la excelencia educativa</p>
</div>

<div class="container my-5">
    <?php if (empty($docentes)): ?>
    <div class="text-center py-5" data-aos="fade-up">
        <i class="bi bi-people" style="font-size:3rem;color:#ccc;"></i>
        <p class="text-muted mt-3">El directorio docente estará disponible próximamente.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php $i = 0; foreach ($docentes as $d): ?>
        <div class="col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 100 ?>">
            <div class="teacher-card">
                <?php if ($d["foto"]): ?>
                <img src="<?= htmlspecialchars($d["foto"]) ?>"
                     alt="<?= htmlspecialchars($d["nombre"]) ?>"
                     class="teacher-img"
                     loading="lazy">
                <?php else: ?>
                <div class="teacher-img-placeholder">
                    <i class="bi bi-person-fill"></i>
                </div>
                <?php endif; ?>
                <div class="teacher-body">
                    <h5><?= htmlspecialchars($d["nombre"]) ?></h5>
                    <?php if ($d["especialidad"]): ?>
                    <div class="specialty"><?= htmlspecialchars($d["especialidad"]) ?></div>
                    <?php endif; ?>
                    <?php if ($d["descripcion"]): ?>
                    <p><?= htmlspecialchars($d["descripcion"]) ?></p>
                    <?php endif; ?>
                    <?php if ($d["email"]): ?>
                    <a href="mailto:<?= htmlspecialchars($d["email"]) ?>">
                        <i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($d["email"]) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php $i++; endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>
