<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Envía una respuesta JSON y termina la ejecución. */
function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Devuelve el usuario en sesión o responde 401. */
function requerirSesion(): array
{
    if (empty($_SESSION['usuario'])) {
        responder(401, ['ok' => false, 'mensaje' => 'Sesión no iniciada.']);
    }
    return $_SESSION['usuario'];
}

/** Exige rol Administrador o responde 403. */
function requerirAdmin(): array
{
    $u = requerirSesion();
    if ($u['rol'] !== 'Administrador') {
        responder(403, ['ok' => false, 'mensaje' => 'No tienes permiso para esta acción.']);
    }
    return $u;
}

/** Lee el cuerpo de la petición como JSON. */
function leerJson(): array
{
    $crudo = file_get_contents('php://input');
    if ($crudo === false || trim($crudo) === '') {
        return [];
    }
    $datos = json_decode($crudo, true);
    if (!is_array($datos)) {
        responder(400, ['ok' => false, 'mensaje' => 'JSON inválido.']);
    }
    return $datos;
}
