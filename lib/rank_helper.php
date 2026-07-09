<?php

function obtenerRankingCurso($conexion, $curso_id, $periodo)
{
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, ROUND(AVG(n.nota), 1) as promedio
        FROM estudiantes e
        JOIN notas n ON e.id = n.estudiante_id
        WHERE e.curso_id = ? AND n.periodo = ?
        GROUP BY e.id
        ORDER BY promedio DESC
    ");
    $stmt->execute([$curso_id, $periodo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calcularPuestoCurso($conexion, $estudiante_id, $periodo)
{
    $curso_id = obtenerCursoIdEstudiante($conexion, $estudiante_id);
    if (!$curso_id) return null;

    $ranking = obtenerRankingCurso($conexion, $curso_id, $periodo);
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

function calcularPromedioGeneralEstudiante($conexion, $estudiante_id, $periodo)
{
    $stmt = $conexion->prepare("
        SELECT ROUND(AVG(nota), 1) as promedio
        FROM notas
        WHERE estudiante_id = ? AND periodo = ?
    ");
    $stmt->execute([$estudiante_id, $periodo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['promedio'] : null;
}

function calcularMejoresPorGrado($conexion, $periodo, $limite = 5)
{
    // Single query: get all students with averages grouped by grade, ordered by grade order + rank
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, c.grado, c.nombre as curso_nombre, c.nivel,
               ROUND(AVG(n.nota), 1) as promedio
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        JOIN notas n ON n.estudiante_id = e.id AND n.periodo = ?
        GROUP BY e.id, c.grado
        ORDER BY
            CASE c.grado
                WHEN 'maternal' THEN 1 WHEN 'prejardin' THEN 2 WHEN 'jardin' THEN 3 WHEN 'transicion' THEN 4
                WHEN 'primero' THEN 5 WHEN 'segundo' THEN 6 WHEN 'tercero' THEN 7 WHEN 'cuarto' THEN 8 WHEN 'quinto' THEN 9
                WHEN 'sexto' THEN 10 WHEN 'septimo' THEN 11 WHEN 'octavo' THEN 12 WHEN 'noveno' THEN 13 WHEN 'decimo' THEN 14 WHEN 'undecimo' THEN 15
                ELSE 16
            END,
            promedio DESC
    ");
    $stmt->execute([$periodo]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultados = [];
    $contadores = [];
    foreach ($rows as $row) {
        $grado = $row['grado'];
        if (!isset($contadores[$grado])) {
            $contadores[$grado] = 0;
            $resultados[$grado] = [];
        }
        if ($contadores[$grado] < $limite) {
            $resultados[$grado][] = $row;
            $contadores[$grado]++;
        }
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
