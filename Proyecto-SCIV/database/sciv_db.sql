CREATE DATABASE IF NOT EXISTS sciv_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sciv_db;

-- ==========================================
-- MÓDULO 1 y 9: Usuarios y Permisos
-- ==========================================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE, -- Ej. Administrador, Cajero, Almacenista
    descripcion VARCHAR(255)
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ==========================================
-- MÓDULO 3: Productos y Categorías
-- ==========================================
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    id_categoria INT,
    precio_compra DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    precio_venta DECIMAL(10, 2) NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5, -- Para alertas de "Stock Bajo" en Pantalla 2
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

-- ==========================================
-- MÓDULO 5: Inventario y Lotes (Caducidades)
-- ==========================================
CREATE TABLE lotes (
    id_lote INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    fecha_caducidad DATE,
    cantidad_inicial INT NOT NULL,
    cantidad_disponible INT NOT NULL,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- ==========================================
-- MÓDULO 6: Compras y Proveedores
-- ==========================================
CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(120) NOT NULL,
    contacto_nombre VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    rfc VARCHAR(20)
);

CREATE TABLE compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    estado ENUM('Pendiente', 'Recibida', 'Cancelada') DEFAULT 'Pendiente', -- Para "Compras Pendientes" en Pantalla 2
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE detalle_compras (
    id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- ==========================================
-- MÓDULO 4: Ventas (POS Funcional)
-- ==========================================
CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL, -- Cajero que realizó el cobro
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL,
    metodo_pago ENUM('Efectivo', 'Tarjeta') NOT NULL DEFAULT 'Efectivo',
    monto_recibido DECIMAL(10, 2) NOT NULL,
    cambio_entregado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    estado ENUM('Completada', 'Cancelada') DEFAULT 'Completada',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE detalle_ventas (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- ==========================================
-- MÓDULO 7: Devoluciones y Pérdidas (Mermas)
-- ==========================================
CREATE TABLE devoluciones_mermas (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('Devolucion_Cliente', 'Merma_Perdida') NOT NULL,
    id_producto INT NOT NULL,
    id_venta INT NULL, -- Opcional: se llena si es devolución de una venta
    id_usuario INT NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ==========================================
-- DATOS INICIALES DE PRUEBA (Basados en tu HTML)
-- ==========================================
INSERT INTO roles (nombre_rol, descripcion) VALUES 
('Administrador', 'Acceso total a todos los módulos del sistema'),
('Cajero', 'Acceso a POS y consultas básicas');

INSERT INTO usuarios (nombre_completo, username, password_hash, id_rol) VALUES 
('Diego Rivera', 'admin_diego', '123456', 1);

INSERT INTO categorias (nombre) VALUES ('Lácteos'), ('Abarrotes'), ('Bebidas');

INSERT INTO productos (codigo_barras, nombre, id_categoria, precio_compra, precio_venta, stock_actual, stock_minimo) VALUES 
('75010001', 'Leche 1L', 1, 18.00, 25.00, 30, 10),
('75010002', 'Pan de Caja Grande', 2, 32.00, 45.00, 4, 8);