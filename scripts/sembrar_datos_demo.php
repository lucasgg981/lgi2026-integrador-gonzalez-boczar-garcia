<?php

// Carga datos de demostración sin duplicar registros existentes.
// Uso: php scripts/sembrar_datos_demo.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/database.php';

$usuarios = [
    ['nombre' => 'Administrador', 'apellido' => 'Demo', 'email' => 'admin@eventos.local', 'clave' => 'Admin12345!', 'rol' => 'administrador'],
    ['nombre' => 'Empleado', 'apellido' => 'Demo', 'email' => 'empleado@eventos.local', 'clave' => 'Empleado12345!', 'rol' => 'empleado'],
];

$eventos = [
    [
        'titulo' => 'Introducción a Python',
        'descripcion' => 'Curso inicial de programación con Python.',
        'lugar' => 'Aula 1',
        'fecha_evento' => '2026-12-01 18:00:00',
        'fecha_limite_inscripcion' => '2026-11-25 23:59:00',
        'cupo_maximo' => 25,
    ],
    [
        'titulo' => 'Taller de Robótica',
        'descripcion' => 'Taller práctico de armado y programación de robots.',
        'lugar' => 'Laboratorio 2',
        'fecha_evento' => '2026-12-10 14:00:00',
        'fecha_limite_inscripcion' => '2026-12-05 23:59:00',
        'cupo_maximo' => 15,
    ],
    [
        'titulo' => 'Diseño Gráfico Digital',
        'descripcion' => 'Introducción a las herramientas de diseño digital.',
        'lugar' => 'Aula 4',
        'fecha_evento' => '2027-03-05 17:00:00',
        'fecha_limite_inscripcion' => '2027-02-28 23:59:00',
        'cupo_maximo' => 30,
    ],
    [
        'titulo' => 'Charla de Ciberseguridad',
        'descripcion' => 'Buenas prácticas para proteger cuentas y dispositivos.',
        'lugar' => 'Auditorio',
        'fecha_evento' => '2027-04-15 19:00:00',
        'fecha_limite_inscripcion' => '2027-04-10 23:59:00',
        'cupo_maximo' => 50,
    ],
];

$participantes = [
    ['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana.perez@example.com', 'telefono' => '11 5555-1234'],
    ['nombre' => 'Bruno', 'apellido' => 'Gómez', 'email' => 'bruno.gomez@example.com', 'telefono' => '11 5555-5678'],
    ['nombre' => 'Carla', 'apellido' => 'Fernández', 'email' => 'carla.fernandez@example.com', 'telefono' => '11 5555-9012'],
    ['nombre' => 'Diego', 'apellido' => 'López', 'email' => 'diego.lopez@example.com', 'telefono' => '11 4444-1122'],
    ['nombre' => 'Elena', 'apellido' => 'Martínez', 'email' => 'elena.martinez@example.com', 'telefono' => '11 4444-3344'],
    ['nombre' => 'Facundo', 'apellido' => 'Rossi', 'email' => 'facundo.rossi@example.com', 'telefono' => null],
];

$inscripciones = [
    ['participante' => 'ana.perez@example.com', 'evento' => 'Introducción a Python', 'estado' => 'activa'],
    ['participante' => 'bruno.gomez@example.com', 'evento' => 'Introducción a Python', 'estado' => 'activa'],
    ['participante' => 'carla.fernandez@example.com', 'evento' => 'Taller de Robótica', 'estado' => 'activa'],
    ['participante' => 'diego.lopez@example.com', 'evento' => 'Introducción a Python', 'estado' => 'cancelada'],
    ['participante' => 'elena.martinez@example.com', 'evento' => 'Diseño Gráfico Digital', 'estado' => 'activa'],
];

$conexion = null;

