<?php

function datos_evento(array $entrada): array
{
    $evento = [];
    foreach (['titulo', 'descripcion', 'lugar', 'fecha_evento', 'fecha_limite_inscripcion', 'cupo_maximo'] as $campo) {
        $evento[$campo] = is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
    }

    return $evento;
}

function validar_evento(array $evento): string
{
    if ($evento['titulo'] === '' || $evento['lugar'] === '') {
        return 'Completá el título y el lugar.';
    }

    if (preg_match('/^.{1,150}$/usD', $evento['titulo']) !== 1
        || preg_match('/^.{1,150}$/usD', $evento['lugar']) !== 1) {
        return 'El título y el lugar admiten hasta 150 caracteres.';
    }

    if (strlen($evento['descripcion']) > 65535) {
        return 'La descripción es demasiado larga.';
    }

    foreach (['fecha_evento', 'fecha_limite_inscripcion'] as $campo) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $evento[$campo]) !== 1) {
            return 'Ingresá fechas válidas para el evento y el límite de inscripción.';
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $evento[$campo]);
        if ($fecha === false || $fecha->format('Y-m-d\TH:i') !== $evento[$campo]
            || (int) $fecha->format('Y') < 1000) {
            return 'Ingresá fechas válidas para el evento y el límite de inscripción.';
        }
    }

    if ($evento['fecha_limite_inscripcion'] > $evento['fecha_evento']) {
        return 'La fecha límite no puede ser posterior a la fecha del evento.';
    }

    if (filter_var($evento['cupo_maximo'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]) === false) {
        return 'El cupo tiene que ser un número entero mayor a cero.';
    }

    return '';
}
