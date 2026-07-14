<?php

/**
 * ============================================================
 * MODELO DE CALIFICACIÓN (consistente en toda la aplicación)
 * ============================================================
 *   • Definitiva de asignatura = nota registrada.
 *   • Definitiva de ÁREA       = promedio de sus asignaturas ponderado por
 *     'porcentaje' (aporte configurado de cada asignatura al área). Si los
 *     porcentajes están en su valor por defecto, equivale al promedio simple.
 *   • PROMEDIO GENERAL         = promedio de las definitivas de área ponderado
 *     por la intensidad horaria total de cada área.
 *
 * Por construcción, el promedio general es la media ponderada de las áreas, de
 * modo que el "puesto" (ranking) siempre es coherente con el promedio mostrado.
 * Las áreas con intensidad horaria 0 (p. ej. Comportamiento) no afectan el
 * promedio general.
 *
 * REQUIERE que la base de datos tenga las tablas/columnas creadas por
 * database/migration_postgres_fix.sql (asignatura_grado, porcentaje).
 * ============================================================
 */

/**
 * Calcula la definitiva por área y el promedio general a partir de un conjunto
 * de filas de notas.
 *
 * @param array $notas Filas con claves: nota, area, intensidad_horaria, porcentaje
 * @return array ['areas' => [area => definitiva(float)], 'general' => float|null]
 */
function calcularDefinitivas(array $notas): array
{
    // area => acumuladores
    $acc = [];
    foreach ($notas as $n) {
        $area = $n['area'] ?? '';
        $nota = (float) ($n['nota'] ?? 0);
        $pct  = (float) ($n['porcentaje'] ?? 100);
        if ($pct <= 0) $pct = 100;                 // porcentaje inválido → peso neutro

        if (!isset($acc[$area])) {
            $acc[$area] = ['num' => 0.0, 'den' => 0.0];
        }
        $acc[$area]['num'] += $nota * $pct;        // Σ nota·porcentaje (ponderado dentro del área)
        $acc[$area]['den'] += $pct;                // Σ porcentaje
    }

    // Definitiva de cada área = promedio ponderado por el porcentaje de sus materias.
    // El promedio GENERAL es la media simple de las áreas. La intensidad horaria
    // NO interviene en el cálculo: es solo informativa en el boletín.
    $areasDef = [];
    foreach ($acc as $area => $d) {
        $areasDef[$area] = round($d['den'] > 0 ? $d['num'] / $d['den'] : 0.0, 1);
    }

    $general = count($areasDef) > 0
        ? round(array_sum($areasDef) / count($areasDef), 1)
        : null;

    return ['areas' => $areasDef, 'general' => $general];
}

/**
 * Obtiene las notas de un estudiante en un período con el peso (intensidad
 * horaria del grado y porcentaje) necesario para el cálculo ponderado.
 */
