<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/verificarAdmin.php';
exigirAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido."]);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
$id = $datos['id'] ?? null;

if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "ID inválido."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM torneos WHERE id = :id");
    $stmt->execute([':id' => (int)$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["exito" => true, "mensaje" => "Torneo eliminado."]);
    } else {
        http_response_code(404);
        echo json_encode(["exito" => false, "mensaje" => "El torneo no existe."]);
    }
} catch (PDOException $e) {
    error_log("adminEliminarTorneo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo eliminar el torneo."]);
}
