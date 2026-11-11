<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
$usuario = requerir_sesion();

$error = '';
$participantes = [];
$busqueda = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$mensaje = is_string($_GET['mensaje'] ?? null) ? $_GET['mensaje'] : '';

try {
    $sql = "SELECT p.*,
            (SELECT COUNT(*) FROM inscripciones i
             WHERE i.id_participante = p.id_participante) AS total_inscripciones,
            (SELECT COUNT(*) FROM inscripciones i
             WHERE i.id_participante = p.id_participante AND i.estado = 'activa') AS inscripciones_activas
        FROM participantes p";
    $parametros = [];

    if ($busqueda !== '') {
        $patron = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
        $sql .= ' WHERE p.nombre LIKE :nombre OR p.apellido LIKE :apellido
            OR p.email LIKE :email OR p.telefono LIKE :telefono';
        $parametros = [
            'nombre' => $patron,
            'apellido' => $patron,
            'email' => $patron,
            'telefono' => $patron,
        ];
    }

    $sql .= ' ORDER BY p.apellido, p.nombre, p.id_participante';
    $consulta = conectar_db()->prepare($sql);
    $consulta->execute($parametros);
    $participantes = $consulta->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al consultar participantes: ' . $exception->getMessage());
    $error = 'No pudimos consultar los participantes. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Participantes | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel panel-participantes">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="<?= escapar(ruta_panel($usuario['rol'])) ?>">Volver al panel</a>
        </header>
        <h1>Participantes</h1>
        <p class="descripcion">Registrá y buscá a las personas que se inscriben a los eventos.</p>
        <p><a class="boton" href="crear.php">Nuevo participante</a></p>
        <?php if ($mensaje !== ''): ?>
            <p class="aviso" role="status"><?= escapar($mensaje) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>

        <form class="buscador" method="get" action="index.php">
            <label for="q">Buscar por nombre, apellido, correo o teléfono</label>
            <input id="q" name="q" type="search" maxlength="100" value="<?= escapar($busqueda) ?>">
            <button type="submit">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="index.php">Ver todos</a>
            <?php endif; ?>
        </form>

        <?php if ($error !== ''): ?>
            <p class="descripcion">No se pudo mostrar el listado.</p>
        <?php elseif ($participantes === []): ?>
            <p class="descripcion">
                <?= $busqueda !== '' ? 'No encontramos participantes con esa búsqueda.' : 'Todavía no hay participantes registrados.' ?>
            </p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Nombre</th><th>Apellido</th><th>Correo</th><th>Teléfono</th>
                            <th>Inscripciones activas</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participantes as $participante): ?>
                            <tr>
                                <td><?= (int) $participante['id_participante'] ?></td>
                                <td><?= escapar($participante['nombre']) ?></td>
                                <td><?= escapar($participante['apellido']) ?></td>
                                <td><?= escapar($participante['email'] ?? '') ?></td>
                                <td><?= escapar($participante['telefono'] ?? '') ?></td>
                                <td><?= (int) $participante['inscripciones_activas'] ?></td>
                                <td>
                                    <div class="acciones-participante">
                                        <a href="editar.php?id=<?= (int) $participante['id_participante'] ?>">Editar</a>
                                        <?php if ($usuario['rol'] === 'administrador' && (int) $participante['total_inscripciones'] === 0): ?>
                                            <form method="post" action="eliminar.php" onsubmit="return confirm('¿Seguro que querés eliminar este participante?');">
                                                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $participante['id_participante'] ?>">
                                                <button class="secundario" type="submit">Eliminar</button>
                                            </form>
                                        <?php elseif ($usuario['rol'] === 'administrador'): ?>
                                            <span class="nota">Tiene inscripciones</span>
                                        <?php endif; ?>
                                    </div>
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
