-- Base de datos del sistema de eventos y cursos.
-- Este archivo se importa una vez en cada computadora (phpMyAdmin o consola MySQL).

CREATE DATABASE IF NOT EXISTS sistema_eventos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE sistema_eventos;

-- Cuentas del personal (administrador y empleados) - parte de Gonzalez
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL
);

-- Eventos y cursos - parte de Boczar
CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    lugar VARCHAR(150) NOT NULL,
    fecha_evento DATETIME NOT NULL,
    fecha_limite_inscripcion DATETIME NOT NULL,
    cupo_maximo INT NOT NULL
);

-- Personas que se inscriben (no tienen cuenta) - parte de Garcia
CREATE TABLE IF NOT EXISTS participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(50)
);

-- Inscripciones de participantes a eventos - parte de Garcia
CREATE TABLE IF NOT EXISTS inscripciones (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_participante INT NOT NULL,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_inscripcion DATETIME NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activa',
    UNIQUE KEY unico_participante_evento (id_participante, id_evento),
    CONSTRAINT fk_inscripcion_participante FOREIGN KEY (id_participante)
        REFERENCES participantes(id_participante),
    CONSTRAINT fk_inscripcion_evento FOREIGN KEY (id_evento)
        REFERENCES eventos(id_evento),
    CONSTRAINT fk_inscripcion_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- Dos eventos de ejemplo para poder ver la pantalla funcionando.
INSERT INTO eventos (titulo, descripcion, lugar, fecha_evento, fecha_limite_inscripcion, cupo_maximo)
VALUES
('Introduccion a Python', 'Curso basico de programacion con Python.', 'Aula 3', '2026-12-01 18:00:00', '2026-11-25 23:59:59', 20),
('Taller de Robotica', 'Taller practico para principiantes.', 'Laboratorio 1', '2026-12-10 09:00:00', '2026-12-05 23:59:59', 15);
