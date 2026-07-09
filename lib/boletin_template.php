<?php

function boletinHTML($estudiante, $curso, $periodo, $notas, $promedio, $promediosArea = [], $logros = [], $directorNombre = '', $puesto = '')
{
    $nivel = $curso['nivel'] ?? '';
    $nivelLabel = $nivel === 'primaria' ? 'PRIMARIA' : 'SECUNDARIA';

    // Build table rows grouped by area
    $rows = '';
    $currentArea = '';
    $areaCounts = [];

    // First pass: count rows per area for rowspan
    foreach ($notas as $n) {
        $areaCounts[$n['area']] = ($areaCounts[$n['area']] ?? 0) + 1;
    }
    $areaRowCounts = $areaCounts;

    $areaDone = [];
    $total_ih = 0;
    foreach ($notas as $n) {
        $firstInArea = !isset($areaDone[$n['area']]);
        if ($firstInArea) {
            $areaDone[$n['area']] = true;
        }

        $notaVal = (int) $n['nota'];
        $ih = (int)($n['intensidad_horaria'] ?? 0);
        $total_ih += $ih;
        $logroText = htmlspecialchars($logros[$n['asignatura_id']] ?? '');

        $rows .= '<tr>';
        if ($firstInArea) {
            $style = 'font-weight:700;font-size:11px;text-align:center;';
            if ($areaRowCounts[$n['area']] > 1) {
                $style .= 'vertical-align:middle;';
            }
            $areaProm = $promediosArea[$n['area']] ?? null;
            $areaLabel = htmlspecialchars($n['area']);
            if ($areaProm !== null) {
                $areaPromColor = $areaProm >= 60 ? '#2e7d32' : ($areaProm >= 40 ? '#e65100' : '#c62828');
                $areaLabel .= '<br><span style="font-size:10px;font-weight:400;">Prom: <b style="color:' . $areaPromColor . ';">' . number_format($areaProm, 1) . '%</b></span>';
            }
            $rows .= '<td rowspan="' . $areaRowCounts[$n['area']] . '" style="' . $style . '">' . $areaLabel . '</td>';
        }
        $rows .= '<td>' . htmlspecialchars($n['asignatura']) . '</td>';
        $rows .= '<td style="text-align:center;">' . $ih . '</td>';
        $rows .= '<td style="text-align:center;font-weight:700;">' . $notaVal . '%</td>';
        $rows .= '<td></td>';
        $rows .= '<td style="font-size:10px;color:#555;">' . ($logroText ?: '') . '</td>';
        $rows .= '<td></td>';
        $rows .= '</tr>';
    }

    $promedioDisplay = number_format($promedio, 1);
    $promColor = $promedio >= 60 ? '#2e7d32' : ($promedio >= 40 ? '#e65100' : '#c62828');

    $directorNombre = $directorNombre ?: '___________________________________________';

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Boletín - ' . htmlspecialchars($estudiante['nombre']) . '</title>
        <style>
            @page {
                margin: 12mm 8mm 10mm;
            }
            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 10px;
                color: #000;
                line-height: 1.3;
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
            .header-table td {
                vertical-align: middle;
                padding: 2px 6px;
            }
            .header-logo {
                width: 70px;
                height: 70px;
                background: #C8A84B;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                font-weight: 900;
                color: #fff;
                text-align: center;
                margin: 0 auto;
            }
            .header-logo img {
                max-width: 70px;
                max-height: 70px;
            }
            .school-name {
                font-family: "DejaVu Serif", serif;
                text-align: center;
            }
            .school-name .gimnasio {
                font-size: 9px;
                letter-spacing: 3px;
                color: #444;
                font-weight: 400;
            }
            .school-name .castillo {
                font-size: 22px;
                font-weight: 700;
                color: #C8A84B;
                margin: 0;
                line-height: 1.1;
            }
            .school-name .lema {
                font-size: 8px;
                font-weight: 600;
                color: #C8A84B;
                letter-spacing: 2px;
                margin-top: 2px;
            }
            .school-name .datos {
                font-size: 7px;
                color: #444;
                font-weight: 600;
                margin-top: 4px;
                line-height: 1.4;
            }
            .school-name .datos span {
                display: inline-block;
            }
            .student-block {
                border: 1px solid #999;
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 10px;
                font-size: 9px;
            }
            .student-block td {
                border: 1px solid #999;
                padding: 3px 6px;
            }
            .student-block .label {
                font-weight: 700;
                background: #f5f3ee;
                width: 1%;
                white-space: nowrap;
            }
            .student-block .title-cell {
                font-size: 11px;
                font-weight: 700;
                text-align: center;
                background: #C8A84B;
                color: #1a1a1a;
                letter-spacing: 2px;
            }
            .main-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9px;
                page-break-inside: auto;
            }
            .main-table thead {
                display: table-header-group;
            }
            .main-table tr {
                page-break-inside: avoid;
            }
            .main-table th {
                background: #C8A84B;
                color: #1a1a1a;
                font-weight: 700;
                text-align: center;
                padding: 5px 3px;
                font-size: 8px;
                border: 1px solid #aaa;
            }
            .main-table td {
                padding: 4px 5px;
                border: 1px solid #ccc;
            }
            .obs-section {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
                font-size: 9px;
            }
            .obs-section td {
                border: 1px solid #ccc;
                padding: 4px 6px;
            }
            .obs-section .obs-title {
                font-weight: 700;
                background: #f5f3ee;
                width: 120px;
            }
            .signature {
                text-align: center;
                margin-top: 30px;
                font-size: 9px;
            }
            .signature .line {
                border-top: 1px solid #000;
                width: 260px;
                margin: 0 auto 4px;
                padding-top: 6px;
            }
            .signature .name {
                font-weight: 700;
                font-size: 10px;
            }
            .signature .title {
                font-size: 8px;
                color: #555;
            }
            .promedio-box {
                text-align: right;
                margin-top: 6px;
                font-size: 10px;
                font-weight: 700;
            }
            .promedio-box .badge {
                display: inline-block;
                background: ' . $promColor . ';
                color: #fff;
                padding: 2px 10px;
                border-radius: 4px;
                font-size: 12px;
            }
        </style>
    </head>
    <body>

        <!-- ─── HEADER ─── -->
        <table class="header-table">
            <tr>
                <td style="width:80px;text-align:center;">
                    <div class="header-logo">
                        <span>GCA</span>
                    </div>
                </td>
                <td>
                    <div class="school-name">
                        <div class="gimnasio">GIMNASIO</div>
                        <div class="castillo">Castillo Americano</div>
                        <div class="lema">CULTIVAR PARA COSECHAR</div>
                        <div class="datos">
                            NIVEL ' . $nivelLabel . '
                            &nbsp;|&nbsp; RES. APROB. No. _____
                            &nbsp;|&nbsp; CARRERA 19B No. 16A&ndash;48
                            &nbsp;|&nbsp; TEL: 3001234567
                            &nbsp;|&nbsp; VALLEDUPAR &ndash; CESAR
                        </div>
                    </div>
                </td>
                <td style="width:80px;text-align:center;">
                    <div class="header-logo">
                        <span>GCA</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ─── STUDENT INFO ─── -->
        <table class="student-block">
            <tr>
                <td class="title-cell" colspan="7">INFORME ACADÉMICO</td>
                <td class="title-cell" style="font-size:9px;">PUESTO: ' . ($puesto ?: '-') . '</td>
            </tr>
            <tr>
                <td class="label" style="width:10%;">ESTUDIANTE:</td>
                <td colspan="7" style="font-weight:700;font-size:10px;">' . htmlspecialchars(strtoupper($estudiante['nombre'])) . '</td>
            </tr>
            <tr>
                <td class="label">JORNADA:</td>
                <td style="font-weight:600;">ÚNICA</td>
                <td class="label">GRADO:</td>
                <td style="font-weight:600;">' . htmlspecialchars(strtoupper($curso['grado'])) . '</td>
                <td class="label">GRUPO:</td>
                <td style="font-weight:600;">' . htmlspecialchars(strtoupper($curso['curso_nombre'])) . '</td>
                <td class="label">PERIODO:</td>
                <td style="font-weight:600;">' . htmlspecialchars($periodo) . '</td>
            </tr>
            <tr>
                <td class="label">SEDE:</td>
                <td colspan="7" style="font-weight:600;">PRINCIPAL</td>
            </tr>
        </table>

        <!-- ─── MAIN GRADES TABLE ─── -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width:14%;">ÁREA</th>
                    <th style="width:16%;">ASIGNATURA</th>
                    <th style="width:6%;">I.H</th>
                    <th style="width:7%;">%</th>
                    <th style="width:19%;">DESEMPEÑO FINAL<br>ASIGNATURA</th>
                    <th style="width:22%;">LOGROS Y RESULTADOS<br>DE APRENDIZAJE</th>
                    <th style="width:16%;">DESEMPEÑO<br>POR COMPETENCIA</th>
                </tr>
            </thead>
            <tbody>
                ' . $rows . '
            </tbody>
        </table>

        <div class="promedio-box">
            PROMEDIO GENERAL: <span class="badge">' . $promedioDisplay . '</span>
        </div>

        <!-- ─── OBSERVATIONS ─── -->
        <table class="obs-section">
            <tr>
                <td class="obs-title">OBSERVACIONES</td>
                <td></td>
            </tr>
            <tr>
                <td class="obs-title"></td>
                <td style="height:30px;"></td>
            </tr>
            <tr>
                <td class="obs-title"></td>
                <td style="height:30px;"></td>
            </tr>
        </table>

        <!-- ─── SIGNATURE ─── -->
        <div class="signature">
            <div class="line">
                <div class="name">' . $directorNombre . '</div>
                <div class="title">DIRECTOR(A) DE GRUPO</div>
            </div>
        </div>

    </body>
    </html>';
}
