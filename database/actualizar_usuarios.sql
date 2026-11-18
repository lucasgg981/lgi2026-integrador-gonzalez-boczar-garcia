-- Solo para bases existentes sin estos campos. Ejecutar una única vez
-- con un usuario administrador de MySQL. No borra registros ni contraseñas.
USE eventos_cursos;
ALTER TABLE usuarios
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN version_sesion INT NOT NULL DEFAULT 1;
