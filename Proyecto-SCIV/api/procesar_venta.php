<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../src/VentaService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

// El cajero sale de la sesión, no del cliente.
$usuario = requerirSesion();
$d       = leerJson();

try {
    $svc = new VentaService(getConexion());
    $res = $svc->registrar(
        $usuario['id'],
        (string)($d['metodo_pago'] ?? 'Efectivo'),
        (float)($d['monto_recibido'] ?? 0),
        is_array($d['items'] ?? null) ? $d['items'] : []
    );
    responder(201, ['ok' => true] + $res);
} catch (InvalidArgumentException $e) {
    responder(422, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'mensaje' => 'Error de servidor al procesar la venta.']);
}
