CREATE DATABASE IF NOT EXISTS torneos_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
 
USE torneos_db;
 
CREATE TABLE IF NOT EXISTS torneos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_evento VARCHAR(150) NOT NULL,
    deporte VARCHAR(50) NOT NULL,
    detalle VARCHAR(100) NULL,
    cantidad_participantes INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);
 