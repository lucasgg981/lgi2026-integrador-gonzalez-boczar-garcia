<?php
// Elimina un evento, pero solo si todavia no tiene inscripciones.
require_once __DIR__ . "/../conexion.php";

$id = (int) $_GET["id"];

$consulta = $conexion->query("SELECT COUNT(*) AS total FROM inscripciones WHERE id_evento = $id");
$fila = $consulta->fetch_assoc();

if ($fila["total"] > 0) {
    die("No se puede eliminar el evento porque tiene inscripciones. <a href='index.php'>Volver</a>");
}

$conexion->query("DELETE FROM eventos WHERE id_evento = $id");

header("Location: index.php?mensaje=" . urlencode("Evento eliminado correctamente."));
exit;
?>
