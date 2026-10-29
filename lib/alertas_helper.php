<?php

/**
 * Create an alert/notification in the system.
 */
function crearAlerta($conexion, $tipo, $titulo, $mensaje, $para_rol = null, $para_usuario_id = null)
{
    // Si ya hay una igual sin leer no se repite (cada "Guardar todo" volvía a crearlas).
    // Buscar + insertar en UN solo viaje a la BD; devuelve el id nuevo o el existente.
    $stmt = $conexion->prepare("
        WITH dup AS (
            SELECT id FROM alertas
            WHERE tipo = ? AND titulo = ? AND mensaje = ? AND leido = FALSE
              AND para_rol IS NOT DISTINCT FROM CAST(? AS VARCHAR)
              AND para_usuario_id IS NOT DISTINCT FROM CAST(? AS INTEGER)
            LIMIT 1
        ), ins AS (
            INSERT INTO alertas (tipo, titulo, mensaje, para_rol, para_usuario_id)
            SELECT ?, ?, ?, CAST(? AS VARCHAR), CAST(? AS INTEGER)
            WHERE NOT EXISTS (SELECT 1 FROM dup)
            RETURNING id
        )
        SELECT id FROM ins UNION ALL SELECT id FROM dup
    ");
    $p = [$tipo, $titulo, $mensaje, $para_rol, $para_usuario_id];
    $stmt->execute(array_merge($p, $p));
    return (int) $stmt->fetchColumn();
}

/**
 * Crea VARIAS alertas en una sola sentencia, sin repetir las que ya existen
 * sin leer (misma regla que crearAlerta). Cada alerta: [tipo, titulo, mensaje, para_rol, para_usuario_id].
 * @return int[] ids de las alertas NUEVAS
 */
function crearAlertas($conexion, array $alertas): array
{
    $unicas = [];
    foreach ($alertas as $a) {
        $unicas[json_encode($a)] = array_values($a);
    }
    if (!$unicas) return [];

    $filas = [];
    $params = [];
    foreach ($unicas as $a) {
        $filas[] = '(?, ?, ?, CAST(? AS VARCHAR), CAST(? AS INTEGER))';
        array_push($params, ...$a);
    }
    $stmt = $conexion->prepare("
        WITH v (tipo, titulo, mensaje, para_rol, para_usuario_id) AS (VALUES " . implode(', ', $filas) . ")
        INSERT INTO alertas (tipo, titulo, mensaje, para_rol, para_usuario_id)
        SELECT v.tipo, v.titulo, v.mensaje, v.para_rol, v.para_usuario_id FROM v
        WHERE NOT EXISTS (
            SELECT 1 FROM alertas x
            WHERE x.tipo = v.tipo AND x.titulo = v.titulo AND x.mensaje = v.mensaje AND x.leido = FALSE
              AND x.para_rol IS NOT DISTINCT FROM v.para_rol
              AND x.para_usuario_id IS NOT DISTINCT FROM v.para_usuario_id
        )
        RETURNING id
    ");
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Get unread alerts for a user.
 */
function obtenerAlertas($conexion, $usuario_id, $rol)
{
    $stmt = $conexion->prepare("
        SELECT * FROM alertas
        WHERE (para_usuario_id = ? OR (para_rol = ? AND para_usuario_id IS NULL))
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$usuario_id, $rol]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get unread alert count for a user.
 */
function contarAlertasNoLeidas($conexion, $usuario_id, $rol)
{
    $stmt = $conexion->prepare("
        SELECT COUNT(*) FROM alertas
        WHERE leido = FALSE
        AND (para_usuario_id = ? OR (para_rol = ? AND para_usuario_id IS NULL))
    ");
    $stmt->execute([$usuario_id, $rol]);
    return (int) $stmt->fetchColumn();
}

/**
 * Mark an alert as read.
 */
function marcarAlertaLeida($conexion, $alerta_id, $usuario_id = null, $rol = null)
{
    if ($usuario_id === null) {
        $stmt = $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE id = ?");
        $stmt->execute([(int) $alerta_id]);
        return;
    }
    // Solo alertas dirigidas a este usuario o a su rol en general
    $stmt = $conexion->prepare("
        UPDATE alertas SET leido = TRUE
        WHERE id = ? AND (para_usuario_id = ? OR (para_rol = ? AND para_usuario_id IS NULL))
    ");
    $stmt->execute([(int) $alerta_id, (int) $usuario_id, $rol]);
}

/**
 * Alertas de riesgo académico tras el 3er período para UN estudiante.
 * (Compatibilidad: delega en verificarRiesgoAcademicoCurso.)
 * @return int[] ids de alertas nuevas
 */
function verificarRiesgoAcademico($conexion, $estudiante_id, $curso_id, $periodo, $anio = null)
{
    return verificarRiesgoAcademicoCurso($conexion, [(int) $estudiante_id], $periodo, $anio);
}

/**
 * Alertas de riesgo académico tras el 3er período para VARIOS estudiantes, con
 * un número FIJO de consultas (≤ 3) sin importar cuántos estudiantes sean.
 *
 * Riesgo = con el MISMO modelo del boletín (rank_helper.php):
 *   definitiva acumulada de cada asignatura = promedio de sus notas en los
 *   períodos 1-3 que TIENE (un período sin nota no cuenta como 0) → áreas →
 *   promedio general. Está en riesgo si el promedio general acumulado no llega
 *   a Básico (notaMinimaAprobacion del año) o alguna área que promedia no llega.
 *
 * @return int[] ids de alertas nuevas
 */
function verificarRiesgoAcademicoCurso($conexion, array $estudiante_ids, $periodo, $anio = null): array
{
    require_once __DIR__ . '/rank_helper.php';
    $anio = (int) ($anio ?? date('Y'));
    $minima = notaMinimaAprobacion($anio);   // inicio de Básico en la escala de ese año
    $periodo = (string) $periodo;
    if ($periodo !== '3') return [];

    $estudiante_ids = array_values(array_unique(array_filter(array_map('intval', $estudiante_ids))));
    if (!$estudiante_ids) return [];
    $ph = implode(',', array_fill(0, count($estudiante_ids), '?'));

    // 1) Notas P1-P3 de todos los estudiantes, ya agrupadas por asignatura
    $stmt = $conexion->prepare("
        SELECT e.id, e.nombre, e.curso_id, c.grado, c.nombre AS curso_nombre,
               a.area, AVG(n.nota) AS nota, COALESCE(MAX(ag.porcentaje), 100) AS porcentaje
        FROM estudiantes e
        JOIN cursos c ON c.id = e.curso_id
        JOIN notas n ON n.estudiante_id = e.id AND n.anio = ? AND n.periodo IN ('1','2','3')
        JOIN asignaturas a ON a.id = n.asignatura_id
        LEFT JOIN asignatura_grado ag ON ag.asignatura_id = a.id AND ag.grado = c.grado
        WHERE e.id IN ($ph)
        GROUP BY e.id, e.nombre, e.curso_id, c.grado, c.nombre, a.id, a.area
    ");
    $stmt->execute(array_merge([$anio], $estudiante_ids));
    $porEst = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $porEst[(int) $r['id']]['info'] = $r;
        $porEst[(int) $r['id']]['notas'][] = $r;
    }

    $enRiesgo = [];   // estudiante_id => [info, detalle, general]
    foreach ($porEst as $eid => $d) {
        $def = calcularDefinitivas($d['notas']);
        $areas = [];
        foreach ($def['areas'] as $area => $v) {
            if ($v < $minima && areaPromedia($area)) {
                $areas[] = $area . ' (' . number_format($v, 1) . ')';
            }
        }
        $general = $def['general'];
        if ($general === null || ($general >= $minima && !$areas)) continue;
        $enRiesgo[$eid] = ['info' => $d['info'], 'areas' => $areas, 'general' => $general];
    }
    if (!$enRiesgo) return [];

    // 2) Directores de los cursos + acudientes de los estudiantes con boletín
    //    del período YA PUBLICADO (al padre solo se le avisa tras la publicación).
    $cursos = array_values(array_unique(array_map(fn($x) => (int) $x['info']['curso_id'], $enRiesgo)));
    $ids = array_keys($enRiesgo);
    $phC = implode(',', array_fill(0, count($cursos), '?'));
    $phE = implode(',', array_fill(0, count($ids), '?'));
    $dest = $conexion->prepare("
        SELECT 'director' AS tipo, dg.curso_id AS clave, dg.profesor_id AS usuario_id
        FROM directores_grupo dg WHERE dg.curso_id IN ($phC)
        UNION ALL
        SELECT 'padre', ea.estudiante_id, ea.padre_id
        FROM estudiante_acudientes ea
        JOIN boletines_pdf b ON b.estudiante_id = ea.estudiante_id
             AND b.periodo = ? AND b.year = ? AND b.publicado = TRUE
        WHERE ea.estudiante_id IN ($phE)
    ");
    $dest->execute(array_merge($cursos, [$periodo, $anio], $ids));
    $directores = [];
    $padres = [];
    foreach ($dest->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['tipo'] === 'director') $directores[(int) $r['clave']][] = (int) $r['usuario_id'];
        else $padres[(int) $r['clave']][] = (int) $r['usuario_id'];
    }

    // 3) Todas las alertas en UNA sentencia (sin duplicar las no leídas)
    $alertas = [];
    foreach ($enRiesgo as $eid => $x) {
        $e = $x['info'];
        $detalle = $x['areas'] ? implode(', ', $x['areas']) : 'promedio general por debajo de ' . $minima;
        $gen = number_format($x['general'], 1);

        $alertas[] = ['riesgo_academico', 'Estudiante en riesgo de perder el año',
            "{$e['nombre']} ({$e['grado']} {$e['curso_nombre']}) presenta riesgo académico después del 3er período. Áreas en riesgo: {$detalle}. Promedio acumulado: {$gen}",
            'admin', null];

        foreach ($directores[(int) $e['curso_id']] ?? [] as $dirId) {
            $alertas[] = ['riesgo_academico', '🚨 Alerta: estudiante en riesgo',
                "{$e['nombre']} está en riesgo de perder el año. Áreas deficientes: {$detalle}. Promedio acumulado: {$gen}. Revisa el consolidado de tu curso.",
                'profesor', $dirId];
        }

        $areasPadre = $x['areas']
            ? implode(', ', array_map(fn($t) => preg_replace('/ \([0-9.]+\)$/', '', $t), $x['areas']))
            : 'el promedio general';
        foreach (array_unique($padres[$eid] ?? []) as $padreId) {
            $alertas[] = ['riesgo_academico', '📢 Alerta académica — Su hijo está en riesgo',
                "Estimado padre, informamos que {$e['nombre']} presenta un rendimiento académico por debajo del mínimo en: {$areasPadre}. Puede ver el detalle en el boletín del período {$periodo}. Es importante tomar medidas de refuerzo antes del 4to período.",
                'padre', $padreId];
        }
    }
    return crearAlertas($conexion, $alertas);
}
