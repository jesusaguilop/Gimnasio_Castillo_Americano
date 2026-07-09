<?php

/**
 * Create an alert/notification in the system.
 */
function crearAlerta($conexion, $tipo, $titulo, $mensaje, $para_rol = null, $para_usuario_id = null)
{
    $stmt = $conexion->prepare("
        INSERT INTO alertas (tipo, titulo, mensaje, para_rol, para_usuario_id)
        VALUES (?, ?, ?, ?, ?) RETURNING id
    ");
    $stmt->execute([$tipo, $titulo, $mensaje, $para_rol, $para_usuario_id]);
    return (int) $stmt->fetchColumn();
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
function marcarAlertaLeida($conexion, $alerta_id)
{
    $stmt = $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE id = ?");
    $stmt->execute([$alerta_id]);
}

/**
 * Check and create risk alerts for a student after saving period 3 grades.
 * Returns array of created alert IDs.
 */
function verificarRiesgoAcademico($conexion, $estudiante_id, $curso_id, $periodo)
{
    $alertas_creadas = [];

    // Only check risk in period 3
    if ($periodo !== '3') {
        return $alertas_creadas;
    }

    // Get student info
    $stmt = $conexion->prepare("
        SELECT e.nombre, e.padre_id, c.grado, c.nombre AS curso_nombre, c.nivel
        FROM estudiantes e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$estudiante_id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$est) return $alertas_creadas;

    // Get all notas for P1, P2, P3
    $notas = $conexion->prepare("
        SELECT n.asignatura_id, a.nombre AS asignatura, a.area,
               AVG(CASE WHEN n.periodo = '1' THEN n.nota END) AS p1,
               AVG(CASE WHEN n.periodo = '2' THEN n.nota END) AS p2,
               AVG(CASE WHEN n.periodo = '3' THEN n.nota END) AS p3
        FROM notas n
        JOIN asignaturas a ON n.asignatura_id = a.id
        WHERE n.estudiante_id = ? AND n.periodo IN ('1','2','3')
        GROUP BY n.asignatura_id
    ");
    $notas->execute([$estudiante_id]);
    $materias = $notas->fetchAll(PDO::FETCH_ASSOC);

    if (count($materias) === 0) return $alertas_creadas;

    $materias_riesgo = [];
    $total_acumulado = 0;

    foreach ($materias as $m) {
        $p1 = (float) ($m['p1'] ?? 0);
        $p2 = (float) ($m['p2'] ?? 0);
        $p3 = (float) ($m['p3'] ?? 0);
        $prom = ($p1 + $p2 + $p3) / 3;
        $total_acumulado += $prom;

        if ($prom < 60) {
            $materias_riesgo[] = $m['asignatura'] . ' (' . number_format($prom, 1) . ')';
        }
    }

    if (count($materias_riesgo) === 0) return $alertas_creadas;

    $promedio_general = $total_acumulado / count($materias);
    $detalle = implode(', ', $materias_riesgo);

    // Alert for admin
    $idAdmin = crearAlerta($conexion, 'riesgo_academico',
        'Estudiante en riesgo de perder el año',
        "{$est['nombre']} ({$est['grado']} {$est['curso_nombre']}) presenta riesgo académico después del 3er período. Materias en riesgo: {$detalle}. Promedio acumulado: " . number_format($promedio_general, 1),
        'admin'
    );
    $alertas_creadas[] = $idAdmin;

    // Alert for the course director(s)
    $directores = $conexion->prepare("
        SELECT u.id FROM directores_grupo dg
        JOIN usuarios u ON dg.profesor_id = u.id
        WHERE dg.curso_id = ?
    ");
    $directores->execute([$curso_id]);
    foreach ($directores->fetchAll(PDO::FETCH_ASSOC) as $dir) {
        $idDir = crearAlerta($conexion, 'riesgo_academico',
            '🚨 Alerta: estudiante en riesgo',
            "{$est['nombre']} está en riesgo de perder el año. Materias deficientes: {$detalle}. Revisa el consolidado de tu curso.",
            'profesor', $dir['id']
        );
        $alertas_creadas[] = $idDir;
    }

    // Alert for the parent
    if ($est['padre_id']) {
        $idPadre = crearAlerta($conexion, 'riesgo_academico',
            '📢 Alerta académica — Su hijo está en riesgo',
            "Estimado padre, informamos que {$est['nombre']} presenta un rendimiento académico por debajo del mínimo en las siguientes materias: {$detalle}. Es importante tomar medidas de refuerzo antes del 4to período.",
            'padre', $est['padre_id']
        );
        $alertas_creadas[] = $idPadre;
    }

    return $alertas_creadas;
}
