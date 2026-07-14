<?php

/**
 * Plantilla del boletín (v2) — replica el MODELO BOLETIN.docx del colegio.
 * Encabezado institucional (escudo + wordmark + resolución/dirección),
 * tabla de datos (con Promedio y Puesto), tabla de notas por área
 * (ÁREA · ASIGNATURA · I.H · % · DESEMPEÑO FINAL · LOGROS · DESEMPEÑO POR COMPETENCIA),
 * observaciones autocompletadas y firma. Ningún cuadro queda vacío.
 */

if (!function_exists('gcaDesempeno')) {
    /** Devuelve [etiqueta, color] del desempeño según la nota (escala 0-100, aprueba 60). */
    function gcaDesempeno($nota): array
    {
        $n = (float) $nota;
        if ($n >= 90) return ['SUPERIOR', '#1b5e20'];
        if ($n >= 80) return ['ALTO', '#2e7d32'];
        if ($n >= 60) return ['BÁSICO', '#e65100'];
        return ['BAJO', '#b71c1c'];
    }
}

if (!function_exists('gcaLogroDefecto')) {
    /** Logro genérico según el nivel de desempeño (cuando no hay logro registrado). */
    function gcaLogroDefecto(string $nivel): string
    {
        switch ($nivel) {
            case 'SUPERIOR': return 'Supera los logros propuestos, evidenciando excelencia, autonomía y dominio del área.';
            case 'ALTO':     return 'Alcanza satisfactoriamente los logros propuestos para el período.';
            case 'BÁSICO':   return 'Alcanza los logros mínimos del área; puede fortalecer su desempeño con dedicación.';
            default:         return 'Presenta dificultades en el alcance de los logros; requiere acompañamiento y refuerzo.';
        }
    }
}

function boletinHTML_v2($estudiante, $curso, $periodo, $notas, $promedio, $promediosArea = [], $logros = [], $directorNombre = '', $puesto = '')
{
    // Imágenes institucionales embebidas (base64, seguras para dompdf)
    $imgData = function ($rel) {
        $path = __DIR__ . '/../' . $rel;
        return is_file($path) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path)) : '';
    };
    $escudo   = $imgData('assets/img/boletin/escudo.jpeg');
    $wordmark = $imgData('assets/img/boletin/wordmark.jpeg');

    $perMap = ['1' => 'PRIMERO', '2' => 'SEGUNDO', '3' => 'TERCERO', '4' => 'CUARTO'];
    $periodoLabel = $perMap[(string) $periodo] ?? strtoupper((string) $periodo);

    $nombre = htmlspecialchars(strtoupper($estudiante['nombre'] ?? ''));
    $grado  = htmlspecialchars(strtoupper($curso['grado'] ?? ''));
    $grupo  = htmlspecialchars(strtoupper($curso['curso_nombre'] ?? ''));
    $puestoTxt = htmlspecialchars($puesto ?: '0/0');

    [$gLabel, $gColor] = gcaDesempeno($promedio);
    $promedioTxt = number_format((float) $promedio, 1);

    // ── Filas de notas agrupadas por área (celda de área con rowspan) ──
    $areaCounts = [];
    foreach ($notas as $n) {
        $areaCounts[$n['area']] = ($areaCounts[$n['area']] ?? 0) + 1;
    }
    $areaDone = [];
    $rows = '';
    foreach ($notas as $n) {
        $area = $n['area'];
        $firstInArea = !isset($areaDone[$area]);
        if ($firstInArea) $areaDone[$area] = true;

        $notaVal = (int) round($n['nota']);
        $ih      = (int) ($n['intensidad_horaria'] ?? 0);
        $pct     = (int) round($n['porcentaje'] ?? 100);
        [$desLabel, $desColor] = gcaDesempeno($n['nota']);

        $logroRaw = trim((string) ($logros[$n['asignatura_id']] ?? ''));
        if ($logroRaw === '') $logroRaw = gcaLogroDefecto($desLabel);
        $logroText = nl2br(htmlspecialchars($logroRaw));

        $rows .= '<tr>';
        if ($firstInArea) {
            $areaProm = $promediosArea[$area] ?? null;
            $areaCell = '<strong>' . htmlspecialchars(strtoupper($area)) . '</strong>';
            if ($areaProm !== null) {
                [$aLabel, $aColor] = gcaDesempeno($areaProm);
                $areaCell .= '<div class="area-def">Definitiva<br><span style="color:' . $aColor . '">' . number_format($areaProm, 1) . '</span><br><span class="area-lvl" style="color:' . $aColor . '">' . $aLabel . '</span></div>';
            }
            $rows .= '<td rowspan="' . $areaCounts[$area] . '" class="c-area">' . $areaCell . '</td>';
        }
        $rows .= '<td class="c-asig">' . htmlspecialchars($n['asignatura']) . '</td>';
        $rows .= '<td class="c-ih">' . $ih . '</td>';
        $rows .= '<td class="c-pct">' . $pct . '%</td>';
        $rows .= '<td class="c-nota" style="color:' . $desColor . '">' . $notaVal . '</td>';
        $rows .= '<td class="c-logro">' . $logroText . '</td>';
        $rows .= '<td class="c-comp" style="color:' . $desColor . '">' . $desLabel . '</td>';
        $rows .= '</tr>';
    }

    // ── Observación general autocompletada ──
    $tail = ($gLabel === 'SUPERIOR' || $gLabel === 'ALTO')
        ? '¡Felicitaciones por su excelente rendimiento y compromiso durante el período!'
        : ($gLabel === 'BÁSICO'
            ? 'Se le anima a fortalecer sus hábitos de estudio para mejorar su rendimiento.'
            : 'Se recomienda acompañamiento en casa y un plan de refuerzo para superar las dificultades.');
    $obsAuto = 'Durante el período, el/la estudiante obtuvo un promedio general de ' . $promedioTxt
        . ' correspondiente a un desempeño ' . $gLabel . '. ' . $tail;

    ob_start();
    ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Boletín - <?= $nombre ?></title>
