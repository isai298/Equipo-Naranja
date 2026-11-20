<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

// GET: devuelve el usuario en sesión. DELETE: cierra sesión.
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $_SESSION = [];
    session_destroy();
    responder(200, ['ok' => true]);
}

$u = requerirSesion();
responder(200, ['ok' => true, 'usuario' => $u]);
