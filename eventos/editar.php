<?php
// Pantalla para modificar un evento que ya existe.
require_once __DIR__ . "/../conexion.php";

$id = (int) $_GET["id"];
$consulta = $conexion->query("SELECT * FROM eventos WHERE id_evento = $id");
$evento = $consulta->fetch_assoc();

if ($evento == null) {
    die("El evento no existe. <a href='index.php'>Volver</a>");
}

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
        $sql = "UPDATE eventos
                SET titulo = ?, descripcion = ?, lugar = ?, fecha_evento = ?, fecha_limite_inscripcion = ?, cupo_maximo = ?
                WHERE id_evento = ?";
        $sentencia = $conexion->prepare($sql);
        $sentencia->bind_param("sssssii", $titulo, $descripcion, $lugar, $fecha_evento, $fecha_limite, $cupo_maximo, $id);
        $sentencia->execute();

        header("Location: index.php?mensaje=" . urlencode("Evento modificado correctamente."));
        exit;
    }
}

$fecha_evento_input = str_replace(" ", "T", $evento["fecha_evento"]);
$fecha_limite_input = str_replace(" ", "T", $evento["fecha_limite_inscripcion"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar evento</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <h1>Editar evento</h1>

    <p><a href="index.php">Volver al listado</a></p>

    <?php if ($error != "") { ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>

    <form method="post" action="editar.php?id=<?php echo $evento["id_evento"]; ?>">
        <label>Titulo</label>
        <input type="text" name="titulo" value="<?php echo htmlspecialchars($evento["titulo"]); ?>" required>

        <label>Descripcion</label>
        <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($evento["descripcion"]); ?></textarea>

        <label>Lugar</label>
        <input type="text" name="lugar" value="<?php echo htmlspecialchars($evento["lugar"]); ?>" required>

        <label>Fecha del evento</label>
        <input type="datetime-local" name="fecha_evento" value="<?php echo $fecha_evento_input; ?>" required>

        <label>Fecha limite de inscripcion</label>
        <input type="datetime-local" name="fecha_limite_inscripcion" value="<?php echo $fecha_limite_input; ?>" required>

        <label>Cupo maximo</label>
        <input type="number" name="cupo_maximo" min="1" value="<?php echo $evento["cupo_maximo"]; ?>" required>

        <button type="submit">Guardar cambios</button>
    </form>
</body>
</html>
