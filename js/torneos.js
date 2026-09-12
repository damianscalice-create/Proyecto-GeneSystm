const deporteSelect = document.getElementById('deporte');
const nombreEventoInput = document.getElementById('nombreEvento');
const cantidadInput = document.getElementById('cantidad');
const contenedorCantidad = document.getElementById('contenedorCantidad');
const resultadoTorneo = document.getElementById('resultadoTorneo');
const resultadoTexto = document.getElementById('resultadoTexto');
const botonConfirmar = document.getElementById('confirmarTorneo');
const mensajeConfirmacion = document.getElementById('mensajeConfirmacion');
let temporizadorConfirmacion;

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

// Devuelve el valor elegido en el sub-select del deporte activo
// (género para Futbol/Futsala/Tenis, o el juego para E-sports/Mental)
function obtenerDetalleDeporte() {
    const idContenedor = mapaDeportes[deporteSelect?.value];
    if (!idContenedor) return '';

    const contenedor = document.getElementById(idContenedor);
    const subSelect = contenedor ? contenedor.querySelector('select') : null;

    return subSelect ? subSelect.value : '';
}

function mostrarMensaje(texto, tipo = 'info') {
    if (!mensajeConfirmacion) return;

    mensajeConfirmacion.textContent = texto;
    mensajeConfirmacion.classList.remove('visible', 'error', 'exito');
    void mensajeConfirmacion.offsetWidth;
    mensajeConfirmacion.classList.add('visible', tipo === 'error' ? 'error' : 'exito');

    clearTimeout(temporizadorConfirmacion);
    temporizadorConfirmacion = setTimeout(() => {
        mensajeConfirmacion.classList.remove('visible');
    }, 3000);
}

async function mostrarMensajeConfirmacion() {
    if (!mensajeConfirmacion || !deporteSelect || !cantidadInput || !nombreEventoInput) {
        return;
    }

    const nombreEvento = nombreEventoInput.value.trim();
    const deporteNombre = deporteSelect.options[deporteSelect.selectedIndex]?.text || '';
    const cantidad = cantidadInput.value;
    const detalle = obtenerDetalleDeporte();

    if (!nombreEvento) {
        mostrarMensaje('Ingresá un nombre de evento antes de confirmar.', 'error');
        return;
    }

    if (!deporteSelect.value) {
        mostrarMensaje('Seleccioná un deporte antes de confirmar.', 'error');
        return;
    }

    if (!cantidad) {
        mostrarMensaje('Selecciona una cantidad de participantes antes de confirmar.', 'error');
        return;
    }

    // Deshabilitar el botón mientras se guarda, para evitar doble envío
    if (botonConfirmar) {
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Guardando...';
    }

    try {
        const respuesta = await fetch('guardar_torneo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombreEvento: nombreEvento,
                deporte: deporteNombre,
                detalle: detalle,
                cantidad: cantidad,
            }),
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            mostrarMensaje(`✅ Confirmaste ${deporteNombre} para ${cantidad} participantes. Guardado correctamente.`, 'exito');
        } else {
            mostrarMensaje(`❌ ${resultado.mensaje}`, 'error');
        }

    } catch (error) {
        mostrarMensaje('❌ No se pudo conectar con el servidor. Intenta nuevamente.', 'error');
    } finally {
        if (botonConfirmar) {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Confirmar selección';
        }
    }
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