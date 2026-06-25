<?php

function boletinHTML_v2($estudiante, $curso, $periodo, $notas, $promedio, $promediosArea = [], $logros = [], $directorNombre = '', $puesto = '')
{
    $nivel = $curso['nivel'] ?? '';
    $nivelLabel = $nivel === 'primaria' ? 'PRIMARIA' : 'SECUNDARIA';

    $rows = '';
    $areaCounts = [];
    foreach ($notas as $n) {
        $areaCounts[$n['area']] = ($areaCounts[$n['area']] ?? 0) + 1;
    }
    $areaDone = [];

    foreach ($notas as $n) {
        $firstInArea = !isset($areaDone[$n['area']]);
        if ($firstInArea) $areaDone[$n['area']] = true;

        $notaVal = (int) $n['nota'];
        $ih = (int)($n['intensidad_horaria'] ?? 0);
        $logroText = htmlspecialchars($logros[$n['asignatura_id']] ?? '');

        $rows .= '<tr>';
        if ($firstInArea) {
            $areaProm = $promediosArea[$n['area']] ?? null;
            $areaLabel = htmlspecialchars($n['area']);
            if ($areaProm !== null) {
                $areaPromColor = $areaProm >= 60 ? '#2e7d32' : ($areaProm >= 40 ? '#e65100' : '#c62828');
                $areaLabel .= '<br><span style="font-size:9px;font-weight:400;">Prom: <b style="color:' . $areaPromColor . ';">' . number_format($areaProm, 1) . '%</b></span>';
            }
            $rows .= '<td rowspan="' . $areaCounts[$n['area']] . '" class="area-cell">' . $areaLabel . '</td>';
        }
        $rows .= '<td class="asignatura">' . htmlspecialchars($n['asignatura']) . '</td>';
        $rows .= '<td class="ih">' . $ih . '</td>';
        $rows .= '<td class="nota">' . $notaVal . '%</td>';
        $rows .= '<td class="desempeno"></td>';
        $rows .= '<td class="logro">' . $logroText . '</td>';
        $rows .= '<td class="competencia"></td>';
        $rows .= '</tr>';
    }

    $promedioDisplay = number_format($promedio, 1);
    $directorNombre = $directorNombre ?: '___________________________________________';

    return '<!doctype html><html><head><meta charset="utf-8"><title>Boletín - ' . htmlspecialchars($estudiante['nombre']) . '</title>' .
        '<style>
            @page { margin: 12mm 8mm; }
            body { font-family: "DejaVu Sans", sans-serif; font-size:11px; color:#111; }
            .top { width:100%; display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
            .logo { width:72px; height:72px; background:#C8A84B; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; }
            .center { text-align:center; flex:1; margin:0 12px; }
            .center .title { font-family:"DejaVu Serif", serif; font-size:20px; color:#C8A84B; font-weight:800; margin:0 }
            .center .subtitle { font-size:8px; color:#444; margin-top:4px; }
            .student { width:100%; border:1px solid #bbb; border-collapse:collapse; margin-bottom:10px; }
            .student td { border:1px solid #bbb; padding:6px; }
            .student .label { background:#f5f3ee; font-weight:700; width:12%; }
            .main { width:100%; border-collapse:collapse; font-size:10px; }
            .main th { background:#C8A84B; color:#1a1a1a; padding:6px; border:1px solid #aaa; font-weight:700; }
            .main td { padding:6px; border:1px solid #ddd; vertical-align:top; }
            .prom { text-align:right; margin-top:6px; font-weight:700; }
            .obs { width:100%; border-collapse:collapse; margin-top:8px; }
            .obs td { border:1px solid #ccc; padding:6px; }
            .signature { text-align:center; margin-top:26px; }
            .area-cell { font-weight:700; text-align:center; background:#f8f8f7; }
            .nota { font-weight:700; text-align:center; }
        </style>' .
        '</head><body>' .
        '<div class="top"><div class="logo">GCA</div><div class="center"><div class="title">Castillo Americano</div><div class="subtitle">Cultivar para cosechar — Nivel ' . $nivelLabel . '</div></div><div class="logo">GCA</div></div>' .

'<table class="student"><tr><td class="label">ESTUDIANTE:</td><td colspan="3" style="font-weight:700">' . htmlspecialchars(strtoupper($estudiante['nombre'])) . '</td><td class="label">PERIODO:</td><td style="font-weight:700">' . htmlspecialchars($periodo) . '</td></tr>' .
'<tr><td class="label">GRADO:</td><td style="font-weight:700">' . htmlspecialchars(strtoupper($curso['grado'])) . '</td><td class="label">GRUPO:</td><td style="font-weight:700">' . htmlspecialchars(strtoupper($curso['curso_nombre'])) . '</td><td class="label">JORNADA:</td><td style="font-weight:700">ÚNICA</td></tr>' .
'<tr><td class="label">PUESTO:</td><td colspan="5" style="font-weight:700">' . ($puesto ?: '—') . '</td></tr>' .
'</table>' .

        '<table class="main"><thead><tr><th style="width:14%">ÁREA</th><th style="width:18%">ASIGNATURA</th><th style="width:6%">I.H</th><th style="width:6%">%</th><th style="width:18%">DESEMPEÑO FINAL</th><th style="width:22%">LOGROS</th><th style="width:16%">COMPETENCIA</th></tr></thead><tbody>' .
        $rows .
        '</tbody></table>' .

        '<div class="prom">PROMEDIO GENERAL: ' . $promedioDisplay . '</div>' .

        '<table class="obs"><tr><td style="width:140px;font-weight:700;background:#f5f3ee">OBSERVACIONES</td><td></td></tr><tr><td></td><td style="height:40px"></td></tr></table>' .

        '<div class="signature"><div style="width:260px;margin:0 auto;border-top:1px solid #000;padding-top:6px">' . $directorNombre . '<div style="font-size:9px;color:#555">DIRECTOR(A) DE GRUPO</div></div></div>' .

        '</body></html>';
}
