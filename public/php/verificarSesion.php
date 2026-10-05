<?php
 
session_start();
 
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.html');
    exit;
}
 
// Opcional: expirar la sesión tras un tiempo de inactividad (ej: 30 minutos)
$TIEMPO_MAXIMO_INACTIVIDAD = 1800; // segundos
 
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > $TIEMPO_MAXIMO_INACTIVIDAD) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.html?expirado=1');
    exit;
}
 
$_SESSION['ultima_actividad'] = time();
 
?>