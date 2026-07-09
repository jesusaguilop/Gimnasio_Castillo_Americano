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

$host    = config('db.host');
$port    = config('db.port');
$dbname  = config('db.name');
$user    = config('db.user');
$pass    = config('db.pass');

try {
    $conexion = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    if (config('app.debug')) {
        die("Error de conexión: " . $e->getMessage());
    }
    die("Error de conexión a la base de datos.");
}
