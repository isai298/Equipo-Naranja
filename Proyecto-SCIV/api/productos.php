<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../src/ProductoService.php';

$usuario = requerirSesion();

try {
    $svc    = new ProductoService(getConexion());
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $lista = $svc->listar(trim((string)($_GET['q'] ?? '')));
        responder(200, ['ok' => true, 'productos' => $lista]);
    }

    requerirAdmin(); // crear / editar / eliminar solo Administrador
    $d  = leerJson();
    $id = (int)($_GET['id'] ?? 0);

    switch ($metodo) {
        case 'POST':
            $nuevo = $svc->crear($d);
            responder(201, ['ok' => true, 'id_producto' => $nuevo]);
        case 'PUT':
            if ($id <= 0) {
                responder(400, ['ok' => false, 'mensaje' => 'Falta el id del producto.']);
            }
            $svc->actualizar($id, $d);
            responder(200, ['ok' => true]);
        case 'DELETE':
            if ($id <= 0) {
                responder(400, ['ok' => false, 'mensaje' => 'Falta el id del producto.']);
            }
            $svc->desactivar($id);
            responder(200, ['ok' => true]);
        default:
            responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
    }
} catch (InvalidArgumentException $e) {
    responder(422, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (PDOException $e) {
    $msg = $e->getCode() === '23000' ? 'El código de barras ya existe.' : 'Error de base de datos.';
    responder(409, ['ok' => false, 'mensaje' => $msg]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'mensaje' => 'Error de servidor.']);
}
