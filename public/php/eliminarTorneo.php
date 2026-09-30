<?php

// Elimina un torneo, solo si pertenece al usuario logueado
 
session_start();
header('Content-Type: application/json; charset=utf-8');
 
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["exito" => false, "mensaje" => "Debés iniciar sesión."]);
    exit;
}
 
require_once 'config.php';
 
$datos = json_decode(file_get_contents('php://input'), true);
$id = $datos['id'] ?? null;
 
if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "ID de torneo inválido."]);
    exit;
}
 
try {
    // Solo permite borrar torneos creados por el propio usuario
    $stmt = $pdo->prepare("DELETE FROM torneos WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([':id' => (int)$id, ':usuario_id' => $_SESSION['usuario_id']]);
 
    if ($stmt->rowCount() > 0) {
        echo json_encode(["exito" => true, "mensaje" => "Torneo eliminado."]);
    } else {
        http_response_code(403);
        echo json_encode(["exito" => false, "mensaje" => "No podés eliminar un torneo que no creaste vos."]);
    }
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo eliminar el torneo."]);
}
 