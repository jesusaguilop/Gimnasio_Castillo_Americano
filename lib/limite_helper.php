<?php
/**
 * Límite de peticiones (rate limit) para cualquier acción, con la misma tabla
 * que el login (login_intentos): cada paso guarda una fila con la IP y la
 * clave "grupo:quién". Si la tabla no existe o la BD falla, no bloquea.
 *
 *   if (limiteSuperado('pdf', 'u' . userId(), 60, 10)) { http_response_code(429); ... }
 *
 * Las filas viejas las borra AuthController (más de un día).
 */

use App\Controllers\AuthController;

if (!function_exists('limiteSuperado')) {
    /**
     * @param string $grupo   nombre corto de la acción ('pdf', 'notif', 'nueva-clave'…)
     * @param string $quien   usuario o dato que identifica (si va vacío, cuenta la IP)
     * @param int    $max     pasos permitidos en la ventana
     * @param int    $minutos tamaño de la ventana
     * @return bool  true = ya se pasó del límite (no se registra este paso)
     */
    function limiteSuperado(string $grupo, string $quien, int $max, int $minutos): bool
    {
        $ip = class_exists(AuthController::class) ? AuthController::ipCliente() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $clave = mb_substr($grupo . ':' . ($quien !== '' ? $quien : $ip), 0, 255);
        try {
            $db = App\Base\Database::getInstance();
            $n = $db->fetch(
                "SELECT COUNT(*) AS n FROM login_intentos
                 WHERE email = ? AND creado_en > now() - make_interval(mins => ?)",
                [$clave, $minutos]
            );
            if ($n && (int) $n['n'] >= $max) {
                error_log("[límite] $clave superó $max en $minutos min (ip $ip)");
                return true;
            }
            $db->query("INSERT INTO login_intentos (ip, email) VALUES (?, ?)", [$ip, $clave]);
        } catch (Throwable $e) {
            error_log('[límite] no disponible: ' . $e->getMessage());
        }
        return false;
    }
}
