<?php

$DB_HOST = getenv('DB_HOST') ?: "10.0.0.130";
$DB_NAME = getenv('DB_NAME') ?: "torneos_db";
$DB_USER = getenv('DB_USER') ?: "GeneSys";
$DB_PASS = getenv('DB_PASS') ?: "Quesito53";
 
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => "Error de conexión a la base de datos."]);
    exit;
}

?>
