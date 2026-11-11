<?php

ini_set('session.use_strict_mode', '1');
session_name('lgi_eventos');
session_set_cookie_params([
    'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function ruta_panel(string $rol): string
{
    return $rol === 'administrador' ? 'admin.php' : 'empleado.php';
}

function requerir_sesion(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        header('Location: index.php');
        exit;
    }

    return $usuario;
}

function requerir_rol(string $rol): array
{
    $usuario = requerir_sesion();

    if ($usuario['rol'] !== $rol) {
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
