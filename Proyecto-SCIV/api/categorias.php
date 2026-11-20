<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/helpers.php';

requerirSesion();
try {
    $filas = getConexion()->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();
    responder(200, ['ok' => true, 'categorias' => $filas]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'mensaje' => 'Error de servidor.']);
}
