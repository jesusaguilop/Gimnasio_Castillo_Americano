<?php

/**
 * ============================================================
 * MODELO DE CALIFICACIÓN — punto ÚNICO de cálculo de la aplicación
 * ============================================================
 * Toda pantalla que muestre definitivas, promedio general o puesto
 * (dashboard/registro del profesor, dirección de grupo, resultados del año,
 * portal del padre, mejores estudiantes, boletín PDF) calcula AQUÍ.
 *
 *   • Nota de asignatura: entero 0..100 (columna notas.nota es INTEGER).
 *   • Definitiva de ÁREA  = Σ(nota · %) / Σ(%), con % = asignatura_grado.porcentaje
 *     (peso de la asignatura dentro de su área para ese grado; cada área suma 100).
 *     Una asignatura SIN nota no entra ni al numerador ni al denominador: el área
 *     se calcula con las asignaturas que sí tienen nota (ver 'cobertura').
 *     Si la asignatura no está en el plan del grado (sin fila en asignatura_grado)
 *     pesa 100.
 *   • PROMEDIO GENERAL    = MEDIA SIMPLE de las definitivas de TODAS las áreas,
 *     Comportamiento incluido (decisión del colegio, 2026-10-23: es parte de la
 *     nota final). AREAS_FUERA_DEL_PROMEDIO queda vacío.
 *   • EXAMEN FINAL DEL PERÍODO (notas.examen): solo se guarda y se muestra en
 *     el boletín. NO entra al cálculo: la nota que registra el profesor ya es
 *     la definitiva de la asignatura en el período.
 *   • La INTENSIDAD HORARIA NO interviene en ningún cálculo (dato informativo).
 *   • Redondeo: definitiva de área a 1 decimal; el promedio general es la media
 *     de esas definitivas YA redondeadas (lo que ve el padre en el boletín), y se
 *     redondea a 1 decimal (PHP round: mitad hacia arriba).
 *   • Desempeño: escala POR AÑO LECTIVO que define el administrador en
 *     Plataforma (escalaDesempeno()). Por defecto Superior ≥ 90, Alto 80–89,
 *     Básico 60–79, Bajo < 60. Aprueba quien llega a Básico.
 *   • PUESTO = ranking del curso por promedio general. Empates: mismo puesto
 *     ("1, 2, 2, 4"); el orden de desempate visual es alfabético.
 *     Solo entran al ranking los estudiantes con promedio (con alguna nota).
 *   • CONSOLIDADO ANUAL: definitiva anual de cada asignatura = promedio de sus
 *     notas en los períodos que tiene; con eso → áreas → promedio del año.
 *     Aprueba el año con promedio general ≥ notaMinimaAprobacion($anio).
 * ============================================================
 */

/* ============================================================
 * ESCALA DE DESEMPEÑO por año lectivo
 * ------------------------------------------------------------
 * Los nombres son los del Decreto 1290 (Superior, Alto, Básico, Bajo); el
 * colegio fija en su SIEE dónde empieza cada uno. Se guarda en
 * configuraciones con la clave "escala_desempeno_<año>" y el valor JSON
 * {"superior":90,"alto":80,"basico":60}. Un año sin escala propia usa la del
 * año anterior más cercano que la tenga (así rige "desde ese año"), y si no
 * hay ninguna, la de siempre: 90 / 80 / 60. La nota mínima para aprobar es
 * el inicio de Básico.
 * ============================================================ */
const ESCALA_DESEMPENO_DEFECTO = ['superior' => 90, 'alto' => 80, 'basico' => 60];

/** Lee de la BD el año activo y las escalas guardadas (una consulta por request). */
function escalasGuardadas(): array
{
    static $datos = null;
    if ($datos !== null) return $datos;
    $datos = ['anio_activo' => (int) date('Y'), 'escalas' => []];
    try {
        $pdo = class_exists('App\Base\Database') ? \App\Base\Database::connection() : ($GLOBALS['conexion'] ?? null);
        if (!$pdo) return $datos;
        $rows = $pdo->query("SELECT clave, valor FROM configuraciones
                             WHERE clave = 'anio_activo' OR clave LIKE 'escala_desempeno_%'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if ($r['clave'] === 'anio_activo') {
                if ((int) $r['valor'] > 0) $datos['anio_activo'] = (int) $r['valor'];
                continue;
            }
            $anio = (int) substr($r['clave'], strlen('escala_desempeno_'));
            $e = json_decode((string) $r['valor'], true);
            if ($anio > 0 && is_array($e) && validarEscala($e) === null) {
                $datos['escalas'][$anio] = array_map('intval', array_intersect_key($e, ESCALA_DESEMPENO_DEFECTO));
            }
        }
        krsort($datos['escalas']);
    } catch (Throwable $e) {
        error_log('[escala desempeño] ' . $e->getMessage());
    }
    return $datos;
}

