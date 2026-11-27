<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/solicitudes.php';
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
            <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver a mi cuenta</a>
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
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Evento</th><th>Fecha</th><th>Lugar</th><th>Estado</th><th>Inscripto el</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td><?= escapar($inscripcion['titulo']) ?></td>
                                <td><?= escapar($inscripcion['fecha_evento']) ?></td>
                                <td><?= escapar($inscripcion['lugar']) ?></td>
                                <td>
                                    <span class="insignia insignia-<?= $inscripcion['estado'] === 'activa' ? 'disponible' : 'cerrado' ?>">
                                        <?= $inscripcion['estado'] === 'activa' ? 'Confirmada' : 'Cancelada' ?>
                                    </span>
                                </td>
                                <td><?= escapar($inscripcion['fecha_inscripcion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="nota"><a href="<?= escapar(ruta_aplicacion('catalogo.php')) ?>">Volver al catálogo de eventos</a></p>
    </main>
</body>
</html>