try {
    $conexion = conectar_db();
    $conexion->beginTransaction();

    $resumen = [];
    $idsUsuarios = [];
    $idsEventos = [];
    $idsParticipantes = [];

    $buscar = $conexion->prepare('SELECT id_usuario FROM usuarios WHERE email = :email');
    $insertar = $conexion->prepare(
        'INSERT INTO usuarios (nombre, apellido, email, clave_hash, rol)
         VALUES (:nombre, :apellido, :email, :clave_hash, :rol)'
    );
    $creados = 0;
    $existentes = 0;
    foreach ($usuarios as $usuario) {
        $buscar->execute(['email' => $usuario['email']]);
        $id = $buscar->fetchColumn();
        if ($id !== false) {
            $idsUsuarios[$usuario['email']] = (int) $id;
            $existentes++;
            continue;
        }

        $insertar->execute([
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'clave_hash' => password_hash($usuario['clave'], PASSWORD_DEFAULT),
            'rol' => $usuario['rol'],
        ]);
        $idsUsuarios[$usuario['email']] = (int) $conexion->lastInsertId();
        $creados++;
    }
    $resumen[] = "Usuarios: $creados creados, $existentes ya existentes.";

    $buscar = $conexion->prepare(
        'SELECT id_evento FROM eventos WHERE titulo = :titulo AND fecha_evento = :fecha_evento'
    );
    $insertar = $conexion->prepare(
        'INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
         VALUES (:titulo, :descripcion, :lugar, :fecha_evento, :fecha_limite_inscripcion, :cupo_maximo)'
    );
    $creados = 0;
    $existentes = 0;
    foreach ($eventos as $evento) {
        $buscar->execute(['titulo' => $evento['titulo'], 'fecha_evento' => $evento['fecha_evento']]);
        $id = $buscar->fetchColumn();
        if ($id !== false) {
            $idsEventos[$evento['titulo']] = (int) $id;
            $existentes++;
            continue;
        }

        $insertar->execute($evento);
        $idsEventos[$evento['titulo']] = (int) $conexion->lastInsertId();
        $creados++;
    }
    $resumen[] = "Eventos: $creados creados, $existentes ya existentes.";

    $buscar = $conexion->prepare('SELECT id_participante FROM participantes WHERE email = :email');
    $insertar = $conexion->prepare(
        'INSERT INTO participantes (nombre, apellido, email, telefono)
         VALUES (:nombre, :apellido, :email, :telefono)'
    );
    $creados = 0;
    $existentes = 0;
    foreach ($participantes as $participante) {
        $buscar->execute(['email' => $participante['email']]);
        $id = $buscar->fetchColumn();
        if ($id !== false) {
            $idsParticipantes[$participante['email']] = (int) $id;
            $existentes++;
            continue;
        }

        $insertar->execute([
            'nombre' => $participante['nombre'],
            'apellido' => $participante['apellido'],
            'email' => $participante['email'],
            'telefono' => $participante['telefono'],
        ]);
        $idsParticipantes[$participante['email']] = (int) $conexion->lastInsertId();
        $creados++;
    }
    $resumen[] = "Participantes: $creados creados, $existentes ya existentes.";

    $idOperador = $idsUsuarios['admin@eventos.local'] ?? null;
    if ($idOperador === null) {
        throw new RuntimeException('No se encontró el usuario administrador para registrar las inscripciones.');
    }

    $buscar = $conexion->prepare(
        'SELECT id_inscripcion FROM inscripciones WHERE id_participante = :id_participante AND id_evento = :id_evento'
    );
    $insertar = $conexion->prepare(
        'INSERT INTO inscripciones (id_participante, id_evento, id_usuario, fecha_inscripcion, estado)
         VALUES (:id_participante, :id_evento, :id_usuario, :fecha_inscripcion, :estado)'
    );
    $creados = 0;
    $existentes = 0;
    foreach ($inscripciones as $inscripcion) {
        $idParticipante = $idsParticipantes[$inscripcion['participante']] ?? null;
        $idEvento = $idsEventos[$inscripcion['evento']] ?? null;
        if ($idParticipante === null || $idEvento === null) {
            throw new RuntimeException('Faltan datos para la inscripción demo de ' . $inscripcion['participante'] . '.');
        }

        $buscar->execute(['id_participante' => $idParticipante, 'id_evento' => $idEvento]);
        if ($buscar->fetchColumn() !== false) {
            $existentes++;
            continue;
        }

        $insertar->execute([
            'id_participante' => $idParticipante,
            'id_evento' => $idEvento,
            'id_usuario' => $idOperador,
            'fecha_inscripcion' => date('Y-m-d H:i:s'),
            'estado' => $inscripcion['estado'],
        ]);
        $creados++;
    }
    $resumen[] = "Inscripciones: $creados creadas, $existentes ya existentes.";

    $conexion->commit();
} catch (PDOException | RuntimeException $exception) {
    if ($conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    fwrite(STDERR, 'No se pudieron cargar los datos demo: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "Seed completado.\n";
foreach ($resumen as $linea) {
    echo $linea, "\n";
}
