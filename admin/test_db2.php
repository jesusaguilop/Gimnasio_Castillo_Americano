<?php
require_once __DIR__ . '/../config/database.php';
try {
    $stmt = $conexion->query("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'cursos' ORDER BY ordinal_position");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
