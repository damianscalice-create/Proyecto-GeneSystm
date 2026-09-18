<?php

// Devuelve todos los torneos guardados, con el nombre de quién los creó
 
session_start();
header('Content-Type: application/json; charset=utf-8');
 
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["exito" => false, "mensaje" => "Debés iniciar sesión."]);
    exit;
}
 
require_once 'config.php';
 
try {
    $stmt = $pdo->query(
        "SELECT t.id, t.nombre_evento, t.deporte, t.detalle, t.cantidad_participantes,
                t.fecha_creacion, t.usuario_id, u.nombre_usuario AS creador
         FROM torneos t
         LEFT JOIN usuarios u ON t.usuario_id = u.id
         ORDER BY t.fecha_creacion DESC"
    );
    $torneos = $stmt->fetchAll();
 
    echo json_encode([
        "exito" => true,
        "torneos" => $torneos,
        "usuarioActualId" => (int)$_SESSION['usuario_id'],
    ]);
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudieron cargar los torneos."]);
}

?>