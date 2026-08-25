<?php

/**
 * Configuración de la base de datos.
 * Carga variables de entorno desde .env y expone $conexion (PDO).
 *
 * Uso (backward compatible):
 *   require_once "config/database.php";
 *   $stmt = $conexion->query("SELECT ...");
 *
 * Uso nuevo (recomendado):
 *   use App\Base\Database;
 *   $db = Database::getInstance();
 */

require_once __DIR__ . '/env.php';

$user = config('db.user');
$pass = config('db.pass');

try {
    $conexion = new PDO(
        db_dsn(),
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Ver la nota en src/Base/Database.php: emulación ON ahorra
            // 3-4 viajes de red por consulta contra Neon.
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]
    );
} catch (PDOException $e) {
    if (config('app.debug')) {
        die("Error de conexión: " . $e->getMessage());
    }
    die("Error de conexión a la base de datos.");
}
