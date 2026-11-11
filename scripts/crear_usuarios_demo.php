<?php

// Este script solo se ejecuta desde la terminal, nunca desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/database.php';

$usuarios = [
    ['nombre' => 'Administrador', 'apellido' => 'Demo', 'email' => 'admin@eventos.local', 'clave' => 'Admin12345!', 'rol' => 'administrador'],
    ['nombre' => 'Empleado', 'apellido' => 'Demo', 'email' => 'empleado@eventos.local', 'clave' => 'Empleado12345!', 'rol' => 'empleado'],
];

try {
    $conexion = conectar_db();
    $buscar = $conexion->prepare('SELECT id_usuario FROM usuarios WHERE email = :email');
    $insertar = $conexion->prepare(
        'INSERT INTO usuarios (nombre, apellido, email, clave_hash, rol) VALUES (:nombre, :apellido, :email, :clave_hash, :rol)'
    );

    foreach ($usuarios as $usuario) {
        $buscar->execute(['email' => $usuario['email']]);
        if ($buscar->fetch()) {
            echo "Ya existe: {$usuario['email']} (sin modificaciones).\n";
            continue;
        }

        $insertar->execute([
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'clave_hash' => password_hash($usuario['clave'], PASSWORD_DEFAULT),
            'rol' => $usuario['rol'],
        ]);
        echo "Creado: {$usuario['email']}\n";
    }
} catch (PDOException | RuntimeException $exception) {
    fwrite(STDERR, 'No se pudieron crear las cuentas: ' . $exception->getMessage() . "\n");
    exit(1);
}
