<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "mensaje" => "Método no permitido."]);
    exit;
}
 
$datos = json_decode(file_get_contents('php://input'), true);
 
$nombreUsuario = trim($datos['nombreUsuario'] ?? '');
$password = $datos['password'] ?? '';
 
if ($nombreUsuario === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["exito" => false, "mensaje" => "Ingresá usuario y contraseña."]);
    exit;
}
 
const MAX_INTENTOS = 5;
const MINUTOS_BLOQUEO = 15;
 
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :u");
    $stmt->execute([':u' => $nombreUsuario]);
    $usuario = $stmt->fetch();
 
    if (!$usuario) {
        // Mensaje genérico: no revelar si el usuario existe o no (buena práctica de seguridad)
        http_response_code(401);
        echo json_encode(["exito" => false, "mensaje" => "Usuario o contraseña incorrectos."]);
        exit;
    }
 
    // ---------------- Verifica si está bloqueado (fail2ban) ----------------
    if ($usuario['bloqueado_hasta'] !== null && strtotime($usuario['bloqueado_hasta']) > time()) {
        $minutosRestantes = ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
        http_response_code(429);
        echo json_encode([
            "exito" => false,
            "mensaje" => "Cuenta bloqueada temporalmente por intentos fallidos. Probá de nuevo en $minutosRestantes minuto(s)."
        ]);
        exit;
    }
 
    // ---------------- Verificar contraseña ----------------
    if (password_verify($password, $usuario['password_hash'])) {
 
        // Login correcto: resetear intentos fallidos
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id");
        $stmt->execute([':id' => $usuario['id']]);
 
        // Iniciar sesión
        session_regenerate_id(true); // evita fijación de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['logueado_en'] = time();
 
        echo json_encode([
            "exito" => true,
            "mensaje" => "Bienvenido, " . $usuario['nombre_usuario'] . ".",
        ]);
 
    } else {
        // Login incorrecto: incrementar contador de intentos
        $intentos = $usuario['intentos_fallidos'] + 1;
 
        if ($intentos >= MAX_INTENTOS) {
            $bloqueadoHasta = date('Y-m-d H:i:s', strtotime("+" . MINUTOS_BLOQUEO . " minutes"));
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = :i, bloqueado_hasta = :b WHERE id = :id");
            $stmt->execute([':i' => $intentos, ':b' => $bloqueadoHasta, ':id' => $usuario['id']]);
 
            http_response_code(429);
            echo json_encode([
                "exito" => false,
                "mensaje" => "Demasiados intentos fallidos. Cuenta bloqueada por " . MINUTOS_BLOQUEO . " minutos."
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = :i WHERE id = :id");
            $stmt->execute([':i' => $intentos, ':id' => $usuario['id']]);
 
            http_response_code(401);
            echo json_encode(["exito" => false, "mensaje" => "Usuario o contraseña incorrectos."]);
        }
    }
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "Error al iniciar sesión. Intente nuevamente."]);
}

?>