<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/helpers.php';

requerirSesion();
try {
    $db = getConexion();
    $ventas = (float)$db->query(
        "SELECT COALESCE(SUM(total),0) FROM ventas
          WHERE estado = 'Completada' AND DATE(fecha_venta) = CURDATE()"
    )->fetchColumn();
    $bajo = (int)$db->query(
        'SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock_actual <= stock_minimo'
    )->fetchColumn();
    $pendientes = (int)$db->query(
        "SELECT COUNT(*) FROM compras WHERE estado = 'Pendiente'"
    )->fetchColumn();

    responder(200, [
        'ok' => true,
        'ventas_dia'         => $ventas,
        'stock_bajo'         => $bajo,
        'compras_pendientes' => $pendientes,
    ]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'mensaje' => 'Error de servidor.']);
}
