<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require __DIR__ . '/includes/usuarios.php';

$usuario = usuario_actual();
if ($usuario !== null) {
    header('Location: ' . ruta_panel($usuario['rol']));
    exit;
}

$datos = datos_cuenta($_POST);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } else {
        $error = validar_datos_cuenta($datos, true, 150);
        if ($error === '') {
            try {
                registrar_cuenta_publica(conectar_db(), $datos);
                $_SESSION['registro_exitoso'] = true;
                header('Location: ' . ruta_aplicacion('index.php'), true, 303);
                exit;
            } catch (PDOException | RuntimeException $exception) {
                if ($exception instanceof PDOException && ($exception->errorInfo[1] ?? null) === 1062) {
                    $error = 'Ya existe una cuenta con ese correo.';
                } else {
                    error_log('Error al registrar cuenta: ' . $exception->getMessage());
                    $error = 'No pudimos crear tu cuenta. Intentá nuevamente más tarde.';
                }
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
    <title>Crear cuenta | Eventos y cursos</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="login">
        <p class="etiqueta">Eventos y cursos</p>
        <h1>Crear cuenta</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <form method="post" action="registro.php">
            <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" autocomplete="given-name" maxlength="100" value="<?= escapar($datos['nombre']) ?>" required>
            <label for="apellido">Apellido</label>
            <input id="apellido" name="apellido" autocomplete="family-name" maxlength="100" value="<?= escapar($datos['apellido']) ?>" required>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= escapar($datos['email']) ?>" required>
            <label for="clave">Contraseña</label>
            <input id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="clave-ayuda" required>
<p class="ayuda" id="clave-ayuda">Usá al menos 8 caracteres.</p>
            <button type="submit">Registrarme</button>
        </form>
        <p class="nota">¿Ya tenés cuenta? <a href="index.php">Iniciá sesión</a>.</p>
    </main>
</body>
</html>
