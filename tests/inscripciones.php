<?php

// Ejecutar con php tests/inscripciones.php. Requiere MySQL y CREATE TEMPORARY TABLES; no modifica registros reales.
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
        ], JSON_THROW_ON_ERROR);
    });

    require $raiz . '/' . $caso['archivo'];
    exit;
}

if (($argv[1] ?? '') === '--cupo') {
    $caso = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    require $raiz . '/includes/database.php';
    require $raiz . '/includes/inscripciones.php';

    $limite = microtime(true) + 5;
    while (!is_file($caso['senal']) && microtime(true) < $limite) {
        usleep(5000);
    }

    try {
        $resultado = registrar_inscripcion(
            conectar_db(),
            (int) $caso['id_participante'],
            (int) $caso['id_evento'],
            (int) $caso['id_usuario']
        );
    } catch (Throwable $exception) {
        $resultado = 'EXCEPCION: ' . $exception->getMessage();
    }

    echo json_encode(['resultado' => $resultado], JSON_THROW_ON_ERROR);
    exit;
}

require $raiz . '/includes/database.php';
require $raiz . '/includes/inscripciones.php';
require $raiz . '/includes/solicitudes.php';
require $raiz . '/includes/formato.php';

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

function prueba_ultimo_cupo(string $directorio): void
{
    $conexion = conectar_db();
    $sufijo = bin2hex(random_bytes(4));
    $id_evento = null;
    $ids_participantes = [];
    $id_usuario = null;
    $senal = $directorio . '/senal-' . $sufijo;

    try {
        $insertar = $conexion->prepare(
            'INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insertar->execute([
            'Prueba último cupo ' . $sufijo,
            'Evento temporal de la prueba de concurrencia.',
            'Aula de prueba',
            date('Y-m-d H:i:s', strtotime('+10 days')),
            date('Y-m-d H:i:s', strtotime('+5 days')),
            1,
        ]);
        $id_evento = (int) $conexion->lastInsertId();

        $insertar = $conexion->prepare('INSERT INTO participantes (nombre, apellido, email) VALUES (?, ?, ?)');
        foreach (['Uno', 'Dos'] as $indice => $nombre) {
            $insertar->execute([$nombre, 'Prueba', 'cupo-' . $sufijo . '-' . $indice . '@eventos.local']);
            $ids_participantes[] = (int) $conexion->lastInsertId();
        }

        $insertar = $conexion->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, clave_hash, rol, activo, version_sesion)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insertar->execute([
            'Operador', 'Prueba', 'cupo-' . $sufijo . '@eventos.local',
            password_hash('Prueba12345!', PASSWORD_BCRYPT, ['cost' => 4]), 'empleado', 1, 1,
        ]);
        $id_usuario = (int) $conexion->lastInsertId();

        $procesos = [];
        foreach ($ids_participantes as $id_participante) {
            $caso = base64_encode(json_encode([
                'id_participante' => $id_participante,
                'id_evento' => $id_evento,
                'id_usuario' => $id_usuario,
                'senal' => $senal,
            ], JSON_THROW_ON_ERROR));
            $proceso = proc_open(
                [PHP_BINARY, __FILE__, '--cupo', $caso],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            comprobar(is_resource($proceso), 'No se pudo iniciar la prueba de concurrencia.');
            fclose($pipes[0]);
            $procesos[] = [$proceso, $pipes];
        }

        // Las dos peticiones esperan la misma señal para competir por el cupo.
        touch($senal);

        $resultados = [];
        foreach ($procesos as [$proceso, $pipes]) {
            $salida = stream_get_contents($pipes[1]);
            $errores = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $codigo = proc_close($proceso);
            comprobar($codigo === 0 && $errores === '', 'La prueba de concurrencia produjo un error PHP: ' . $errores);
            $resultados[] = json_decode($salida, true, 512, JSON_THROW_ON_ERROR)['resultado'];
        }

        $confirmadas = array_filter($resultados, static fn (string $resultado): bool => $resultado === '');
        comprobar(count($confirmadas) === 1, 'Con un solo cupo, exactamente una solicitud debe confirmarse.');
        comprobar(
            in_array('No hay cupos disponibles para ese evento.', $resultados, true),
            'La solicitud que pierde el último cupo debe recibir el mensaje de cupo agotado.'
        );

        $consulta = $conexion->prepare("SELECT COUNT(*) FROM inscripciones WHERE id_evento = :evento AND estado = 'activa'");
        $consulta->execute(['evento' => $id_evento]);
        comprobar(
            (int) $consulta->fetchColumn() === 1,
            'La base debe conservar una única inscripción activa para el último cupo.'
        );
    } finally {
        if ($id_evento !== null) {
            $conexion->prepare('DELETE FROM inscripciones WHERE id_evento = :evento')->execute(['evento' => $id_evento]);
            $conexion->prepare('DELETE FROM eventos WHERE id_evento = :evento')->execute(['evento' => $id_evento]);
        }
        foreach ($ids_participantes as $id_participante) {
            $conexion->prepare('DELETE FROM participantes WHERE id_participante = :id')->execute(['id' => $id_participante]);
        }
        if ($id_usuario !== null) {
            $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = :id')->execute(['id' => $id_usuario]);
        }
        if (is_file($senal)) {
            unlink($senal);
        }
    }
}

