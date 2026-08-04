
const deporteSelect = document.getElementById('Juegos');
const cantidadSelect = document.getElementById('Concursantes');
const resultadoTorneo = document.getElementById('resultadoTorneo');
const resultadoTitulo = document.getElementById('resultadoTitulo');
const resultadoTexto = document.getElementById('resultadoTexto');
const botonConfirmar = document.getElementById('confirmarTorneo');
const mensajeConfirmacion = document.getElementById('mensajeConfirmacion');


const datosTorneos = {
    value1: {
        2: { titulo: 'Torneo de Fútbol', mensaje: 'La jornada comenzará con un encuentro inicial muy dinámico.', fecha: 'Sábado 9:00', hora: '09:00' },
        4: { titulo: 'Torneo de Fútbol', mensaje: 'Se organizará una llave rápida para que todos jueguen.', fecha: 'Domingo 12:30', hora: '12:30' },
        6: { titulo: 'Torneo de Fútbol', mensaje: 'Habrá un formato mixto con partidos cortos.', fecha: 'Lunes 18:00', hora: '18:00' }
    },
    value2: {
        2: { titulo: 'Torneo de Fútbol Sala', mensaje: 'El turno será ideal para una competencia cerrada y rápida.', fecha: 'Sábado 11:00', hora: '11:00' },
        4: { titulo: 'Torneo de Fútbol Sala', mensaje: 'Se armará una ronda con partidos intensos.', fecha: 'Domingo 14:00', hora: '14:00' },
        6: { titulo: 'Torneo de Fútbol Sala', mensaje: 'Habrá una fase de grupos para repartir el tiempo.', fecha: 'Martes 19:30', hora: '19:30' }
    },
    value3: {
        2: { titulo: 'Torneo de Tennis', mensaje: 'La cancha estará disponible para un duelo directo.', fecha: 'Viernes 17:00', hora: '17:00' },
        4: { titulo: 'Torneo de Tennis', mensaje: 'Se realizará una ronda de dobles muy entretenida.', fecha: 'Sábado 16:00', hora: '16:00' },
        6: { titulo: 'Torneo de Tennis', mensaje: 'Habrá varias partidas con descanso entre turno y turno.', fecha: 'Domingo 10:00', hora: '10:00' }
    },
    value4: {
        2: { titulo: 'Torneo E-sports', mensaje: 'La competencia empezará con un enfrentamiento inicial.', fecha: 'Viernes 20:00', hora: '20:00' },
        4: { titulo: 'Torneo E-sports', mensaje: 'Se dispondrá de una fase de eliminación directa.', fecha: 'Sábado 20:30', hora: '20:30' },
        6: { titulo: 'Torneo E-sports', mensaje: 'Habrá una mezcla de partidas clasificatorias y finales.', fecha: 'Domingo 19:00', hora: '19:00' }
    },
    value5: {
        2: { titulo: 'Torneo de Ajedrez', mensaje: 'La partida comenzará con un ritmo calmado y estratégico.', fecha: 'Lunes 17:30', hora: '17:30' },
        4: { titulo: 'Torneo de Ajedrez', mensaje: 'Se trabajará con una ronda de apertura dinámica.', fecha: 'Miércoles 18:30', hora: '18:30' },
        6: { titulo: 'Torneo de Ajedrez', mensaje: 'Habrá una fase más larga para pensar cada movimiento.', fecha: 'Jueves 20:00', hora: '20:00' }
    }
};


function mostrarResultadoTorneo() {
    if (!resultadoTorneo || !resultadoTitulo || !resultadoTexto || !deporteSelect || !cantidadSelect) {
        return;
    }

    const deporte = deporteSelect.value;
    const cantidad = cantidadSelect.value;

    if (!cantidad) {
        resultadoTitulo.textContent = 'Tu torneo está listo';
        resultadoTexto.textContent = 'Selecciona un deporte y la cantidad de participantes para ver la fecha y la hora.';
        resultadoTorneo.classList.remove('visible');
        return;
    }

    const datos = datosTorneos[deporte]?.[cantidad];

    if (!datos) {
        resultadoTitulo.textContent = 'Ajusta la selección';
        resultadoTexto.textContent = 'Prueba con otra cantidad para ver el horario disponible.';
        resultadoTorneo.classList.remove('visible');
        return;
    }

    resultadoTitulo.textContent = datos.titulo;
    resultadoTexto.innerHTML = `${datos.mensaje}<br><strong>${datos.fecha}</strong> · ${datos.hora}`;

    resultadoTorneo.classList.remove('visible');
    void resultadoTorneo.offsetWidth;
    resultadoTorneo.classList.add('visible');
}

function mostrarMensajeConfirmacion() {
    if (!mensajeConfirmacion || !deporteSelect || !cantidadSelect) {
        return;
    }

    const deporteNombre = deporteSelect.options[deporteSelect.selectedIndex].text;
    const cantidad = cantidadSelect.value;

    if (!cantidad) {
        mensajeConfirmacion.textContent = 'Selecciona una cantidad de participantes antes de confirmar.';
        mensajeConfirmacion.classList.remove('visible');
        return;
    }

    mensajeConfirmacion.textContent = `Confirmaste ${deporteNombre} para ${cantidad} participantes.`;
    mensajeConfirmacion.classList.remove('visible');
    void mensajeConfirmacion.offsetWidth;
    mensajeConfirmacion.classList.add('visible');
}

if (deporteSelect && cantidadSelect) {
    deporteSelect.addEventListener('change', mostrarResultadoTorneo);
    cantidadSelect.addEventListener('change', mostrarResultadoTorneo);
    mostrarResultadoTorneo();
}

if (botonConfirmar) {
    botonConfirmar.addEventListener('click', mostrarMensajeConfirmacion);
}

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
