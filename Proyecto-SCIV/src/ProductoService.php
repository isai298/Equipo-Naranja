<?php
declare(strict_types=1);

/** CRUD de productos (módulo 3). El borrado es lógico (activo = 0). */
class ProductoService
{
    public function __construct(private PDO $db)
    {
    }

    public function listar(string $busqueda = '', bool $soloActivos = true): array
    {
        $sql = 'SELECT p.id_producto, p.codigo_barras, p.nombre, p.id_categoria,
                       c.nombre AS categoria, p.precio_compra, p.precio_venta,
                       p.stock_actual, p.stock_minimo, p.activo
                  FROM productos p
             LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
                 WHERE 1=1';
        $params = [];
        if ($soloActivos) {
            $sql .= ' AND p.activo = 1';
        }
        if ($busqueda !== '') {
            $sql .= ' AND (p.nombre LIKE ? OR p.codigo_barras LIKE ?)';
            $params[] = "%$busqueda%";
            $params[] = "%$busqueda%";
        }
        $sql .= ' ORDER BY p.nombre';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Valida y normaliza los datos de entrada. */
    public static function validar(array $d): array
    {
        $nombre = trim((string)($d['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            throw new InvalidArgumentException('El nombre es obligatorio (máx. 120 caracteres).');
        }
        $precioVenta  = filter_var($d['precio_venta'] ?? null, FILTER_VALIDATE_FLOAT);
        $precioCompra = filter_var($d['precio_compra'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($precioVenta === false || $precioVenta <= 0) {
            throw new InvalidArgumentException('El precio de venta debe ser mayor a cero.');
        }
        if ($precioCompra === false || $precioCompra < 0) {
            throw new InvalidArgumentException('El precio de compra no puede ser negativo.');
        }
        $stock  = filter_var($d['stock_actual'] ?? 0, FILTER_VALIDATE_INT);
        $minimo = filter_var($d['stock_minimo'] ?? 5, FILTER_VALIDATE_INT);
        if ($stock === false || $stock < 0 || $minimo === false || $minimo < 0) {
            throw new InvalidArgumentException('El stock debe ser un entero mayor o igual a cero.');
        }
       
        $codigo = trim((string)($d['codigo_barras'] ?? ''));
        $cat    = filter_var($d['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        $catLimpia = ($cat === false || $cat === null || $cat <= 0) ? null : $cat;

        return [
            'codigo_barras' => $codigo === '' ? null : $codigo,
            'nombre'        => $nombre,
            'id_categoria'  => $catLimpia,
            'precio_compra' => round((float)$precioCompra, 2),
            'precio_venta'  => round((float)$precioVenta, 2),
            'stock_actual'  => $stock,
            'stock_minimo'  => $minimo,
        ];
        
    }

    public function crear(array $datos): int
    {
        $d = self::validar($datos);
        $this->db->prepare(
            'INSERT INTO productos (codigo_barras, nombre, id_categoria, precio_compra, precio_venta, stock_actual, stock_minimo)
             VALUES (:codigo_barras, :nombre, :id_categoria, :precio_compra, :precio_venta, :stock_actual, :stock_minimo)'
        )->execute($d);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $d = self::validar($datos);
        $d['id'] = $id;
        $st = $this->db->prepare(
            'UPDATE productos SET codigo_barras=:codigo_barras, nombre=:nombre, id_categoria=:id_categoria,
                    precio_compra=:precio_compra, precio_venta=:precio_venta,
                    stock_actual=:stock_actual, stock_minimo=:stock_minimo
              WHERE id_producto=:id'
        );
        $st->execute($d);
    }

    public function desactivar(int $id): void
    {
        $this->db->prepare('UPDATE productos SET activo = 0 WHERE id_producto = ?')->execute([$id]);
    }
}