function prueba_solicitud_exitosa(string $sesiones): void
{
    $conexion = conectar_db();
    // La clave foránea de inscripciones apunta a la tabla real de usuarios.
    if ((int) $conexion->query('SELECT COUNT(*) FROM usuarios WHERE id_usuario = 1')->fetchColumn() === 0) {
        return;
    }

    $sufijo = bin2hex(random_bytes(4));
    $id_evento = null;
    $id_participante = null;

    try {
        $insertar = $conexion->prepare(
            'INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insertar->execute([
            'Prueba solicitud ' . $sufijo,
            'Evento temporal de la prueba de solicitud.',
            'Aula de prueba',
            date('Y-m-d H:i:s', strtotime('+10 days')),
            date('Y-m-d H:i:s', strtotime('+5 days')),
            2,
        ]);
        $id_evento = (int) $conexion->lastInsertId();

        $conexion->prepare('INSERT INTO participantes (nombre, apellido, email) VALUES (?, ?, ?)')
            ->execute(['Solicitud', 'Prueba', 'solicitud-' . $sufijo . '@eventos.local']);
        $id_participante = (int) $conexion->lastInsertId();

        $respuesta = peticion([
            'archivo' => 'inscripciones/solicitar.php',
            'rol' => 'usuario',
            'id_participante' => $id_participante,
            'get' => ['id_evento' => (string) $id_evento],
        ], $sesiones);
        comprobar($respuesta['estado'] === 200, 'La solicitud debe mostrar la confirmación antes de enviarla.');
        comprobar(str_contains($respuesta['salida'], 'Confirmar solicitud'), 'Falta el botón de confirmación de la solicitud.');
        comprobar(str_contains($respuesta['salida'], 'Disponible'), 'Falta el estado comprensible del evento en la solicitud.');
        comprobar(
            str_contains($respuesta['salida'], formatear_fecha(date('Y-m-d H:i:s', strtotime('+10 days')))),
            'La fecha del evento debe estar formateada en la solicitud.'
        );

        $respuesta = peticion([
            'archivo' => 'inscripciones/solicitar.php',
            'rol' => 'usuario',
            'id_participante' => $id_participante,
            'metodo' => 'POST',
            'post' => ['id_evento' => (string) $id_evento, 'csrf' => 'token-de-prueba'],
        ], $sesiones);

        comprobar($respuesta['estado'] === 303, 'Una solicitud válida debe confirmarse y redirigir a mis inscripciones.');
        comprobar($respuesta['salida'] === '', 'La confirmación no debe volver a mostrar el formulario.');

        $consulta = $conexion->prepare(
            "SELECT COUNT(*) FROM inscripciones
             WHERE id_participante = :participante AND id_evento = :evento
               AND id_usuario = 1 AND estado = 'activa'"
        );
        $consulta->execute(['participante' => $id_participante, 'evento' => $id_evento]);
        comprobar(
            (int) $consulta->fetchColumn() === 1,
            'La autoinscripción debe guardar la cuenta pública que la realizó.'
        );

        $respuesta = peticion([
            'archivo' => 'inscripciones/mis-inscripciones.php',
            'rol' => 'usuario',
            'id_participante' => $id_participante,
        ], $sesiones);
        comprobar($respuesta['estado'] === 200, 'La consulta propia debe renderizarse después de confirmar.');
        comprobar(str_contains($respuesta['salida'], 'Prueba solicitud ' . $sufijo), 'Falta el evento recién inscripto en la consulta propia.');
        comprobar(str_contains($respuesta['salida'], 'Confirmada'), 'La inscripción debe mostrarse como confirmada.');
        comprobar(
            str_contains($respuesta['salida'], formatear_fecha(date('Y-m-d H:i:s', strtotime('+10 days')))),
            'La fecha debe estar formateada en la consulta propia.'
        );
    } finally {
        if ($id_participante !== null && $id_evento !== null) {
            $conexion->prepare('DELETE FROM inscripciones WHERE id_participante = :participante AND id_evento = :evento')
                ->execute(['participante' => $id_participante, 'evento' => $id_evento]);
        }
        if ($id_evento !== null) {
            $conexion->prepare('DELETE FROM eventos WHERE id_evento = :evento')->execute(['evento' => $id_evento]);
        }
        if ($id_participante !== null) {
            $conexion->prepare('DELETE FROM participantes WHERE id_participante = :id')->execute(['id' => $id_participante]);
        }
    }
}

