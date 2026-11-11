<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
$usuario = requerir_sesion();

$error = '';
$eventos = [];
$mensaje = is_string($_GET['mensaje'] ?? null) ? $_GET['mensaje'] : '';

try {
    $consulta = conectar_db()->query(
        "SELECT e.*, e.cupo_maximo - (
            SELECT COUNT(*) FROM inscripciones i
            WHERE i.id_evento = e.id_evento AND i.estado = 'activa'
        ) AS cupos_disponibles
        FROM eventos e ORDER BY e.fecha_evento, e.id_evento"
    );
    $eventos = $consulta->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al consultar eventos: ' . $exception->getMessage());
    $error = 'No pudimos consultar los eventos. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eventos y cupos | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-eventos">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver al panel</a>
        </header>
        <h1>Eventos y cupos</h1>
        <?php if ($usuario['rol'] === 'administrador'): ?>
            <p><a class="boton" href="crear.php">Nuevo evento</a></p>
        <?php endif; ?>
        <?php if ($mensaje !== ''): ?>
            <p class="aviso" role="status"><?= escapar($mensaje) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php elseif ($eventos === []): ?>
            <p class="descripcion">Todavía no hay eventos cargados.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Título</th><th>Lugar</th><th>Fecha del evento</th>
                            <th>Límite de inscripción</th><th>Cupo máximo</th><th>Cupos disponibles</th>
                            <?php if ($usuario['rol'] === 'administrador'): ?><th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?= (int) $evento['id_evento'] ?></td>
                                <td><?= escapar($evento['titulo']) ?></td>
                                <td><?= escapar($evento['lugar']) ?></td>
                                <td><?= escapar($evento['fecha_evento']) ?></td>
                                <td><?= escapar($evento['fecha_limite_inscripcion']) ?></td>
                                <td><?= (int) $evento['cupo_maximo'] ?></td>
                                <td><?= (int) $evento['cupos_disponibles'] ?></td>
                                <?php if ($usuario['rol'] === 'administrador'): ?>
                                    <td>
                                        <div class="acciones-evento">
                                            <a href="editar.php?id=<?= (int) $evento['id_evento'] ?>">Editar</a>
                                            <form method="post" action="eliminar.php" onsubmit="return confirm('¿Seguro que querés eliminar este evento?');">
                                                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $evento['id_evento'] ?>">
                                                <button class="secundario" type="submit">Eliminar</button>
                                            </form>
                                        </div>
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
