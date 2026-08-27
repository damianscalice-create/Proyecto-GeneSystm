const menuContainer = document.getElementById('menu-container');

if (menuContainer) {
    menuContainer.innerHTML = `
        <button type="button" id="botonAbrir">
            <img src="assets/bombilla.png" alt="Abrir menú">
        </button>
        <nav id="miSidebar" class="sidebar">
            <button id="botonCerrar" class="close-btn" aria-label="Cerrar sidebar" title="Cerrar">&times;</button>
            <ul>
                <li><a href="index.html">Inicio</a></li>
                <li><a href="sobreNosotros.html">Sobre nosotros</a></li>
                <li><a href="torneos.html">Torneos</a></li>
            </ul>
            <p>© 2026 GeneSysTM. Todos los derechos reservados.</p>
        </nav>
    `;

    const botonAbrir = document.getElementById('botonAbrir');
    const botonCerrar = document.getElementById('botonCerrar');
    const sidebar = document.getElementById('miSidebar');
    const bombilla = botonAbrir.querySelector('img');

    const actualizarBombilla = () => {
        bombilla.src = sidebar.classList.contains('activo')
            ? 'assets/Bombilla_on.png'
            : 'assets/bombilla.png';
        bombilla.classList.remove('cambio-bombilla');
        void bombilla.offsetWidth;
        bombilla.classList.add('cambio-bombilla');
    };

    botonAbrir.addEventListener('click', () => {
        sidebar.classList.toggle('activo');
        actualizarBombilla();
    });

    botonCerrar.addEventListener('click', () => {
        sidebar.classList.remove('activo');
        actualizarBombilla();
    });
}
