const lista = document.getElementById('listaTorneos');
const resumen = document.getElementById('resumen');
const aviso = document.getElementById('aviso');
let temporizador;

const detalleLegible = { male: 'Masculino', fem: 'Femenino' };

function escaparHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function mostrarAviso(texto, tipo) {
    aviso.textContent = texto;
    aviso.className = 'aviso visible ' + tipo;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => aviso.classList.remove('visible'), 3000);
}

async function cargar() {
    try {
        const r = await fetch('php/torneosDisponibles.php');
        if (r.status === 401) { window.location.href = 'login.html'; return; }
        const d = await r.json();
        if (!d.exito) { lista.textContent = d.mensaje; return; }

        const misTorneos = d.torneos.filter(t => t.inscrito).length;
        resumen.textContent = `Estás inscripto en ${misTorneos} torneo(s).`;

        if (d.torneos.length === 0) {
            lista.textContent = 'Todavía no hay torneos creados.';
            return;
        }

        lista.innerHTML = d.torneos.map(t => {
            const porcentaje = Math.min(100, Math.round((t.inscriptos / t.cupo) * 100));
            const detalle = t.detalle ? (detalleLegible[t.detalle] || t.detalle) : '';

            let boton;
            if (t.inscrito) {
                boton = `<button class="btn-cancelar" data-accion="cancelar" data-id="${t.id}">Cancelar inscripción</button>`;
            } else if (t.lleno) {
                boton = `<button disabled>Cupo completo</button>`;
            } else {
                boton = `<button class="btn-participar" data-accion="inscribir" data-id="${t.id}">Participar</button>`;
            }

            return `<article class="tarjeta">
                ${t.inscrito ? '<span class="etiqueta">Inscripto</span>' : ''}
                <h3>${escaparHTML(t.nombre_evento)}</h3>
                <p class="meta">${escaparHTML(t.deporte)}${detalle ? ' · ' + escaparHTML(detalle) : ''}</p>
                <p class="meta">Creado por ${escaparHTML(t.creador || 'un usuario')}</p>
                <div class="barra ${t.lleno ? 'llena' : ''}"><div style="width:${porcentaje}%"></div></div>
                <p class="cupo">${t.inscriptos} de ${t.cupo} lugares ocupados</p>
                ${boton}
            </article>`;
        }).join('');
    } catch (e) {
        lista.textContent = 'No se pudo conectar con el servidor.';
    }
}

async function enviar(url, id) {
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        if (r.status === 401) { window.location.href = 'login.html'; return; }
        const d = await r.json();
        mostrarAviso(d.mensaje, d.exito ? 'exito' : 'error');
        cargar(); // refresca cupos siempre, también ante un error (ej. cupo que se llenó)
    } catch (e) {
        mostrarAviso('No se pudo conectar con el servidor.', 'error');
    }
}

lista.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-accion]');
    if (!b) return;
    const id = Number(b.dataset.id);
    enviar(b.dataset.accion === 'inscribir' ? 'php/inscribirse.php' : 'php/cancelarInscripcion.php', id);
});

cargar();
