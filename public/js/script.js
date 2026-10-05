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
        botonColor.title = modoOscuroActivo ? 'Activar modo claro' : 'Activar modo oscuro';
        botonColor.querySelector('span').textContent = modoOscuroActivo ? 'Modo claro' : 'Modo oscuro';

        if (imagenBombilla) {
            imagenBombilla.src = modoOscuroActivo
                ? 'assets/Bombilla_on.png'
                : 'assets/bombilla.png';
        }

        if (iconoModo) {
            iconoModo.src = modoOscuroActivo
                ? 'assets/claro.png'
                : 'assets/oscuro.png';
            iconoModo.classList.remove('cambioModo');
            requestAnimationFrame(() => iconoModo.classList.add('cambioModo'));
        }
    });
}

const botonUsuario = document.getElementById('botonUsuario');

if (botonUsuario) {
    fetch('php/estadoSesion.php', { credentials: 'same-origin' })
        .then((respuesta) => respuesta.json())
        .then((sesion) => {
            if (sesion.iniciada) {
                botonUsuario.setAttribute('aria-label', `Ver usuario ${sesion.nombreUsuario}`);
                botonUsuario.title = `Usuario: ${sesion.nombreUsuario}`;
            }
        })
        .catch(() => {
            botonUsuario.setAttribute('aria-label', 'Iniciar sesión');
        });

    botonUsuario.addEventListener('click', async () => {
        try {
            const respuesta = await fetch('php/estadoSesion.php', { credentials: 'same-origin' });
            const sesion = await respuesta.json();
            window.location.href = sesion.iniciada ? 'php/torneos.php' : 'login.html';
        } catch {
            window.location.href = 'login.html';
        }
    });
}

const botonSecreto = document.getElementById('botonSecreto');
const osakaImage = document.getElementById('osakaImage');
let position = 0;
let speed = 2;
let osakaTimeout;

if (botonSecreto && osakaImage) {
    botonSecreto.addEventListener('click', () => {
        if (osakaImage.classList.contains('visible')) {
            clearTimeout(osakaTimeout);
            osakaImage.classList.remove('visible');
            return;
        }

        osakaImage.classList.add('visible');
        osakaTimeout = setTimeout(() => {
            osakaImage.classList.remove('visible');
        }, 2000);
    });
}