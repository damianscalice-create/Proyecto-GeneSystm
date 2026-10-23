-- Agrega el rol a los usuarios. Ejecutar UNA vez.
USE torneos_db;

ALTER TABLE usuarios
    ADD COLUMN rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario' AFTER password_hash;

-- El primer administrador se crea SIEMPRE a mano, nunca desde el formulario de registro.
-- Registrá tu cuenta normalmente y después ejecutá (cambiando el nombre):
-- UPDATE usuarios SET rol = 'admin' WHERE nombre_usuario = 'TU_USUARIO';
