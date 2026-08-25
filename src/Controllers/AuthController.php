<?php

namespace App\Controllers;

use App\Base\Auth;
use App\Base\Controller;
use App\Base\Database;

class AuthController extends Controller
{
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('../login.php');
        }

        $email = trim($this->post('correo', ''));
        $password = $this->post('password', '');
        $rol = $this->post('rol', '');

        if ($email === '' || $password === '' || $rol === '') {
            $this->redirect("../login.php?error=empty&rol=$rol&email=$email");
        }

        $db = Database::getInstance();
        $usuario = $db->fetch(
            "SELECT id, nombre, password, rol FROM usuarios WHERE email = ? LIMIT 1",
            [$email]
        );

        // Credenciales mal: mismo mensaje exista o no el correo, para no
        // revelar qué direcciones están registradas.
        if (!$usuario || !password_verify($password, $usuario['password'])) {
            $this->redirect("../login.php?error=1&rol=$rol&email=$email");
        }

        // Credenciales BIEN pero el tipo de usuario elegido no es el suyo.
        // Antes daba el mismo "Credenciales incorrectas" y la gente creía que
        // se había equivocado de contraseña.
        if ($usuario['rol'] !== $rol) {
            $this->redirect("../login.php?error=rol&rol={$usuario['rol']}&email=$email");
        }

        Auth::login($usuario['id'], $usuario['nombre'], $usuario['rol']);

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
}