$sesiones = sys_get_temp_dir() . '/lgi-inscripciones-' . bin2hex(random_bytes(8));
mkdir($sesiones, 0700);

try {
    $valido = [
        'id_participante' => '1',
        'id_evento' => '2',
    ];
    comprobar(validar_inscripcion($valido) === '', 'Se rechazó una inscripción válida.');
    foreach ([
        ['id_participante' => ''], ['id_evento' => ''],
        ['id_participante' => '0'], ['id_evento' => '-1'],
        ['id_participante' => 'texto'], ['id_evento' => ['1']],
    ] as $cambio) {
        comprobar(validar_inscripcion(array_replace($valido, $cambio)) !== '', 'Se aceptaron datos inválidos.');
    }
    comprobar(datos_inscripcion(['id_participante' => ['valor']])['id_participante'] === '', 'Una entrada de tipo array no debe causar errores.');

    $ahora = time();
    $evento_base = [
        'cupos_disponibles' => 2,
        'fecha_limite_inscripcion' => date('Y-m-d H:i:s', $ahora + 3600),
    ];
    comprobar(estado_evento($evento_base, $ahora) === 'disponible', 'Un evento con cupos y plazo abierto debe estar disponible.');
    comprobar(
        estado_evento(['cupos_disponibles' => 0] + $evento_base, $ahora) === 'completo',
        'Un evento sin cupos debe estar completo.'
    );
    comprobar(
        estado_evento(['fecha_limite_inscripcion' => date('Y-m-d H:i:s', $ahora - 60)] + $evento_base, $ahora) === 'cerrado',
        'Un evento con plazo vencido debe estar cerrado.'
    );
    comprobar(
        mensaje_solicitud('No hay cupos disponibles para ese evento.') === 'Ese evento ya no tiene cupos disponibles.',
        'El mensaje de cupo agotado debe ser claro para el usuario.'
    );
    comprobar(
        !str_contains(mensaje_solicitud('detalle técnico interno'), 'detalle técnico interno'),
        'El mensaje público no debe exponer el detalle técnico.'
    );

    comprobar(formatear_fecha('2026-12-01 10:30:00') === '01/12/2026 10:30', 'La fecha debe mostrarse en formato día/mes/año hora:minuto.');
    comprobar(formatear_fecha('fecha inválida') === 'fecha inválida', 'Una fecha inválida debe conservarse sin cambios.');
    comprobar(etiqueta_estado_evento('completo') === 'Sin cupos', 'El estado de evento completo debe ser comprensible.');
    comprobar(etiqueta_estado_evento('cerrado') === 'Plazo cerrado', 'El estado de evento cerrado debe ser comprensible.');
    comprobar(etiqueta_estado_inscripcion('activa') === 'Confirmada', 'La inscripción activa debe mostrarse como confirmada.');
    comprobar(etiqueta_estado_inscripcion('cancelada') === 'Cancelada', 'La inscripción cancelada debe mostrarse como cancelada.');

    foreach (['inscripciones/index.php', 'inscripciones/crear.php', 'inscripciones/cancelar.php', 'inscripciones/eliminar.php'] as $archivo) {
        $respuesta = peticion(['archivo' => $archivo], $sesiones);
        comprobar($respuesta['salida'] === '', 'El acceso sin sesión no se interrumpió: ' . $archivo);
        comprobar($respuesta['inicio'] === '/integrador/index.php', 'La ruta de login debe apuntar a la raíz.');
        comprobar($respuesta['cookie'] === '/integrador/', 'La sesión debe compartirse con las subcarpetas.');
    }

    foreach (['administrador', 'empleado'] as $rol) {
        $respuesta = peticion(['archivo' => 'inscripciones/crear.php', 'rol' => $rol], $sesiones);
        comprobar($respuesta['estado'] === 200, 'Ambos roles deben poder registrar inscripciones.');
        comprobar(str_contains($respuesta['salida'], 'Nueva inscripción'), 'No se renderizó el formulario de inscripciones.');
        comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario de inscripciones.');
    }

    $respuesta = peticion(['archivo' => 'inscripciones/cancelar.php', 'rol' => 'empleado'], $sesiones);
    comprobar($respuesta['estado'] === 403, 'El empleado pudo acceder a la cancelación de inscripciones.');

    foreach (['inscripciones/crear.php'] as $archivo) {
        $respuesta = peticion([
            'archivo' => $archivo, 'rol' => 'empleado', 'metodo' => 'POST',
            'get' => ['id' => '1'], 'post' => $valido + ['id' => '1', 'csrf' => 'incorrecto'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 403, 'Se aceptó una acción sin token válido en ' . $archivo);
    }

    $respuesta = peticion([
        'archivo' => 'inscripciones/cancelar.php', 'rol' => 'administrador', 'metodo' => 'POST',
        'post' => ['id' => '1', 'csrf' => 'incorrecto'],
    ], $sesiones);
    comprobar($respuesta['estado'] === 403, 'Se aceptó una cancelación sin token válido.');

    $respuesta = peticion(['archivo' => 'inscripciones/cancelar.php', 'rol' => 'administrador'], $sesiones);
    comprobar($respuesta['estado'] === 405, 'La cancelación no debe aceptar GET.');

    foreach (['', '0', '-1', 'texto', ['1']] as $id) {
        $respuesta = peticion([
            'archivo' => 'inscripciones/cancelar.php', 'rol' => 'administrador', 'metodo' => 'POST',
            'post' => ['id' => $id, 'csrf' => 'token-de-prueba'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 400, 'Se aceptó un identificador inválido al cancelar.');
    }

    foreach (['inscripciones/solicitar.php', 'inscripciones/mis-inscripciones.php'] as $archivo) {
        $respuesta = peticion(['archivo' => $archivo], $sesiones);
        comprobar($respuesta['salida'] === '', 'El acceso sin sesión no se interrumpió: ' . $archivo);
    }

    foreach (['administrador', 'empleado'] as $rol) {
        $respuesta = peticion([
            'archivo' => 'inscripciones/solicitar.php', 'rol' => $rol, 'get' => ['id_evento' => '1'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 403, 'El personal no debe usar la solicitud pública de cupos.');
    }

    $respuesta = peticion(['archivo' => 'inscripciones/solicitar.php', 'rol' => 'usuario', 'id_participante' => 1], $sesiones);
    comprobar($respuesta['estado'] === 400, 'Falta rechazar una solicitud sin evento.');

    $respuesta = peticion([
        'archivo' => 'inscripciones/solicitar.php', 'rol' => 'usuario', 'id_participante' => 1,
        'get' => ['id_evento' => 'texto'],
    ], $sesiones);
    comprobar($respuesta['estado'] === 400, 'Falta rechazar un evento inválido.');

    $respuesta = peticion([
        'archivo' => 'inscripciones/solicitar.php', 'rol' => 'usuario', 'id_participante' => 1,
        'get' => ['id_evento' => '999999999'],
    ], $sesiones);
    comprobar($respuesta['estado'] === 404, 'Falta informar que el evento no existe.');

    $respuesta = peticion([
        'archivo' => 'inscripciones/mis-inscripciones.php', 'rol' => 'usuario', 'id_participante' => 999999,
    ], $sesiones);
    comprobar($respuesta['estado'] === 200, 'El usuario debe poder consultar sus inscripciones.');
    comprobar(str_contains($respuesta['salida'], 'Mis inscripciones'), 'No se renderizó la consulta de inscripciones propias.');

    $respuesta = peticion(['archivo' => 'inscripciones/mis-inscripciones.php', 'rol' => 'administrador'], $sesiones);
    comprobar($respuesta['estado'] === 403, 'El personal no debe entrar a la consulta pública de inscripciones.');

    $id_evento_prueba = (int) conectar_db()->query('SELECT COALESCE(MIN(id_evento), 0) FROM eventos')->fetchColumn();
    if ($id_evento_prueba > 0) {
        $respuesta = peticion([
            'archivo' => 'inscripciones/solicitar.php', 'rol' => 'usuario', 'id_participante' => 1, 'metodo' => 'POST',
            'post' => ['id_evento' => (string) $id_evento_prueba, 'csrf' => 'incorrecto'],
        ], $sesiones);
        comprobar($respuesta['estado'] === 403, 'Se aceptó una solicitud sin token válido.');
    }

    prueba_ultimo_cupo($sesiones);
    prueba_solicitud_exitosa($sesiones);

    $respuesta = peticion([
        'archivo' => 'inscripciones/crear.php', 'rol' => 'empleado', 'metodo' => 'POST',
        'post' => array_replace($valido, ['id_participante' => '', 'id_evento' => '', 'csrf' => 'token-de-prueba']),
    ], $sesiones);
    comprobar(str_contains($respuesta['salida'], 'Seleccioná un participante y un evento válidos'), 'No se mostró el error de validación.');
    comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario de inscripciones.');

    foreach (['', '/integrador'] as $base) {
        foreach (['admin.php', 'empleado.php', 'inscripciones/crear.php'] as $archivo) {
            $respuesta = peticion([
                'archivo' => $archivo, 'rol' => $archivo === 'empleado.php' ? 'empleado' : 'administrador', 'base' => $base,
            ], $sesiones);
            comprobar($respuesta['cookie'] === $base . '/', 'La cookie debe funcionar en la raíz y en subcarpetas.');
            comprobar($respuesta['panel'] === $base . '/admin.php', 'La ruta del panel es incorrecta.');
            comprobar(str_contains($respuesta['salida'], '<main class="panel'), 'No se renderizó el panel o formulario.');
            comprobar(str_contains($respuesta['salida'], 'name="csrf"'), 'Falta el token del formulario.');
        }
    }

    echo "OK: validación de inscripciones, permisos, CSRF, cancelación por POST, solicitudes propias, último cupo concurrente, escape HTML y sesiones en subcarpetas.\n";
} finally {
    foreach (glob($sesiones . '/*') as $archivo) {
        unlink($archivo);
    }
    rmdir($sesiones);
}
