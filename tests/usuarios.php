<?php

// Ejecutar con php tests/participantes.php. Requiere MySQL y CREATE TEMPORARY TABLES; no modifica registros reales.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);

if (($argv[1] ?? '') === '--peticion') {
    $caso = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SERVER['SCRIPT_NAME'] = $caso['base'] . '/' . $caso['archivo'];
    $_SERVER['SCRIPT_FILENAME'] = $raiz . '/' . $caso['archivo'];
    $_SERVER['REQUEST_METHOD'] = $caso['metodo'] ?? 'GET';
    $_GET = $caso['get'] ?? [];
    $_POST = $caso['post'] ?? [];

    session_save_path($caso['sesiones']);
    session_name('lgi_eventos');
    session_id('prueba' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION['csrf'] = 'token-de-prueba';
    if (isset($caso['rol'])) {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Prueba', 'apellido' => 'Local',
            'email' => 'prueba@eventos.local', 'rol' => $caso['rol'], 'version_sesion' => 1,
        ];
    }
    session_write_close();
    require __DIR__ . '/preparar_usuario.php';

    ob_start();
    register_shutdown_function(function (): void {
        $salida = ob_get_clean();
        echo json_encode([
            'estado' => http_response_code() ?: 200,
            'salida' => $salida,
            'cookie' => session_get_cookie_params()['path'],
            'inicio' => function_exists('ruta_aplicacion') ? ruta_aplicacion('index.php') : null,
            'panel' => function_exists('ruta_panel') ? ruta_panel('administrador') : null,
            'cuentas' => isset($GLOBALS['db_prueba']) ? $GLOBALS['db_prueba']->query(
                'SELECT id_usuario, nombre, email, rol, activo, version_sesion FROM usuarios ORDER BY id_usuario'
            )->fetchAll() : [],
            'clave_nueva' => isset($GLOBALS['db_prueba']) ? password_verify('Nueva12345!',
                $GLOBALS['db_prueba']->query('SELECT clave_hash FROM usuarios WHERE id_usuario = 1')->fetchColumn()) : false,
        ], JSON_THROW_ON_ERROR);
    });

    require $raiz . '/' . $caso['archivo'];
    exit;
}

