<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido."]);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);

$nombreEvento = trim($datos['nombreEvento'] ?? '');
$deporte = trim($datos['deporte'] ?? '');
$detalle = trim($datos['detalle'] ?? '');
$cantidad = $datos['cantidad'] ?? null;

// Tu JS manda el texto del deporte (option.text), no el value1/value2...
$deportesValidos = ['Futbol', 'Futbol Sala', 'Tennis', 'E-sports', 'Juego de mesa'];

$errores = [];
if ($nombreEvento === '') {
    $errores[] = "El nombre del evento es obligatorio.";
}
if (!in_array($deporte, $deportesValidos, true)) {
    $errores[] = "Debe seleccionar un deporte válido.";
}
if (!is_numeric($cantidad) || (int)$cantidad < 2) {
    $errores[] = "La cantidad debe ser un número mayor o igual a 2.";
} elseif ((int)$cantidad % 2 !== 0) {
    $errores[] = "La cantidad debe ser un número par.";
}

if ($errores) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => implode(' ', $errores)]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO torneos (nombre_evento, deporte, detalle, cantidad_participantes, usuario_id)
         VALUES (:nombre_evento, :deporte, :detalle, :cantidad, :usuario_id)"
    );
    $stmt->execute([
        ':nombre_evento' => $nombreEvento,
        ':deporte' => $deporte,
        ':detalle' => $detalle !== '' ? $detalle : null,
        ':cantidad' => (int)$cantidad,
        ':usuario_id' => $_SESSION['usuario_id'] ?? null,
    ]);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Torneo guardado correctamente.",
        "id" => $pdo->lastInsertId(),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo guardar el torneo."]);
}

?>