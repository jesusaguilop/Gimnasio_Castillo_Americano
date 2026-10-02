<?php
require_once __DIR__ . '/includes/init.php';

function getColombianHolidays(int $year): array
{
    $holidays = [];

    $easter = new DateTime('@' . easter_date($year));
    $easter->setTimezone(new DateTimeZone(date_default_timezone_get()));

    $fixed = [
        ['01-01', 'Año Nuevo'],
        ['05-01', 'Día del Trabajo'],
        ['07-20', 'Independencia de Colombia'],
        ['08-07', 'Batalla de Boyacá'],
        ['12-08', 'Inmaculada Concepción'],
        ['12-25', 'Navidad'],
    ];
    foreach ($fixed as $f) {
        $holidays[$year . '-' . $f[0]] = $f[1];
    }

    $easterBased = [
        [-3, 'Jueves Santo'],
        [-2, 'Viernes Santo'],
        [43, 'Ascensión de Jesús'],
        [64, 'Corpus Christi'],
        [71, 'Sagrado Corazón'],
    ];
    foreach ($easterBased as $eb) {
        $d = clone $easter;
        $d->modify(($eb[0]) . ' days');
        $holidays[$d->format('Y-m-d')] = $eb[1];
    }

    $movable = [
        ['01-06', 'Reyes Magos'],
        ['03-19', 'San José'],
        ['06-29', 'San Pedro y San Pablo'],
        ['08-15', 'Asunción de la Virgen'],
        ['10-12', 'Día de la Raza'],
        ['11-01', 'Todos los Santos'],
        ['11-11', 'Independencia de Cartagena'],
    ];
    foreach ($movable as $m) {
        $d = new DateTime("$year-{$m[0]}");
        if ((int)$d->format('N') !== 1) {
            $d->modify('next monday');
        }
        $holidays[$d->format('Y-m-d')] = $m[1];
    }

    return $holidays;
}

$mes = (int)($_GET["mes"] ?? date("m"));
$anio = (int)($_GET["anio"] ?? date("Y"));
if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

$eventos = db()->fetchAll(
    "SELECT * FROM eventos WHERE activo = TRUE AND EXTRACT(YEAR FROM fecha_evento) = ? AND EXTRACT(MONTH FROM fecha_evento) = ? ORDER BY fecha_evento ASC",
    [$anio, $mes]
);

$agrupados = [];
foreach ($eventos as $e) {
    $dia = (int)date("j", strtotime($e["fecha_evento"]));
    $agrupados[$dia][] = $e;
}

$festivos = getColombianHolidays($anio);
$festivos_mes = [];
foreach ($festivos as $fecha => $nombre) {
    $f = new DateTime($fecha);
    if ((int)$f->format('m') === $mes) {
        $festivos_mes[(int)$f->format('j')] = $nombre;
    }
}

$proximos = db()->fetchAll(
    "SELECT * FROM eventos WHERE activo = TRUE AND fecha_evento >= CURRENT_DATE ORDER BY fecha_evento ASC LIMIT 5"
);

$meses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

$primer_dia = (int)date("w", strtotime("$anio-$mes-01"));
$total_dias = (int)date("t", strtotime("$anio-$mes-01"));
$dia_actual = (int)date("j");
$dias_semana = ["Dom","Lun","Mar","Mié","Jue","Vie","Sáb"];

