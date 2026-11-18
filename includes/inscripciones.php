<?php

function datos_inscripcion(array $entrada): array
{
    $inscripcion = [];
    foreach (['id_participante', 'id_evento'] as $campo) {
        $valor = $entrada[$campo] ?? null;
        $inscripcion[$campo] = is_scalar($valor) ? trim((string) $valor) : '';
    }

    return $inscripcion;
}

function validar_inscripcion(array $inscripcion): string
{
    foreach (['id_participante', 'id_evento'] as $campo) {
        if (filter_var($inscripcion[$campo] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) === false) {
            return 'Seleccioná un participante y un evento válidos.';
        }
    }

    return '';
}

function registrar_inscripcion(PDO $conexion, int $id_participante, int $id_evento, int $id_usuario): string
{
    $conexion->beginTransaction();

    try {
        // Bloqueamos el evento antes de cualquier otra lectura para que dos
        // inscripciones simultáneas no lean el mismo cupo disponible.
        $consulta = $conexion->prepare(
            'SELECT cupo_maximo, fecha_limite_inscripcion
             FROM eventos WHERE id_evento = :evento FOR UPDATE'
        );
        $consulta->execute(['evento' => $id_evento]);
        $evento = $consulta->fetch();

        if ($evento === false) {
            $conexion->rollBack();
            return 'El evento seleccionado no existe.';
        }

        $consulta = $conexion->prepare(
            'SELECT 1 FROM participantes WHERE id_participante = :participante'
        );
        $consulta->execute(['participante' => $id_participante]);
        if ($consulta->fetchColumn() === false) {
            $conexion->rollBack();
            return 'El participante seleccionado no existe.';
        }

        $consulta = $conexion->prepare(
            'SELECT COUNT(*) FROM inscripciones
             WHERE id_participante = :participante AND id_evento = :evento'
        );
        $consulta->execute([
            'participante' => $id_participante,
            'evento' => $id_evento,
        ]);
        if ((int) $consulta->fetchColumn() > 0) {
            $conexion->rollBack();
            return 'Ese participante ya está inscripto en ese evento.';
        }

        $consulta = $conexion->prepare(
            "SELECT COUNT(*) FROM inscripciones
             WHERE id_evento = :evento AND estado = 'activa'"
        );
        $consulta->execute(['evento' => $id_evento]);
        if ((int) $consulta->fetchColumn() >= (int) $evento['cupo_maximo']) {
            $conexion->rollBack();
            return 'No hay cupos disponibles para ese evento.';
        }

        if (strtotime((string) $evento['fecha_limite_inscripcion']) < time()) {
            $conexion->rollBack();
            return 'La inscripción a ese evento ya cerró.';
        }

        $consulta = $conexion->prepare(
            'INSERT INTO inscripciones (id_participante, id_evento, id_usuario, fecha_inscripcion, estado)
             VALUES (:participante, :evento, :usuario, :fecha, :estado)'
        );
        $consulta->execute([
            'participante' => $id_participante,
            'evento' => $id_evento,
            'usuario' => $id_usuario,
            'fecha' => date('Y-m-d H:i:s'),
            'estado' => 'activa',
        ]);

        $conexion->commit();
        return '';
    } catch (PDOException $exception) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        if ($exception->getCode() === '23000') {
            return 'Ese participante ya está inscripto en ese evento.';
        }

        throw $exception;
    }
}
