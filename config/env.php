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
 * Descompone una cadena de conexión PostgreSQL en sus partes.
 * Formato de Neon (y de Railway/Heroku):
 *   postgresql://usuario:clave@ep-xxx-pooler.us-east-2.aws.neon.tech/neondb?sslmode=require
 * Devuelve [] si la cadena no es válida, para que se usen las variables sueltas.
 */
if (!function_exists('parse_db_url')) {
    function parse_db_url(string $url): array
    {
        $p = parse_url($url);
        if ($p === false || empty($p['host'])) return [];

        $out = [
            'host' => $p['host'],
            'port' => (string) ($p['port'] ?? '5432'),
        ];

        if (isset($p['user'])) $out['user'] = rawurldecode($p['user']);
        if (isset($p['pass'])) $out['pass'] = rawurldecode($p['pass']);

        $name = trim($p['path'] ?? '', '/');
        if ($name !== '') $out['name'] = rawurldecode($name);

        if (!empty($p['query'])) {
            parse_str($p['query'], $q);
            if (!empty($q['sslmode'])) $out['sslmode'] = $q['sslmode'];
        }

        return $out;
    }
}

/**
 * Nombre de persona en formato único: "Gutierrez Medina Juan David".
 * Mayúscula inicial en cada palabra, partículas (de, del, la…) en minúscula
 * salvo al inicio, sin espacios dobles ni punto final. No toca tildes.
 */
if (!function_exists('normalizar_nombre')) {
    function normalizar_nombre(string $nombre): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre), " .,");
        $palabras = explode(' ', mb_convert_case(mb_strtolower($nombre, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'));
        foreach ($palabras as $i => $p) {
            if ($i > 0 && in_array(mb_strtolower($p, 'UTF-8'), ['de', 'del', 'la', 'las', 'los', 'y'], true)) {
                $palabras[$i] = mb_strtolower($p, 'UTF-8');
            }
        }
        return implode(' ', $palabras);
    }
}

/**
 * Directorio de TODO lo que la aplicación escribe en tiempo de ejecución:
 * boletines generados, fotos de docentes e imágenes de noticias.
 *
 * Están juntos a propósito. En el hosting el disco del contenedor es
 * efímero (se borra en cada despliegue), así que hay que montar un volumen
 * persistente encima — y Railway solo permite UNO por servicio. Si cada cosa
 * viviera en su carpeta no se podrían persistir todas.
 *
 * OJO: no montar el volumen en `assets/` a secas; taparía el CSS y las
 * imágenes del diseño, que sí vienen dentro de la imagen.
 *
 * @param string $sub Subruta opcional, p.ej. 'docentes' o 'boletines/2026/1'.
 */
if (!function_exists('ruta_subidas')) {
    function ruta_subidas(string $sub = ''): string
    {
        $base = dirname(__DIR__) . '/assets/uploads';
        return $sub === '' ? $base : $base . '/' . trim($sub, '/');
    }
}

/**
 * La misma ubicación pero como ruta relativa, que es la que se guarda en la
 * base de datos y se usa en el HTML.
 */
if (!function_exists('url_subidas')) {
    function url_subidas(string $sub = ''): string
    {
        $base = 'assets/uploads';
        return $sub === '' ? $base : $base . '/' . trim($sub, '/');
    }
}

/**
 * URL base pública de la aplicación, sin barra final.
 *
 * Se usa para armar enlaces absolutos (el correo de recuperación de
 * contraseña). En el hosting NO hay archivo .env, así que si nadie define
 * APP_URL se deduce del dominio real en vez de quedarse en "localhost"
 * y mandar enlaces que no funcionan.
 *
 * Orden: APP_URL → dominio público de Railway → host de la petición.
 */
if (!function_exists('app_url_base')) {
    function app_url_base(): string
    {
        $explicita = env('APP_URL', '');
        if (is_string($explicita) && $explicita !== '' && !str_contains($explicita, 'localhost')) {
            return rtrim($explicita, '/');
        }

        $railway = env('RAILWAY_PUBLIC_DOMAIN', '');
        if (is_string($railway) && $railway !== '') {
            return 'https://' . rtrim($railway, '/');
        }

        // Deducir de la petición (no existe en CLI)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $https = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

            return ($https ? 'https://' : 'http://') . $host;
        }

        // Último recurso: lo que diga APP_URL, aunque sea localhost (desarrollo)
        return rtrim(is_string($explicita) && $explicita !== '' ? $explicita : 'http://localhost', '/');
    }
}

