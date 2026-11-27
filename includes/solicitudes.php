<?php

// Consultas y mensajes del flujo público de solicitud de cupos.
// La escritura reutiliza registrar_inscripcion() de includes/inscripciones.php.

function estado_evento(array $evento, ?int $ahora = null): string
{
    $ahora ??= time();

    if (strtotime((string) ($evento['fecha_limite_inscripcion'] ?? '')) < $ahora) {
        return 'cerrado';
    }

    if ((int) ($evento['cupos_disponibles'] ?? 0) <= 0) {
        return 'completo';
    }

    return 'disponible';
}

function evento_para_solicitud(PDO $conexion, int $id_evento): ?array
{
    $consulta = $conexion->prepare(
        "SELECT e.id_evento, e.titulo, e.descripcion, e.lugar, e.fecha_evento,
                e.fecha_limite_inscripcion, e.cupo_maximo,
                e.cupo_maximo - (
                    SELECT COUNT(*) FROM inscripciones i
                    WHERE i.id_evento = e.id_evento AND i.estado = 'activa'
                ) AS cupos_disponibles
         FROM eventos e
         WHERE e.id_evento = :evento"
    );
    $consulta->execute(['evento' => $id_evento]);
    $evento = $consulta->fetch();

    return $evento === false ? null : $evento;
}

function inscripcion_de_participante_en_evento(PDO $conexion, int $id_participante, int $id_evento): ?array
{
    $consulta = $conexion->prepare(
        'SELECT id_inscripcion, estado, fecha_inscripcion
         FROM inscripciones
         WHERE id_participante = :participante AND id_evento = :evento'
    );
    $consulta->execute([
        'participante' => $id_participante,
        'evento' => $id_evento,
    ]);
    $inscripcion = $consulta->fetch();

    return $inscripcion === false ? null : $inscripcion;
}

function inscripciones_de_participante(PDO $conexion, int $id_participante): array
{
    $consulta = $conexion->prepare(
        "SELECT i.id_inscripcion, i.fecha_inscripcion, i.estado,
                e.id_evento, e.titulo, e.lugar, e.fecha_evento, e.fecha_limite_inscripcion
         FROM inscripciones i
         JOIN eventos e ON e.id_evento = i.id_evento
         WHERE i.id_participante = :participante
         ORDER BY e.fecha_evento DESC, i.id_inscripcion DESC"
    );
    $consulta->execute(['participante' => $id_participante]);

    return $consulta->fetchAll();
}

function mensaje_solicitud(string $resultado): string
{
    return match ($resultado) {
        'Ese participante ya está inscripto en ese evento.' => 'Ya tenés una inscripción para ese evento.',
        'No hay cupos disponibles para ese evento.' => 'Ese evento ya no tiene cupos disponibles.',
        'La inscripción a ese evento ya cerró.' => 'El plazo para inscribirte a ese evento ya cerró.',
        'El evento seleccionado no existe.' => 'El evento ya no está disponible.',
        default => 'No pudimos completar tu solicitud. Intentá nuevamente más tarde.',
    };
}
