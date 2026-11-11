<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/eventos.php';
$usuario = requerir_rol('administrador');

$error = '';
$evento = datos_evento([]);
$accion = 'crear.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evento = datos_evento($_POST);
    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } else {
        $error = validar_evento($evento);
        if ($error === '') {
            try {
                $consulta = conectar_db()->prepare(
                    'INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
                     VALUES (:titulo, :descripcion, :lugar, :fecha_evento, :fecha_limite_inscripcion, :cupo_maximo)'
                );
                $consulta->execute($evento);
                header('Location: index.php?mensaje=' . urlencode('Evento creado correctamente.'));
                exit;
            } catch (PDOException | RuntimeException $exception) {
                error_log('Error al crear evento: ' . $exception->getMessage());
                $error = 'No pudimos crear el evento. Intentá nuevamente más tarde.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo evento | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Nuevo evento</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php require __DIR__ . '/../includes/formulario_evento.php'; ?>
    </main>
</body>
</html>
