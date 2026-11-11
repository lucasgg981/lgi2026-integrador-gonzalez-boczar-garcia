<?php
if (!isset($evento, $accion)) {
    http_response_code(404);
    exit;
}
?>
<form class="formulario-evento" method="post" action="<?= escapar($accion) ?>">
    <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">

    <label for="titulo">Título</label>
    <input id="titulo" name="titulo" type="text" maxlength="150" value="<?= escapar($evento['titulo']) ?>" required>

    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" rows="3"><?= escapar($evento['descripcion'] ?? '') ?></textarea>

    <label for="lugar">Lugar</label>
    <input id="lugar" name="lugar" type="text" maxlength="150" value="<?= escapar($evento['lugar']) ?>" required>

    <label for="fecha_evento">Fecha del evento</label>
    <input id="fecha_evento" name="fecha_evento" type="datetime-local" value="<?= escapar($evento['fecha_evento']) ?>" required>

    <label for="fecha_limite_inscripcion">Límite de inscripción</label>
    <input id="fecha_limite_inscripcion" name="fecha_limite_inscripcion" type="datetime-local" value="<?= escapar($evento['fecha_limite_inscripcion']) ?>" required>

    <label for="cupo_maximo">Cupo máximo</label>
    <input id="cupo_maximo" name="cupo_maximo" type="number" min="1" max="2147483647" value="<?= escapar((string) $evento['cupo_maximo']) ?>" required>

    <button type="submit">Guardar evento</button>
</form>
