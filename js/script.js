/* Script para abrir el sidebar */

const botonAbrir = document.getElementById('botonAbrir');
const botonCerrar = document.getElementById('botonCerrar');
const sidebar = document.getElementById('miSidebar');
const bombilla = botonAbrir?.querySelector('img');

if (botonAbrir && botonCerrar && sidebar && bombilla) {
    const actualizarBombilla = () => {
        bombilla.src = sidebar.classList.contains('activo')
            ? 'assets/Bombilla_on.png'
            : 'assets/bombilla.png';
        bombilla.classList.remove('cambio-bombilla');
        void bombilla.offsetWidth;
        bombilla.classList.add('cambio-bombilla');
    };

    botonAbrir.addEventListener('click', () => {
        sidebar.classList.add('activo');
        actualizarBombilla();
    });

    botonCerrar.addEventListener('click', () => {
        sidebar.classList.remove('activo');
        actualizarBombilla();
    });
}