<style>
    @page { margin: 10mm 9mm; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1a1a1a; }

    /* ── Encabezado institucional ── */
    .head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .head td { vertical-align: middle; }
    .head .escudo { width: 82px; text-align: center; }
    .head .escudo img { height: 74px; }
    .head .inst { text-align: center; padding: 0 6px; }
    .head .inst .wm { height: 34px; margin-bottom: 3px; }
    .head .inst .name { font-family: "DejaVu Serif", serif; font-size: 17px; font-weight: 700; color: #b8962e; margin: 0; }
    .head .inst .lines { font-size: 7.5px; color: #444; line-height: 1.35; margin-top: 2px; }
    .gold-rule { height: 3px; background: #c9a24d; border: none; margin: 4px 0 8px; }

    /* ── Tabla de datos ── */
    .info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .info td { border: 1px solid #c9b98a; padding: 5px 7px; font-size: 9.5px; }
    .info .k { background: #f6f1e2; font-weight: 700; color: #6b5a24; text-transform: uppercase; font-size: 8.5px; letter-spacing: .3px; white-space: nowrap; }
    .info .v { font-weight: 700; }
    .info .titlebar { background: #c9a24d; color: #1a1400; font-weight: 700; text-align: center; letter-spacing: 1px; font-size: 11px; }

    /* ── Tabla de notas ── */
    .grades { width: 100%; border-collapse: collapse; }
    .grades th { background: #c9a24d; color: #1a1400; border: 1px solid #b8962e; padding: 6px 4px; font-size: 8px; text-transform: uppercase; letter-spacing: .3px; font-weight: 700; }
    .grades td { border: 1px solid #d9cfae; padding: 5px 6px; vertical-align: middle; font-size: 9px; }
    .grades .c-area { text-align: center; font-size: 9px; background: #faf6ec; }
    .grades .c-area .area-def { font-size: 7.5px; font-weight: 400; color: #555; margin-top: 3px; line-height: 1.4; }
    .grades .c-area .area-lvl { font-weight: 700; font-size: 7px; }
    .grades .c-asig { font-weight: 600; }
    .grades .c-ih, .grades .c-pct { text-align: center; }
    .grades .c-nota { text-align: center; font-weight: 700; font-size: 12px; }
    .grades .c-logro { text-align: left; font-size: 8.5px; color: #333; }
    .grades .c-comp { text-align: center; font-weight: 700; font-size: 8.5px; }

    /* ── Observaciones ── */
    .obs { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .obs .oh { background: #f6f1e2; color: #6b5a24; font-weight: 700; text-transform: uppercase; font-size: 8.5px; letter-spacing: .5px; border: 1px solid #c9b98a; padding: 5px 7px; }
    .obs .ob { border: 1px solid #c9b98a; padding: 8px; font-size: 9px; color: #333; line-height: 1.5; }

    /* ── Firma ── */
    .sign { text-align: center; margin-top: 34px; }
    .sign .line { width: 260px; margin: 0 auto; border-top: 1px solid #333; padding-top: 5px; font-weight: 700; font-size: 10px; }
    .sign .role { font-size: 8.5px; color: #555; letter-spacing: .5px; }
</style>
</head>
<body>

    <!-- ENCABEZADO -->
    <table class="head">
        <tr>
            <td class="escudo"><?php if ($escudo): ?><img src="<?= $escudo ?>" alt="GCA"><?php endif; ?></td>
            <td class="inst">
                <?php if ($wordmark): ?>
                    <img class="wm" src="<?= $wordmark ?>" alt="Gimnasio Castillo Americano">
                <?php else: ?>
                    <p class="name">Gimnasio Castillo Americano</p>
                <?php endif; ?>
                <div class="lines">
                    EDUCACIÓN PREESCOLAR, BÁSICA PRIMARIA Y SECUNDARIA<br>
                    RESOLUCIÓN DE APROBACIÓN N° 002789 DEL 20 DE NOVIEMBRE DE 2013<br>
                    DIRECCIÓN: CARRERA 3B No. 23-13, CALLEJAS II &nbsp;·&nbsp; TEL.: 3163388226<br>
                    VALLEDUPAR - CESAR
                </div>
            </td>
            <td class="escudo"><?php if ($escudo): ?><img src="<?= $escudo ?>" alt="GCA"><?php endif; ?></td>
        </tr>
    </table>
    <hr class="gold-rule">

    <!-- DATOS -->
    <table class="info">
        <tr>
            <td class="titlebar" colspan="4">INFORME ACADÉMICO</td>
            <td class="k">Promedio</td>
            <td class="v" style="text-align:center;color:<?= $gColor ?>"><?= $promedioTxt ?></td>
            <td class="k">Puesto</td>
            <td class="v" style="text-align:center"><?= $puestoTxt ?></td>
        </tr>
        <tr>
            <td class="k">Estudiante</td>
            <td class="v" colspan="5"><?= $nombre ?></td>
            <td class="k">Jornada</td>
            <td class="v" style="text-align:center">ÚNICA</td>
        </tr>
        <tr>
            <td class="k">Grado</td>
            <td class="v"><?= $grado ?></td>
            <td class="k">Grupo</td>
            <td class="v"><?= $grupo ?></td>
            <td class="k">Período</td>
            <td class="v"><?= htmlspecialchars($periodoLabel) ?></td>
            <td class="k">Sede</td>
            <td class="v" style="text-align:center">PRINCIPAL</td>
        </tr>
    </table>

    <!-- NOTAS -->
    <table class="grades">
        <thead>
            <tr>
                <th style="width:12%">Área</th>
                <th style="width:16%">Asignatura</th>
                <th style="width:5%">I.H</th>
                <th style="width:5%">%</th>
                <th style="width:12%">Desempeño final asignatura</th>
                <th style="width:35%">Logros y resultados de aprendizaje</th>
                <th style="width:15%">Desempeño por competencia</th>
            </tr>
        </thead>
        <tbody>
            <?= $rows ?>
        </tbody>
    </table>

    <!-- OBSERVACIONES -->
    <table class="obs">
        <tr><td class="oh">Observaciones</td></tr>
        <tr><td class="ob"><?= htmlspecialchars($obsAuto) ?></td></tr>
    </table>

    <!-- FIRMA -->
    <div class="sign">
        <div class="line"><?= $directorNombre ?: '___________________________________________' ?></div>
        <div class="role">DIRECTOR(A) DE GRUPO</div>
    </div>

</body>
</html>
    <?php
    return ob_get_clean();
}
