<?php

// Ejecutar con php tests/eventos.php. No modifica la base de datos.
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

require $raiz . '/includes/eventos.php';

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

$sesiones = sys_get_temp_dir() . '/lgi-eventos-' . bin2hex(random_bytes(8));
mkdir($sesiones, 0700);

try {
    $valido = [
        'titulo' => 'Curso de programación', 'descripcion' => 'Descripción', 'lugar' => 'Aula 3',
        'fecha_evento' => '2026-12-01T18:00', 'fecha_limite_inscripcion' => '2026-11-25T23:59',
        'cupo_maximo' => '20',
    ];
    comprobar(validar_evento($valido) === '', 'Se rechazó un evento válido.');
    comprobar(validar_evento(array_replace($valido, ['titulo' => str_repeat('á', 150)])) === '', 'El límite debe contar caracteres, no bytes.');
    foreach ([
        ['titulo' => ''], ['titulo' => str_repeat('á', 151)], ['lugar' => ''],
        ['fecha_evento' => '2026-02-30T18:00'], ['fecha_evento' => "2026-12-01T18:00\0"],
        ['fecha_limite_inscripcion' => '2026-12-02T18:00'],
        ['cupo_maximo' => '0'], ['cupo_maximo' => '-1'], ['cupo_maximo' => '2.5'],
        ['cupo_maximo' => '2147483648'],
    ] as $cambio) {
        comprobar(validar_evento(array_replace($valido, $cambio)) !== '', 'Se aceptaron datos inválidos.');
    }
    comprobar(datos_evento(['titulo' => ['valor']])['titulo'] === '', 'Una entrada de tipo array no debe causar errores.');

    foreach (['eventos/index.php', 'eventos/crear.php', 'eventos/editar.php', 'eventos/eliminar.php'] as $archivo) {
        $respuesta = peticion(['archivo' => $archivo], $sesiones);
        comprobar($respuesta['salida'] === '', 'El acceso sin sesión no se interrumpió: ' . $archivo);
        comprobar($respuesta['inicio'] === '/integrador/index.php', 'La ruta de login debe apuntar a la raíz.');
        comprobar($respuesta['cookie'] === '/integrador/', 'La sesión debe compartirse con las subcarpetas.');
    }

    foreach (['eventos/crear.php', 'eventos/editar.php', 'eventos/eliminar.php'] as $archivo) {
        $respuesta = peticion(['archivo' => $archivo, 'rol' => 'empleado'], $sesiones);
        comprobar($respuesta['estado'] === 403, 'El empleado pudo acceder a una acción de administración.');

        $respuesta = peticion([
            'archivo' => $archivo, 'rol' => 'administrador', 'metodo' => 'POST',
            'get' => ['id' => '1'], 'post' => $valido + ['id' => '1', 'csrf' => 'incorrecto'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 403, 'Se aceptó una acción sin token válido.');
    }

    $respuesta = peticion(['archivo' => 'eventos/eliminar.php', 'rol' => 'administrador'], $sesiones);
    comprobar($respuesta['estado'] === 405, 'La eliminación no debe aceptar GET.');

    foreach (['', '0', '-1', 'texto', ['1']] as $id) {
        $respuesta = peticion(['archivo' => 'eventos/editar.php', 'rol' => 'administrador', 'get' => ['id' => $id]], $sesiones);
        comprobar($respuesta['estado'] === 400, 'Se aceptó un identificador inválido al editar.');
        $respuesta = peticion([
            'archivo' => 'eventos/eliminar.php', 'rol' => 'administrador', 'metodo' => 'POST',
            'post' => ['id' => $id, 'csrf' => 'token-de-prueba'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 400, 'Se aceptó un identificador inválido al eliminar.');
    }

    foreach (['', '/integrador'] as $base) {
        foreach (['admin.php', 'empleado.php', 'eventos/crear.php'] as $archivo) {
            $respuesta = peticion([
                'archivo' => $archivo, 'rol' => $archivo === 'empleado.php' ? 'empleado' : 'administrador', 'base' => $base,
            ], $sesiones);
            comprobar($respuesta['cookie'] === $base . '/', 'La cookie debe funcionar en la raíz y en subcarpetas.');
            comprobar($respuesta['panel'] === $base . '/admin.php', 'La ruta del panel es incorrecta.');
            comprobar(str_contains($respuesta['salida'], '<main class="panel'), 'No se renderizó el panel o formulario.');
            comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario.');
        }
    }

    $respuesta = peticion([
        'archivo' => 'eventos/crear.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'post' => array_replace($valido, ['titulo' => '<script>alert(1)</script>', 'cupo_maximo' => '0', 'csrf' => 'token-de-prueba']),
    ], $sesiones);
    comprobar(str_contains($respuesta['salida'], 'entero mayor a cero'), 'No se mostró el error de validación.');
    comprobar(!str_contains($respuesta['salida'], '<script>alert(1)</script>'), 'El formulario no escapó el HTML.');
    comprobar(str_contains($respuesta['salida'], '&lt;script&gt;'), 'El formulario debe conservar los datos escapados.');

    echo "OK: validación, permisos, CSRF, eliminación por POST, escape HTML y sesiones en subcarpetas.\n";
} finally {
    foreach (glob($sesiones . '/*') as $archivo) {
        unlink($archivo);
    }
    rmdir($sesiones);
}
