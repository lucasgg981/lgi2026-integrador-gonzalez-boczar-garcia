<?php
// Datos de conexion a la base de datos local.
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "sistema_eventos";

// Si existe config.local.php se usan esos datos.
// Ese archivo no se sube al repositorio para no compartir la clave de cada uno.
if (file_exists(__DIR__ . "/config.local.php")) {
    include __DIR__ . "/config.local.php";
}

$conexion = new mysqli($servidor, $usuario, $clave, $base);

if ($conexion->connect_error) {
    die("No se pudo conectar a la base de datos: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
