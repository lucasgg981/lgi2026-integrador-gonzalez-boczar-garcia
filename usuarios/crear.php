<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/usuarios.php';
$usuario = requerir_rol('administrador');

$error = '';
$nuevo = true;
$datos = datos_usuario(['rol' => 'empleado', 'activo' => '1']);
$accion = 'crear.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = datos_usuario($_POST);
    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } else {
        $error = validar_usuario($datos, true);
        if ($error === '') {
            try {
                $parametros = $datos;
                unset($parametros['clave']);
                $parametros['clave_hash'] = password_hash($datos['clave'], PASSWORD_DEFAULT);
                $consulta = conectar_db()->prepare(
                    'INSERT INTO usuarios (nombre, apellido, email, rol, activo, clave_hash)
                     VALUES (:nombre, :apellido, :email, :rol, :activo, :clave_hash)'
                );
                $consulta->execute($parametros);
                header('Location: index.php?mensaje=' . urlencode('Usuario creado correctamente.'));
                exit;
            } catch (PDOException | RuntimeException $exception) {
                error_log('Error al crear usuario: ' . $exception->getMessage());
                $error = $exception instanceof PDOException && ($exception->errorInfo[1] ?? null) === 1062
                    ? 'Ya existe un usuario con ese correo.'
                    : 'No pudimos crear el usuario. Intentá nuevamente más tarde.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo usuario | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Nuevo usuario</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php if (is_array($datos)): ?>
            <?php require __DIR__ . '/../includes/formulario_usuario.php'; ?>
        <?php endif; ?>
    </main>
</body>
</html>
