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

        // Freno a la fuerza bruta: tras 5 intentos fallidos en la sesión,
        // cada intento extra espera 1 segundo.
        $fallidos = (int) Session::get('login_fallidos', 0);
        if ($fallidos >= 5) {
            sleep(1);
        }

        $db = Database::getInstance();
        $usuario = $db->fetch(
            "SELECT id, nombre, password, rol FROM usuarios WHERE email = ? LIMIT 1",
            [$email]
        );

        // Credenciales mal: mismo mensaje exista o no el correo, para no
        // revelar qué direcciones están registradas.
        if (!$usuario || !password_verify($password, $usuario['password'])) {
            Session::set('login_fallidos', $fallidos + 1);
            $this->redirect("../login.php?error=1$volver");
        }

        // El tipo de usuario elegido en el formulario ya no bloquea la entrada:
        // el servidor conoce el rol real y lleva a cada quien a su panel.
        Session::remove('login_fallidos');
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
