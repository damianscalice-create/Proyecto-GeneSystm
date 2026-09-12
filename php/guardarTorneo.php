<?php
// ============================================================
// Endpoint: guardar_torneo.php
// Recibe los datos del formulario de torneos.html (vía fetch/JSON)
// y los guarda en la base de datos.
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido."]);
    exit;
}

// Leer el JSON enviado desde el JavaScript
$datos = json_decode(file_get_contents('php://input'), true);

$nombreEvento = trim($datos['nombreEvento'] ?? '');
$deporte = trim($datos['deporte'] ?? '');
$detalle = trim($datos['detalle'] ?? '');
$cantidad = $datos['cantidad'] ?? null;

// ---------------- Validaciones ----------------
$errores = [];

if ($nombreEvento === '') {
    $errores[] = "El nombre del evento es obligatorio.";
}

if ($deporte === '') {
    $errores[] = "Debe seleccionar un deporte.";
}

if (!is_numeric($cantidad) || (int)$cantidad < 2) {
    $errores[] = "La cantidad de participantes debe ser un número mayor o igual a 2.";
} elseif ((int)$cantidad % 2 !== 0) {
    $errores[] = "La cantidad de participantes debe ser un número par.";
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => implode(' ', $errores)]);
    exit;
}

// ---------------- Insertar en la base de datos ----------------
try {
    $stmt = $pdo->prepare(
        "INSERT INTO torneos (nombre_evento, deporte, detalle, cantidad_participantes)
         VALUES (:nombre_evento, :deporte, :detalle, :cantidad)"
    );

    $stmt->execute([
        ':nombre_evento' => $nombreEvento,
        ':deporte' => $deporte,
        ':detalle' => $detalle !== '' ? $detalle : null,
        ':cantidad' => (int)$cantidad,
    ]);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Torneo guardado correctamente.",
        "id" => $pdo->lastInsertId(),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo guardar el torneo. Intente nuevamente."]);
}