<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
$usuario = requerir_sesion();

$error = '';
$inscripciones = [];
$mensaje = is_string($_GET['mensaje'] ?? null) ? $_GET['mensaje'] : '';

try {
    $consulta = conectar_db()->query(
        "SELECT i.id_inscripcion, i.fecha_inscripcion, i.estado,
                p.id_participante, p.nombre AS participante_nombre, p.apellido AS participante_apellido,
                e.id_evento, e.titulo AS evento_titulo, e.fecha_evento,
                u.nombre AS operador_nombre, u.apellido AS operador_apellido
         FROM inscripciones i
         JOIN participantes p ON p.id_participante = i.id_participante
         JOIN eventos e ON e.id_evento = i.id_evento
         JOIN usuarios u ON u.id_usuario = i.id_usuario
         ORDER BY i.fecha_inscripcion DESC, i.id_inscripcion DESC"
    );
    $inscripciones = $consulta->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') { error_log('Error al consultar inscripciones: ' . $exception->getMessage()); }
    $error = 'No pudimos consultar las inscripciones. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscripciones | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-inscripciones">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver al panel</a>
        </header>
        <h1>Inscripciones y cancelaciones</h1>
        <p class="descripcion">Inscribí participantes a los eventos y cancelá inscripciones cuando sea necesario.</p>
        <p><a class="boton" href="crear.php">Nueva inscripción</a></p>
        <?php if ($mensaje !== ''): ?>
            <p class="aviso" role="status"><?= escapar($mensaje) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="descripcion">No se pudo mostrar el listado.</p>
        <?php elseif ($inscripciones === []): ?>
            <p class="descripcion">Todavía no hay inscripciones registradas.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Participante</th><th>Evento</th><th>Fecha</th>
                            <th>Estado</th><th>Operador</th>
                            <?php if ($usuario['rol'] === 'administrador'): ?><th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td><?= (int) $inscripcion['id_inscripcion'] ?></td>
                                <td><?= escapar($inscripcion['participante_apellido'] . ', ' . $inscripcion['participante_nombre']) ?></td>
                                <td><?= escapar($inscripcion['evento_titulo']) ?></td>
                                <td><?= escapar($inscripcion['fecha_inscripcion']) ?></td>
                                <td><?= escapar($inscripcion['estado']) ?></td>
                                <td><?= escapar($inscripcion['operador_apellido'] . ', ' . $inscripcion['operador_nombre']) ?></td>
                                <?php if ($usuario['rol'] === 'administrador'): ?>
                                    <td>
                                        <?php if ($inscripcion['estado'] === 'activa'): ?>
                                            <form method="post" action="cancelar.php" onsubmit="return confirm('¿Seguro que querés cancelar esta inscripción?');">
                                                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $inscripcion['id_inscripcion'] ?>">
                                                <button class="secundario" type="submit">Cancelar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="nota">Cancelada</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>


