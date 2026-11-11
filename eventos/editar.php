<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/eventos.php';
$usuario = requerir_rol('administrador');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('El identificador del evento no es válido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
    http_response_code(403);
    exit('La sesión del formulario venció. Volvé al listado e intentá nuevamente.');
}

$error = '';
$evento = null;
$accion = 'editar.php?id=' . $id;

try {
    $conexion = conectar_db();
    $consulta = $conexion->prepare('SELECT * FROM eventos WHERE id_evento = :id');
    $consulta->execute(['id' => $id]);
    $evento = $consulta->fetch();
    if ($evento === false) {
        http_response_code(404);
        exit('El evento no existe.');
    }

    $evento['fecha_evento'] = (new DateTimeImmutable($evento['fecha_evento']))->format('Y-m-d\TH:i');
    $evento['fecha_limite_inscripcion'] = (new DateTimeImmutable($evento['fecha_limite_inscripcion']))->format('Y-m-d\TH:i');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $evento = datos_evento($_POST);
        $error = validar_evento($evento);
        if ($error === '') {
            $consulta = $conexion->prepare(
                'UPDATE eventos SET titulo = :titulo, descripcion = :descripcion, lugar = :lugar,
                 fecha_evento = :fecha_evento, fecha_limite_inscripcion = :fecha_limite_inscripcion,
                 cupo_maximo = :cupo_maximo WHERE id_evento = :id'
            );
            $consulta->execute($evento + ['id' => $id]);
            header('Location: index.php?mensaje=' . urlencode('Evento modificado correctamente.'));
            exit;
        }
    }
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al editar evento: ' . $exception->getMessage());
    $error = 'No pudimos cargar o guardar el evento. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar evento | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Editar evento</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php if (is_array($evento)): ?>
            <?php require __DIR__ . '/../includes/formulario_evento.php'; ?>
        <?php endif; ?>
    </main>
</body>
</html>
