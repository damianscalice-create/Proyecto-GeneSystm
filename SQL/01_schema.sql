-- ============================================================
-- GeneSysTM - Esquema relacional normalizado (3FN)
-- Se ejecuta primero (prefijo 01_) al inicializar MySQL.
-- ============================================================
CREATE DATABASE IF NOT EXISTS torneos_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE torneos_db;

-- ---------- Tablas de referencia ----------
CREATE TABLE IF NOT EXISTS roles (
    id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipos_torneo (
    id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id, nombre) VALUES
    (1, 'administrador'), (2, 'organizador'), (3, 'participante');
INSERT IGNORE INTO tipos_torneo (id, nombre) VALUES
    (1, 'liga'), (2, 'eliminacion'), (3, 'suizo');

-- ---------- Usuarios ----------
CREATE TABLE IF NOT EXISTS usuarios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id            TINYINT UNSIGNED NOT NULL,
    nombre_usuario    VARCHAR(50)  NOT NULL UNIQUE,
    email             VARCHAR(150) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME NULL,
    fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------- Torneos ----------
CREATE TABLE IF NOT EXISTS torneos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizador_id INT UNSIGNED NOT NULL,
    tipo_id        TINYINT UNSIGNED NOT NULL,
    nombre         VARCHAR(60) NOT NULL,
    ida_vuelta     BOOLEAN NOT NULL DEFAULT FALSE,
    rondas_suizo   TINYINT UNSIGNED NULL,
    estado         ENUM('borrador','publicado','cerrado') NOT NULL DEFAULT 'borrador',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_torneos_organizador FOREIGN KEY (organizador_id) REFERENCES usuarios(id),
    CONSTRAINT fk_torneos_tipo        FOREIGN KEY (tipo_id)        REFERENCES tipos_torneo(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipos (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS participantes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(40) NOT NULL,
    usuario_id INT UNSIGNED NULL,
    equipo_id  INT UNSIGNED NULL,
    CONSTRAINT fk_part_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_part_equipo  FOREIGN KEY (equipo_id)  REFERENCES equipos(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripciones (
    torneo_id       INT UNSIGNED NOT NULL,
    participante_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (torneo_id, participante_id),
    CONSTRAINT fk_insc_torneo FOREIGN KEY (torneo_id)       REFERENCES torneos(id)       ON DELETE CASCADE,
    CONSTRAINT fk_insc_part   FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Rondas, enfrentamientos y resultados ----------
CREATE TABLE IF NOT EXISTS rondas (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT UNSIGNED NOT NULL,
    numero    TINYINT UNSIGNED NOT NULL,
    estado    ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    UNIQUE KEY uq_ronda (torneo_id, numero),
    CONSTRAINT fk_rondas_torneo FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enfrentamientos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ronda_id          INT UNSIGNED NOT NULL,
    participante_a_id INT UNSIGNED NOT NULL,
    participante_b_id INT UNSIGNED NULL,          -- NULL = pase libre (BYE)
    fecha_hora        DATETIME NULL,
    CONSTRAINT fk_enf_ronda FOREIGN KEY (ronda_id)          REFERENCES rondas(id) ON DELETE CASCADE,
    CONSTRAINT fk_enf_a     FOREIGN KEY (participante_a_id) REFERENCES participantes(id),
    CONSTRAINT fk_enf_b     FOREIGN KEY (participante_b_id) REFERENCES participantes(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resultados (
    enfrentamiento_id INT UNSIGNED PRIMARY KEY,
    puntos_a          SMALLINT UNSIGNED NOT NULL,
    puntos_b          SMALLINT UNSIGNED NOT NULL,
    registrado_por    INT UNSIGNED NOT NULL,
    fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_enf FOREIGN KEY (enfrentamiento_id) REFERENCES enfrentamientos(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_usr FOREIGN KEY (registrado_por)    REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------- Auditoria (historial de cambios en la base) ----------
CREATE TABLE IF NOT EXISTS auditoria (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    accion     VARCHAR(30) NOT NULL,
    entidad    VARCHAR(40) NOT NULL,
    entidad_id VARCHAR(40) NULL,
    detalle    JSON NULL,
    ip         VARCHAR(45) NULL,
    fecha      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_fecha (fecha),
    INDEX idx_auditoria_entidad (entidad, entidad_id)
) ENGINE=InnoDB;

-- ---------- Vistas ----------
CREATE OR REPLACE VIEW v_partidos AS
SELECT e.id AS enfrentamiento_id, r.torneo_id, r.numero AS ronda,
       e.participante_a_id AS a_id, e.participante_b_id AS b_id,
       res.puntos_a, res.puntos_b
FROM enfrentamientos e
JOIN rondas r            ON r.id = e.ronda_id
LEFT JOIN resultados res ON res.enfrentamiento_id = e.id;

-- Posiciones: victoria 3, empate 1, derrota 0; el pase libre cuenta como victoria.
CREATE OR REPLACE VIEW v_posiciones AS
SELECT torneo_id, participante_id,
       SUM(pj) AS pj, SUM(g) AS g, SUM(e) AS e, SUM(p) AS p,
       SUM(gf) AS gf, SUM(gc) AS gc, SUM(gf) - SUM(gc) AS dg, SUM(pts) AS pts
FROM (
    SELECT torneo_id, a_id AS participante_id, 1 AS pj,
           (puntos_a > puntos_b) AS g, (puntos_a = puntos_b) AS e, (puntos_a < puntos_b) AS p,
           puntos_a AS gf, puntos_b AS gc,
           CASE WHEN puntos_a > puntos_b THEN 3 WHEN puntos_a = puntos_b THEN 1 ELSE 0 END AS pts
    FROM v_partidos WHERE b_id IS NOT NULL AND puntos_a IS NOT NULL
    UNION ALL
    SELECT torneo_id, b_id, 1,
           (puntos_b > puntos_a), (puntos_a = puntos_b), (puntos_b < puntos_a),
           puntos_b, puntos_a,
           CASE WHEN puntos_b > puntos_a THEN 3 WHEN puntos_a = puntos_b THEN 1 ELSE 0 END
    FROM v_partidos WHERE b_id IS NOT NULL AND puntos_a IS NOT NULL
    UNION ALL
    SELECT torneo_id, a_id, 1, 1, 0, 0, 0, 0, 3
    FROM v_partidos WHERE b_id IS NULL
) t
GROUP BY torneo_id, participante_id;

-- ---------- Triggers de auditoria ----------
-- La aplicacion debe ejecutar  SET @usuario_id = <id>;  al abrir cada conexion
-- para que el cambio quede asociado a la persona que lo hizo.
DELIMITER $$

DROP TRIGGER IF EXISTS tr_usuarios_ai $$
CREATE TRIGGER tr_usuarios_ai AFTER INSERT ON usuarios FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'INSERT', 'usuarios', NEW.id,
            JSON_OBJECT('nombre_usuario', NEW.nombre_usuario, 'rol_id', NEW.rol_id));
END $$

DROP TRIGGER IF EXISTS tr_usuarios_au $$
CREATE TRIGGER tr_usuarios_au AFTER UPDATE ON usuarios FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'UPDATE', 'usuarios', NEW.id,
            JSON_OBJECT('rol_antes', OLD.rol_id, 'rol_despues', NEW.rol_id,
                        'clave_cambiada', OLD.password_hash <> NEW.password_hash));
END $$

DROP TRIGGER IF EXISTS tr_usuarios_ad $$
CREATE TRIGGER tr_usuarios_ad AFTER DELETE ON usuarios FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'DELETE', 'usuarios', OLD.id,
            JSON_OBJECT('nombre_usuario', OLD.nombre_usuario));
END $$

DROP TRIGGER IF EXISTS tr_torneos_ai $$
CREATE TRIGGER tr_torneos_ai AFTER INSERT ON torneos FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'INSERT', 'torneos', NEW.id,
            JSON_OBJECT('nombre', NEW.nombre, 'tipo_id', NEW.tipo_id, 'estado', NEW.estado));
END $$

DROP TRIGGER IF EXISTS tr_torneos_au $$
CREATE TRIGGER tr_torneos_au AFTER UPDATE ON torneos FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'UPDATE', 'torneos', NEW.id,
            JSON_OBJECT('nombre_antes', OLD.nombre, 'nombre_despues', NEW.nombre,
                        'estado_antes', OLD.estado, 'estado_despues', NEW.estado));
END $$

DROP TRIGGER IF EXISTS tr_torneos_ad $$
CREATE TRIGGER tr_torneos_ad AFTER DELETE ON torneos FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'DELETE', 'torneos', OLD.id, JSON_OBJECT('nombre', OLD.nombre));
END $$

DROP TRIGGER IF EXISTS tr_resultados_ai $$
CREATE TRIGGER tr_resultados_ai AFTER INSERT ON resultados FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (COALESCE(@usuario_id, NEW.registrado_por), 'INSERT', 'resultados', NEW.enfrentamiento_id,
            JSON_OBJECT('puntos_a', NEW.puntos_a, 'puntos_b', NEW.puntos_b));
END $$

DROP TRIGGER IF EXISTS tr_resultados_au $$
CREATE TRIGGER tr_resultados_au AFTER UPDATE ON resultados FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (COALESCE(@usuario_id, NEW.registrado_por), 'UPDATE', 'resultados', NEW.enfrentamiento_id,
            JSON_OBJECT('antes', JSON_ARRAY(OLD.puntos_a, OLD.puntos_b),
                        'despues', JSON_ARRAY(NEW.puntos_a, NEW.puntos_b)));
END $$

DROP TRIGGER IF EXISTS tr_resultados_ad $$
CREATE TRIGGER tr_resultados_ad AFTER DELETE ON resultados FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle)
    VALUES (@usuario_id, 'DELETE', 'resultados', OLD.enfrentamiento_id,
            JSON_OBJECT('puntos_a', OLD.puntos_a, 'puntos_b', OLD.puntos_b));
END $$

DELIMITER ;
