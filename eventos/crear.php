<?php
// Pantalla para cargar un evento nuevo.
require_once __DIR__ . "/../conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $lugar = trim($_POST["lugar"]);
    $fecha_evento = str_replace("T", " ", $_POST["fecha_evento"]);
    $fecha_limite = str_replace("T", " ", $_POST["fecha_limite_inscripcion"]);
    $cupo_maximo = (int) $_POST["cupo_maximo"];

    if ($titulo == "" || $lugar == "" || $fecha_evento == "" || $fecha_limite == "" || $cupo_maximo <= 0) {
        $error = "Completa todos los campos. El cupo tiene que ser mayor a cero.";
    } elseif ($fecha_limite > $fecha_evento) {
        $error = "La fecha limite no puede ser posterior a la fecha del evento.";
    } else {
        $sql = "INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
                VALUES (?, ?, ?, ?, ?, ?)";
        $sentencia = $conexion->prepare($sql);
        $sentencia->bind_param("sssssi", $titulo, $descripcion, $lugar, $fecha_evento, $fecha_limite, $cupo_maximo);
        $sentencia->execute();

        header("Location: index.php?mensaje=" . urlencode("Evento creado correctamente."));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo evento</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <h1>Nuevo evento</h1>

    <p><a href="index.php">Volver al listado</a></p>

    <?php if ($error != "") { ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>

    <form method="post" action="crear.php">
        <label>Titulo</label>
        <input type="text" name="titulo" required>

        <label>Descripcion</label>
        <textarea name="descripcion" rows="3"></textarea>

        <label>Lugar</label>
        <input type="text" name="lugar" required>

        <label>Fecha del evento</label>
        <input type="datetime-local" name="fecha_evento" required>

        <label>Fecha limite de inscripcion</label>
        <input type="datetime-local" name="fecha_limite_inscripcion" required>

        <label>Cupo maximo</label>
        <input type="number" name="cupo_maximo" min="1" required>

        <button type="submit">Guardar evento</button>
    </form>
</body>
</html>
