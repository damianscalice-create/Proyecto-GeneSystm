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
$torneoId = (int)$torneoId;

try {
    // Transacción + FOR UPDATE: bloquea la fila del torneo mientras se comprueba el cupo,
    // así dos personas anotándose a la vez no pueden pasarse del límite.
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT cantidad_participantes FROM torneos WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $torneoId]);
    $cupo = $stmt->fetchColumn();

    if ($cupo === false) {
        responder(404, false, "El torneo no existe.");
    }

    $stmt = $pdo->prepare("SELECT 1 FROM inscripciones WHERE torneo_id = :t AND usuario_id = :u");
    $stmt->execute([':t' => $torneoId, ':u' => $uid]);
    if ($stmt->fetchColumn()) {
        responder(409, false, "Ya estás inscripto en este torneo.");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE torneo_id = :t");
    $stmt->execute([':t' => $torneoId]);
    if ((int)$stmt->fetchColumn() >= (int)$cupo) {
        responder(409, false, "El cupo de este torneo está completo.");
    }

    $stmt = $pdo->prepare("INSERT INTO inscripciones (torneo_id, usuario_id) VALUES (:t, :u)");
    $stmt->execute([':t' => $torneoId, ':u' => $uid]);
    $pdo->commit();

    echo json_encode(["exito" => true, "mensaje" => "¡Te inscribiste al torneo!"]);

} catch (PDOException $e) {
    error_log("inscribirse: " . $e->getMessage());
    responder(500, false, "No se pudo completar la inscripción.");
}
