<?php

// Registra un nuevo usuario con contraseña hasheada
 
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido."]);
    exit;
}
 
$datos = json_decode(file_get_contents('php://input'), true);
 
$nombreUsuario = trim($datos['nombreUsuario'] ?? '');
$email = trim($datos['email'] ?? '');
$password = $datos['password'] ?? '';
$password2 = $datos['password2'] ?? '';
 
// ---------------- Validaciones ----------------
$errores = [];
 
if (strlen($nombreUsuario) < 3) {
    $errores[] = "El nombre de usuario debe tener al menos 3 caracteres.";
}
 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El email no es válido.";
}
 
if (strlen($password) < 8) {
    $errores[] = "La contraseña debe tener al menos 8 caracteres.";
}
 
if ($password !== $password2) {
    $errores[] = "Las contraseñas no coinciden.";
}
 
if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => implode(' ', $errores)]);
    exit;
}
 
// ---------------- Verificar que no exista esa cuenta ----------------
try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :u OR email = :e");
    $stmt->execute([':u' => $nombreUsuario, ':e' => $email]);
 
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["exito" => false, "mensaje" => "Ese usuario o email ya está registrado."]);
        exit;
    }
 
    // ---------------- Guardar con contraseña hasheada ----------------
    $hash = password_hash($password, PASSWORD_DEFAULT);
 
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nombre_usuario, email, password_hash) VALUES (:u, :e, :p)"
    );
    $stmt->execute([':u' => $nombreUsuario, ':e' => $email, ':p' => $hash]);
 
    echo json_encode(["exito" => true, "mensaje" => "Usuario registrado correctamente. Ya podés iniciar sesión."]);
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo completar el registro. Intente nuevamente."]);
}

?>