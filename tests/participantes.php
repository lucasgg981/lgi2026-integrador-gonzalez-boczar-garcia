<?php

// Ejecutar con php tests/participantes.php. No modifica la base de datos.
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
            'email' => 'prueba@eventos.local', 'rol' => $caso['rol'],
        ];
    }
    session_write_close();

    ob_start();
    register_shutdown_function(function (): void {
        $salida = ob_get_clean();
        echo json_encode([
            'estado' => http_response_code() ?: 200,
            'salida' => $salida,
            'cookie' => session_get_cookie_params()['path'],
            'inicio' => function_exists('ruta_aplicacion') ? ruta_aplicacion('index.php') : null,
            'panel' => function_exists('ruta_panel') ? ruta_panel('administrador') : null,
        ], JSON_THROW_ON_ERROR);
    });

    require $raiz . '/' . $caso['archivo'];
    exit;
}

require $raiz . '/includes/participantes.php';

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
    comprobar($codigo === 0 && $errores === '', 'La petición produjo un error PHP: ' . $caso['archivo']);

    return json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
}

$sesiones = sys_get_temp_dir() . '/lgi-participantes-' . bin2hex(random_bytes(8));
mkdir($sesiones, 0700);

try {
    $valido = [
        'nombre' => 'Ana', 'apellido' => 'Pérez',
        'email' => 'ana@example.com', 'telefono' => '11 5555-1234',
    ];
    comprobar(validar_participante($valido) === '', 'Se rechazó un participante válido.');
    comprobar(validar_participante(array_replace($valido, ['nombre' => str_repeat('á', 100)])) === '', 'El límite debe contar caracteres, no bytes.');
    comprobar(validar_participante(array_replace($valido, ['email' => ''])) === '', 'El correo debe ser opcional.');
    comprobar(validar_participante(array_replace($valido, ['telefono' => ''])) === '', 'El teléfono debe ser opcional.');

    foreach ([
        ['nombre' => ''], ['apellido' => ''], ['nombre' => '   '],
        ['nombre' => str_repeat('á', 101)], ['apellido' => str_repeat('á', 101)],
        ['email' => 'no-es-correo'], ['email' => str_repeat('a', 150) . '@example.com'],
        ['telefono' => str_repeat('1', 51)],
    ] as $cambio) {
        comprobar(validar_participante(array_replace($valido, $cambio)) !== '', 'Se aceptaron datos inválidos.');
    }
    comprobar(datos_participante(['nombre' => ['valor']])['nombre'] === '', 'Una entrada de tipo array no debe causar errores.');

    foreach (['participantes/index.php', 'participantes/crear.php', 'participantes/editar.php', 'participantes/eliminar.php'] as $archivo) {
        $respuesta = peticion(['archivo' => $archivo], $sesiones);
        comprobar($respuesta['salida'] === '', 'El acceso sin sesión no se interrumpió: ' . $archivo);
        comprobar($respuesta['inicio'] === '/integrador/index.php', 'La ruta de login debe apuntar a la raíz.');
        comprobar($respuesta['cookie'] === '/integrador/', 'La sesión debe compartirse con las subcarpetas.');
    }

    foreach (['administrador', 'empleado'] as $rol) {
        $respuesta = peticion(['archivo' => 'participantes/crear.php', 'rol' => $rol], $sesiones);
        comprobar($respuesta['estado'] === 200, 'Ambos roles deben poder registrar participantes.');
        comprobar(str_contains($respuesta['salida'], 'Nuevo participante'), 'No se renderizó el formulario de participantes.');
        comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario de participantes.');
    }

    $respuesta = peticion(['archivo' => 'participantes/eliminar.php', 'rol' => 'empleado'], $sesiones);
    comprobar($respuesta['estado'] === 403, 'El empleado pudo acceder a la eliminación de participantes.');

    foreach (['participantes/crear.php', 'participantes/editar.php'] as $archivo) {
        $respuesta = peticion([
            'archivo' => $archivo, 'rol' => 'empleado', 'metodo' => 'POST',
            'get' => ['id' => '1'], 'post' => $valido + ['id' => '1', 'csrf' => 'incorrecto'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 403, 'Se aceptó una acción sin token válido en ' . $archivo);
    }

    $respuesta = peticion([
        'archivo' => 'participantes/eliminar.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'post' => ['id' => '1', 'csrf' => 'incorrecto'],
    ], $sesiones);
    comprobar($respuesta['estado'] === 403, 'Se aceptó una eliminación sin token válido.');

    $respuesta = peticion(['archivo' => 'participantes/eliminar.php', 'rol' => 'administrador'], $sesiones);
    comprobar($respuesta['estado'] === 405, 'La eliminación no debe aceptar GET.');

    foreach (['', '0', '-1', 'texto', ['1']] as $id) {
        $respuesta = peticion(['archivo' => 'participantes/editar.php', 'rol' => 'empleado', 'get' => ['id' => $id]], $sesiones);
        comprobar($respuesta['estado'] === 400, 'Se aceptó un identificador inválido al editar.');
        $respuesta = peticion([
            'archivo' => 'participantes/eliminar.php', 'rol' => 'administrador', 'metodo' => 'POST',
            'post' => ['id' => $id, 'csrf' => 'token-de-prueba'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 400, 'Se aceptó un identificador inválido al eliminar.');
    }

    $respuesta = peticion([
        'archivo' => 'participantes/crear.php', 'rol' => 'empleado', 'metodo' => 'POST',
        'post' => array_replace($valido, [
            'nombre' => '<script>alert(1)</script>', 'apellido' => '', 'csrf' => 'token-de-prueba',
        ]),
    ], $sesiones);
    comprobar(str_contains($respuesta['salida'], 'Completá el nombre y el apellido'), 'No se mostró el error de validación.');
    comprobar(!str_contains($respuesta['salida'], '<script>alert(1)</script>'), 'El formulario no escapó el HTML.');
    comprobar(str_contains($respuesta['salida'], '&lt;script&gt;'), 'El formulario debe conservar los datos escapados.');

    foreach (['', '/integrador'] as $base) {
        foreach (['admin.php', 'empleado.php', 'participantes/crear.php'] as $archivo) {
            $respuesta = peticion([
                'archivo' => $archivo, 'rol' => $archivo === 'empleado.php' ? 'empleado' : 'administrador', 'base' => $base,
            ], $sesiones);
            comprobar($respuesta['cookie'] === $base . '/', 'La cookie debe funcionar en la raíz y en subcarpetas.');
            comprobar($respuesta['panel'] === $base . '/admin.php', 'La ruta del panel es incorrecta.');
            comprobar(str_contains($respuesta['salida'], '<main class="panel'), 'No se renderizó el panel o formulario.');
            comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario.');
        }
    }

    echo "OK: validación de participantes, permisos, CSRF, eliminación por POST, escape HTML y sesiones en subcarpetas.\n";
} finally {
    foreach (glob($sesiones . '/*') as $archivo) {
        unlink($archivo);
    }
    rmdir($sesiones);
}
