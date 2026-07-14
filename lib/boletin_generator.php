<?php
/**
 * Generación de boletines (lógica compartida).
 * Usado por:
 *   - profesores/generar_boletin.php        (individual, se transmite al navegador)
 *   - profesores/generar_boletines_curso.php (masivo, director de grupo)
 *
 * NO valida permisos: el archivo que llama debe verificar acceso.
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/boletin_template.php';
require_once __DIR__ . '/boletin_template_v2.php';
require_once __DIR__ . '/rank_helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera el PDF del boletín de un estudiante, lo guarda en disco y lo registra
 * en boletines_pdf (con promedio y puesto calculados).
 *
 * @return array {
 *   ok: bool, motivo: ?string, ruta: ?string, pdf: ?string (bytes),
 *   promedio: ?float, puesto: ?string, estudiante: ?array
 * }
 */
function generarBoletinEstudiante(PDO $conexion, int $estudiante_id, string $periodo, int $generado_por, string $template = 'v2', int $anio = 0): array
{
    if ($anio <= 0) $anio = (int) date('Y');
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento, e.curso_id, e.padre_id,
               c.grado, c.nombre AS curso_nombre, c.nivel
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$estudiante_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$est) {
        return ['ok' => false, 'motivo' => 'Estudiante no encontrado'];
    }

    // Notas del período (con intensidad horaria del grado y porcentaje)
    $notasStmt = $conexion->prepare("
        SELECT a.nombre AS asignatura, a.area, a.id AS asignatura_id,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               n.nota,
               COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = ?
        WHERE n.estudiante_id = ? AND n.periodo = ? AND n.anio = ?
        ORDER BY a.area, a.nombre
    ");
    $notasStmt->execute([$est['grado'], $estudiante_id, $periodo, $anio]);
    $notas = $notasStmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($notas) === 0) {
        return ['ok' => false, 'motivo' => 'Sin notas', 'estudiante' => $est];
    }

    // Logros
    $logros = [];
    $sl = $conexion->prepare("SELECT asignatura_id, logro FROM logros WHERE estudiante_id = ? AND periodo = ? AND anio = ?");
    $sl->execute([$estudiante_id, $periodo, $anio]);
    foreach ($sl->fetchAll(PDO::FETCH_ASSOC) as $l) {
        $logros[$l['asignatura_id']] = $l['logro'];
    }

    // Definitivas + promedio (ponderado) + puesto
    $def = calcularDefinitivas($notas);
    $promediosArea = $def['areas'];
    $promedio = $def['general'] ?? 0;
    $puesto = calcularPuestoCurso($conexion, $estudiante_id, $periodo, $anio);

    $curso = ['nivel' => $est['nivel'], 'grado' => $est['grado'], 'curso_nombre' => $est['curso_nombre']];

    // Director de grupo
    $sd = $conexion->prepare("
        SELECT u.nombre FROM directores_grupo dg
        JOIN usuarios u ON dg.profesor_id = u.id WHERE dg.curso_id = ?
    ");
    $sd->execute([$est['curso_id']]);
    $dir = $sd->fetch(PDO::FETCH_ASSOC);
    $directorNombre = $dir ? htmlspecialchars(strtoupper($dir['nombre'])) : '___________________________________________';

    // HTML
    $useV2 = ($template === 'v2');
    if ($useV2) {
        $html = boletinHTML_v2($est, $curso, $periodo, $notas, $promedio, $promediosArea, $logros, $directorNombre, $puesto);
    } else {
        $html = boletinHTML($est, $curso, $periodo, $notas, $promedio, $promediosArea, $logros, $directorNombre, $puesto);
    }

    // PDF
    $options = new Options();
    $options->set('isRemoteEnabled', $useV2);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();

    // Guardar en disco
    $year = $anio;
    $dir_fs = __DIR__ . "/../assets/boletines/{$year}/{$periodo}";
    if (!is_dir($dir_fs)) {
        mkdir($dir_fs, 0777, true);
    }
    $filename = "boletin_{$est['id']}_{$periodo}_{$year}.pdf";
    file_put_contents("{$dir_fs}/{$filename}", $pdf);
    $ruta_relativa = "assets/boletines/{$year}/{$periodo}/{$filename}";

    // Registrar en BD
    $rec = $conexion->prepare("
        INSERT INTO boletines_pdf (estudiante_id, periodo, year, ruta_pdf, generado_por)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (estudiante_id, periodo, year) DO UPDATE
            SET ruta_pdf = EXCLUDED.ruta_pdf, generado_por = EXCLUDED.generado_por
    ");
    $rec->execute([$estudiante_id, $periodo, $year, $ruta_relativa, $generado_por]);

    return [
        'ok' => true,
        'ruta' => $ruta_relativa,
        'pdf' => $pdf,
        'promedio' => $promedio,
        'puesto' => $puesto,
        'estudiante' => $est,
    ];
}
