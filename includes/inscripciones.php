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