/** Año lectivo activo (configuraciones.anio_activo). */
function anioLectivoActivo(): int
{
    return escalasGuardadas()['anio_activo'];
}

/**
 * Escala vigente para un año lectivo (por defecto, el activo).
 * @return array{superior:int, alto:int, basico:int, desde:?int}  desde = año en que se fijó (null = la de siempre)
 */
function escalaDesempeno(?int $anio = null): array
{
    $anio = $anio ?: anioLectivoActivo();
    foreach (escalasGuardadas()['escalas'] as $desde => $e) {   // de la más nueva a la más vieja
        if ($desde <= $anio) return $e + ['desde' => $desde];
    }
    return ESCALA_DESEMPENO_DEFECTO + ['desde' => null];
}

/** Revisa una escala: enteros, dentro de 1..100 y en orden Superior > Alto > Básico. null = válida. */
function validarEscala(array $e): ?string
{
    foreach (['superior', 'alto', 'basico'] as $k) {
        if (!isset($e[$k]) || !is_numeric($e[$k]) || (int) $e[$k] != $e[$k]) return 'Cada nivel necesita un número entero.';
        if ((int) $e[$k] < 1 || (int) $e[$k] > 100) return 'Los valores van de 1 a 100.';
    }
    if (!((int) $e['superior'] > (int) $e['alto'] && (int) $e['alto'] > (int) $e['basico'])) {
        return 'Superior debe empezar más arriba que Alto, y Alto más arriba que Básico.';
    }
    return null;
}

/** Nota mínima para aprobar en ese año (= inicio de Básico). */
function notaMinimaAprobacion(?int $anio = null): int
{
    return escalaDesempeno($anio)['basico'];
}

/** Los cuatro rangos para mostrar: ['Superior' => '90–100', ..., 'Bajo' => '0–59']. */
function escalaRangos(?int $anio = null): array
{
    $e = escalaDesempeno($anio);
    return [
        'Superior' => $e['superior'] . '–100',
        'Alto'     => $e['alto'] . '–' . ($e['superior'] - 1),
        'Básico'   => $e['basico'] . '–' . ($e['alto'] - 1),
        'Bajo'     => '0–' . ($e['basico'] - 1),
    ];
}

/** "Superior 90–100 · Alto 80–89 · Básico 60–79 · Bajo 0–59" */
function escalaTexto(?int $anio = null): string
{
    $partes = [];
    foreach (escalaRangos($anio) as $nivel => $rango) $partes[] = "$nivel $rango";
    return implode(' · ', $partes);
}

/** Áreas que se mostrarían en el boletín sin entrar al promedio ni al puesto.
 *  Vacío: el colegio decidió que Comportamiento SÍ cuenta en la nota final. */
if (!defined('AREAS_FUERA_DEL_PROMEDIO')) {
    define('AREAS_FUERA_DEL_PROMEDIO', []);
}

/** ¿El área cuenta para el promedio general? */
function areaPromedia(string $area): bool
{
    return !in_array(mb_strtolower(trim($area)), AREAS_FUERA_DEL_PROMEDIO, true);
}

/** Desempeño cualitativo de una nota/promedio según la escala del año (por defecto, el activo). */
function desempenoNota($nota, ?int $anio = null): string
{
    $n = (float) $nota;
    $e = escalaDesempeno($anio);
    if ($n >= $e['superior']) return 'Superior';
    if ($n >= $e['alto']) return 'Alto';
    if ($n >= $e['basico']) return 'Básico';
    return 'Bajo';
}

