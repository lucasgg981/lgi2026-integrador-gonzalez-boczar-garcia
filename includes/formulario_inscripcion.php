<?php
if (!isset($inscripcion, $accion) || !isset($participantes, $eventos)) {
    http_response_code(404);
    exit;
}
?>
<form class="formulario-inscripcion" method="post" action="<?= escapar($accion) ?>">
    <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">

    <label for="id_participante">Participante</label>
    <select id="id_participante" name="id_participante" required>
        <option value="">Seleccioná un participante</option>
        <?php foreach ($participantes as $participante): ?>
            <option value="<?= (int) $participante['id_participante'] ?>"
                <?= (string) ($inscripcion['id_participante'] ?? '') === (string) $participante['id_participante'] ? 'selected' : '' ?>>
                <?= escapar($participante['apellido'] . ', ' . $participante['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="id_evento">Evento</label>
    <select id="id_evento" name="id_evento" required>
        <option value="">Seleccioná un evento</option>
        <?php foreach ($eventos as $evento): ?>
            <option value="<?= (int) $evento['id_evento'] ?>"
                <?= (string) ($inscripcion['id_evento'] ?? '') === (string) $evento['id_evento'] ? 'selected' : '' ?>>
                <?= escapar($evento['titulo']) ?> (<?= escapar($evento['fecha_evento']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <p class="nota">La inscripción queda activa y descuenta un cupo del evento.</p>

    <button type="submit">Inscribir participante</button>
</form>
