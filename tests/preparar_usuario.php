<?php
// Datos aislados en una tabla temporal: nunca se modifican las cuentas reales.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (isset($caso['rol'])) {
    require_once __DIR__ . '/../includes/database.php';
    $db_prueba = conectar_db();
    $esquema_prueba = file_get_contents(__DIR__ . '/../database/esquema.sql');
    preg_match('/CREATE TABLE IF NOT EXISTS usuarios \(.*?ENGINE=InnoDB;/s', $esquema_prueba, $tabla_prueba);
    $db_prueba->exec(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $tabla_prueba[0]));
    $insertar_prueba = $db_prueba->prepare(
        'INSERT INTO usuarios (id_usuario, nombre, apellido, email, clave_hash, rol, activo, version_sesion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertar_prueba->execute([1, 'Prueba', 'Local', 'prueba@eventos.local', password_hash('Anterior123!', PASSWORD_BCRYPT, ['cost' => 4]),
        $caso['rol_real'] ?? $caso['rol'], $caso['activo_real'] ?? 1, $caso['version_real'] ?? 1]);
    if (isset($caso['segundo'])) {
        $insertar_prueba->execute([2, 'Segundo', 'Local', 'segundo@eventos.local', password_hash('Anterior123!', PASSWORD_BCRYPT, ['cost' => 4]),
            $caso['segundo'], 1, 1]);
    }
}
