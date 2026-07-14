<?php

/**
 * Plantilla del boletín (INFORME ACADÉMICO) — replica exacta del diseño
 * institucional del Gimnasio Castillo Americano.
 */

if (!function_exists('gca_desempeno_label')) {
    /**
     * Valoración cualitativa del desempeño según la nota (escala 0–100),
     * modelo del Decreto 1290.
     */
    function gca_desempeno_label($nota): string
    {
        $n = (float) $nota;
        if ($n >= 90) return 'SUPERIOR';
        if ($n >= 80) return 'ALTO';
        if ($n >= 60) return 'BÁSICO';
        return 'BAJO';
    }
}

if (!function_exists('gca_periodo_palabra')) {
    /** Convierte el número de periodo ('1'..'4') en su palabra. */
    function gca_periodo_palabra($periodo): string
    {
        $map = ['1' => 'PRIMERO', '2' => 'SEGUNDO', '3' => 'TERCERO', '4' => 'CUARTO'];
        $p = (string) $periodo;
        return $map[$p] ?? strtoupper($p);
    }
}

function boletinHTML($estudiante, $curso, $periodo, $notas, $promedio, $promediosArea = [], $logros = [], $directorNombre = '', $puesto = '')
{
    // ─── Escudo institucional embebido (base64) ───
    $escudoData = '';
    $escudoPath = __DIR__ . '/../assets/img/escudo-gca.png';
    if (is_file($escudoPath)) {
        $escudoData = 'data:image/png;base64,' . base64_encode(file_get_contents($escudoPath));
    }
    $escudoImg = $escudoData
        ? '<img src="' . $escudoData . '" alt="GCA" style="width:78px;height:auto;">'
        : '<div style="font-weight:900;font-size:20px;color:#B7943F;">GCA</div>';

    // ─── Datos derivados ───
    $documento    = trim((string)($estudiante['documento'] ?? ''));
    $nombre       = strtoupper(trim((string)($estudiante['nombre'] ?? '')));
    $estudianteId = $documento !== '' ? $documento . ' - ' . $nombre : $nombre;

    $grado  = strtoupper(trim((string)($curso['grado'] ?? '')));
    $grupo  = strtoupper(trim((string)($curso['curso_nombre'] ?? '')));

    $periodoPalabra = gca_periodo_palabra($periodo);
    $year           = date('Y');
    $periodoCodigo  = $year . '-' . str_pad(preg_replace('/\D/', '', (string)$periodo) ?: '0', 2, '0', STR_PAD_LEFT);
    $fechaGen       = date('d/m/Y H:i:s');

    // ─── Filas de la tabla, agrupadas por área ───
    $areaCounts = [];
    foreach ($notas as $n) {
        $areaCounts[$n['area']] = ($areaCounts[$n['area']] ?? 0) + 1;
    }

    $rows = '';
    $areaDone = [];
    foreach ($notas as $n) {
        $area        = $n['area'];
        $firstInArea = !isset($areaDone[$area]);
        if ($firstInArea) $areaDone[$area] = true;

        $notaVal   = (int) $n['nota'];
        $ih        = (int) ($n['intensidad_horaria'] ?? 0);
        $desempeno = gca_desempeno_label($n['nota']);
        $logroText = htmlspecialchars($logros[$n['asignatura_id']] ?? '');

        $rows .= '<tr>';
        if ($firstInArea) {
            $rows .= '<td class="c-area" rowspan="' . $areaCounts[$area] . '">'
                . htmlspecialchars(strtoupper($area)) . '</td>';
        }
        $rows .= '<td class="c-asig">' . htmlspecialchars(strtoupper($n['asignatura'])) . '</td>';
        $rows .= '<td class="c-ih">' . $ih . '</td>';
        $rows .= '<td class="c-pct">' . $notaVal . '</td>';
        $rows .= '<td class="c-des">' . $desempeno . '</td>';
        $rows .= '<td class="c-logro">' . ($logroText !== '' ? '&bull; ' . $logroText : '') . '</td>';
        $rows .= '</tr>';
    }

    $directorNombre = $directorNombre ?: '&nbsp;';
    $puestoTxt      = $puesto !== '' ? htmlspecialchars($puesto) : '&mdash;';

    return '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Informe Académico - ' . htmlspecialchars($nombre) . '</title>
<style>
    @page { margin: 8mm 7mm; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8px; color: #000; }

    .sheet { border: 1.5px solid #000; }

    /* ── Encabezado ── */
    .head { width: 100%; border-collapse: collapse; }
    .head td { vertical-align: middle; padding: 4px 6px; }
    .head .crest { width: 92px; text-align: center; }
    .head .center { text-align: center; }
    .head .center .g-line { font-size: 8px; letter-spacing: 4px; color: #6b6b6b; }
    .head .center .name {
        font-family: "DejaVu Serif", serif; font-size: 22px; font-weight: 700;
        color: #B7943F; line-height: 1.05; margin: 1px 0;
    }
    .head .center .lema { font-size: 7px; letter-spacing: 3px; color: #B7943F; margin-bottom: 3px; }
    .head .center .edu { font-size: 8px; font-weight: 700; color: #222; }
    .head .center .meta { font-size: 6.5px; color: #333; line-height: 1.5; }

    /* ── Franja fecha / página ── */
    .genrow { width: 100%; border-collapse: collapse; border-top: 1px solid #000; }
    .genrow td { padding: 2px 6px; font-size: 7px; }
    .genrow .right { text-align: right; }

    /* ── Bloque de información del estudiante ── */
    .info { width: 100%; border-collapse: collapse; border-top: 1px solid #000; }
    .info td { border: 1px solid #000; padding: 3px 6px; font-size: 8px; }
    .info .lbl { background: #ffffff; font-weight: 700; white-space: nowrap; text-align: left; }
    .info .val { font-weight: 700; }
    .info .titulo { text-align: center; font-size: 13px; font-weight: 700; letter-spacing: 1px; }

    /* ── Tabla principal de notas ── */
    .grades { width: 100%; border-collapse: collapse; margin-top: 0; }
    .grades th {
        background: #C6A46B; color: #1a1a1a; font-weight: 700; font-size: 8px;
        text-align: center; padding: 4px 3px; border: 1px solid #000;
    }
    .grades td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
    .grades .c-area  { font-weight: 700; text-align: center; vertical-align: middle; background: #f3ead6; font-size: 7.5px; }
    .grades .c-asig  { font-weight: 700; font-size: 7.5px; }
    .grades .c-ih    { text-align: center; }
    .grades .c-pct   { text-align: center; font-weight: 700; }
    .grades .c-des   { text-align: center; font-weight: 700; }
    .grades .c-logro { font-size: 7px; text-align: justify; }

    /* ── Observaciones ── */
    .obs { width: 100%; border-collapse: collapse; }
    .obs td { border: 1px solid #000; padding: 4px 6px; font-size: 8px; }
    .obs .obs-lbl { font-weight: 700; }

    /* ── Firma ── */
    .sign { text-align: center; padding: 34px 0 14px; }
    .sign .line { display: inline-block; border-top: 1px solid #000; width: 280px; padding-top: 4px; }
    .sign .nm { font-weight: 700; font-size: 9px; }
    .sign .rl { font-size: 7.5px; color: #333; }
</style>
</head>
<body>
    <div class="sheet">

        <!-- ENCABEZADO -->
        <table class="head">
            <tr>
                <td class="crest">' . $escudoImg . '</td>
                <td class="center">
                    <div class="g-line">- GIMNASIO -</div>
                    <div class="name">Castillo Americano</div>
                    <div class="lema">CULTIVAR PARA COSECHAR</div>
                    <div class="edu">EDUCACION PREESCOLAR, BASICA PRIMARIA, SECUNDARIA</div>
                    <div class="meta">
                        RESOLUCIÓN DE APROBACIÓN: 002789 DEL 20 DE NOVIEMBRE DE 2013<br>
                        DIRECCIÓN: CARRERA 3 B No. 23 - 13 CALLEJAS II<br>
                        CONTACTOS: TEL.: 3163388226<br>
                        VALLEDUPAR - CESAR
                    </div>
                </td>
                <td class="crest">' . $escudoImg . '</td>
            </tr>
        </table>

        <!-- FECHA / PÁGINA -->
        <table class="genrow">
            <tr>
                <td>Fecha de generación del documento: ' . $fechaGen . '</td>
                <td class="right">Página 1 de 1</td>
            </tr>
        </table>

        <!-- INFORMACIÓN DEL ESTUDIANTE -->
        <table class="info">
            <tr>
                <td class="titulo" colspan="7">INFORME ACADÉMICO</td>
                <td class="lbl">PUESTO</td>
                <td class="val" style="text-align:center;">' . $puestoTxt . '</td>
            </tr>
            <tr>
                <td class="lbl">ESTUDIANTE</td>
                <td class="val" colspan="6">' . htmlspecialchars($estudianteId) . '</td>
                <td class="lbl">JORNADA</td>
                <td class="val" style="text-align:center;">ÚNICA</td>
            </tr>
            <tr>
                <td class="lbl">GRADO</td>
                <td class="val">' . htmlspecialchars($grado) . '</td>
                <td class="lbl">GRUPO</td>
                <td class="val">' . htmlspecialchars($grupo) . '</td>
                <td class="lbl">PERIODO ACADÉMICO</td>
                <td class="val" style="text-align:center;">' . $periodoPalabra . '</td>
                <td class="val" style="text-align:center;">' . htmlspecialchars($periodoCodigo) . '</td>
                <td class="lbl">SEDE</td>
                <td class="val" style="text-align:center;">PRINCIPAL</td>
            </tr>
        </table>

        <!-- TABLA DE CALIFICACIONES -->
        <table class="grades">
            <thead>
                <tr>
                    <th style="width:15%;">ÁREA</th>
                    <th style="width:16%;">ASIGNATURA</th>
                    <th style="width:5%;">I.H</th>
                    <th style="width:5%;">%</th>
                    <th style="width:12%;">DESEMPEÑO</th>
                    <th style="width:47%;">LOGROS Y RESULTADOS DE APRENDIZAJE</th>
                </tr>
            </thead>
            <tbody>
                ' . $rows . '
            </tbody>
        </table>

        <!-- OBSERVACIONES -->
        <table class="obs">
            <tr>
                <td class="obs-lbl" style="height:20px;">Observaciones:</td>
            </tr>
            <tr><td style="height:22px;"></td></tr>
            <tr><td style="height:22px;"></td></tr>
        </table>

        <!-- FIRMA -->
        <div class="sign">
            <div class="line">
                <div class="nm">' . $directorNombre . '</div>
                <div class="rl">DIRECTOR(A) DE GRUPO</div>
            </div>
        </div>

    </div>
</body>
</html>';
}
