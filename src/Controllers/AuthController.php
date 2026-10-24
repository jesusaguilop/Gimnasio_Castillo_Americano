<?php

namespace App\Controllers;

use App\Base\Auth;
use App\Base\Controller;
use App\Base\Database;
use App\Base\Session;

class AuthController extends Controller
{
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('../login.php');
        }

        Session::start();
        require_once __DIR__ . '/../../lib/csrf_helper.php';

        $email = trim($this->post('correo', ''));
        $password = $this->post('password', '');
        $volver = '&email=' . urlencode($email);

        if (!validar_token_csrf($this->post('_csrf_token', ''))) {
            $this->redirect("../login.php?error=expirado$volver");
        }

        if ($email === '' || $password === '') {
            $this->redirect("../login.php?error=empty$volver");
        }

        $db = Database::getInstance();
        $emailNorm = mb_strtolower($email);
        $ip = self::ipCliente();

        // Freno a la fuerza bruta, guardado en la BD (antes se contaba en la
        // sesión y bastaba con borrar la cookie para saltárselo).
        if ($this->demasiadosIntentos($db, $ip, $emailNorm)) {
            $this->redirect("../login.php?error=bloqueado$volver");
        }

        // SELECT * para no romper el login si aún no se aplicó la columna
        // debe_cambiar_clave (database/seguridad_2026_10.sql).
        $usuario = $db->fetch(
            "SELECT * FROM usuarios WHERE lower(email) = ? LIMIT 1",
            [$emailNorm]
        );

        // Credenciales mal: mismo mensaje exista o no el correo, para no
        // revelar qué direcciones están registradas. Se verifica contra un
        // hash ficticio cuando el correo no existe para que el tiempo de
        // respuesta tampoco lo delate.
        $hash = $usuario['password'] ?? '$2y$12$z6RGyuXyGSyPM7S.ZohXJurfRxE1dfko90zIuocMYOPSg5Op/cPby';
        if (!password_verify($password, $hash) || !$usuario) {
            $this->registrarFallo($db, $ip, $emailNorm);
            $this->redirect("../login.php?error=1$volver");
        }

        $this->limpiarFallos($db, $emailNorm);

        // Las claves viejas con costo 10 se suben a 12 al entrar (nunca baja:
        // PHP 8.3 usa 10 por defecto y las de admin ya están en 12).
        if (password_needs_rehash($usuario['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $usuario['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->query("UPDATE usuarios SET password = ? WHERE id = ?", [$usuario['password'], $usuario['id']]);
        }

        // El tipo de usuario elegido en el formulario ya no bloquea la entrada:
        // el servidor conoce el rol real y lleva a cada quien a su panel.
        Auth::login($usuario['id'], $usuario['nombre'], $usuario['rol'], $usuario['password'],
            !empty($usuario['debe_cambiar_clave']));

        if (!empty($usuario['debe_cambiar_clave'])) {
            $this->redirect('../cambiar_clave.php');
        }

        match ($usuario['rol']) {
            'admin'    => $this->redirect('../admin/dashboard.php'),
            'profesor' => $this->redirect('../profesores/dashboard.php'),
            'padre'    => $this->redirect('../padres/dashboard.php'),
            default    => $this->redirect('../login.php'),
        };
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('../login.php');
    }

    /* ── Límite de intentos ──────────────────────────────────────────
     * Por correo: 8 fallos en 15 min bloquean ESA cuenta 15 min (protege
     * contra adivinar la clave de alguien concreto).
     * Por IP: 30 fallos en 15 min (protege contra probar muchas cuentas).
     * Si la tabla login_intentos aún no existe, no bloquea a nadie. */
    private const VENTANA = "15 minutes";
    private const MAX_EMAIL = 8;
    private const MAX_IP = 30;

    private function demasiadosIntentos(Database $db, string $ip, string $email): bool
    {
        try {
            $r = $db->fetch(
                "SELECT COUNT(*) FILTER (WHERE email = ?) AS por_email,
                        COUNT(*) FILTER (WHERE ip = ?)    AS por_ip
                 FROM login_intentos
                 WHERE creado_en > now() - interval '" . self::VENTANA . "'
                   AND (email = ? OR ip = ?)",
                [$email, $ip, $email, $ip]
            );
            return $r && ($r['por_email'] >= self::MAX_EMAIL || $r['por_ip'] >= self::MAX_IP);
        } catch (\Throwable $e) {
            error_log('[login] límite de intentos no disponible: ' . $e->getMessage());
            return false;
        }
    }

    private function registrarFallo(Database $db, string $ip, string $email): void
    {
        try {
            $db->query("INSERT INTO login_intentos (ip, email) VALUES (?, ?)", [$ip, mb_substr($email, 0, 255)]);
            // Limpieza ocasional de registros viejos
            if (random_int(1, 50) === 1) {
                $db->query("DELETE FROM login_intentos WHERE creado_en < now() - interval '1 day'");
            }
        } catch (\Throwable $e) {
            error_log('[login] no se pudo registrar el intento: ' . $e->getMessage());
        }
    }

    private function limpiarFallos(Database $db, string $email): void
    {
        try {
            $db->query("DELETE FROM login_intentos WHERE email = ?", [$email]);
        } catch (\Throwable) {
        }
    }

    /** IP del cliente. Detrás de Vercel → Railway, REMOTE_ADDR es el proxy.
     *  No se usa el primer valor de X-Forwarded-For: lo escribe el cliente.
     *  x-vercel-forwarded-for lo pone Vercel; x-real-ip, el borde de Railway.
     *  (Quien vaya directo a Railway puede falsearlas: por eso el límite
     *  fuerte es el de por correo, no el de por IP.) */
    public static function ipCliente(): string
    {
        foreach (['HTTP_X_VERCEL_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $h) {
            $ip = trim(explode(',', $_SERVER[$h] ?? '')[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}