function obtenerNotasPonderadas($conexion, $estudiante_id, $periodo, $anio = null): array
{
    $anio = $anio ?? (int) date('Y');
    $stmtG = $conexion->prepare("
        SELECT c.grado FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmtG->execute([$estudiante_id]);
    $grado = $stmtG->fetchColumn();

    $stmt = $conexion->prepare("
        SELECT a.area,
               n.nota,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = ?
        WHERE n.estudiante_id = ? AND n.periodo = ? AND n.anio = ?
    ");
    $stmt->execute([$grado, $estudiante_id, $periodo, $anio]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ranking del curso por promedio general ponderado (descendente).
 * @return array [ ['id'=>int, 'nombre'=>string, 'promedio'=>float|null], ... ]
 */
function obtenerRankingCurso($conexion, $curso_id, $periodo, $anio = null)
{
    $anio = $anio ?? (int) date('Y');
    $stmtG = $conexion->prepare("SELECT grado FROM cursos WHERE id = ?");
    $stmtG->execute([$curso_id]);
    $grado = $stmtG->fetchColumn();

    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, a.area, n.nota,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM estudiantes e
        JOIN notas n ON n.estudiante_id = e.id
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = ?
        WHERE e.curso_id = ? AND n.periodo = ? AND n.anio = ?
    ");
    $stmt->execute([$grado, $curso_id, $periodo, $anio]);

    $porEst = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $eid = $r['id'];
        if (!isset($porEst[$eid])) {
            $porEst[$eid] = ['id' => (int)$eid, 'nombre' => $r['nombre'], 'notas' => []];
        }
        $porEst[$eid]['notas'][] = $r;
    }

    $ranking = [];
    foreach ($porEst as $e) {
        $ranking[] = [
            'id'       => $e['id'],
            'nombre'   => $e['nombre'],
            'promedio' => calcularDefinitivas($e['notas'])['general'],
        ];
    }
    usort($ranking, fn($a, $b) => ($b['promedio'] ?? -1) <=> ($a['promedio'] ?? -1));
    return $ranking;
}

function calcularPuestoCurso($conexion, $estudiante_id, $periodo, $anio = null)
{
    $curso_id = obtenerCursoIdEstudiante($conexion, $estudiante_id);
    if (!$curso_id) return null;

    $ranking = obtenerRankingCurso($conexion, $curso_id, $periodo, $anio);
    if (empty($ranking)) return null;

    $total = count($ranking);
    $position = 0;
    foreach ($ranking as $i => $r) {
        if ((int)$r['id'] === (int)$estudiante_id) {
            $position = $i + 1;
            break;
        }
    }

    if ($position === 0) return null;
    return "$position/$total";
}

function obtenerCursoIdEstudiante($conexion, $estudiante_id)
{
    $stmt = $conexion->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
    $stmt->execute([$estudiante_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    return $est ? $est['curso_id'] : null;
}

/**
 * Promedio general ponderado de un estudiante en un período.
 */
function calcularPromedioGeneralEstudiante($conexion, $estudiante_id, $periodo, $anio = null)
{
    $notas = obtenerNotasPonderadas($conexion, $estudiante_id, $periodo, $anio);
    if (count($notas) === 0) return null;
    return calcularDefinitivas($notas)['general'];
}

/**
 * Consolidado ANUAL de un estudiante: la definitiva anual de cada asignatura es el
 * promedio de sus notas en todos los períodos del año; con eso se calculan las
 * definitivas de área y el PROMEDIO GENERAL del año, y si aprobó (>= nota mínima).
 *
 * @return array ['areas'=>[area=>def], 'general'=>float|null, 'aprobo'=>bool, 'areas_perdidas'=>int]
 */
function consolidadoAnualEstudiante($conexion, $estudiante_id, $anio = null, $notaMinima = 60)
{
    $anio = $anio ?? (int) date('Y');
    $stmtG = $conexion->prepare("SELECT c.grado FROM estudiantes e JOIN cursos c ON e.curso_id = c.id WHERE e.id = ?");
    $stmtG->execute([$estudiante_id]);
    $grado = $stmtG->fetchColumn();

    // Definitiva anual por asignatura = promedio de sus notas en los períodos del año
    $stmt = $conexion->prepare("
        SELECT a.area,
               AVG(n.nota) AS nota,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = ?
        WHERE n.estudiante_id = ? AND n.anio = ?
        GROUP BY a.id, a.area, ag.intensidad_horaria, a.intensidad_horaria, ag.porcentaje
    ");
    $stmt->execute([$grado, $estudiante_id, $anio]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($filas) === 0) {
        return ['areas' => [], 'general' => null, 'aprobo' => false, 'areas_perdidas' => 0];
    }

    $def = calcularDefinitivas($filas);
    $perdidas = 0;
    foreach ($def['areas'] as $v) {
        if ($v < $notaMinima) $perdidas++;
    }
    $aprobo = ($def['general'] !== null && $def['general'] >= $notaMinima);

    return ['areas' => $def['areas'], 'general' => $def['general'], 'aprobo' => $aprobo, 'areas_perdidas' => $perdidas];
}

/**
 * Ranking anual del curso (por promedio general del año), para el puesto anual.
 */
function rankingAnualCurso($conexion, $curso_id, $anio = null, $notaMinima = 60)
{
    $anio = $anio ?? (int) date('Y');
    $ests = $conexion->prepare("SELECT id, nombre FROM estudiantes WHERE curso_id = ? ORDER BY nombre");
    $ests->execute([$curso_id]);
    $ranking = [];
    foreach ($ests->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $c = consolidadoAnualEstudiante($conexion, (int)$e['id'], $anio, $notaMinima);
        $ranking[] = ['id' => (int)$e['id'], 'nombre' => $e['nombre'], 'promedio' => $c['general'], 'aprobo' => $c['aprobo'], 'areas_perdidas' => $c['areas_perdidas']];
    }
    usort($ranking, fn($a, $b) => ($b['promedio'] ?? -1) <=> ($a['promedio'] ?? -1));
    return $ranking;
}

/**
 * Mejores estudiantes por grado, usando el promedio general ponderado.
 * @return array [ grado => [ ['id','nombre','grado','curso_nombre','nivel','promedio'], ... ] ]
 */
function calcularMejoresPorGrado($conexion, $periodo, $limite = 5, $anio = null)
{
    $anio = $anio ?? (int) date('Y');
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre, c.nivel,
               a.area, n.nota,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        JOIN notas n ON n.estudiante_id = e.id AND n.periodo = ? AND n.anio = ?
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
    ");
    $stmt->execute([$periodo, $anio]);

    // Agrupar filas por estudiante
    $est = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $eid = $r['id'];
        if (!isset($est[$eid])) {
            $est[$eid] = [
                'id' => (int)$eid, 'nombre' => $r['nombre'], 'grado' => $r['grado'],
                'curso_nombre' => $r['curso_nombre'], 'nivel' => $r['nivel'], 'notas' => [],
            ];
        }
        $est[$eid]['notas'][] = $r;
    }

    // Promedio general por estudiante, agrupado por grado
    $porGrado = [];
    foreach ($est as $e) {
        $porGrado[$e['grado']][] = [
            'id' => $e['id'], 'nombre' => $e['nombre'], 'grado' => $e['grado'],
            'curso_nombre' => $e['curso_nombre'], 'nivel' => $e['nivel'],
            'promedio' => calcularDefinitivas($e['notas'])['general'],
        ];
    }

    // Orden de grados + top N por grado
    $ordenGrado = [
        'maternal'=>1,'prejardin'=>2,'jardin'=>3,'transicion'=>4,
        'primero'=>5,'segundo'=>6,'tercero'=>7,'cuarto'=>8,'quinto'=>9,
        'sexto'=>10,'septimo'=>11,'octavo'=>12,'noveno'=>13,'decimo'=>14,
        'undecimo'=>15,'once'=>15,
    ];
    uksort($porGrado, fn($a, $b) => ($ordenGrado[$a] ?? 16) <=> ($ordenGrado[$b] ?? 16));

    $resultados = [];
    foreach ($porGrado as $grado => $lista) {
        usort($lista, fn($a, $b) => ($b['promedio'] ?? -1) <=> ($a['promedio'] ?? -1));
        $resultados[$grado] = array_slice($lista, 0, $limite);
    }
    return $resultados;
}

function obtenerNivelGrado($grado)
{
    $preescolar = ['maternal', 'prejardin', 'jardin', 'transicion'];
    $primaria   = ['primero', 'segundo', 'tercero', 'cuarto', 'quinto'];

    $g = mb_strtolower(trim($grado));
    if (in_array($g, $preescolar)) return 'Preescolar';
    if (in_array($g, $primaria))   return 'Primaria';
    return 'Secundaria';
}
