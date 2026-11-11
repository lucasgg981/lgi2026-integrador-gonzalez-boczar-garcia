<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/participantes.php';
$usuario = requerir_sesion();

$error = '';
$participante = datos_participante([]);
$accion = 'crear.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participante = datos_participante($_POST);
    if (!csrf_valido()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Volvé a intentarlo.';
    } else {
        $error = validar_participante($participante);
        if ($error === '') {
            try {
                $consulta = conectar_db()->prepare(
                    'INSERT INTO participantes (nombre, apellido, email, telefono)
                     VALUES (:nombre, :apellido, :email, :telefono)'
                );
                $consulta->execute([
                    'nombre' => $participante['nombre'],
                    'apellido' => $participante['apellido'],
                    'email' => $participante['email'] === '' ? null : $participante['email'],
                    'telefono' => $participante['telefono'] === '' ? null : $participante['telefono'],
                ]);
                header('Location: index.php?mensaje=' . urlencode('Participante creado correctamente.'));
                exit;
            } catch (PDOException | RuntimeException $exception) {
                error_log('Error al crear participante: ' . $exception->getMessage());
                $error = 'No pudimos crear el participante. Intentá nuevamente más tarde.';
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
    <title>Nuevo participante | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Nuevo participante</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php require __DIR__ . '/../includes/formulario_participante.php'; ?>
    </main>
</body>
</html>
