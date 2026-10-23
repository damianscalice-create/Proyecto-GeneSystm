<?php
// Incluir al principio de TODA página o endpoint de administración.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';

function exigirAdmin(PDO $pdo, bool $respuestaJson = true): void {
    $id = $_SESSION['usuario_id'] ?? null;
    $rol = null;

    if ($id) {
        // El rol se consulta en la base en cada petición (no se confía solo en la sesión),
        // así a un admin al que le quitan el rol se le corta el acceso enseguida.
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $rol = $stmt->fetchColumn();
    }

    if ($rol !== 'admin') {
        if ($respuestaJson) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["exito" => false, "mensaje" => "Acceso restringido a administradores."]);
        } else {
            header('Location: login.html');
        }
        exit;
    }
}
