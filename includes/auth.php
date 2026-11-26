<?php

ini_set('session.use_strict_mode', '1');
session_name('lgi_eventos');
session_set_cookie_params([
    'path' => ruta_aplicacion(''),
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');

function ruta_aplicacion(string $ruta): string
{
    $directorio = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    $raiz = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    if (str_starts_with($script, $raiz)) {
        $niveles = substr_count(substr($script, strlen($raiz)), DIRECTORY_SEPARATOR);
        for ($nivel = 0; $nivel < $niveles; $nivel++) {
            $directorio = str_replace('\\', '/', dirname($directorio));
        }
    }

    return rtrim($directorio, '/') . '/' . ltrim($ruta, '/');
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function usuario_actual(): ?array
{
    if (!isset($_SESSION['usuario'])) {
        return null;
    }

    require_once __DIR__ . '/database.php';
    try {
        $consulta = conectar_db()->prepare(
            'SELECT id_usuario, nombre, apellido, email, rol, activo, version_sesion, id_participante
             FROM usuarios WHERE id_usuario = :id'
        );
        $consulta->execute(['id' => $_SESSION['usuario']['id_usuario']]);
        $usuario = $consulta->fetch();
    } catch (PDOException | RuntimeException $exception) {
        error_log('Error al verificar la sesión: ' . $exception->getMessage());
        http_response_code(503);
        exit('No pudimos verificar la sesión. Intentá nuevamente más tarde.');
    }

    if (!$usuario || (int) $usuario['activo'] !== 1
        || (int) $usuario['version_sesion'] !== (int) ($_SESSION['usuario']['version_sesion'] ?? 0)) {
        unset($_SESSION['usuario'], $_SESSION['csrf']);
        return null;
    }

    $_SESSION['usuario'] = $usuario;
    return $usuario;
}

function ruta_panel(string $rol): string
{
    return ruta_aplicacion(match ($rol) {
        'administrador' => 'admin.php',
        'empleado' => 'empleado.php',
        'usuario' => 'mi-cuenta.php',
    });
}

function requerir_sesion(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        header('Location: ' . ruta_aplicacion('index.php'));
        exit;
    }

    return $usuario;
}

function requerir_rol(string ...$roles): array
{
    $usuario = requerir_sesion();

    if (!in_array($usuario['rol'], $roles, true)) {
        http_response_code(403);
        exit('No tenés permiso para acceder a este panel.');
    }

    return $usuario;
}

function token_csrf(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_valido(): bool
{
    return isset($_SESSION['csrf'])
        && is_string($_POST['csrf'] ?? null)
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
