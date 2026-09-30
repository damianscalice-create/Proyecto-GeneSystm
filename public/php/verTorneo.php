<?php require_once 'verificar_sesion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/torneos.css">
    <link rel="icon" href="assets/bombilla.png" type="image/x-icon">
    <title>Torneos guardados</title>
    <style>
        table.tabla-torneos { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.tabla-torneos th, table.tabla-torneos td {
            border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px;
        }
        table.tabla-torneos th { background-color: #1F4E79; color: white; }
        table.tabla-torneos tr:nth-child(even) { background-color: #f2f2f2; }
        .btn-eliminar {
            background-color: #c00000; color: white; border: none;
            padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 13px;
        }
        .btn-eliminar:hover { background-color: #900000; }
        .sin-torneos { text-align: center; color: #666; padding: 30px 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    </style>
</head>
<body>
 
    <button type="button" id="botonAbrir">
        <img src="assets/bombilla.png" alt="" srcset="">
    </button>
 
    <nav id="miSidebar" class="sidebar" aria-label="Navegación principal">
        <button id="botonCerrar" class="close-btn" aria-label="Cerrar sidebar" title="Cerrar">&times;</button>
        <ul>
            <li><a href="index.html">Inicio</a></li>
            <li><a href="sobreNosotros.html">Sobre nosotros</a></li>
            <li><a href="torneos.php">Crear torneo</a></li>
            <li><a href="ver_torneos.php">Torneos guardados</a></li>
        </ul>
    </nav>
 
    <div class="Borde_lol1">
        <div class="top-bar">
            <h2 class="titulo" style="margin:0;">Torneos guardados</h2>
            <div>
                Hola, <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong>
                — <a href="#" id="cerrarSesion">Cerrar sesión</a>
            </div>
        </div>
 
        <p><a href="torneos.php" class="btn-confirmar" style="display:inline-block; text-decoration:none;">+ Crear nuevo torneo</a></p>
 
        <div id="listaTorneos">Cargando torneos...</div>
    </div>
 
    <script src="js/ver_torneos.js"></script>
    <script>
        document.getElementById('cerrarSesion')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('logout.php', { method: 'POST' });
            window.location.href = 'login.html';
        });
 
        /* Sidebar */
        const botonAbrir = document.getElementById('botonAbrir');
        const botonCerrar = document.getElementById('botonCerrar');
        const sidebar = document.getElementById('miSidebar');
        if (botonAbrir && botonCerrar && sidebar) {
            botonAbrir.addEventListener('click', () => sidebar.classList.toggle('activo'));
            botonCerrar.addEventListener('click', () => sidebar.classList.remove('activo'));
        }
    </script>
</body>
</html>

?>