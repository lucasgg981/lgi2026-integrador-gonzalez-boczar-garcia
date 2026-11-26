<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/usuarios.php';
$usuario = requerir_rol('administrador');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('El identificador del usuario no es válido.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
    http_response_code(403);
    exit('La sesión del formulario venció. Volvé al listado e intentá nuevamente.');
}

$error = '';
$nuevo = false;
$datos = null;
$accion = 'editar.php?id=' . $id;
try {
    $conexion = conectar_db();
    $consulta = $conexion->prepare(
        "SELECT nombre, apellido, email, rol, activo FROM usuarios WHERE id_usuario = :id
         AND rol IN ('administrador', 'empleado')"
    );
    $consulta->execute(['id' => $id]);
    $datos = $consulta->fetch();
    if ($datos === false) {
        http_response_code(404);
        exit('El usuario no existe.');
    }
    $datos['clave'] = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = datos_usuario($_POST);
        $error = validar_usuario($datos, false);
        if ($error === '') {
            $error = actualizar_usuario($conexion, $id, $datos, (int) $usuario['id_usuario']);
            if ($error === '') {
                header('Location: index.php?mensaje=' . urlencode('Usuario actualizado correctamente.'));
                exit;
            }
        }
    }
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al editar usuario: ' . $exception->getMessage());
    $error = $exception instanceof PDOException && ($exception->errorInfo[1] ?? null) === 1062
        ? 'Ya existe un usuario con ese correo.'
        : 'No pudimos cargar o guardar el usuario. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar usuario | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Editar usuario</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php if (is_array($datos)): ?>
            <?php require __DIR__ . '/../includes/formulario_usuario.php'; ?>
        <?php endif; ?>
    </main>
</body>
</html>
