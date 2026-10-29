<?php
/**
 * Generación de boletines (lógica compartida).
 * Usado por:
 *   - profesores/generar_boletin.php        (individual, se transmite al navegador)
 *   - profesores/generar_boletines_curso.php (masivo, director de grupo)
 *
 * NO valida permisos: el archivo que llama debe verificar acceso.
 */
// dompdf 3.1.6+ (vulnerabilidades de la 2.x corregidas), instalado con Composer en vendor/
require_once __DIR__ . '/../vendor/autoload.php';
// Para ruta_subidas()/url_subidas(): no depender de que el archivo que llama
// haya cargado ya la configuración.
require_once __DIR__ . '/../config/env.php';
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
function generarBoletinEstudiante(PDO $conexion, int $estudiante_id, string $periodo, int $generado_por, string $template = 'v2', int $anio = 0, bool $paz_y_salvo = false): array
{
    if ($anio <= 0) $anio = (int) date('Y');
    // Estudiante + curso + director de grupo en UNA consulta
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, e.documento, e.curso_id, e.padre_id,
               c.grado, c.nombre AS curso_nombre, c.nivel,
               (SELECT u.nombre FROM directores_grupo dg JOIN usuarios u ON u.id = dg.profesor_id
                 WHERE dg.curso_id = e.curso_id ORDER BY dg.created_at LIMIT 1) AS director_nombre
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$estudiante_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$est) {
        return ['ok' => false, 'motivo' => 'Estudiante no encontrado'];
    }

    // Notas del período + porcentaje del grado + logro, en UNA consulta (rank_helper)
    $notas = obtenerNotasPonderadas($conexion, $estudiante_id, $periodo, $anio);
    if (count($notas) === 0) {
        return ['ok' => false, 'motivo' => 'Sin notas', 'estudiante' => $est];
    }
    $logros = [];
    foreach ($notas as $n) {
        if ($n['logro'] !== null) $logros[$n['asignatura_id']] = $n['logro'];
    }

    // Definitivas + promedio + puesto: el mismo cálculo que todas las pantallas
    $def = calcularDefinitivas($notas);
    if ($def['general'] === null) {
        return ['ok' => false, 'motivo' => 'No hay notas suficientes para calcular el promedio.', 'estudiante' => $est];
    }
    $promediosArea = $def['areas'];
    $promedio = $def['general'];
    $puesto = calcularPuestoCurso($conexion, $estudiante_id, $periodo, $anio, (int) $est['curso_id']);

    $curso = ['nivel' => $est['nivel'], 'grado' => $est['grado'], 'curso_nombre' => $est['curso_nombre']];

    $directorNombre = $est['director_nombre'] ? htmlspecialchars(mb_strtoupper($est['director_nombre'])) : '___________________________________________';

    // HTML
    $useV2 = ($template === 'v2');
    if ($useV2) {
        $html = boletinHTML_v2($est, $curso, $periodo, $notas, $promedio, $promediosArea, $logros, $directorNombre, $puesto, $anio);
    } else {
        $html = boletinHTML($est, $curso, $periodo, $notas, $promedio, $promediosArea, $logros, $directorNombre, $puesto);
    }

    // PDF
    $options = new Options();
    // Sin acceso remoto: las imágenes van embebidas en base64 y la fuente
    // (Carlito, métrica de Calibri) se lee del disco dentro del chroot.
    $options->set('isRemoteEnabled', false);
    $options->setChroot([__DIR__, realpath(__DIR__ . '/../assets/img/boletin')]);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();

    // Guardar en disco
    $year = $anio;
    $dir_fs = ruta_subidas("boletines/{$year}/{$periodo}");
    if (!is_dir($dir_fs)) {
        mkdir($dir_fs, 0777, true);
    }
    // El nombre lleva un sufijo aleatorio a propósito: antes era
    // boletin_{id}_{periodo}_{año}.pdf y cualquiera podía adivinar la URL del
    // boletín de otro estudiante. El acceso normal es por
    // boletin_descargar.php (que valida quién pide), y este sufijo es la
    // segunda barrera si el directorio quedara servido como estático.
    $filename = "boletin_{$est['id']}_{$periodo}_{$year}_" . bin2hex(random_bytes(8)) . ".pdf";

    file_put_contents("{$dir_fs}/{$filename}", $pdf);
    $ruta_relativa = url_subidas("boletines/{$year}/{$periodo}") . "/{$filename}";

    // Registrar en BD. Queda PENDIENTE de publicación (el admin decide el paso al padre).
    // Al (re)generar se reinicia a no-publicado para que el admin apruebe la versión vigente.
    // El CTE "anterior" devuelve la ruta del PDF previo en el MISMO viaje a la BD
    // (todas las partes de la sentencia ven la foto previa a la actualización).
    $pazSQL = $paz_y_salvo ? 'TRUE' : 'FALSE';
    $rec = $conexion->prepare("
        WITH anterior AS (
            SELECT ruta_pdf FROM boletines_pdf WHERE estudiante_id = ? AND periodo = ? AND year = ?
        )
        INSERT INTO boletines_pdf (estudiante_id, periodo, year, ruta_pdf, generado_por, paz_y_salvo, publicado, publicado_en)
        VALUES (?, ?, ?, ?, ?, $pazSQL, FALSE, NULL)
        ON CONFLICT (estudiante_id, periodo, year) DO UPDATE
            SET ruta_pdf = EXCLUDED.ruta_pdf, generado_por = EXCLUDED.generado_por,
                paz_y_salvo = $pazSQL, publicado = FALSE, publicado_en = NULL
        RETURNING (SELECT ruta_pdf FROM anterior) AS ruta_anterior
    ");
    $rec->execute([$estudiante_id, $periodo, $year, $estudiante_id, $periodo, $year, $ruta_relativa, $generado_por]);

    // Al regenerar, borrar el PDF anterior (después de registrar el nuevo: si la
    // BD fallara, el boletín vigente seguiría teniendo su archivo).
    $rutaVieja = $rec->fetchColumn();
    if ($rutaVieja && $rutaVieja !== $ruta_relativa) {
        $archivoViejo = __DIR__ . '/../' . ltrim($rutaVieja, '/');
        if (is_file($archivoViejo)) {
            @unlink($archivoViejo);
        }
    }

    return [
        'ok' => true,
        'ruta' => $ruta_relativa,
        'pdf' => $pdf,
        'promedio' => $promedio,
        'puesto' => $puesto,
        'estudiante' => $est,
    ];
}
