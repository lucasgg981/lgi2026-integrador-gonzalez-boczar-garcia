<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/solicitudes.php';
require __DIR__ . '/../includes/formato.php';
$usuario = requerir_rol('usuario');

$id_participante = filter_var($usuario['id_participante'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($id_participante === false) {
    http_response_code(403);
    exit('Tu cuenta no está vinculada a un participante. Pedile al personal que revise tu registro.');
}

$error = '';
$inscripciones = [];
$mensaje = is_string($_GET['mensaje'] ?? null) ? $_GET['mensaje'] : '';

try {
    $inscripciones = inscripciones_de_participante(conectar_db(), $id_participante);
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') { error_log('Error al consultar las inscripciones propias: ' . $exception->getMessage()); }
    $error = 'No pudimos consultar tus inscripciones. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis inscripciones | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-inscripciones">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <nav class="cabecera-enlaces">
                <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver a mi cuenta</a>
                <a href="<?= escapar(ruta_aplicacion('catalogo.php')) ?>">Catálogo</a>
            </nav>
        </header>
        <h1>Mis inscripciones</h1>
        <p class="descripcion">Acá ves únicamente las inscripciones de tu cuenta.</p>

        <?php if ($mensaje !== ''): ?>
            <p class="aviso" role="status"><?= escapar($mensaje) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php elseif ($inscripciones === []): ?>
            <p class="descripcion">Todavía no tenés inscripciones. <a href="<?= escapar(ruta_aplicacion('catalogo.php')) ?>">Mirá los eventos disponibles</a>.</p>
        <?php else: ?>
            <ul class="lista-inscripciones">
                <?php foreach ($inscripciones as $inscripcion): ?>
                    <li class="tarjeta-inscripcion">
                        <div class="tarjeta-cabecera">
                            <h2><?= escapar($inscripcion['titulo']) ?></h2>
                            <span class="insignia insignia-<?= $inscripcion['estado'] === 'activa' ? 'disponible' : 'cerrado' ?>">
                                <?= escapar(etiqueta_estado_inscripcion($inscripcion['estado'])) ?>
                            </span>
                        </div>
                        <dl class="datos-evento">
                            <div>
                                <dt>Fecha del evento</dt>
                                <dd><?= escapar(formatear_fecha((string) $inscripcion['fecha_evento'])) ?></dd>
                            </div>
                            <div>
                                <dt>Lugar</dt>
                                <dd><?= escapar($inscripcion['lugar']) ?></dd>
                            </div>
                            <div>
                                <dt>Inscripto el</dt>
                                <dd><?= escapar(formatear_fecha((string) $inscripcion['fecha_inscripcion'])) ?></dd>
                            </div>
                        </dl>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
