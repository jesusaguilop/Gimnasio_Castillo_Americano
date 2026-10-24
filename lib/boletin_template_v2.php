<?php

/**
 * Plantilla del boletín (v2) — réplica del MODELO BOLETIN.docx/.pdf del colegio.
 *
 * Medidas tomadas del modelo (hoja carta, 612 x 792 pt):
 *  - Encabezado enmarcado (escudo · wordmark + datos institucionales · escudo),
 *    repetido en todas las páginas (position: fixed en el margen superior).
 *  - Tabla de datos: INFORME ACADÉMICO / PUESTO, ESTUDIANTE / JORNADA,
 *    GRADO / GRUPO / PERIODO ACADÉMICO / SEDE (Arial negrita 8).
 *  - Fila de títulos con fondo dorado #D1B377: ÁREA · ASIGNATURA · I.H · % · EXAMEN ·
 *    DESEMPEÑO FINAL ASIGNATURA · LOGROS Y RESULTADOS DE APRENDIZAJE ·
 *    DESEMPEÑO POR COMPETENCIA (Calibri negrita 8). Área con rowspan.
 *  - OBSERVACIONES (título + contenido), firma centrada del director de grupo
 *    y número de página abajo a la derecha.
 *  - Bordes negros de 0,5 pt; texto en negro.
 *
 * Fuente: el modelo usa Calibri; aquí se usa Carlito (métrica idéntica, licencia
 * OFL) desde assets/img/boletin/fonts. Si dompdf no puede cargarla (chroot),
 * cae a Helvetica/Arial, que también conserva el aspecto.
 *
 * Ningún cuadro queda vacío: logro por defecto según el desempeño y
 * observación general autocompletada.
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

if (!function_exists('gcaMayus')) {
    /** Mayúsculas seguras para UTF-8 (strtoupper no convierte tildes ni ñ). */
    function gcaMayus($s): string
    {
        return mb_strtoupper(trim((string) $s), 'UTF-8');
    }
}

/**
 * @param array       $estudiante     fila de estudiantes (nombre, ...)
 * @param array       $curso          ['nivel','grado','curso_nombre']
 * @param string|int  $periodo
 * @param array       $notas          filas: asignatura, area, asignatura_id, intensidad_horaria, nota, porcentaje
 * @param float       $promedio       promedio general ponderado
 * @param array       $promediosArea  (no se muestra: el modelo no tiene esa casilla)
 * @param array       $logros         asignatura_id => texto
 * @param string      $directorNombre ya escapado por el generador (o una línea de guiones bajos)
 * @param string      $puesto         "n/total"
 * @param int|null    $anio           año lectivo (si no llega, el año actual)
 */
