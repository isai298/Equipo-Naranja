<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/ProductoService.php';
require_once __DIR__ . '/../src/VentaService.php';

$pasaron = 0;
$fallaron = 0;

function probar(string $nombre, callable $fn): void
{
    global $pasaron, $fallaron;
    try {
        $fn();
        $pasaron++;
        echo "[PASS] $nombre\n";
    } catch (Throwable $e) {
        $fallaron++;
        echo "[FAIL] $nombre -> " . $e->getMessage() . "\n";
    }
}

function assertIgual($esperado, $actual, string $msg = ''): void
{
    if ($esperado !== $actual) {
        $eVar = var_export($esperado, true);
        $aVar = var_export($actual, true);
        throw new RuntimeException($msg ?: "Esperado $eVar, se obtuvo $aVar");
    }
}

function assertLanzaExcepcion(string $claseExcepcion, callable $fn, string $msg = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $claseExcepcion) {
            return;
        }
        throw new RuntimeException($msg ?: "Se esperaba $claseExcepcion, pero lanzó " . get_class($e));
    }
    throw new RuntimeException($msg ?: "Se esperaba excepción $claseExcepcion y no se lanzó ninguna.");
}

// --- Pruebas de ProductoService::validar ---
probar('ProductoService::validar - datos válidos normalizados', function () {
    $res = ProductoService::validar([
        'nombre' => '  Refresco Cola 600ml  ',
        'precio_venta' => '18.556',
        'precio_compra' => '12.344',
        'stock_actual' => '20',
        'stock_minimo' => '5',
        'codigo_barras' => '7501001',
        'id_categoria' => '2',
    ]);
    assertIgual('Refresco Cola 600ml', $res['nombre']);
    assertIgual(18.56, $res['precio_venta']);
    assertIgual(12.34, $res['precio_compra']);
    assertIgual(20, $res['stock_actual']);
    assertIgual(2, $res['id_categoria']);
});

probar('ProductoService::validar - rechaza nombre vacío', function () {
    assertLanzaExcepcion(InvalidArgumentException::class, function () {
        ProductoService::validar([
            'nombre' => '   ',
            'precio_venta' => 10,
        ]);
    });
});

probar('ProductoService::validar - rechaza precio de venta <= 0', function () {
    assertLanzaExcepcion(InvalidArgumentException::class, function () {
        ProductoService::validar([
            'nombre' => 'Sabritas',
            'precio_venta' => 0,
        ]);
    });
});

probar('ProductoService::validar - rechaza stock negativo', function () {
    assertLanzaExcepcion(InvalidArgumentException::class, function () {
        ProductoService::validar([
            'nombre' => 'Agua',
            'precio_venta' => 12,
            'stock_actual' => -1,
        ]);
    });
});

probar('ProductoService::validar - id_categoria <= 0 debe ser null (detecta bug)', function () {
    $res = ProductoService::validar([
        'nombre' => 'Galletas',
        'precio_venta' => 15,
        'id_categoria' => -2,
    ]);
    assertIgual(null, $res['id_categoria'], 'id_categoria negativa no debería conservarse');
});

// --- Pruebas de VentaService ---
probar('VentaService::agruparItems - suma cantidades de producto repetido', function () {
    $res = VentaService::agruparItems([
        ['id_producto' => 1, 'cantidad' => 2],
        ['id_producto' => 3, 'cantidad' => 1],
        ['id_producto' => 1, 'cantidad' => 4],
    ]);
    assertIgual([1 => 6, 3 => 1], $res);
});

probar('VentaService::agruparItems - rechaza cantidad <= 0', function () {
    assertLanzaExcepcion(InvalidArgumentException::class, function () {
        VentaService::agruparItems([
            ['id_producto' => 1, 'cantidad' => 0],
        ]);
    });
});

probar('VentaService::calcularTotal - suma subtotales redondeados', function () {
    $total = VentaService::calcularTotal([
        ['precio_unitario' => 10.255, 'cantidad' => 2], // 20.51
        ['precio_unitario' => 5.00,   'cantidad' => 3], // 15.00
    ]);
    assertIgual(35.51, $total);
});

probar('VentaService::calcularTotal - lista vacía regresa 0.0', function () {
    assertIgual(0.0, VentaService::calcularTotal([]));
});

echo "\nResumen: $pasaron pasaron, $fallaron fallaron.\n";
exit($fallaron > 0 ? 1 : 0);