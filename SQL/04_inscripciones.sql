-- Tabla de inscripciones: qué usuario participa en qué torneo. Ejecutar UNA vez.
USE torneos_db;

CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Un usuario no puede anotarse dos veces al mismo torneo (lo garantiza la base)
    UNIQUE KEY uq_inscripcion (torneo_id, usuario_id),
    -- Si se borra el torneo o el usuario, sus inscripciones se borran solas
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
