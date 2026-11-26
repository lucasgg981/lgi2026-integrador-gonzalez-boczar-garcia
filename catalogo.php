<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require __DIR__ . '/includes/solicitudes.php';
require __DIR__ . '/includes/formato.php';

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
            <nav class="cabecera-enlaces">
                <?php if ($usuario !== null): ?>
                    <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver al panel</a>
                    <?php if ($usuario['rol'] === 'usuario'): ?>
                        <a href="<?= escapar(ruta_aplicacion('inscripciones/mis-inscripciones.php')) ?>">Mis inscripciones</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= escapar(ruta_aplicacion('index.php?login=1')) ?>">Iniciar sesión</a>
                    <a href="<?= escapar(ruta_aplicacion('registro.php')) ?>">Registrarse</a>
                <?php endif; ?>
            </nav>
        </header>
        <h1>Catálogo de eventos</h1>
        <p class="descripcion">Consultá los eventos disponibles y solicitá tu cupo.</p>

        <nav class="filtros-catalogo" aria-label="Filtro de eventos">
            <a class="filtro <?= !$filtro_disponible ? 'filtro-activo' : '' ?>" href="<?= escapar(ruta_aplicacion('catalogo.php')) ?>">Todos los eventos</a>
            <a class="filtro <?= $filtro_disponible ? 'filtro-activo' : '' ?>" href="<?= escapar(ruta_aplicacion('catalogo.php?disponible=1')) ?>">Solo disponibles</a>
        </nav>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php elseif ($eventos === []): ?>
            <p class="descripcion">
                <?php if ($filtro_disponible): ?>
                    No hay eventos disponibles con ese filtro.
                <?php else: ?>
                    Todavía no hay eventos cargados.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <ul class="lista-eventos">
                <?php foreach ($eventos as $evento): ?>
                    <li class="tarjeta-evento">
                        <div class="tarjeta-cabecera">
                            <h2><?= escapar($evento['titulo']) ?></h2>
                            <span class="insignia insignia-<?= escapar($evento['estado']) ?>" aria-label="Estado: <?= escapar(etiqueta_estado_evento($evento['estado'])) ?>">
                                <?= escapar(etiqueta_estado_evento($evento['estado'])) ?>
                            </span>
                        </div>
                        <dl class="datos-evento">
                            <div>
                                <dt>Fecha</dt>
                                <dd><?= escapar(formatear_fecha((string) $evento['fecha_evento'])) ?></dd>
                            </div>
                            <div>
                                <dt>Lugar</dt>
                                <dd><?= escapar($evento['lugar']) ?></dd>
                            </div>
                            <div>
                                <dt>Límite para inscribirse</dt>
                                <dd><?= escapar(formatear_fecha((string) $evento['fecha_limite_inscripcion'])) ?></dd>
                            </div>
                            <div>
                                <dt>Cupos disponibles</dt>
                                <dd><?= max(0, (int) $evento['cupos_disponibles']) ?> de <?= (int) $evento['cupo_maximo'] ?></dd>
                            </div>
                        </dl>
                        <p class="acciones-evento">
                            <?php if ($evento['estado'] !== 'disponible'): ?>
                                <span class="nota">
                                    <?php if ($evento['estado'] === 'cerrado'): ?>
                                        El plazo para inscribirse ya cerró.
                                    <?php else: ?>
                                        Ese evento ya no tiene cupos disponibles.
                                    <?php endif; ?>
                                </span>
                            <?php elseif ($usuario === null): ?>
                                <a class="boton secundario" href="<?= escapar(ruta_aplicacion('index.php?login=1')) ?>">Iniciá sesión para pedir cupo</a>
                            <?php elseif ($usuario['rol'] === 'usuario'): ?>
                                <a class="boton" href="<?= escapar(ruta_aplicacion('inscripciones/solicitar.php?id_evento=' . (int) $evento['id_evento'])) ?>">Solicitar cupo</a>
                            <?php else: ?>
                                <a class="boton secundario" href="<?= escapar(ruta_aplicacion('inscripciones/crear.php')) ?>">Inscribir desde el panel</a>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