/** Estilo en línea (fondo + texto) de un badge según el desempeño. */
function desempenoEstilo($nota, ?int $anio = null): string
{
    $map = [
        'Superior' => 'background:#1b5e20;color:#fff;',
        'Alto'     => 'background:#e8f5e9;color:#1b5e20;',
        'Básico'   => 'background:#fff3e0;color:#a33f00;',
        'Bajo'     => 'background:#ffebee;color:#b71c1c;',
    ];
    return $map[desempenoNota($nota, $anio)];
}

/**
 * Interpreta lo que el profesor escribe en la casilla de nota.
 * Acepta "85", " 85 ", "85.0", "85,0". La nota es ENTERA (columna INTEGER):
 * "85,5" se rechaza en vez de redondearse a escondidas.
 *
 * @return array{0: bool, 1: ?int}  [válida, valor]  ('' → [true, null] = sin nota)
 */
function parsearNota($raw): array
{
    $v = trim((string) $raw);
    if ($v === '') return [true, null];
    $v = str_replace(',', '.', $v);
    if (!preg_match('/^\d{1,3}(\.0+)?$/', $v)) return [false, null];
    $n = (int) $v;
    if ($n < 0 || $n > 100) return [false, null];
    return [true, $n];
}

/**
 * Calcula la definitiva por área y el promedio general a partir de filas de notas.
 *
 * @param array $notas Filas con claves: area, nota, porcentaje (intensidad_horaria se ignora)
 * @return array [
 *   'areas'     => [area => definitiva (float, 1 decimal)],
 *   'general'   => float|null  (media simple de las áreas que promedian),
 *   'cobertura' => [area => Σ% de las asignaturas con nota (100 = área completa)],
 * ]
 */
function calcularDefinitivas(array $notas): array
{
    $acc = [];
    foreach ($notas as $n) {
        if (!isset($n['nota']) || $n['nota'] === '') continue;   // sin nota: no cuenta
        $area = (string) ($n['area'] ?? '');
        $nota = max(0.0, min(100.0, (float) $n['nota']));
        $pct  = (float) ($n['porcentaje'] ?? 100);
        if ($pct <= 0) $pct = 100;                                 // porcentaje inválido → peso neutro

        if (!isset($acc[$area])) $acc[$area] = ['num' => 0.0, 'den' => 0.0];
        $acc[$area]['num'] += $nota * $pct;
        $acc[$area]['den'] += $pct;
    }

    $areasDef = [];
    $cobertura = [];
    $suma = 0.0;
    $cuenta = 0;
    foreach ($acc as $area => $d) {
        $def = round($d['num'] / $d['den'], 1);
        $areasDef[$area] = $def;
        $cobertura[$area] = $d['den'];
        if (areaPromedia($area)) {
            $suma += $def;
            $cuenta++;
        }
    }

    return [
        'areas'     => $areasDef,
        'general'   => $cuenta > 0 ? round($suma / $cuenta, 1) : null,
        'cobertura' => $cobertura,
    ];
}

/**
 * Ordena por promedio (desc) y asigna 'puesto' con empates ("1, 2, 2, 4") y
 * 'total' (estudiantes con promedio). Los que no tienen promedio quedan al
 * final con puesto null.
 */
