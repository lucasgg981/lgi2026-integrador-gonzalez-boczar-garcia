CREATE DATABASE IF NOT EXISTS eventos_cursos
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eventos_cursos;

-- Primera parte: cuentas del personal. Los participantes no son usuarios.
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    CONSTRAINT usuarios_rol_valido CHECK (rol IN ('administrador', 'empleado'))
) ENGINE=InnoDB;
