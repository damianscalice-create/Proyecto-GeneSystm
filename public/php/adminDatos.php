<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/verificarAdmin.php';
exigirAdmin($pdo);

try {
    $usuarios = $pdo->query(
        "SELECT id, nombre_usuario, email, rol, fecha_registro FROM usuarios ORDER BY id"
    )->fetchAll();

    $torneos = $pdo->query(
        "SELECT t.id, t.nombre_evento, t.deporte, t.detalle, t.cantidad_participantes,
                t.fecha_creacion, u.nombre_usuario AS creador
         FROM torneos t LEFT JOIN usuarios u ON t.usuario_id = u.id
         ORDER BY t.fecha_creacion DESC"
    )->fetchAll();

    echo json_encode([
        "exito" => true,
        "usuarioActualId" => (int)$_SESSION['usuario_id'],
        "usuarios" => $usuarios,
        "torneos" => $torneos,
    ]);
} catch (PDOException $e) {
    error_log("adminDatos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudieron cargar los datos."]);
}
