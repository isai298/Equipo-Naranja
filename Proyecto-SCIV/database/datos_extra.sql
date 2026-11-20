-- Datos adicionales de prueba (ejecutar DESPUÉS de sciv_db.sql)
USE sciv_db;

INSERT INTO productos (codigo_barras, nombre, id_categoria, precio_compra, precio_venta, stock_actual, stock_minimo) VALUES
('75010003', 'Refresco Cola 600ml', 3, 12.00, 18.00, 50, 12),
('75010004', 'Arroz 1kg', 2, 20.00, 29.50, 25, 8),
('75010005', 'Yogurt Natural 1L', 1, 22.00, 32.00, 3, 6);

INSERT INTO proveedores (nombre_empresa, contacto_nombre, telefono, email) VALUES
('Distribuidora del Golfo', 'Marta López', '2281234567', 'ventas@distgolfo.mx');

INSERT INTO compras (id_proveedor, id_usuario, total, estado) VALUES
(1, 1, 1500.00, 'Pendiente'),
(1, 1, 820.00, 'Pendiente');
