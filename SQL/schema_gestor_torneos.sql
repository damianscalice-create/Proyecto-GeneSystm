USE torneos_db;

CREATE TABLE IF NOT EXISTS gestor_torneos (
    id CHAR(32) NOT NULL PRIMARY KEY,
    usuario_id INT NOT NULL,
    datos LONGTEXT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gestor_torneos_usuario (usuario_id),
    CONSTRAINT fk_gestor_torneos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
