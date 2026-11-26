<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

$usuario = usuario_actual();
if ($usuario !== null) {
    header('Location: ' . ruta_panel($usuario['rol']));
    exit;
}

$error = '';
$email = '';
$registroExitoso = $_SESSION['registro_exitoso'] ?? false;
unset($_SESSION['registro_exitoso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
        || $clave === '' || strlen($clave) > 72 || str_contains($clave, "\0")) {
        $error = 'Ingresá un correo válido y tu contraseña.';
    } else {
        try {
            $consulta = conectar_db()->prepare(
                'SELECT id_usuario, nombre, apellido, email, clave_hash, rol, activo, version_sesion, id_participante
                 FROM usuarios WHERE email = :email'
            );
            $consulta->execute(['email' => $email]);
            $usuario = $consulta->fetch();

            if ($usuario && (int) $usuario['activo'] === 1 && password_verify($clave, $usuario['clave_hash'])
                && in_array($usuario['rol'], ['administrador', 'empleado', 'usuario'], true)) {
                session_regenerate_id(true);
                unset($usuario['clave_hash']);
                $_SESSION['usuario'] = $usuario;
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header('Location: ' . ruta_panel($usuario['rol']));
                exit;
            }

            $error = 'El correo o la contraseña son incorrectos.';
        } catch (PDOException | RuntimeException $exception) {
            error_log('Error al iniciar sesión en eventos: ' . $exception->getMessage());
            $error = 'No pudimos iniciar sesión. Intentá nuevamente más tarde.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | Eventos y cursos</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="login">
        <p class="etiqueta">Eventos y cursos</p>
        <h1>Iniciar sesión</h1>
        <p class="descripcion">Ingresá con tu correo y contraseña.</p>

        <?php if ($registroExitoso): ?>
            <p class="aviso" role="status">Tu cuenta está lista. Ya podés iniciar sesión.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>

        <form method="post" action="index.php">
            <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="username" maxlength="254" value="<?= escapar($email) ?>" required>

            <label for="clave">Contraseña</label>
            <input id="clave" name="clave" type="password" autocomplete="current-password" required>

            <button type="submit">Ingresar</button>
        </form>
        <p class="nota">¿Todavía no tenés cuenta? <a href="registro.php">Registrate</a>.</p>
    </main>
</body>
</html>
