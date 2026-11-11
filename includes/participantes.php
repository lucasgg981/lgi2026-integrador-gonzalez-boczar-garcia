<?php

function datos_participante(array $entrada): array
{
    $participante = [];
    foreach (['nombre', 'apellido', 'email', 'telefono'] as $campo) {
        $participante[$campo] = is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
    }

    return $participante;
}

function validar_participante(array $participante): string
{
    if (trim($participante['nombre']) === '' || trim($participante['apellido']) === '') {
        return 'Completá el nombre y el apellido.';
    }

    if (preg_match('/^.{1,100}$/usD', $participante['nombre']) !== 1
        || preg_match('/^.{1,100}$/usD', $participante['apellido']) !== 1) {
        return 'El nombre y el apellido admiten hasta 100 caracteres.';
    }

    if ($participante['email'] !== ''
        && (strlen($participante['email']) > 150
            || filter_var($participante['email'], FILTER_VALIDATE_EMAIL) === false)) {
        return 'Ingresá un correo electrónico válido o dejá el campo vacío.';
    }

    if (preg_match('/^.{0,50}$/usD', $participante['telefono']) !== 1) {
        return 'El teléfono admite hasta 50 caracteres.';
    }

    return '';
}
