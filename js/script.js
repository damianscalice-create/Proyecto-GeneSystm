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
const iconoModo = document.getElementById('iconoModo');

if (botonColor) {
    botonColor.addEventListener('click', () => {
        const modoOscuroActivo = cuerpo.classList.toggle('modoOscuro');
        botonColor.setAttribute('aria-label', modoOscuroActivo ? 'Activar modo claro' : 'Activar modo oscuro');

        if (imagenBombilla) {
            imagenBombilla.src = modoOscuroActivo
                ? 'assets/Bombilla_on.png'
                : 'assets/bombilla.png';
        }

        if (iconoModo) {
            iconoModo.src = modoOscuroActivo
                ? 'assets/claro.png'
                : 'assets/oscuro.png';
        }
    });
}