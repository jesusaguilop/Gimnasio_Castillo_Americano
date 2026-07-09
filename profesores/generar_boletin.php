<?php
session_start();
require_once "../config/database.php";
require_once "../lib/vendor/autoload.php";
require_once "../lib/boletin_template.php";
require_once "../lib/boletin_template_v2.php";
require_once "../lib/rank_helper.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "profesor") {
    header("Location: ../login.php");
    exit;
}

$profesor_id = $_SESSION["id"];
$estudiante_id = $_GET['estudiante'] ?? null;
$periodo = $_GET['periodo'] ?? null;

if (!$estudiante_id || !$periodo) {
    die("Faltan parámetros.");
}

// Verify the teacher has access to this student (through their asignaciones)
$check = $conexion->prepare("
    SELECT e.id, e.nombre, e.documento, e.curso_id,
           c.grado, c.nombre AS curso_nombre, c.nivel
    FROM estudiantes e
    JOIN cursos c ON e.curso_id = c.id
    JOIN profesor_curso_asignatura pca ON c.id = pca.curso_id
    WHERE e.id = ? AND pca.profesor_id = ?
    GROUP BY e.id
");
$check->execute([$estudiante_id, $profesor_id]);
$estudiante = $check->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    die("No tienes acceso a este estudiante.");
}

// Get notas for this student in this period
$notas = $conexion->prepare("
    SELECT a.nombre AS asignatura, a.area, a.id AS asignatura_id, a.intensidad_horaria, n.nota
    FROM notas n
    JOIN asignaturas a ON n.asignatura_id = a.id
    WHERE n.estudiante_id = ? AND n.periodo = ?
    ORDER BY a.area, a.nombre
");
$notas->execute([$estudiante_id, $periodo]);
$notas = $notas->fetchAll(PDO::FETCH_ASSOC);

if (count($notas) === 0) {
    die("No hay notas registradas para este estudiante en el período seleccionado.");
}

// Get logros
$logros = [];
$stmtLog = $conexion->prepare("
    SELECT asignatura_id, logro FROM logros WHERE estudiante_id = ? AND periodo = ?
");
$stmtLog->execute([$estudiante_id, $periodo]);
foreach ($stmtLog->fetchAll(PDO::FETCH_ASSOC) as $l) {
    $logros[$l['asignatura_id']] = $l['logro'];
}

// Calculate average
$sum = array_sum(array_column($notas, 'nota'));
$promedio = round($sum / count($notas), 1);

$curso = [
    'nivel' => $estudiante['nivel'],
    'grado' => $estudiante['grado'],
    'curso_nombre' => $estudiante['curso_nombre'],
];

// Calculate student's position in course
$puesto = calcularPuestoCurso($conexion, $estudiante_id, $periodo);

// Look up director de grupo
$directorNombre = '';
$stmtDir = $conexion->prepare("
    SELECT u.nombre FROM directores_grupo dg
    JOIN usuarios u ON dg.profesor_id = u.id
    WHERE dg.curso_id = ?
");
$stmtDir->execute([$estudiante['curso_id']]);
$dirRow = $stmtDir->fetch(PDO::FETCH_ASSOC);
if ($dirRow) {
    $directorNombre = htmlspecialchars(strtoupper($dirRow['nombre']));
} else {
    $directorNombre = '___________________________________________';
}

// Generate HTML
$template = $_GET['template'] ?? '';
if ($template === 'v2') {
    $html = boletinHTML_v2($estudiante, $curso, $periodo, $notas, $promedio, $logros, $directorNombre, $puesto);
    $enableRemote = true;
} else {
    $html = boletinHTML($estudiante, $curso, $periodo, $notas, $promedio, $logros, $directorNombre, $puesto);
    $enableRemote = false;
}

// PDF options
$options = new Options();
$options->set('isRemoteEnabled', $enableRemote);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save to disk
$year = date('Y');
$dir = "../assets/boletines/{$year}/{$periodo}";
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$filename = "boletin_{$estudiante['id']}_{$periodo}_{$year}.pdf";
$filepath = "{$dir}/{$filename}";
file_put_contents($filepath, $dompdf->output());

// Record in DB
$stmtRec = $conexion->prepare("
    INSERT INTO boletines_pdf (estudiante_id, periodo, year, ruta_pdf, generado_por)
    VALUES (?, ?, ?, ?, ?)
    ON CONFLICT (estudiante_id, periodo, year) DO UPDATE SET ruta_pdf = EXCLUDED.ruta_pdf, generado_por = EXCLUDED.generado_por
");
$ruta_relativa = "assets/boletines/{$year}/{$periodo}/{$filename}";
$stmtRec->execute([$estudiante_id, $periodo, $year, $ruta_relativa, $profesor_id]);

// Output PDF to browser
$dompdf->stream("boletin_{$estudiante['nombre']}_{$periodo}.pdf", ['Attachment' => false]);
exit;
