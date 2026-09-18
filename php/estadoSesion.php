<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'iniciada' => isset($_SESSION['usuario_id']),
    'nombreUsuario' => $_SESSION['nombre_usuario'] ?? null,
]);