function asignarPuestos(array $lista): array
{
    usort($lista, function ($a, $b) {
        $pa = $a['promedio'] ?? null;
        $pb = $b['promedio'] ?? null;
        if ($pa === null || $pb === null) {
            if ($pa !== $pb) return $pa === null ? 1 : -1;
        } elseif (($c = ((int) round($pb * 10)) <=> ((int) round($pa * 10))) !== 0) {
            return $c;
        }
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    $total = count(array_filter($lista, fn($r) => ($r['promedio'] ?? null) !== null));
    $puesto = 0;
    $anterior = null;
    foreach ($lista as $i => &$r) {
        if (($r['promedio'] ?? null) === null) {
            $r['puesto'] = null;
        } else {
            $clave = (int) round($r['promedio'] * 10);
            if ($clave !== $anterior) {
                $puesto = $i + 1;
                $anterior = $clave;
            }
            $r['puesto'] = $puesto;
        }
        $r['total'] = $total;
    }
    unset($r);
    return $lista;
}

/**
 * Ranking a partir de filas sueltas (una por nota) con claves
 * id, nombre, area, nota, porcentaje. Sirve para no repetir la consulta
 * cuando la página ya trajo las notas del curso.
 */
function rankingDesdeFilas(array $filas): array
{
    $porEst = [];
    foreach ($filas as $r) {
        $eid = (int) $r['id'];
        if (!isset($porEst[$eid])) $porEst[$eid] = ['id' => $eid, 'nombre' => $r['nombre'] ?? '', 'notas' => []];
        $porEst[$eid]['notas'][] = $r;
    }
    $lista = [];
    foreach ($porEst as $e) {
        $prom = calcularDefinitivas($e['notas'])['general'];
        if ($prom === null) continue;                    // sin áreas que promedien: fuera del ranking
        $lista[] = ['id' => $e['id'], 'nombre' => $e['nombre'], 'promedio' => $prom];
    }
    return asignarPuestos($lista);
}

/** Caché por request de rankings de período: clave "curso|periodo|anio". */
function &_rankingMemo(): array
{
    static $memo = [];
    return $memo;
}

/**
 * Rankings de VARIOS cursos de un período en UNA consulta.
 * @return array [curso_id => ranking (ver obtenerRankingCurso)]
 */
function obtenerRankingsCursos($conexion, array $curso_ids, $periodo, $anio = null): array
{
    $anio = (int) ($anio ?? date('Y'));
    $periodo = (string) $periodo;
    $memo = &_rankingMemo();

    $curso_ids = array_values(array_unique(array_map('intval', $curso_ids)));
    $faltan = array_values(array_filter($curso_ids, fn($c) => !isset($memo["$c|$periodo|$anio"])));

    if ($faltan) {
        $ph = implode(',', array_fill(0, count($faltan), '?'));
        $stmt = $conexion->prepare("
            SELECT e.curso_id, e.id, e.nombre, a.area, n.nota,
                   COALESCE(ag.porcentaje, 100) AS porcentaje
            FROM estudiantes e
            JOIN cursos c       ON c.id = e.curso_id
            JOIN notas n        ON n.estudiante_id = e.id AND n.periodo = ? AND n.anio = ?
            JOIN asignaturas a  ON a.id = n.asignatura_id
            LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
            WHERE e.curso_id IN ($ph)
        ");
        $stmt->execute(array_merge([$periodo, $anio], $faltan));
        $porCurso = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $porCurso[(int) $r['curso_id']][] = $r;
        }
        foreach ($faltan as $c) {
            $memo["$c|$periodo|$anio"] = rankingDesdeFilas($porCurso[$c] ?? []);
        }
    }

    $out = [];
    foreach ($curso_ids as $c) $out[$c] = $memo["$c|$periodo|$anio"];
    return $out;
}

/**
 * Ranking del curso por promedio general (descendente). 1 consulta (memo por request).
 * @return array [ ['id','nombre','promedio','puesto','total'], ... ]
 */
function obtenerRankingCurso($conexion, $curso_id, $periodo, $anio = null)
{
    return obtenerRankingsCursos($conexion, [(int) $curso_id], $periodo, $anio)[(int) $curso_id];
}

/**
 * Puesto "N/Total" del estudiante en su curso, o null si no tiene notas.
 * Si se pasa $curso_id se ahorra una consulta.
 */
function calcularPuestoCurso($conexion, $estudiante_id, $periodo, $anio = null, $curso_id = null)
{
    $curso_id = $curso_id ?: obtenerCursoIdEstudiante($conexion, $estudiante_id);
    if (!$curso_id) return null;

    foreach (obtenerRankingCurso($conexion, $curso_id, $periodo, $anio) as $r) {
        if ((int) $r['id'] === (int) $estudiante_id) {
            return $r['puesto'] . '/' . $r['total'];
        }
    }
    return null;
}

function obtenerCursoIdEstudiante($conexion, $estudiante_id)
{
    $stmt = $conexion->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
    $stmt->execute([$estudiante_id]);
    $c = $stmt->fetchColumn();
    return $c ? (int) $c : null;
}

/**
 * Notas de un estudiante en un período, listas para calcularDefinitivas() y
 * para el boletín (una sola consulta: el grado sale del JOIN con cursos).
 * Columnas: asignatura_id, asignatura, area, nota, examen (solo informativo), porcentaje, intensidad_horaria, logro.
 */
function obtenerNotasPonderadas($conexion, $estudiante_id, $periodo, $anio = null): array
{
    $anio = (int) ($anio ?? date('Y'));
    $stmt = $conexion->prepare("
        SELECT a.id AS asignatura_id, a.nombre AS asignatura, a.area, n.nota, n.examen, e.curso_id,
               COALESCE(ag.porcentaje, 100) AS porcentaje,
               COALESCE(ag.intensidad_horaria, a.intensidad_horaria, 0) AS intensidad_horaria,
               l.logro
        FROM notas n
        JOIN estudiantes e  ON e.id = n.estudiante_id
        LEFT JOIN cursos c  ON c.id = e.curso_id
        JOIN asignaturas a  ON a.id = n.asignatura_id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
        LEFT JOIN logros l  ON l.estudiante_id = n.estudiante_id AND l.asignatura_id = n.asignatura_id
                           AND l.periodo = n.periodo AND l.anio = n.anio
        WHERE n.estudiante_id = ? AND n.periodo = ? AND n.anio = ?
        ORDER BY a.area, a.nombre
    ");
    $stmt->execute([$estudiante_id, (string) $periodo, $anio]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Promedio general de un estudiante en un período (null si no tiene notas). */
function calcularPromedioGeneralEstudiante($conexion, $estudiante_id, $periodo, $anio = null)
{
    $notas = obtenerNotasPonderadas($conexion, $estudiante_id, $periodo, $anio);
    if (count($notas) === 0) return null;
    return calcularDefinitivas($notas)['general'];
}

/**
 * Consolidado anual de VARIOS cursos en una consulta.
 * @return array [curso_id => [ ['id','nombre','promedio','aprobo','areas_perdidas','areas','periodos','puesto','total'], ... ]]
 */
function rankingAnualCursos($conexion, array $curso_ids, $anio = null, $notaMinima = null): array
{
    $anio = (int) ($anio ?? date('Y'));
    $notaMinima = $notaMinima ?? notaMinimaAprobacion($anio);
    $curso_ids = array_values(array_unique(array_map('intval', $curso_ids)));
    if (!$curso_ids) return [];
    $ph = implode(',', array_fill(0, count($curso_ids), '?'));

    // Definitiva anual por asignatura = AVG de sus notas en los períodos del año.
    $stmt = $conexion->prepare("
        SELECT e.curso_id, e.id, e.nombre, a.area,
               AVG(n.nota) AS nota,
               COALESCE(MAX(ag.porcentaje), 100) AS porcentaje,
               COUNT(n.id) AS n_notas,
               STRING_AGG(DISTINCT n.periodo, ',') AS periodos
        FROM estudiantes e
        JOIN cursos c ON c.id = e.curso_id
        LEFT JOIN notas n ON n.estudiante_id = e.id AND n.anio = ?
        LEFT JOIN asignaturas a ON a.id = n.asignatura_id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
        WHERE e.curso_id IN ($ph)
        GROUP BY e.curso_id, e.id, e.nombre, a.id, a.area
    ");
    $stmt->execute(array_merge([$anio], $curso_ids));

    $porEst = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $eid = (int) $r['id'];
        if (!isset($porEst[$eid])) {
            $porEst[$eid] = ['curso_id' => (int) $r['curso_id'], 'id' => $eid, 'nombre' => $r['nombre'], 'notas' => [], 'periodos' => []];
        }
        if ($r['area'] !== null && $r['nota'] !== null) {
            $porEst[$eid]['notas'][] = ['area' => $r['area'], 'nota' => $r['nota'], 'porcentaje' => $r['porcentaje']];
            foreach (explode(',', (string) $r['periodos']) as $p) {
                if ($p !== '') $porEst[$eid]['periodos'][$p] = true;
            }
        }
    }

    $porCurso = array_fill_keys($curso_ids, []);
    foreach ($porEst as $e) {
        $def = $e['notas'] ? calcularDefinitivas($e['notas']) : ['areas' => [], 'general' => null];
        $perdidas = 0;
        foreach ($def['areas'] as $area => $v) {
            if ($v < $notaMinima && areaPromedia($area)) $perdidas++;
        }
        $porCurso[$e['curso_id']][] = [
            'id'             => $e['id'],
            'nombre'         => $e['nombre'],
            'promedio'       => $def['general'],
            'aprobo'         => $def['general'] !== null && $def['general'] >= $notaMinima,
            'areas_perdidas' => $perdidas,
            'areas'          => $def['areas'],
            'periodos'       => count($e['periodos']),
        ];
    }
    foreach ($porCurso as $c => $lista) $porCurso[$c] = asignarPuestos($lista);
    return $porCurso;
}

/** Ranking anual de un curso (ver rankingAnualCursos). */
function rankingAnualCurso($conexion, $curso_id, $anio = null, $notaMinima = null)
{
    return rankingAnualCursos($conexion, [(int) $curso_id], $anio, $notaMinima)[(int) $curso_id] ?? [];
}

/**
 * Consolidado ANUAL de un estudiante (mismo cálculo que rankingAnualCursos).
 * @return array ['areas'=>[area=>def], 'general'=>float|null, 'aprobo'=>bool, 'areas_perdidas'=>int, 'periodos'=>int, 'puesto'=>?int, 'total'=>int]
 */
function consolidadoAnualEstudiante($conexion, $estudiante_id, $anio = null, $notaMinima = null)
{
    $curso_id = obtenerCursoIdEstudiante($conexion, $estudiante_id);
    $vacio = ['areas' => [], 'general' => null, 'aprobo' => false, 'areas_perdidas' => 0, 'periodos' => 0, 'puesto' => null, 'total' => 0];
    if (!$curso_id) return $vacio;
    foreach (rankingAnualCurso($conexion, $curso_id, $anio, $notaMinima) as $r) {
        if ($r['id'] === (int) $estudiante_id) {
            return ['areas' => $r['areas'], 'general' => $r['promedio'], 'aprobo' => $r['aprobo'],
                    'areas_perdidas' => $r['areas_perdidas'], 'periodos' => $r['periodos'],
                    'puesto' => $r['puesto'], 'total' => $r['total']];
        }
    }
    return $vacio;
}

/**
 * Mejores estudiantes por grado (promedio general del período). 1 consulta.
 * @return array [ grado => [ ['id','nombre','grado','curso_nombre','nivel','promedio','puesto','total'], ... ] ]
 */
function calcularMejoresPorGrado($conexion, $periodo, $limite = 5, $anio = null)
{
    $anio = (int) ($anio ?? date('Y'));
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, c.grado, c.nombre AS curso_nombre, c.nivel,
               a.area, n.nota, COALESCE(ag.porcentaje, 100) AS porcentaje
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        JOIN notas n ON n.estudiante_id = e.id AND n.periodo = ? AND n.anio = ?
        JOIN asignaturas a ON n.asignatura_id = a.id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
    ");
    $stmt->execute([(string) $periodo, $anio]);

    $est = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $eid = (int) $r['id'];
        if (!isset($est[$eid])) {
            $est[$eid] = ['id' => $eid, 'nombre' => $r['nombre'], 'grado' => $r['grado'],
                          'curso_nombre' => $r['curso_nombre'], 'nivel' => $r['nivel'], 'notas' => []];
        }
        $est[$eid]['notas'][] = $r;
    }

    $porGrado = [];
    foreach ($est as $e) {
        $prom = calcularDefinitivas($e['notas'])['general'];
        if ($prom === null) continue;
        unset($e['notas']);
        $e['promedio'] = $prom;
        $porGrado[$e['grado']][] = $e;
    }

    $ordenGrado = [
        'maternal'=>1,'prejardin'=>2,'jardin'=>3,'transicion'=>4,
        'primero'=>5,'segundo'=>6,'tercero'=>7,'cuarto'=>8,'quinto'=>9,
        'sexto'=>10,'septimo'=>11,'octavo'=>12,'noveno'=>13,'decimo'=>14,
        'undecimo'=>15,'once'=>15,
    ];
    uksort($porGrado, fn($a, $b) => ($ordenGrado[$a] ?? 16) <=> ($ordenGrado[$b] ?? 16));

    $resultados = [];
    foreach ($porGrado as $grado => $lista) {
        $resultados[$grado] = array_slice(asignarPuestos($lista), 0, $limite);
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
