<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require __DIR__ . '/includes/solicitudes.php';

$filtro_disponible = isset($_GET['disponible']) || isset($_GET['disponibles'])
    || (($_GET['filtro'] ?? '') === 'disponible')
    || (($_GET['filtro'] ?? '') === 'disponibles')
    || (($_GET['estado'] ?? '') === 'disponible')
    || (($_GET['disponibilidad'] ?? '') === '1');

$error = '';
$eventos = [];

try {
    $consulta = conectar_db()->query(
        "SELECT e.id_evento, e.titulo, e.lugar, e.fecha_evento, e.fecha_limite_inscripcion, e.cupo_maximo,
                e.cupo_maximo - (
                    SELECT COUNT(*) FROM inscripciones i
                    WHERE i.id_evento = e.id_evento AND i.estado = 'activa'
                ) AS cupos_disponibles
         FROM eventos e
         ORDER BY e.fecha_evento, e.id_evento"
    );
    $eventos = $consulta->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') {
        error_log('Error al consultar el catálogo de eventos: ' . $exception->getMessage());
    }
    $error = 'No pudimos consultar los eventos. Intentá nuevamente más tarde.';
}

$ahora = time();
foreach ($eventos as &$evento) {
    $evento['estado'] = estado_evento($evento, $ahora);
}
unset($evento);

if ($filtro_disponible) {
    $eventos = array_values(array_filter($eventos, static function (array $evento): bool {
        return $evento['estado'] === 'disponible';
    }));
}

$usuario = usuario_actual();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de eventos | Eventos y cursos</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="panel panel-catalogo">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <?php if ($usuario !== null): ?>
                <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver al panel</a>
                <?php if ($usuario['rol'] === 'usuario'): ?>
                    <a href="<?= escapar(ruta_aplicacion('inscripciones/mis-inscripciones.php')) ?>">Mis inscripciones</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= escapar(ruta_aplicacion('index.php')) ?>">Iniciar sesión</a>
            <?php endif; ?>
        </header>
        <h1>Catálogo de eventos</h1>
        <p class="descripcion">Consultá los eventos disponibles y solicitá tu cupo.</p>

        <form method="get" action="catalogo.php" class="filtros-catalogo">
            <label>
                <input type="checkbox" name="disponible" value="1" <?= $filtro_disponible ? 'checked' : '' ?>>
                Solo disponibles
            </label>
            <button type="submit">Filtrar</button>
        </form>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php elseif ($eventos === []): ?>
            <p class="descripcion">Todavía no hay eventos disponibles.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Lugar</th>
                            <th>Plazo</th>
                            <th>Estado</th>
                            <th>Solicitud</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?= escapar($evento['titulo']) ?></td>
                                <td><?= escapar($evento['fecha_evento']) ?></td>
                                <td><?= escapar($evento['lugar']) ?></td>
                                <td><?= escapar($evento['fecha_limite_inscripcion']) ?></td>
                                <td><?= escapar($evento['estado']) ?></td>
                                <td>
                                    <?php if ($evento['estado'] !== 'disponible'): ?>
                                        <span class="nota">No disponible</span>
                                    <?php elseif ($usuario === null): ?>
                                        <a href="<?= escapar(ruta_aplicacion('index.php')) ?>">Iniciá sesión para pedir cupo</a>
                                    <?php elseif ($usuario['rol'] === 'usuario'): ?>
                                        <a class="boton" href="<?= escapar(ruta_aplicacion('inscripciones/solicitar.php?id_evento=' . (int) $evento['id_evento'])) ?>">Solicitar cupo</a>
                                    <?php else: ?>
                                        <a href="<?= escapar(ruta_aplicacion('inscripciones/crear.php')) ?>">Inscribir desde el panel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
