<?php
require_once __DIR__ . '/php/verificarAdmin.php';
exigirAdmin($pdo, false);   // si no es admin, redirige a login.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" href="assets/bombilla.png" type="image/x-icon">
    <title>Panel de administración</title>
</head>
<body>
    <header class="admin-top">
        <h1>Panel de administración</h1>
        <div>
            <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'admin'); ?></strong>
            · <a href="torneos.html">Volver al sitio</a>
            · <a href="#" id="cerrarSesion">Cerrar sesión</a>
        </div>
    </header>

    <main>
        <section class="admin-stats" id="estadisticas"></section>

        <section>
            <h2>Usuarios</h2>
            <div class="tabla-wrap" id="tablaUsuarios">Cargando...</div>
        </section>

        <section>
            <h2>Torneos</h2>
            <div class="tabla-wrap" id="tablaTorneos">Cargando...</div>
        </section>

        <div id="mensajeAdmin" class="mensaje-admin" aria-live="polite"></div>
    </main>

    <script src="js/admin.js"></script>
</body>
</html>
