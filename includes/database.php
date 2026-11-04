<?php

function conectar_db(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $archivo = __DIR__ . '/../config/database.local.php';
    if (!is_file($archivo)) {
        throw new RuntimeException('Falta configurar config/database.local.php.');
    }

    $config = require $archivo;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );

    $conexion = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $conexion;
}
