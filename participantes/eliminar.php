<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/database.php';
$usuario = requerir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Usá el botón Eliminar del listado de participantes.');
}

if (!csrf_valido()) {
    http_response_code(403);
    exit('La solicitud no es válida. Volvé al listado e intentá nuevamente.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('El identificador del participante no es válido.');
}

try {
    $conexion = conectar_db();
    $consulta = $conexion->prepare('SELECT COUNT(*) FROM inscripciones WHERE id_participante = :id');
    $consulta->execute(['id' => $id]);
    if ((int) $consulta->fetchColumn() > 0) {
        $mensaje = 'No se puede eliminar el participante porque tiene inscripciones.';
    } else {
        $consulta = $conexion->prepare('DELETE FROM participantes WHERE id_participante = :id');
        $consulta->execute(['id' => $id]);
        $mensaje = $consulta->rowCount() > 0 ? 'Participante eliminado correctamente.' : 'El participante no existe.';
    }
} catch (PDOException | RuntimeException $exception) {
    error_log('Error al eliminar participante: ' . $exception->getMessage());
    $mensaje = 'No pudimos eliminar el participante. Intentá nuevamente más tarde.';
}

header('Location: index.php?mensaje=' . urlencode($mensaje));
exit;
