CREATE DATABASE IF NOT EXISTS eventos_cursos
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eventos_cursos;

-- Cuentas del personal. Los participantes no son usuarios.
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    CONSTRAINT usuarios_rol_valido CHECK (rol IN ('administrador', 'empleado'))
) ENGINE=InnoDB;

-- Eventos y cursos: módulo de Boczar.
CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    lugar VARCHAR(150) NOT NULL,
    fecha_evento DATETIME NOT NULL,
    fecha_limite_inscripcion DATETIME NOT NULL,
    cupo_maximo INT NOT NULL
) ENGINE=InnoDB;

-- Tablas necesarias para contar cupos y evitar eliminar eventos inscriptos.
-- La gestión de participantes e inscripciones corresponde al módulo de García.
CREATE TABLE IF NOT EXISTS participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(50)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;
