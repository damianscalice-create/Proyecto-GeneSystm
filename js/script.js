/* Script para abrir el sidebar */

const botonAbrir = document.getElementById('botonAbrir');
const botonCerrar = document.getElementById('botonCerrar');
const sidebar = document.getElementById('miSidebar');

if (botonAbrir && botonCerrar && sidebar) {
    botonAbrir.addEventListener('click', () => {
        sidebar.classList.add('activo');
    });

    botonCerrar.addEventListener('click', () => {
        sidebar.classList.remove('activo');
    });
}

const botonColor = document.getElementById('botonColor');
const cuerpo = document.body;
const imagenBombilla = document.getElementById('imagenBombilla');

if (botonColor) {
    botonColor.addEventListener('click', () => {
        const modoOscuroActivo = cuerpo.classList.toggle('modoOscuro');
        botonColor.textContent = modoOscuroActivo ? 'Modo Claro' : 'Modo Oscuro';

        if (imagenBombilla) {
            imagenBombilla.src = modoOscuroActivo
                ? 'assets/Bombilla_on.png'
                : 'assets/bombilla.png';
        }
    });
}