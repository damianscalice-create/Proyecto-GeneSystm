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
$rol = $datos['rol'] ?? '';

if (!is_numeric($id) || !in_array($rol, ['usuario', 'admin'], true)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "Datos inválidos."]);
    exit;
}

// Evita que un admin se quite el rol a sí mismo y deje el sistema sin administradores
if ((int)$id === (int)$_SESSION['usuario_id']) {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No podés cambiar tu propio rol."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => (int)$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["exito" => false, "mensaje" => "El usuario no existe."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET rol = :rol WHERE id = :id");
    $stmt->execute([':rol' => $rol, ':id' => (int)$id]);
    echo json_encode(["exito" => true, "mensaje" => "Rol actualizado."]);
} catch (PDOException $e) {
    error_log("adminCambiarRol: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo cambiar el rol."]);
}
