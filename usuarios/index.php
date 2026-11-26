<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
$usuario = requerir_rol('administrador');
$error = '';
$cuentas = [];
$mensaje = is_string($_GET['mensaje'] ?? null) ? $_GET['mensaje'] : '';
try {
    $cuentas = conectar_db()->query(
        "SELECT id_usuario, nombre, apellido, email, rol, activo FROM usuarios
         WHERE rol IN ('administrador', 'empleado') ORDER BY apellido, nombre, id_usuario"
    )->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al listar usuarios: ' . $exception->getMessage());
    $error = 'No pudimos consultar los usuarios. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-eventos">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="../admin.php">Volver al panel</a>
        </header>
        <h1>Usuarios del sistema</h1>
        <p><a class="boton" href="crear.php">Nuevo usuario</a></p>
        <p class="descripcion">Para activar o desactivar una cuenta, entrá en Editar. Sus inscripciones se conservan.</p>
        <?php if ($mensaje !== ''): ?><p class="aviso" role="status"><?= escapar($mensaje) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="error" role="alert"><?= escapar($error) ?></p><?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Nombre</th><th>Apellido</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($cuentas as $cuenta): ?>
                            <tr>
                                <td><?= escapar($cuenta['nombre']) ?></td>
                                <td><?= escapar($cuenta['apellido']) ?></td>
                                <td><?= escapar($cuenta['email']) ?></td>
                                <td><?= escapar($cuenta['rol']) ?></td>
                                <td><?= (int) $cuenta['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                                <td><a href="editar.php?id=<?= (int) $cuenta['id_usuario'] ?>">Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
