<?php
if (!isset($participante, $accion)) {
    http_response_code(404);
    exit;
}
?>
<form class="formulario-participante" method="post" action="<?= escapar($accion) ?>">
    <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">

    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" type="text" maxlength="100" value="<?= escapar($participante['nombre']) ?>" required>

    <label for="apellido">Apellido</label>
    <input id="apellido" name="apellido" type="text" maxlength="100" value="<?= escapar($participante['apellido']) ?>" required>

    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" maxlength="150" value="<?= escapar($participante['email'] ?? '') ?>">

    <label for="telefono">Teléfono</label>
    <input id="telefono" name="telefono" type="text" maxlength="50" value="<?= escapar($participante['telefono'] ?? '') ?>">

    <p class="nota">El correo y el teléfono son opcionales.</p>

    <button type="submit">Guardar participante</button>
</form>