require $raiz . '/includes/usuarios.php';

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function peticion(array $caso, string $sesiones): array
{
    $caso += ['base' => '/integrador', 'sesiones' => $sesiones];
    $proceso = proc_open(
        [PHP_BINARY, __FILE__, '--peticion', base64_encode(json_encode($caso, JSON_THROW_ON_ERROR))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    comprobar(is_resource($proceso), 'No se pudo iniciar la petición de prueba.');
    fclose($pipes[0]);
    $salida = stream_get_contents($pipes[1]);
    $errores = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $codigo = proc_close($proceso);
    comprobar($codigo === 0 && ($errores === '' || (isset($caso['error_esperado']) && str_starts_with($errores, $caso['error_esperado']))), 'La petición produjo un error PHP: ' . $caso['archivo']);

    return json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
}

$sesiones = sys_get_temp_dir() . '/lgi-usuarios-' . bin2hex(random_bytes(8));
mkdir($sesiones, 0700);
try {
    $valido = ['nombre' => 'Lucas', 'apellido' => 'González', 'email' => 'lucas@example.com',
        'rol' => 'administrador', 'activo' => '1', 'clave' => 'Nueva12345!'];
    comprobar(validar_usuario($valido, true) === '', 'Se rechazó un usuario válido.');
    comprobar(validar_usuario(array_replace($valido, ['clave' => '']), false) === '', 'Editar debe permitir conservar contraseña.');
    foreach ([['nombre' => ''], ['apellido' => str_repeat('á', 101)], ['email' => 'incorrecto'],
        ['rol' => 'otro'], ['activo' => 'otro'], ['clave' => ''], ['clave' => 'corta'],
        ['clave' => str_repeat('x', 73)]] as $cambio) {
        comprobar(validar_usuario(array_replace($valido, $cambio), true) !== '', 'Se aceptaron datos inválidos.');
    }
    foreach (['usuarios/index.php', 'usuarios/crear.php', 'usuarios/editar.php'] as $archivo) {
        $r = peticion(['archivo' => $archivo], $sesiones);
        comprobar($r['salida'] === '', 'Debe exigir sesión.');
        $r = peticion(['archivo' => $archivo, 'rol' => 'empleado'], $sesiones);
        comprobar($r['estado'] === 403, 'Un empleado no puede administrar cuentas.');
        $r = peticion(['archivo' => $archivo, 'rol' => 'administrador', 'version_real' => 2], $sesiones);
        comprobar($r['salida'] === '', 'Una sesión antigua debe invalidarse.');
        $r = peticion(['archivo' => $archivo, 'rol' => 'administrador', 'activo_real' => 0], $sesiones);
        comprobar($r['salida'] === '', 'Una cuenta desactivada no puede seguir usando su sesión.');
    }
    foreach (['usuarios/crear.php', 'usuarios/editar.php'] as $archivo) {
        $r = peticion(['archivo' => $archivo, 'rol' => 'administrador', 'metodo' => 'POST',
            'get' => ['id' => '1'], 'post' => $valido + ['csrf' => 'incorrecto']], $sesiones);
        comprobar($r['estado'] === 403, 'El formulario debe verificar CSRF.');
    }
    $r = peticion(['archivo' => 'usuarios/crear.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'post' => $valido + ['csrf' => 'token-de-prueba']], $sesiones);
    comprobar(count($r['cuentas']) === 2, 'No se creó la cuenta.');
    $r = peticion(['archivo' => 'usuarios/crear.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'error_esperado' => 'Error al crear usuario:', 'post' => array_replace($valido, ['email' => 'prueba@eventos.local', 'csrf' => 'token-de-prueba'])], $sesiones);
    comprobar(str_contains($r['salida'], 'Ya existe un usuario'), 'No se informó el correo duplicado.');
    foreach ([['activo' => '0'], ['rol' => 'empleado']] as $cambio) {
        $r = peticion(['archivo' => 'usuarios/editar.php', 'rol' => 'administrador', 'metodo' => 'POST',
            'get' => ['id' => '1'], 'post' => array_replace($valido, $cambio, ['csrf' => 'token-de-prueba'])], $sesiones);
        comprobar(str_contains($r['salida'], 'No podés desactivar'), 'No debe quitarse el propio acceso.');
        comprobar($r['cuentas'][0]['rol'] === 'administrador' && (int) $r['cuentas'][0]['activo'] === 1, 'Se modificó la cuenta pese al rechazo.');
    }
    $r = peticion(['archivo' => 'usuarios/editar.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'get' => ['id' => '1'], 'post' => $valido + ['csrf' => 'token-de-prueba']], $sesiones);
    comprobar($r['clave_nueva'] && (int) $r['cuentas'][0]['version_sesion'] === 2, 'No se cambió el hash o la versión de sesión.');
    $r = peticion(['archivo' => 'usuarios/editar.php', 'rol' => 'administrador', 'segundo' => 'empleado', 'metodo' => 'POST',
        'get' => ['id' => '2'], 'post' => array_replace($valido, ['email' => 'segundo@eventos.local', 'rol' => 'empleado', 'activo' => '0', 'clave' => '', 'csrf' => 'token-de-prueba'])], $sesiones);
    comprobar((int) $r['cuentas'][1]['activo'] === 0 && (int) $r['cuentas'][1]['version_sesion'] === 2, 'No se desactivó la cuenta conservando el registro.');
    $r = peticion(['archivo' => 'usuarios/crear.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'post' => array_replace($valido, ['nombre' => '<script>alert(1)</script>', 'email' => '', 'csrf' => 'token-de-prueba'])], $sesiones);
    comprobar(str_contains($r['salida'], '&lt;script&gt;') && !str_contains($r['salida'], 'value="Nueva12345!"'), 'El formulario debe escapar HTML y nunca volver a mostrar la contraseña.');
    echo "OK: usuarios, permisos, CSRF, correo único, contraseñas, desactivación e invalidación de sesiones.\n";
} finally {
    foreach (glob($sesiones . '/*') as $archivo) { unlink($archivo); }
    rmdir($sesiones);
}
