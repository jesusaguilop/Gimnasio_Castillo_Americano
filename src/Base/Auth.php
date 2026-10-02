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

    public static function login(int $id, string $name, string $role): void
    {
        Session::start();
        // ID de sesión nuevo al autenticarse: evita la fijación de sesión.
        session_regenerate_id(true);
        Session::set('id', $id);
        Session::set('nombre', $name);
        Session::set('rol', $role);
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

        $roles = (array) $roles;
        if (!in_array(self::role(), $roles, true)) {
            header("Location: " . ($redirectTo ?? '../login.php'));
            exit;
        }
    }
}
