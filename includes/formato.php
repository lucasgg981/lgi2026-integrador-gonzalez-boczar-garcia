<?php

function formatear_fecha(string $fecha): string
{
    $marca = strtotime($fecha);
    if ($marca === false) {
        return $fecha;
    }

    return date('d/m/Y H:i', $marca);
}
