<?php
if (!isset($datos, $accion, $nuevo)) {
    http_response_code(404);
    exit;
}
?>
<form class="formulario-evento" method="post" action="<?= escapar($accion) ?>">
    <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" maxlength="100" value="<?= escapar($datos['nombre']) ?>" required>
    <label for="apellido">Apellido</label>
    <input id="apellido" name="apellido" maxlength="100" value="<?= escapar($datos['apellido']) ?>" required>
    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" maxlength="254" value="<?= escapar($datos['email']) ?>" required>
    <label for="rol">Rol</label>
    <select id="rol" name="rol" required>
        <option value="empleado" <?= $datos['rol'] === 'empleado' ? 'selected' : '' ?>>Empleado</option>
        <option value="administrador" <?= $datos['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
    </select>
    <label for="activo">Estado</label>
    <select id="activo" name="activo" required>
        <option value="1" <?= (string) $datos['activo'] === '1' ? 'selected' : '' ?>>Activo</option>
        <option value="0" <?= (string) $datos['activo'] === '0' ? 'selected' : '' ?>>Inactivo</option>
    </select>
    <label for="clave"><?= $nuevo ? 'Contraseña' : 'Nueva contraseña (opcional)' ?></label>
    <input id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" maxlength="72" <?= $nuevo ? 'required' : '' ?>>
    <p class="nota">Usá entre 8 y 72 bytes.<?= $nuevo ? '' : ' Dejá la contraseña vacía para conservar la actual. Guardar los cambios cierra las sesiones anteriores de esta cuenta.' ?></p>
    <button type="submit">Guardar usuario</button>
</form>
