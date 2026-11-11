<?php
require __DIR__ . '/includes/auth.php';
$usuario = requerir_rol('empleado');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personal | Eventos y cursos</title>
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
        <span class="rol">Empleado</span>
        <h1>Hola, <?= escapar($usuario['nombre']) ?></h1>
        <p class="descripcion">Bienvenido al panel del personal.</p>
        <section class="estado">
            <h2>Tu sesión está activa</h2>
            <p>Desde este espacio se incorporarán la consulta de eventos y el registro de participantes e inscripciones.</p>
        </section>
    </main>
</body>
</html>