/**
 * DSN de PDO para PostgreSQL, construido en un solo lugar.
 */
if (!function_exists('db_dsn')) {
    function db_dsn(): string
    {
        $dsn = 'pgsql:host=' . config('db.host')
             . ';port=' . config('db.port')
             . ';dbname=' . config('db.name');

        $sslmode = config('db.sslmode');
        if ($sslmode) {
            $dsn .= ';sslmode=' . $sslmode;
        }

        return $dsn;
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
        // PostgreSQL (Neon) — acepta DATABASE_URL o las variables sueltas (PG* / DB_*)
        $url    = env('DATABASE_URL') ?: env('POSTGRES_URL', '');
        $parsed = $url ? parse_db_url($url) : [];

        $dbHost = $parsed['host'] ?? (env('PGHOST') ?: env('DB_HOST', 'localhost'));
        $dbPort = $parsed['port'] ?? (env('PGPORT') ?: env('DB_PORT', '5432'));
        $dbName = $parsed['name'] ?? (env('PGDATABASE') ?: env('DB_NAME', 'neondb'));
        $dbUser = $parsed['user'] ?? (env('PGUSER') ?: env('DB_USER', 'postgres'));
        $dbPass = $parsed['pass'] ?? (env('PGPASSWORD') ?: env('DB_PASS', ''));

        // Neon SOLO acepta conexiones TLS. Contra un Postgres local sin SSL se
        // puede bajar con DB_SSLMODE=prefer (o disable).
        $dbSsl = $parsed['sslmode'] ?? (env('PGSSLMODE') ?: env('DB_SSLMODE', ''));
        if ($dbSsl === '') {
            $dbSsl = in_array($dbHost, ['localhost', '127.0.0.1', '::1'], true) ? 'prefer' : 'require';
        }

        $config = [
            'app.name'    => env('APP_NAME', 'Sistema Académico'),
            'app.env'     => env('APP_ENV', 'production'),
            // "false" en una variable de Railway es un texto (verdadero en PHP):
            // se interpreta como booleano, y en producción nunca hay depuración.
            'app.debug'   => env('APP_ENV', 'production') !== 'production'
                             && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
            'app.url'     => app_url_base(),
            'db.host'     => $dbHost,
            'db.port'     => $dbPort,
            'db.name'     => $dbName,
            'db.user'     => $dbUser,
            'db.pass'     => $dbPass,
            'db.sslmode'  => $dbSsl,
            'db.charset'  => 'utf8',
            'session.lifetime' => (int) env('SESSION_LIFETIME', 7200),
            'mail.brevo_key'  => env('BREVO_API_KEY', ''),
            'mail.host'       => env('MAIL_HOST', 'smtp.gmail.com'),
            'mail.port'       => (int) env('MAIL_PORT', 587),
            'mail.username'   => env('MAIL_USERNAME', ''),
            'mail.password'   => env('MAIL_PASSWORD', ''),
            'mail.encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail.from'       => env('MAIL_FROM_ADDRESS', 'no-reply@gimcaste.com'),
            'mail.from_name'  => env('MAIL_FROM_NAME', 'Gimnasio Castillo Americano'),
            'mail.admin'      => env('MAIL_ADMIN', 'admin@gimcaste.com'),
            // Redes del colegio: cada ícono se muestra solo si su URL está definida
            'social.facebook'  => env('SOCIAL_FACEBOOK', ''),
            'social.instagram' => env('SOCIAL_INSTAGRAM', ''),
            'social.youtube'   => env('SOCIAL_YOUTUBE', ''),
            'social.tiktok'    => env('SOCIAL_TIKTOK', ''),
        ];
    }

    return $config[$key] ?? $default;
    }
}
