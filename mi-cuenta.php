<?php
require __DIR__ . '/includes/auth.php';
$usuario = requerir_rol('usuario');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi cuenta | Eventos y cursos</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="panel">
        <header class="cabecera">
            <p class="etiqueta">Eventos y cursos</p>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                <button class="secundario" type="submit">Cerrar sesión</button>
            </form>
        </header>
        <h1>Hola, <?= escapar($usuario['nombre']) ?></h1>
        <div class="acciones-panel">
            <a class="boton" href="catalogo.php">Ver eventos</a>
            <a class="boton secundario" href="inscripciones/mis-inscripciones.php">Mis inscripciones</a>
        </div>
    </main>
</body>
</html>
