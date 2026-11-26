-- Ejecutar una vez sobre una base del MVP seleccionada, con permiso ALTER.
ALTER TABLE usuarios
    DROP CHECK usuarios_rol_valido,
    ADD CONSTRAINT usuarios_rol_valido CHECK (rol IN ('administrador', 'empleado', 'usuario')),
    ADD COLUMN id_participante INT NULL UNIQUE,
    ADD CONSTRAINT fk_usuario_participante FOREIGN KEY (id_participante) REFERENCES participantes(id_participante);
