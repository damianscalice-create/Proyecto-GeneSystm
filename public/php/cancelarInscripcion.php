<?php
require_once __DIR__ . '/exigirLogin.php';
$uid = exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, "Método no permitido.");
}

$datos = json_decode(file_get_contents('php://input'), true);
$torneoId = $datos['id'] ?? null;

if (!is_numeric($torneoId)) {
    responder(400, false, "Torneo inválido.");
}

try {
    // Solo borra la inscripción del usuario logueado, nunca la de otro
    $stmt = $pdo->prepare("DELETE FROM inscripciones WHERE torneo_id = :t AND usuario_id = :u");
    $stmt->execute([':t' => (int)$torneoId, ':u' => $uid]);

    if ($stmt->rowCount() === 0) {
        responder(404, false, "No estabas inscripto en ese torneo.");
    }
    echo json_encode(["exito" => true, "mensaje" => "Inscripción cancelada."]);

} catch (PDOException $e) {
    error_log("cancelarInscripcion: " . $e->getMessage());
    responder(500, false, "No se pudo cancelar la inscripción.");
}
