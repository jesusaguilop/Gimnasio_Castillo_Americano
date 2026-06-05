<?php
require_once __DIR__ . '/includes/init.php';

$mes = (int)($_GET["mes"] ?? date("m"));
$anio = (int)($_GET["anio"] ?? date("Y"));
if ($mes < 1) { $mes = 1; $anio--; }
if ($mes > 12) { $mes = 12; $anio++; }

$eventos = db()->fetchAll(
    "SELECT * FROM eventos WHERE activo = 1 AND YEAR(fecha_evento) = ? AND MONTH(fecha_evento) = ? ORDER BY fecha_evento ASC",
    [$anio, $mes]
);

$agrupados = [];
foreach ($eventos as $e) {
    $dia = (int)date("j", strtotime($e["fecha_evento"]));
    $agrupados[$dia][] = $e;
}

$proximos = db()->fetchAll(
    "SELECT * FROM eventos WHERE activo = 1 AND fecha_evento >= CURDATE() ORDER BY fecha_evento ASC LIMIT 5"
);

$meses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

include "header.php";
?>

<style>
    .cal-header {
        background: linear-gradient(135deg, #0f0f0f, #1a1a2e);
        color: #fff;
        padding: 80px 20px 56px;
        text-align: center;
        position: relative;
        isolation: isolate;
    }
    .cal-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gca-gold), transparent);
    }
    .cal-header h1 { font-weight: 900; font-size: 2.4rem; }
    .cal-header h1::after {
        content: '';
        display: block;
        width: 70px; height: 3px;
        background: var(--gca-gold);
        margin: 14px auto 0;
        border-radius: 2px;
    }
    .cal-header p { color: #aaa; max-width: 560px; margin: 14px auto 0; font-size: 1.05rem; }
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(0,0,0,.05);
        border: 1px solid #f0eee8;
    }
    .cal-grid .day-name {
        background: var(--gca-dark);
        color: var(--gca-gold);
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        padding: 14px 0;
        text-align: center;
        letter-spacing: 1px;
    }
    .cal-grid .day {
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 8px;
        font-size: 14px;
        background: #fcfcfc;
        border: 1px solid #f5f5f5;
        position: relative;
        min-height: 80px;
        transition: background .2s ease;
    }
    .cal-grid .day:hover {
        background: rgba(201,162,77,.04);
    }
    .cal-grid .day .num {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 3px;
        color: #444;
    }
    .cal-grid .day.has-event {
        background: rgba(201,162,77,.06);
        cursor: pointer;
    }
    .cal-grid .day .event-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    .cal-grid .day.other-month .num { opacity: .2; }
    .cal-grid .day.day-today {
        background: rgba(201,162,77,.12);
        box-shadow: inset 0 0 0 2px var(--gca-gold);
    }
    .cal-grid .day.day-today .num {
        color: var(--gca-dark);
        font-weight: 800;
    }
    .event-dots {
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
    .cal-month-title { margin: 0; font-weight: 800; font-size: 1.4rem; }
    .btn-nav-cal {
        border: 1px solid #ddd; border-radius: 40px; padding: 6px 18px;
        font-size: 13px; font-weight: 600; color: #444; background: #fff;
        transition: all .3s ease; display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
    }
    .btn-nav-cal:hover { border-color: var(--gca-gold); color: var(--gca-dark); background: rgba(201,162,77,.06); }

    @media (max-width: 576px) {
        .cal-grid .day { min-height: 50px; font-size: 12px; padding: 3px; }
        .cal-grid .day-name { font-size: 11px; padding: 8px 0; }
        .cal-nav h3 { font-size: 1.1rem; }
    }
</style>

<div class="cal-header" data-aos="fade-down">
    <h1>Calendario de Eventos</h1>
    <p>Fechas importantes, actividades y eventos institucionales</p>
</div>

<div class="container my-5">
    <div class="row g-5">
        <div class="col-lg-8" data-aos="fade-up">
            <div class="cal-nav">
                <a href="?mes=<?= $mes - 1 ?>&anio=<?= $anio ?>" class="btn btn-nav-cal"><i class="bi bi-chevron-left"></i> Anterior</a>
                <h3 class="cal-month-title"><?= $meses[$mes] ?> <?= $anio ?></h3>
                <a href="?mes=<?= $mes + 1 ?>&anio=<?= $anio ?>" class="btn btn-nav-cal">Siguiente <i class="bi bi-chevron-right"></i></a>
            </div>

            <div class="cal-grid">
                <?php $dias = ["Dom","Lun","Mar","Mié","Jue","Vie","Sáb"]; ?>
                <?php foreach ($dias as $d): ?>
                    <div class="day-name"><?= $d ?></div>
                <?php endforeach; ?>

                <?php
                $primer_dia = (int)date("w", strtotime("$anio-$mes-01"));
                $total_dias = (int)date("t", strtotime("$anio-$mes-01"));
                $dia_actual = (int)date("j");

                for ($i = 0; $i < $primer_dia; $i++):
                ?>
                    <div class="day other-month"></div>
                <?php endfor; ?>

                <?php for ($d = 1; $d <= $total_dias; $d++):
                    $tiene = isset($agrupados[$d]);
                    $hoy = ($d === $dia_actual && $mes === (int)date("m") && $anio === (int)date("Y"));
                ?>
                    <div class="day <?= $tiene ? 'has-event' : '' ?> <?= $hoy ? 'day-today' : '' ?>"
                         title="<?= $tiene ? count($agrupados[$d]) . ' evento(s)' : '' ?>">
                        <span class="num"><?= $d ?></span>
                        <?php if ($tiene): ?>
                            <div class="event-dots">
                                <?php foreach ($agrupados[$d] as $ev): ?>
                                    <span class="event-dot" style="background:<?= htmlspecialchars($ev['color']) ?>;"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="col-lg-4" data-aos="fade-left">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom:2px solid var(--gca-gold);">
                <i class="bi bi-calendar-event" style="color:var(--gca-gold);font-size:1.2rem;"></i>
                <h5 class="fw-bold mb-0">Próximos Eventos</h5>
            </div>
            <?php if (empty($proximos)): ?>
                <p class="text-muted">No hay eventos próximos.</p>
            <?php else: ?>
                <?php foreach ($proximos as $e):
                    $evColor = htmlspecialchars($e["color"] ?? '#c9a24d');
                ?>
                <div class="event-card mb-3" style="border-left-color: <?= $evColor ?>;">
                    <div class="event-date-badge">
                        <span class="day"><?= date("j", strtotime($e["fecha_evento"])) ?></span>
                        <span class="month"><?= date("M", strtotime($e["fecha_evento"])) ?></span>
                    </div>
                    <div class="event-body">
                        <span class="event-type-badge" style="background:<?= $evColor ?>15;color:<?= $evColor ?>;"><?= htmlspecialchars($e["tipo"]) ?></span>
                        <h5><?= htmlspecialchars($e["titulo"]) ?></h5>
                        <?php if ($e["hora_evento"]): ?>
                            <small><i class="bi bi-clock"></i> <?= date("h:i A", strtotime($e["hora_evento"])) ?></small>
                        <?php endif; ?>
                        <?php if ($e["descripcion"]): ?>
                            <p><?= htmlspecialchars($e["descripcion"]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
