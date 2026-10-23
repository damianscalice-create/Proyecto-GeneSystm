<?php
// Helper para endpoints JSON que requieren usuario logueado.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json; charset=utf-8');

// Responde en JSON y corta. Si había una transacción abierta, la deshace.
function responder(int $codigo, bool $exito, string $mensaje, array $extra = []): void {
    global $pdo;
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($codigo);
    echo json_encode(array_merge(["exito" => $exito, "mensaje" => $mensaje], $extra));
    exit;
}

function exigirLogin(): int {
    global $pdo;
    $id = $_SESSION['usuario_id'] ?? null;

    if ($id) {
        // Verifica que el usuario siga existiendo (por si un admin lo eliminó con la sesión abierta)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn()) {
            return (int)$id;
        }
        $_SESSION = [];
        session_destroy();
    }

    responder(401, false, "Debés iniciar sesión.");
    return 0; // nunca se alcanza
}
