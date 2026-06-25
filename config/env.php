<?php

/**
 * Carga variables de entorno desde el archivo .env
 * Implementación ligera sin dependencias externas.
 */
if (!class_exists('EnvLoader')) {
    class EnvLoader
    {
    private static array $loaded = [];

    public static function load(string $path): void
    {
        if (self::$loaded) return;

        $file = rtrim($path, '/') . '/.env';
        if (!file_exists($file)) return;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove surrounding quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $value = match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                default => $value,
            };

            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        self::$loaded[] = $path;
    }
}
}

EnvLoader::load(__DIR__ . '/../');

/**
 * Obtiene una variable de entorno con valor por defecto.
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

/**
 * Obtiene una constante de configuración de la app.
 */
if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
    static $config = [];

    if (empty($config)) {
        // Railway.app MySQL — detecta vars inyectadas automáticamente
        $dbHost = env('MYSQLHOST') ?: env('DB_HOST', 'localhost');
        $dbPort = env('MYSQLPORT') ?: env('DB_PORT', '3306');
        $dbName = env('MYSQLDATABASE') ?: env('DB_NAME', 'colegio_db');
        $dbUser = env('MYSQLUSER') ?: env('DB_USER', 'root');
        $dbPass = env('MYSQLPASSWORD') ?: env('DB_PASS', '');

        $config = [
            'app.name'    => env('APP_NAME', 'Sistema Académico'),
            'app.env'     => env('APP_ENV', 'production'),
            'app.debug'   => env('APP_DEBUG', false),
            'app.url'     => env('APP_URL', 'http://localhost'),
            'db.host'     => $dbHost,
            'db.port'     => $dbPort,
            'db.name'     => $dbName,
            'db.user'     => $dbUser,
            'db.pass'     => $dbPass,
            'db.charset'  => env('DB_CHARSET', 'utf8mb4'),
            'session.lifetime' => (int) env('SESSION_LIFETIME', 7200),
            'mail.host'       => env('MAIL_HOST', 'smtp.gmail.com'),
            'mail.port'       => (int) env('MAIL_PORT', 587),
            'mail.username'   => env('MAIL_USERNAME', ''),
            'mail.password'   => env('MAIL_PASSWORD', ''),
            'mail.encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail.from'       => env('MAIL_FROM_ADDRESS', 'no-reply@gimcaste.com'),
            'mail.from_name'  => env('MAIL_FROM_NAME', 'Gimnasio Castillo Americano'),
            'mail.admin'      => env('MAIL_ADMIN', 'admin@gimcaste.com'),
        ];
    }

    return $config[$key] ?? $default;
    }
}
