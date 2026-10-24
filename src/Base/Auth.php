<?php

namespace App\Base;

class Auth
{
    public static function check(): bool
    {
        Session::start();
        return Session::has('id') && Session::has('rol');
    }

    public static function id(): ?int
    {
        Session::start();
        return Session::get('id');
    }

    public static function user(): ?array
    {
        Session::start();
        $id = self::id();
        if (!$id) return null;

        $db = Database::getInstance();
        return $db->fetch("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?", [$id]);
    }

    public static function role(): ?string
    {
        Session::start();
        return Session::get('rol');
    }

    public static function name(): ?string
    {
        Session::start();
        return Session::get('nombre');
    }

    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    public static function isAdmin(): bool
    {
        return self::is('admin');
    }

    public static function isProfesor(): bool
    {
        return self::is('profesor');
    }

    public static function isPadre(): bool
    {
        return self::is('padre');
    }

    public static function login(int $id, string $name, string $role, string $passwordHash = '', bool $debeCambiarClave = false): void
    {
        Session::start();
        // ID de sesión nuevo al autenticarse: evita la fijación de sesión.
        session_regenerate_id(true);
        Session::set('id', $id);
        Session::set('nombre', $name);
        Session::set('rol', $role);
        Session::set('_huella', self::huella($passwordHash));
        Session::set('_revisado', time());
        Session::set('_actividad', time());
        Session::set('_inicio', time());
        Session::set('debe_cambiar_clave', $debeCambiarClave);
    }

    /** Huella corta del hash de la clave: si la clave cambia (o se restablece),
     *  las sesiones abiertas con la anterior dejan de valer. */
    public static function huella(string $passwordHash): string
    {
        return substr(hash('sha256', $passwordHash), 0, 16);
    }

    /**
     * Comprueba que la sesión siga siendo válida:
     *  - inactividad > SESSION_LIFETIME o más de 12 h desde el login → fuera;
     *  - cada 2 minutos, contra la BD: que el usuario exista, conserve el rol
     *    y no haya cambiado la clave (borrar o degradar a alguien lo saca).
     * Devuelve false si hubo que cerrar la sesión.
     */
    public static function sesionValida(): bool
    {
        $ahora = time();
        $inactividad = (int) config('session.lifetime', 7200);
        if ($ahora - (int) Session::get('_actividad', 0) > $inactividad
            || $ahora - (int) Session::get('_inicio', 0) > 12 * 3600) {
            return false;
        }
        Session::set('_actividad', $ahora);

        if ($ahora - (int) Session::get('_revisado', 0) >= 120) {
            $u = Database::getInstance()->fetch(
                "SELECT rol, password FROM usuarios WHERE id = ?", [self::id()]
            );
            if (!$u || $u['rol'] !== self::role()
                || !hash_equals((string) Session::get('_huella', ''), self::huella($u['password']))) {
                return false;
            }
            Session::set('_revisado', $ahora);
        }
        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Redirige si no está autenticado o no tiene el rol requerido.
     */
    public static function requireRole(string|array $roles, ?string $redirectTo = null): void
    {
        Session::start();

        if (!self::check()) {
            header("Location: " . ($redirectTo ?? '../login.php'));
            exit;
        }

        if (!self::sesionValida()) {
            self::logout();
            header("Location: " . ($redirectTo ?? '../login.php?error=sesion'));
            exit;
        }

        // Clave provisional: no se usa el panel hasta cambiarla
        if (Session::get('debe_cambiar_clave')) {
            header("Location: ../cambiar_clave.php");
            exit;
        }

        $roles = (array) $roles;
        if (!in_array(self::role(), $roles, true)) {
            header("Location: " . ($redirectTo ?? '../login.php'));
            exit;
        }
    }
}
