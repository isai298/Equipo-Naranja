<?php
declare(strict_types=1);

/**
 * Lógica de negocio del módulo de ventas (POS).
 * Los precios SIEMPRE se toman de la base de datos, nunca del cliente.
 */
class VentaService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Valida y agrupa los ítems [{id_producto, cantidad}], sumando duplicados.
     * @return array<int,int> id_producto => cantidad
     */
    public static function agruparItems(array $items): array
    {
        $agrupados = [];
        foreach ($items as $item) {
            $id   = filter_var($item['id_producto'] ?? null, FILTER_VALIDATE_INT);
            $cant = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                throw new InvalidArgumentException('Producto inválido en la venta.');
            }
            if ($cant === false || $cant <= 0) {
                throw new InvalidArgumentException('La cantidad debe ser un entero mayor a cero.');
            }
            $agrupados[$id] = ($agrupados[$id] ?? 0) + $cant;
        }
        return $agrupados;
    }

    /** Suma de subtotales (precio * cantidad) redondeada a 2 decimales. */
    public static function calcularTotal(array $lineas): float
    {
        $total = 0.0;
        foreach ($lineas as $l) {
            $total += $l['precio_unitario'] * $l['cantidad'];
        }
        return round($total, 2);
    }

    /**
     * Registra la venta completa en una transacción:
     * valida stock, inserta ventas + detalle_ventas y descuenta productos.stock_actual.
     * @return array{id_venta:int,total:float,cambio:float}
     */
    public function registrar(int $idUsuario, string $metodoPago, float $montoRecibido, array $items): array
    {
        if (!in_array($metodoPago, ['Efectivo', 'Tarjeta'], true)) {
            throw new InvalidArgumentException('Método de pago inválido.');
        }
        $items = self::agruparItems($items);
        if (count($items) === 0) {
            throw new InvalidArgumentException('La venta no tiene productos.');
        }

        $this->db->beginTransaction();
        try {
            $sel = $this->db->prepare(
                'SELECT id_producto, nombre, precio_venta, stock_actual
                   FROM productos WHERE id_producto = ? AND activo = 1 FOR UPDATE'
            );

            $lineas = [];
            foreach ($items as $idProducto => $cantidad) {
                $sel->execute([$idProducto]);
                $p = $sel->fetch();
                if (!$p) {
                    throw new InvalidArgumentException("El producto #$idProducto no existe o está inactivo.");
                }
                if ((int)$p['stock_actual'] < $cantidad) {
                    throw new InvalidArgumentException(
                        "Stock insuficiente de {$p['nombre']} (disponible: {$p['stock_actual']})."
                    );
                }
                $lineas[] = [
                    'id_producto'     => $idProducto,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => (float)$p['precio_venta'],
                ];
            }

            $total = self::calcularTotal($lineas);
            if ($metodoPago === 'Tarjeta') {
                $montoRecibido = $total;
            }
            if (round($montoRecibido, 2) < $total) {
                throw new InvalidArgumentException('El monto recibido es menor al total de la venta.');
            }
            $cambio = round($montoRecibido - $total, 2);

            $this->db->prepare(
                'INSERT INTO ventas (id_usuario, total, metodo_pago, monto_recibido, cambio_entregado)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$idUsuario, $total, $metodoPago, $montoRecibido, $cambio]);
            $idVenta = (int)$this->db->lastInsertId();

            $insDet = $this->db->prepare(
                'INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $updStock = $this->db->prepare(
                'UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?'
            );
            foreach ($lineas as $l) {
                $insDet->execute([
                    $idVenta, $l['id_producto'], $l['cantidad'], $l['precio_unitario'],
                    round($l['precio_unitario'] * $l['cantidad'], 2),
                ]);
                $updStock->execute([$l['cantidad'], $l['id_producto']]);
            }

            $this->db->commit();
            return ['id_venta' => $idVenta, 'total' => $total, 'cambio' => $cambio];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