function renderCalGrid($primer_dia, $total_dias, $dia_actual, $mes, $anio, $agrupados, $festivos_mes, $dias_semana): string
{
    global $meses;
    $html = '';
    foreach ($dias_semana as $d) {
        $html .= '<div class="day-name">' . $d . '</div>';
    }
    for ($i = 0; $i < $primer_dia; $i++) {
        $html .= '<div class="day other-month"></div>';
    }
    for ($d = 1; $d <= $total_dias; $d++) {
        $tiene = isset($agrupados[$d]);
        $es_festivo = isset($festivos_mes[$d]);
        $hoy = ($d === $dia_actual && $mes === (int)date("m") && $anio === (int)date("Y"));
        $cls = trim(($tiene ? 'has-event' : '') . ' ' . ($es_festivo ? 'festivo' : '') . ' ' . ($hoy ? 'day-today' : ''));
        $tt = ($es_festivo ? $festivos_mes[$d] : '') . ($tiene && $es_festivo ? ' · ' : '') . ($tiene ? count($agrupados[$d]) . ' evento(s)' : '');
        $html .= '<div class="day ' . $cls . '" title="' . htmlspecialchars($tt) . '">';
        $html .= '<span class="num" aria-hidden="true">' . $d . '</span>';
        $lectura = $d . ' de ' . mb_strtolower($meses[$mes]) . ($hoy ? ', hoy' : '');
        if ($es_festivo) {
            $lectura .= ', festivo: ' . $festivos_mes[$d];
        }
        if ($tiene) {
            $lectura .= ', ' . implode(', ', array_map(fn($ev) => $ev['titulo'], $agrupados[$d]));
        }
        $html .= '<span class="visually-hidden">' . htmlspecialchars($lectura) . '</span>';
        if ($es_festivo) {
            $html .= '<span class="festivo-label" aria-hidden="true">' . htmlspecialchars($festivos_mes[$d]) . '</span>';
        }
        if ($tiene) {
            $html .= '<div class="event-dots" aria-hidden="true">';
            foreach ($agrupados[$d] as $ev) {
                $html .= '<span class="event-dot" style="background:' . htmlspecialchars($ev['color']) . ';"></span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    return $html;
}

function renderProximos($proximos): string
{
    global $meses;
    if (empty($proximos)) {
        return '<p style="color:#6b7280;">No hay eventos próximos.</p>';
    }
    $html = '';
    foreach ($proximos as $e) {
        $evColor = htmlspecialchars($e["color"] ?? '#c9a24d');
        $html .= '<div class="event-card-glow mb-3" style="border-left-color: ' . $evColor . ';">';
        $html .= '<div class="event-date-badge">';
        $html .= '<span class="day">' . date("j", strtotime($e["fecha_evento"])) . '</span>';
        $html .= '<span class="month">' . mb_substr($meses[(int) date("n", strtotime($e["fecha_evento"]))], 0, 3) . '</span>';
        $html .= '</div>';
        $html .= '<div class="event-body">';
        $html .= '<span class="event-type-badge" style="background:' . $evColor . '1f;">' . htmlspecialchars($e["tipo"]) . '</span>';
        $html .= '<h3>' . htmlspecialchars($e["titulo"]) . '</h3>';
        if ($e["hora_evento"]) {
            $html .= '<small><i class="bi bi-clock" aria-hidden="true"></i> ' . date("h:i A", strtotime($e["hora_evento"])) . '</small>';
        }
        if ($e["descripcion"]) {
            $html .= '<p>' . htmlspecialchars($e["descripcion"]) . '</p>';
        }
        $html .= '</div></div>';
    }
    return $html;
}

if (isset($_GET["json"])) {
    header('Content-Type: application/json');
    echo json_encode([
        "monthTitle" => $meses[$mes] . " " . $anio,
        "calGrid"    => renderCalGrid($primer_dia, $total_dias, $dia_actual, $mes, $anio, $agrupados, $festivos_mes, $dias_semana),
        "proximos"   => renderProximos($proximos),
        "mes"        => $mes,
        "anio"       => $anio,
    ]);
    exit;
}

$pageTitle = 'Calendario';
$pageDesc = 'Calendario de eventos, festivos y actividades del Gimnasio Castillo Americano.';
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
        gap: 3px;
        background: #fff;
        border-radius: 20px;
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
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 6px 4px;
        font-size: 13px;
        background: #fcfcfc;
        border: 1px solid #f5f5f5;
        position: relative;
        min-height: 80px;
        overflow: hidden;
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
        text-decoration: none; cursor: pointer; user-select: none;
    }
    .btn-nav-cal:hover { border-color: var(--gca-gold); color: var(--gca-dark); background: rgba(201,162,77,.06); }
    .btn-nav-cal:focus-visible { outline: 2px solid #876524; outline-offset: 2px; }
    .cal-msg { color: #b91c1c; font-size: 13px; margin: -16px 0 16px; text-align: center; }

    .day.festivo {
        background: rgba(220,53,69,.08);
    }
    .day.festivo .num {
        color: #c0392b;
        font-weight: 800;
    }
    .day.festivo .festivo-label {
        font-size: 10px;
        line-height: 1.2;
        color: #c0392b;
        font-weight: 600;
        text-align: center;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-top: 2px;
    }

    .day.has-event.festivo {
        background: linear-gradient(135deg, rgba(201,162,77,.06) 0%, rgba(220,53,69,.06) 100%);
    }

    .day.has-event.festivo .num {
        background: linear-gradient(135deg, var(--gca-dark), #c0392b);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    @media (max-width: 992px) {
        .cal-grid .day { min-height: 70px; font-size: 12px; padding: 4px; }
        .cal-grid .day .num { font-size: 12px; }
        .cal-grid .day-name { font-size: 11px; padding: 10px 0; }
        .cal-nav h2 { font-size: 1.1rem; }
        .cal-col { max-width: 100%; flex: 0 0 100%; }
    }
    @media (max-width: 576px) {
        .cal-grid { gap: 2px; }
        .cal-grid .day { min-height: 44px; font-size: 10px; padding: 2px; }
        .cal-grid .day .num { font-size: 10px; margin-bottom: 1px; }
        .cal-grid .day-name { font-size: 9px; padding: 6px 0; }
        .cal-nav { flex-wrap: wrap; gap: 8px; justify-content: center; }
        .cal-nav h2 { font-size: 1rem; order: -1; width: 100%; text-align: center; }
        .btn-nav-cal { font-size: 12px; padding: 4px 12px; }
        .day.festivo .festivo-label { display: none; }
        .event-dots { gap: 2px; }
        .cal-grid .day .event-dot { width: 5px; height: 5px; }
    }

    .event-card-glow {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        display: flex;
        border-left: 4px solid var(--gca-gold);
        position: relative;
        transition: all .4s cubic-bezier(.22,1,.36,1);
        box-shadow: 0 2px 12px rgba(201,162,77,.08);
    }
    .event-card-glow::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 18px;
        background: conic-gradient(
            var(--gca-gold) 0deg,
            transparent 60deg,
            transparent 300deg,
            var(--gca-gold) 360deg
        );
        z-index: -1;
        opacity: 0;
        transition: opacity .4s ease;
    }
    .event-card-glow:hover::before {
        opacity: .7;
    }
    .event-card-glow:hover {
        transform: translateX(6px) translateY(-2px);
        box-shadow: 0 8px 28px rgba(201,162,77,.18);
    }
    .event-card-glow .event-date-badge {
        text-align: center;
        padding: 18px 14px;
        font-weight: 700;
        flex-shrink: 0;
        min-width: 72px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: rgba(201,162,77,.06);
        position: relative;
    }
    .event-card-glow .event-date-badge::after {
        content: '';
        position: absolute;
        right: 0; top: 20%; bottom: 20%;
        width: 1px;
        background: rgba(0,0,0,.06);
    }
    .event-card-glow .event-date-badge .day {
        font-size: 1.7rem;
        line-height: 1;
        display: block;
        font-weight: 900;
        color: var(--gca-dark);
    }
    .event-card-glow .event-date-badge .month {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 700;
        margin-top: 3px;
        color: #876524;
    }
    .event-card-glow .event-body {
        padding: 16px 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
    }
    .event-card-glow .event-body h3,
    .event-card-glow .event-body h5 {
        font-weight: 700;
        margin-bottom: 2px;
        font-size: 1rem;
        line-height: 1.3;
    }
    .event-card-glow .event-body small {
        font-size: 12px;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .event-card-glow .event-body p {
        font-size: 13px;
        color: #6b7280;
        margin: 6px 0 0;
        line-height: 1.5;
    }
    .event-card-glow .event-type-badge {
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 20px;
        background: rgba(201,162,77,.1);
        color: #3a3324;
        margin-bottom: 4px;
        align-self: flex-start;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .cal-loading {
        opacity: .5;
        pointer-events: none;
        transition: opacity .25s ease;
    }
</style>

<section class="hm-page-hero">
    <span class="hm-ph-glow"></span>
    <div class="hm-ph-inner" data-aos="fade-down">
        <h1 class="hm-ph-title">Calendario de <span class="grad">Eventos</span></h1>
        <p class="hm-ph-sub">Fechas importantes, actividades y eventos institucionales.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-7 cal-col mx-auto" data-aos="fade-up">
            <div class="cal-nav">
                <button type="button" class="btn btn-nav-cal" data-nav="-1" aria-label="Mes anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i> Anterior</button>
                <h2 class="cal-month-title" aria-live="polite"><?= $meses[$mes] ?> <?= $anio ?></h2>
                <button type="button" class="btn btn-nav-cal" data-nav="1" aria-label="Mes siguiente">Siguiente <i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            </div>
            <p class="cal-msg" id="calMsg" role="alert" hidden>No se pudo cargar el mes. Revisa tu conexión e intenta de nuevo.</p>

            <div class="cal-grid" id="calGrid">
                <?= renderCalGrid($primer_dia, $total_dias, $dia_actual, $mes, $anio, $agrupados, $festivos_mes, $dias_semana) ?>
            </div>
        </div>

        <div class="col-lg-5" data-aos="fade-up">
            <div class="d-flex align-items-center gap-2 mb-4 pb-2" style="border-bottom:2px solid var(--gca-gold);">
                <i class="bi bi-calendar-event" style="color:var(--gca-gold);font-size:1.2rem;" aria-hidden="true"></i>
                <h2 class="h5 fw-bold mb-0">Próximos Eventos</h2>
            </div>
            <div id="proximosContainer">
                <?= renderProximos($proximos) ?>
            </div>

            <div class="mt-4 p-3 rounded-4" style="background:#faf9f6;border:1px solid #f0eee8;">
                <small class="d-flex align-items-center gap-2" style="color:#4b5563;">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#c0392b;" aria-hidden="true"></span>
                    Festivo nacional
                </small>
                <small class="d-flex align-items-center gap-2 mt-1" style="color:#4b5563;">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--gca-gold);" aria-hidden="true"></span>
                    Evento institucional
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calGrid = document.getElementById('calGrid');
    const monthTitle = document.querySelector('.cal-month-title');
    const proximosContainer = document.getElementById('proximosContainer');
    const container = document.querySelector('.container.my-5');

    let currentMes = <?= $mes ?>;
    let currentAnio = <?= $anio ?>;

    const calMsg = document.getElementById('calMsg');

    function loadMonth(mes, anio, desdeHistorial) {
        container.classList.add('cal-loading');
        calMsg.hidden = true;

        const url = new URL(window.location.href);
        url.searchParams.set('mes', mes);
        url.searchParams.set('anio', anio);
        url.searchParams.set('json', '1');

        fetch(url)
            .then(function(r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function(data) {
                calGrid.innerHTML = data.calGrid;
                monthTitle.textContent = data.monthTitle;
                proximosContainer.innerHTML = data.proximos;
                currentMes = data.mes;
                currentAnio = data.anio;

                if (!desdeHistorial) {
                    history.pushState({ mes: data.mes, anio: data.anio }, '', '?mes=' + data.mes + '&anio=' + data.anio);
                }

                container.classList.remove('cal-loading');
            })
            .catch(function() {
                container.classList.remove('cal-loading');
                calMsg.hidden = false;
            });
    }

    document.querySelectorAll('[data-nav]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var dir = parseInt(this.getAttribute('data-nav'));
            var newMes = currentMes + dir;
            var newAnio = currentAnio;
            if (newMes < 1) { newMes = 12; newAnio--; }
            if (newMes > 12) { newMes = 1; newAnio++; }
            loadMonth(newMes, newAnio);
        });
    });

    window.addEventListener('popstate', function(e) {
        var params = new URLSearchParams(window.location.search);
        var m = parseInt(params.get('mes')) || currentMes;
        var a = parseInt(params.get('anio')) || currentAnio;
        loadMonth(m, a, true);
    });
});
</script>

<?php include "footer.php"; ?>
