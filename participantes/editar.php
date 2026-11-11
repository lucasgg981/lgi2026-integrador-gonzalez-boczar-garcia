<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/participantes.php';
$usuario = requerir_sesion();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('El identificador del participante no es válido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
    http_response_code(403);
    exit('La sesión del formulario venció. Volvé al listado e intentá nuevamente.');
}

$error = '';
$participante = null;
$accion = 'editar.php?id=' . $id;

try {
    $conexion = conectar_db();
    $consulta = $conexion->prepare('SELECT * FROM participantes WHERE id_participante = :id');
    $consulta->execute(['id' => $id]);
    $participante = $consulta->fetch();
    if ($participante === false) {
        http_response_code(404);
        exit('El participante no existe.');
    }

    $participante['email'] = $participante['email'] ?? '';
    $participante['telefono'] = $participante['telefono'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $participante = datos_participante($_POST);
        $error = validar_participante($participante);
        if ($error === '') {
            $consulta = $conexion->prepare(
                'UPDATE participantes SET nombre = :nombre, apellido = :apellido,
                 email = :email, telefono = :telefono WHERE id_participante = :id'
            );
            $consulta->execute([
                'nombre' => $participante['nombre'],
                'apellido' => $participante['apellido'],
                'email' => $participante['email'] === '' ? null : $participante['email'],
                'telefono' => $participante['telefono'] === '' ? null : $participante['telefono'],
                'id' => $id,
            ]);
            header('Location: index.php?mensaje=' . urlencode('Participante modificado correctamente.'));
            exit;
        }
    }
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al editar participante: ' . $exception->getMessage());
    $error = 'No pudimos cargar o guardar el participante. Intentá nuevamente más tarde.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar participante | Eventos y cursos</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <a href="index.php">Volver al listado</a>
        </header>
        <h1>Editar participante</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escapar($error) ?></p>
        <?php endif; ?>
        <?php if (is_array($participante)): ?>
            <?php require __DIR__ . '/../includes/formulario_participante.php'; ?>
        <?php endif; ?>
    </main>
</body>
</html>