function boletinHTML_v2($estudiante, $curso, $periodo, $notas, $promedio, $promediosArea = [], $logros = [], $directorNombre = '', $puesto = '', $anio = null)
{
    $base = __DIR__ . '/../assets/img/boletin/';

    // Imágenes institucionales embebidas (base64, seguras para dompdf)
    $imgData = function ($file) use ($base) {
        $path = $base . $file;
        return is_file($path) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path)) : '';
    };
    $escudo   = $imgData('escudo.jpeg');
    $wordmark = $imgData('wordmark.jpeg');

    // Fuente Carlito (equivalente métrico de Calibri, como en el modelo)
    $fontUrl = function ($file) use ($base) {
        $p = realpath($base . 'fonts/' . $file);
        return $p ? 'file://' . str_replace('\\', '/', $p) : '';
    };
    $carlitoR = $fontUrl('Carlito-Regular.ttf');
    $carlitoB = $fontUrl('Carlito-Bold.ttf');

    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

    $nombre    = $h(gcaMayus($estudiante['nombre'] ?? ''));
    $grado     = $h(gcaMayus($curso['grado'] ?? ''));
    $grupo     = $h(gcaMayus($curso['curso_nombre'] ?? ''));
    $puestoTxt = $h($puesto !== '' && $puesto !== null ? $puesto : '0/0');
    $periodoTxt = $h(gcaMayus($periodo));
    $anioTxt   = $h($anio ?: date('Y'));

    // ── Orden de áreas: el del generador, agrupando por área y con
    //    COMPORTAMIENTO siempre al final (como en el modelo). ──
    $grupos = [];
    foreach ($notas as $n) {
        $grupos[(string) $n['area']][] = $n;
    }
    $fin = [];
    foreach (array_keys($grupos) as $area) {
        if (gcaMayus($area) === 'COMPORTAMIENTO') {
            $fin[$area] = $grupos[$area];
            unset($grupos[$area]);
        }
    }
    $grupos += $fin;

    // ── Filas de notas ──
    // Cada área es UNA fila: celda ÁREA (equivale a la celda combinada del
    // modelo) + tabla anidada con sus asignaturas. Así dompdf nunca parte un
    // área entre dos páginas (con rowspan descuadra las celdas al paginar).
    $rows = '';
    foreach ($grupos as $area => $items) {
        $sub = '';
        $last = count($items) - 1;
        foreach ($items as $i => $n) {
            $notaVal = (int) round((float) $n['nota']);
            $ih      = (int) ($n['intensidad_horaria'] ?? 0);
            $pct     = (int) round((float) ($n['porcentaje'] ?? 100));
            [$desLabel] = gcaDesempeno($n['nota']);

            $logroRaw = trim((string) ($logros[$n['asignatura_id']] ?? ''));
            if ($logroRaw === '') $logroRaw = gcaLogroDefecto($desLabel);

            $pos = ($i === 0 ? ' first' : '') . ($i === $last ? ' last' : '');
            $sub .= '<tr class="' . trim($pos) . '">'
                . '<td class="c-asig w-asig">' . $h(gcaMayus($n['asignatura'])) . '</td>'
                . '<td class="c-num w-ih">' . $ih . '</td>'
                . '<td class="c-num w-pct">' . $pct . '</td>'
                . '<td class="c-num w-ex">' . (isset($n['examen']) ? (int) $n['examen'] : '–') . '</td>'
                . '<td class="c-nota w-des">' . $notaVal . '</td>'
                . '<td class="c-logro w-log">' . nl2br($h($logroRaw)) . '</td>'
                . '<td class="c-comp w-comp">' . $h($desLabel) . '</td>'
                . '</tr>';
        }
        $rows .= '<tr class="area">'
            . '<td class="c-area w-area">' . $h(gcaMayus($area)) . '</td>'
            . '<td class="c-sub"><table class="sub">' . $sub . '</table></td>'
            . '</tr>';
    }
    if ($rows === '') {
        $rows = '<tr class="area"><td class="c-vacio" colspan="2">No se registran calificaciones para este período.</td></tr>';
    }

    // ── Observación general autocompletada ──
    if (count($notas) === 0) {
        $obsAuto = 'No se registran calificaciones del estudiante para este período académico.';
    } else {
        [$gLabel] = gcaDesempeno($promedio);
        $tail = ($gLabel === 'SUPERIOR' || $gLabel === 'ALTO')
            ? '¡Felicitaciones por su excelente rendimiento y compromiso durante el período!'
            : ($gLabel === 'BÁSICO'
                ? 'Se le anima a fortalecer sus hábitos de estudio para mejorar su rendimiento.'
                : 'Se recomienda acompañamiento en casa y un plan de refuerzo para superar las dificultades.');
        $obsAuto = 'Durante el período, el/la estudiante obtuvo un promedio general de '
            . number_format((float) $promedio, 1) . ' correspondiente a un desempeño ' . $gLabel . '. ' . $tail;
    }

    // Director: el generador lo entrega ya escapado, o una línea "____" si no hay.
    $dirTxt = trim((string) $directorNombre);
    if ($dirTxt === '' || preg_match('/^_+$/', $dirTxt)) {
        $dirTxt = '&nbsp;';
    } else {
        // Rehacer las mayúsculas con soporte UTF-8 (strtoupper deja "ñ"/tildes en minúscula)
        $dirTxt = $h(gcaMayus(html_entity_decode($dirTxt, ENT_QUOTES, 'UTF-8')));
    }

    ob_start();
    ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Boletín - <?= $nombre ?></title>
<style>
<?php if ($carlitoR && $carlitoB): ?>
    @font-face { font-family: "Carlito"; font-style: normal; font-weight: normal; src: url("<?= $carlitoR ?>") format("truetype"); }
    @font-face { font-family: "Carlito"; font-style: normal; font-weight: bold;   src: url("<?= $carlitoB ?>") format("truetype"); }
