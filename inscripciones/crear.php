<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/inscripciones.php';
$usuario = requerir_sesion();

$error = '';
$inscripcion = datos_inscripcion([]);
$participantes = [];
$eventos = [];
$accion = 'crear.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscripcion = datos_inscripcion($_POST);
    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } else {
        $error = validar_inscripcion($inscripcion);
        if ($error === '') {
            try {
                $error = registrar_inscripcion(
                    conectar_db(),
                    (int) $inscripcion['id_participante'],
                    (int) $inscripcion['id_evento'],
                    (int) $usuario['id_usuario']
                );

                if ($error === '') {
                    header('Location: index.php?mensaje=' . urlencode('Participante inscripto correctamente.'));
                    exit;
                }
            } catch (PDOException | RuntimeException $exception) {
                if (PHP_SAPI !== 'cli') { error_log('Error al crear inscripción: ' . $exception->getMessage()); }
                $error = 'No pudimos crear la inscripción. Intentá nuevamente más tarde.';
            }
        }
    }
}

try {
    $conexion = conectar_db();
    $consulta = $conexion->query('SELECT id_participante, nombre, apellido FROM participantes ORDER BY apellido, nombre');
    $participantes = $consulta->fetchAll();
    $consulta = $conexion->query("SELECT id_evento, titulo, fecha_evento, cupo_maximo, cupo_maximo - (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = eventos.id_evento AND i.estado = 'activa') AS cupos_disponibles FROM eventos ORDER BY fecha_evento, id_evento");
    $eventos = $consulta->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') { error_log('Error al cargar datos para inscripción: ' . $exception->getMessage()); }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva inscripción | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Nueva inscripción</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php require __DIR__ . '/../includes/formulario_inscripcion.php'; ?>
    </main>
</body>
</html>


