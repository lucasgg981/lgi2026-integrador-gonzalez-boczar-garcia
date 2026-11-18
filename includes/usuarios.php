<?php

function datos_usuario(array $entrada): array
{
    $datos = [];
    foreach (['nombre', 'apellido', 'email', 'rol', 'activo'] as $campo) {
        $datos[$campo] = is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
    }
    // La contraseña no se recorta ni se vuelve a mostrar en el formulario.
    $datos['clave'] = is_string($entrada['clave'] ?? null) ? $entrada['clave'] : '';
    return $datos;
}

function validar_usuario(array $datos, bool $nuevo): string
{
    if (trim($datos['nombre']) === '' || trim($datos['apellido']) === ''
        || preg_match('/^.{1,100}$/usD', $datos['nombre']) !== 1
        || preg_match('/^.{1,100}$/usD', $datos['apellido']) !== 1) {
        return 'Completá nombre y apellido, con hasta 100 caracteres cada uno.';
    }
    if (strlen($datos['email']) > 254 || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Ingresá un correo electrónico válido.';
    }
    if (!in_array($datos['rol'], ['administrador', 'empleado'], true)
        || !in_array($datos['activo'], ['0', '1'], true)) {
        return 'Seleccioná un rol y un estado válidos.';
    }
    if (($nuevo || $datos['clave'] !== '')
        && (strlen($datos['clave']) < 8 || strlen($datos['clave']) > 72 || str_contains($datos['clave'], "\0"))) {
        return 'La contraseña debe tener entre 8 y 72 bytes y no contener caracteres nulos.';
    }
    return '';
}

function actualizar_usuario(PDO $conexion, int $id, array $datos, int $actor): string
{
    $conexion->beginTransaction();
    try {
        // Serializar los cambios de administradores para conservar al menos uno activo.
        $administradores = $conexion->query(
            "SELECT id_usuario FROM usuarios WHERE rol = 'administrador' AND activo = 1
             ORDER BY id_usuario FOR UPDATE"
        )->fetchAll(PDO::FETCH_COLUMN);
        $consulta = $conexion->prepare('SELECT id_usuario, rol, activo FROM usuarios WHERE id_usuario = :id FOR UPDATE');
        $consulta->execute(['id' => $id]);
        $anterior = $consulta->fetch();
        $error = '';
        if (!$anterior) {
            $error = 'El usuario no existe.';
        } elseif ($id === $actor && ($datos['rol'] !== 'administrador' || $datos['activo'] !== '1')) {
            $error = 'No podés desactivar tu propia cuenta ni quitarte el rol de administrador.';
        } elseif ($anterior['rol'] === 'administrador' && (int) $anterior['activo'] === 1
            && count($administradores) <= 1 && ($datos['rol'] !== 'administrador' || $datos['activo'] !== '1')) {
            $error = 'Debe quedar al menos un administrador activo.';
        }
        if ($error !== '') {
            $conexion->rollBack();
            return $error;
        }

        $sql = 'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email,
                rol = :rol, activo = :activo, version_sesion = version_sesion + 1';
        $parametros = $datos;
        unset($parametros['clave']);
        if ($datos['clave'] !== '') {
            $sql .= ', clave_hash = :clave_hash';
            $parametros['clave_hash'] = password_hash($datos['clave'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id_usuario = :id';
        $parametros['id'] = $id;
        $consulta = $conexion->prepare($sql);
        $consulta->execute($parametros);
        $conexion->commit();
        return '';
    } catch (Throwable $exception) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $exception;
    }
}
