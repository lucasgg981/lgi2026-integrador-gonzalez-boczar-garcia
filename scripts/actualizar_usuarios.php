<?php
// Actualiza el esquema sin borrar usuarios ni cambiar contraseñas.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';
try {
    $conexion = conectar_db();
    $columnas = $conexion->query('SHOW COLUMNS FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('activo', $columnas, true)) {
        $conexion->exec('ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1');
    }
    if (!in_array('version_sesion', $columnas, true)) {
        $conexion->exec('ALTER TABLE usuarios ADD COLUMN version_sesion INT NOT NULL DEFAULT 1');
    }
    if (!in_array('id_participante', $columnas, true)) {
        $conexion->exec(file_get_contents(__DIR__ . '/../database/registro_publico.sql'));
    }
    echo "Esquema de usuarios actualizado.\n";
} catch (PDOException | RuntimeException $exception) {
    fwrite(STDERR, 'No se pudo actualizar el esquema: ' . $exception->getMessage() . "\n");
    exit(1);
}
