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

if (botonSecreto && osakaImage) {
    botonSecreto.addEventListener('click', () => {
        osakaImage.classList.toggle('visible');
    });
}