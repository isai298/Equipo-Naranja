<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$d        = leerJson();
$username = trim((string)($d['username'] ?? ''));
$password = (string)($d['password'] ?? '');
if ($username === '' || $password === '') {
    responder(400, ['ok' => false, 'mensaje' => 'Ingresa usuario y contraseña.']);
}

try {
    $db = getConexion();
    $st = $db->prepare(
        'SELECT u.id_usuario, u.nombre_completo, u.password_hash, r.nombre_rol
           FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
          WHERE u.username = ? AND u.activo = 1'
    );
    $st->execute([$username]);
    $u = $st->fetch();

    $valido = false;
    if ($u) {
        $hash = $u['password_hash'];
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon')) {
            $valido = password_verify($password, $hash);
        } elseif (hash_equals($hash, $password)) {
            // Contraseña en texto plano del dato de prueba: se migra a hash al primer login.
            $valido = true;
            $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?')
               ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id_usuario']]);
        }
    }

    if (!$valido) {
        responder(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'     => (int)$u['id_usuario'],
        'nombre' => $u['nombre_completo'],
        'rol'    => $u['nombre_rol'],
    ];
    responder(200, ['ok' => true, 'usuario' => $_SESSION['usuario']]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'mensaje' => 'Error de servidor: ' . $e->getMessage()]);
}