<?php endif; ?>
    /* Hoja carta como el modelo; márgenes = posición de la tabla en el modelo */
    @page { size: letter portrait; margin: 182.1pt 23.8pt 70pt 28.1pt; }
    body { margin: 0; padding: 0; }
    body { font-family: "Carlito", "Helvetica", sans-serif; font-size: 8pt; color: #000; background: #fff; }
    table { border-collapse: collapse; width: 560.1pt; }
    td { border: 0.5pt solid #000; padding: 0 5.4pt; vertical-align: top; }

    /* ── Encabezado institucional (se repite en cada página) ── */
    #encabezado { position: fixed; top: -146.7pt; left: 0; width: 560.1pt; }
    .head td { height: 117.5pt; padding: 0; }
    .head .escudo { width: 78pt; text-align: center; vertical-align: middle; }
    .head .escudo img { width: 60pt; height: 81.5pt; }
    .head .inst { text-align: center; padding: 1.8pt 26pt 0 0; }
    .head .inst .wm { width: 171.7pt; height: 42.8pt; }
    .head .inst .l1 { font-weight: bold; font-size: 9pt; line-height: 0.91; margin-top: 10pt; }
    .head .inst .l2 { font-weight: bold; font-size: 8pt; line-height: 0.91; margin-top: 5.5pt; }

    /* ── Número de página ── */
    #pie { position: fixed; bottom: -22pt; left: 0; width: 501pt; text-align: right; font-size: 11pt; }
    #pie .num:after { content: counter(page); }

    /* ── Tabla de datos (Arial negrita 8, como el modelo) ── */
    .info td { font-family: "Helvetica", sans-serif; font-weight: bold; font-size: 8pt; line-height: 0.905; padding-top: 1.6pt; }
    .info .r1 td { height: 14.5pt; }
    .info .r2 td { height: 13.4pt; }
    .info .r3 td { height: 14.5pt; padding-bottom: 1.2pt; }
    .info .titulo { font-size: 10pt; line-height: 0.905; text-align: center; }
    .info .c { text-align: center; }

    /* ── Tabla de notas ── */
    /* anchos de contenido (= ancho del modelo - 10,8 pt de relleno - borde) */
    .w-area { width: 66.8pt; } .w-asig { width: 77.9pt; } .w-ih { width: 12.7pt; } .w-pct { width: 11.7pt; }
    .w-des { width: 68.1pt; } .w-log { width: 143.1pt; } .w-comp { width: 66.7pt; }
    /* EXAMEN final del período (solo informativo): su ancho sale de LOGROS
       para que la tabla conserve el ancho total del modelo. */
    .w-ex { width: 34pt; }
    .notas td { line-height: 0.91; }
    .titulos td { background: #D1B377; font-weight: bold; text-align: center; height: 21.7pt; padding-top: 2pt; line-height: 0.91; }
    .notas tr.area { page-break-inside: avoid; }
    .notas .c-sub { padding: 0; vertical-align: top; }
    .notas table.sub { width: 100%; }
    .sub td { height: 34pt; }
    .sub td.w-asig { border-left: none; }
    .sub td.w-comp { border-right: none; }
    .sub td.w-log { width: 142.4pt; }
    .sub tr.first td { border-top: none; }
    .sub tr.last td { border-bottom: none; }
    .notas .c-area, .notas .c-asig, .notas .c-num, .notas .c-nota, .notas .c-comp { text-align: center; vertical-align: middle; }
    .notas .c-nota { font-weight: bold; font-size: 10pt; }
    .notas .c-comp { font-weight: bold; }
    .notas .c-logro { text-align: justify; vertical-align: middle; padding-top: 2pt; padding-bottom: 2pt; }
    .notas .c-vacio { text-align: center; vertical-align: middle; height: 38pt; }

    /* ── Observaciones + firma (no se separan) ── */
    .cierre { page-break-inside: avoid; }
    .obs .oh { font-weight: bold; font-size: 10pt; line-height: 0.91; height: 21.4pt; }
    .obs .ob { font-size: 9pt; line-height: 0.91; text-align: justify; height: 20.4pt; padding-top: 3pt; padding-bottom: 3pt; }
    .firma { text-align: center; padding-top: 50pt; width: 560.1pt; }
    .firma .linea { font-size: 11pt; line-height: 0.91; }
    .firma .nom { font-weight: bold; font-size: 10pt; line-height: 0.91; }
    .firma .rol { font-weight: bold; font-size: 8pt; line-height: 0.91; }
</style>
</head>
<body>

    <!-- ENCABEZADO (fijo: se repite en cada página) -->
    <div id="encabezado">
        <table class="head">
            <tr>
                <td class="escudo"><?php if ($escudo): ?><img src="<?= $escudo ?>" alt="GCA"><?php endif; ?></td>
                <td class="inst" style="width:376.3pt">
                    <?php if ($wordmark): ?><img class="wm" src="<?= $wordmark ?>" alt="Gimnasio Castillo Americano"><?php else: ?><div style="font-size:20pt;line-height:42.8pt;color:#b8962e">Gimnasio Castillo Americano</div><?php endif; ?>
                    <div class="l1">EDUCACION PREESCOLAR, BASICA PRIMARIA, SECUNDARIA</div>
                    <div class="l2">RESOLUCIÓN DE APROBACIÓN: 002789 DEL 20 DE NOVIEMBRE DE 2013<br>
                        DIRECCIÓN: CARRERA 3 B No. 23-13 CALLEJAS II<br>
                        CONTACTOS: TEL.: 3163388226<br>
                        VALLEDUPAR - CESAR</div>
                </td>
                <td class="escudo"><?php if ($escudo): ?><img src="<?= $escudo ?>" alt="GCA"><?php endif; ?></td>
            </tr>
        </table>
    </div>

    <!-- NÚMERO DE PÁGINA -->
    <div id="pie"><span class="num"></span></div>

    <!-- DATOS -->
    <table class="info">
        <tr class="r3" style="visibility:hidden">
            <td style="width:67.3pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:78.4pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:36.2pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:28.1pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:52pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:33.4pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:35.1pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:65.2pt;height:0;padding:0 5.4pt;border:none"></td>
            <td style="width:67.2pt;height:0;padding:0 5.4pt;border:none"></td>
        </tr>
        <tr class="r1">
            <td class="titulo" colspan="7">INFORME ACADÉMICO</td>
            <td>PUESTO</td>
            <td class="c"><?= $puestoTxt ?></td>
        </tr>
        <tr class="r2">
            <td>ESTUDIANTE</td>
            <td colspan="6"><?= $nombre ?></td>
            <td>JORNADA</td>
            <td class="c">ÚNICA</td>
        </tr>
        <tr class="r3">
            <td>GRADO</td>
            <td class="c"><?= $grado ?></td>
            <td>GRUPO</td>
            <td class="c"><?= $grupo ?></td>
            <td class="c">PERIODO ACADÉMICO</td>
            <td class="c"><?= $periodoTxt ?></td>
            <td class="c"><?= $anioTxt ?></td>
            <td>SEDE</td>
            <td class="c">PRINCIPAL</td>
        </tr>
    </table>

    <!-- NOTAS -->
    <table class="titulos">
        <tr>
            <td class="w-area">ÁREA</td>
            <td class="w-asig">ASIGNATURA</td>
            <td class="w-ih">I.H</td>
            <td class="w-pct">%</td>
            <td class="w-ex">EXAMEN</td>
            <td class="w-des">DESEMPEÑO FINAL ASIGNATURA</td>
            <td class="w-log">LOGROS Y RESULTADOS DE APRENDIZAJE</td>
            <td class="w-comp">DESEMPEÑO POR COMPETENCIA</td>
        </tr>
    </table>
    <table class="notas">
        <?= $rows ?>
    </table>

    <!-- OBSERVACIONES + FIRMA -->
    <div class="cierre">
        <table class="obs">
            <tr><td class="oh">OBSERVACIONES</td></tr>
            <tr><td class="ob"><?= $h($obsAuto) ?></td></tr>
        </table>
        <div class="firma">
            <div class="linea">___________________________________________</div>
            <div class="nom"><?= $dirTxt ?></div>
            <div class="rol">DIRECTOR(A) DE GRUPO</div>
        </div>
    </div>

</body>
</html>
    <?php
    return ob_get_clean();
}
