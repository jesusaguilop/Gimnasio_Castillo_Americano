<?php

if (!isset($_SESSION)) {
    session_start();
}

function generar_token_csrf(): string {
    if (empty($_SESSION["_csrf_token"])) {
        $_SESSION["_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["_csrf_token"];
}

function validar_token_csrf(string $token): bool {
    if (empty($_SESSION["_csrf_token"]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION["_csrf_token"], $token);
}

function campo_csrf(): string {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(generar_token_csrf()) . '">';
}
