<?php
declare(strict_types=1);

/**
 * Conexión PDO a MariaDB/MySQL (XAMPP por defecto: root sin contraseña).
 * Cambia estos valores si tu configuración es distinta.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'sciv_db';
const DB_USER = 'root';
const DB_PASS = '';

function getConexion(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}
