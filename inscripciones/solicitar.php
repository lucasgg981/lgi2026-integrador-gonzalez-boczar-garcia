<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/inscripciones.php';
require __DIR__ . '/../includes/solicitudes.php';
$usuario = requerir_rol('usuario');

$id_participante = filter_var($usuario['id_participante'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($id_participante === false) {
    http_response_code(403);
    exit('Tu cuenta no está vinculada a un participante. Pedile al personal que revise tu registro.');
}

$id_evento = filter_var($_POST['id_evento'] ?? $_GET['id_evento'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($id_evento === false) {
    http_response_code(400);
    exit('El evento solicitado no es válido.');
}

$error = '';
$evento = null;
$inscripcion = null;

try {
    $conexion = conectar_db();
    $evento = evento_para_solicitud($conexion, $id_evento);

    if ($evento === null) {
        http_response_code(404);
        exit('El evento solicitado no existe.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valido()) {
            http_response_code(403);
            $error = 'La sesión del formulario venció. Volvé a intentarlo.';
        } else {
            $resultado = registrar_inscripcion(
                $conexion,
                $id_participante,
                $id_evento,
                (int) $usuario['id_usuario']
            );

            if ($resultado === '') {
                $mensaje = 'Tu solicitud quedó confirmada para "' . $evento['titulo'] . '".';
                header('Location: mis-inscripciones.php?mensaje=' . urlencode($mensaje), true, 303);
                exit;
            }

            $error = mensaje_solicitud($resultado);
        }

        // El intento pudo consumir el último cupo: volvemos a leer el evento.
        $evento = evento_para_solicitud($conexion, $id_evento) ?? $evento;
    }

    $inscripcion = inscripcion_de_participante_en_evento($conexion, $id_participante, $id_evento);
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') { error_log('Error al procesar la solicitud de cupo: ' . $exception->getMessage()); }
    http_response_code(503);
    exit('No pudimos procesar tu solicitud. Intentá nuevamente más tarde.');
}

$estado = estado_evento($evento);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitar cupo | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-solicitud">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="mis-inscripciones.php">Mis inscripciones</a>
        </header>
        <h1>Solicitar cupo</h1>
        <p class="descripcion">Revisá los datos del evento antes de confirmar tu solicitud.</p>

        <article class="detalle-evento">
            <h2><?= escapar($evento['titulo']) ?></h2>
            <dl class="datos-evento">
                <div>
                    <dt>Fecha del evento</dt>
                    <dd><?= escapar($evento['fecha_evento']) ?></dd>
                </div>
                <div>
                    <dt>Lugar</dt>
                    <dd><?= escapar($evento['lugar']) ?></dd>
                </div>
                <div>
                    <dt>Límite de inscripción</dt>
                    <dd><?= escapar($evento['fecha_limite_inscripcion']) ?></dd>
                </div>
                <div>
                    <dt>Cupos disponibles</dt>
                    <dd><?= max(0, (int) $evento['cupos_disponibles']) ?> de <?= (int) $evento['cupo_maximo'] ?></dd>
                </div>
                <div>
                    <dt>Estado</dt>
                    <dd><span class="insignia insignia-<?= escapar($estado) ?>"><?= escapar($estado) ?></span></dd>
                </div>
            </dl>
        </article>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>

        <?php if ($inscripcion !== null): ?>
            <?php if ($inscripcion['estado'] === 'activa'): ?>
                <p class="aviso" role="status">Ya tenés una plaza confirmada en este evento.</p>
                <p><a class="boton" href="mis-inscripciones.php">Ver mis inscripciones</a></p>
            <?php else: ?>
                <p class="aviso" role="status">Tu inscripción a este evento está cancelada. Consultá con el personal si necesitás volver a inscribirte.</p>
            <?php endif; ?>
        <?php elseif ($estado === 'disponible'): ?>
            <form class="formulario-solicitud" method="post" action="solicitar.php">
                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                <input type="hidden" name="id_evento" value="<?= (int) $evento['id_evento'] ?>">
                <p class="nota">Al confirmar, tu plaza queda reservada y descuenta un cupo del evento.</p>
                <button type="submit">Confirmar solicitud</button>
            </form>
        <?php elseif ($estado === 'completo'): ?>
            <p class="aviso" role="status">Ese evento ya no tiene cupos disponibles.</p>
        <?php else: ?>
            <p class="aviso" role="status">El plazo para inscribirte a ese evento ya cerró.</p>
        <?php endif; ?>

        <p class="nota"><a href="<?= escapar(ruta_aplicacion('catalogo.php')) ?>">Volver al catálogo de eventos</a></p>
    </main>
</body>
</html>
