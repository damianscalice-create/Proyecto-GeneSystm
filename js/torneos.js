const deporteSelect = document.getElementById('deporte');
const nombreEventoInput = document.getElementById('nombreEvento');
const cantidadInput = document.getElementById('cantidad');
const contenedorCantidad = document.getElementById('contenedorCantidad');
const resultadoTorneo = document.getElementById('resultadoTorneo');
const resultadoTexto = document.getElementById('resultadoTexto');
const botonConfirmar = document.getElementById('confirmarTorneo');
const mensajeConfirmacion = document.getElementById('mensajeConfirmacion');

function actualizarVisibilidadCantidad() {
    if (!contenedorCantidad || !nombreEventoInput || !deporteSelect) {
        return;
    }

    const hayNombre = nombreEventoInput.value.trim() !== '';
    const hayDeporte = deporteSelect.value !== '';

    if (hayNombre && hayDeporte) {
        contenedorCantidad.classList.add('visible');
    } else {
        contenedorCantidad.classList.remove('visible');
        if (cantidadInput) {
            cantidadInput.value = '';
        }
    }
}

function mostrarResultadoTorneo() {
    if (!resultadoTorneo || !resultadoTexto || !deporteSelect || !cantidadInput) {
        return;
    }

    const deporte = deporteSelect.value;
    const cantidad = cantidadInput.value;

    if (!cantidad) {
        resultadoTexto.textContent = 'Selecciona un deporte y la cantidad de participantes para ver la fecha y la hora.';
        resultadoTorneo.classList.remove('visible');
        return;
    }

    const datos = typeof datosTorneos !== 'undefined' ? datosTorneos[deporte]?.[cantidad] : null;

    if (!datos) {
        resultadoTexto.textContent = 'Ajusta la selección: la fecha y hora aún no están disponibles para esta combinación.';
        resultadoTorneo.classList.remove('visible');
        return;
    }

    resultadoTexto.innerHTML = `${datos.mensaje}<br><strong>${datos.fecha}</strong> · ${datos.hora}`;

    resultadoTorneo.classList.remove('visible');
    void resultadoTorneo.offsetWidth;
    resultadoTorneo.classList.add('visible');
}

function mostrarMensajeConfirmacion() {
    if (!mensajeConfirmacion || !deporteSelect || !cantidadInput) {
        return;
    }

    const deporteNombre = deporteSelect.options[deporteSelect.selectedIndex].text;
    const cantidad = cantidadInput.value;

    if (!cantidad) {
        mensajeConfirmacion.textContent = 'Selecciona una cantidad de participantes antes de confirmar.';
        mensajeConfirmacion.classList.add('visible');
        return;
    }

    mensajeConfirmacion.textContent = `Confirmaste ${deporteNombre} para ${cantidad} participantes.`;
    mensajeConfirmacion.classList.remove('visible');
    void mensajeConfirmacion.offsetWidth;
    mensajeConfirmacion.classList.add('visible');
}

if (deporteSelect) {
    deporteSelect.addEventListener('change', () => {
        mostrarResultadoTorneo();
        actualizarVisibilidadCantidad();
    });
}

if (nombreEventoInput) {
    nombreEventoInput.addEventListener('input', actualizarVisibilidadCantidad);
}

if (cantidadInput) {
    cantidadInput.addEventListener('input', mostrarResultadoTorneo);
}

mostrarResultadoTorneo();
actualizarVisibilidadCantidad();

if (botonConfirmar) {
    botonConfirmar.addEventListener('click', mostrarMensajeConfirmacion);
}

/* Script para abrir el sidebar */
const botonAbrir = document.getElementById('botonAbrir');
const botonCerrar = document.getElementById('botonCerrar');
const sidebar = document.getElementById('miSidebar');

if (botonAbrir && botonCerrar && sidebar) {
    botonAbrir.addEventListener('click', () => {
        sidebar.classList.toggle('activo');
    });

    botonCerrar.addEventListener('click', () => {
        sidebar.classList.remove('activo');
    });
}

const contenidos = document.querySelectorAll('.deporte-content');

const mapaDeportes = {
    value1: 'contenedor-futbol',
    value2: 'contenedor-futSala',
    value3: 'contenedor-tennis',
    value4: 'contenedor-Esports',
    value5: 'contenedor-mental'
};

function actualizarContenidoDeporte() {
    contenidos.forEach(contenido => contenido.classList.remove('active'));

    const idMostrar = mapaDeportes[deporteSelect?.value];
    if (idMostrar) {
        const elemento = document.getElementById(idMostrar);
        if (elemento) {
            elemento.classList.add('active');
        }
    }
}

if (deporteSelect) {
    deporteSelect.addEventListener('change', () => {
        actualizarContenidoDeporte();
        actualizarVisibilidadCantidad();
    });
    actualizarContenidoDeporte();
    actualizarVisibilidadCantidad();
}

