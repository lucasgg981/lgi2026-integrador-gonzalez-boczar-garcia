<?php
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
$usuario = requerir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Usá el botón Cancelar del listado de inscripciones.');
}

if (!csrf_valido()) {
    http_response_code(403);
    exit('La solicitud no es válida. Volvé al listado e intentá nuevamente.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(400);
    exit('El identificador de la inscripción no es válido.');
}

try {
    $conexion = conectar_db();
    $consulta = $conexion->prepare("SELECT estado FROM inscripciones WHERE id_inscripcion = :id");
    $consulta->execute(['id' => $id]);
    $estado = $consulta->fetchColumn();
    if ($estado === false) {
        $mensaje = 'La inscripción no existe.';
    } elseif ($estado !== 'activa') {
        $mensaje = 'La inscripción ya estaba cancelada.';
    } else {
        $consulta = $conexion->prepare("UPDATE inscripciones SET estado = 'cancelada' WHERE id_inscripcion = :id");
        $consulta->execute(['id' => $id]);
        $mensaje = $consulta->rowCount() > 0 ? 'Inscripción cancelada correctamente.' : 'La inscripción no existe.';
    }
} catch (PDOException | RuntimeException $exception) {
    if (PHP_SAPI !== 'cli') { error_log('Error al cancelar inscripción: ' . $exception->getMessage()); }
    $mensaje = 'No pudimos cancelar la inscripción. Intentá nuevamente más tarde.';
}

header('Location: index.php?mensaje=' . urlencode($mensaje));
exit;


