<?php
// Listado de eventos con la cantidad de cupos que quedan libres.
require_once __DIR__ . "/../conexion.php";

$resultado = $conexion->query("SELECT * FROM eventos ORDER BY fecha_evento");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eventos y cupos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <h1>Eventos y cursos</h1>

    <p><a class="boton" href="crear.php">Nuevo evento</a></p>

    <?php if (isset($_GET["mensaje"])) { ?>
        <p class="aviso"><?php echo htmlspecialchars($_GET["mensaje"]); ?></p>
    <?php } ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Titulo</th>
            <th>Lugar</th>
            <th>Fecha del evento</th>
            <th>Limite de inscripcion</th>
            <th>Cupo maximo</th>
            <th>Cupos disponibles</th>
            <th>Acciones</th>
        </tr>

        <?php while ($evento = $resultado->fetch_assoc()) { ?>
            <?php
                // Buscamos cuantas inscripciones activas tiene este evento.
                $id = (int) $evento["id_evento"];
                $consulta = $conexion->query("SELECT COUNT(*) AS total FROM inscripciones WHERE id_evento = $id AND estado = 'activa'");
                $fila = $consulta->fetch_assoc();
                $disponibles = $evento["cupo_maximo"] - $fila["total"];
            ?>
            <tr>
                <td><?php echo $evento["id_evento"]; ?></td>
                <td><?php echo htmlspecialchars($evento["titulo"]); ?></td>
                <td><?php echo htmlspecialchars($evento["lugar"]); ?></td>
                <td><?php echo $evento["fecha_evento"]; ?></td>
                <td><?php echo $evento["fecha_limite_inscripcion"]; ?></td>
                <td><?php echo $evento["cupo_maximo"]; ?></td>
                <td><?php echo $disponibles; ?></td>
                <td>
                    <a href="editar.php?id=<?php echo $evento["id_evento"]; ?>">Editar</a>
                    <a href="eliminar.php?id=<?php echo $evento["id_evento"]; ?>" onclick="return confirm('¿Seguro que queres eliminar este evento?');">Eliminar</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
