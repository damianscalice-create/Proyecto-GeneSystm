const tablaUsuarios = document.getElementById('tablaUsuarios');
const tablaTorneos = document.getElementById('tablaTorneos');
const estadisticas = document.getElementById('estadisticas');
const mensajeAdmin = document.getElementById('mensajeAdmin');
let temporizador;

function escaparHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function aviso(texto, tipo) {
    mensajeAdmin.textContent = texto;
    mensajeAdmin.className = 'mensaje-admin visible ' + tipo;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => mensajeAdmin.classList.remove('visible'), 3000);
}

async function enviar(url, cuerpo) {
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cuerpo),
        });
        const res = await r.json();
        aviso(res.mensaje, res.exito ? 'exito' : 'error');
        if (res.exito) cargarDatos();
    } catch (e) {
        aviso('No se pudo conectar con el servidor.', 'error');
    }
}

async function cargarDatos() {
    try {
        const r = await fetch('php/adminDatos.php');
        if (r.status === 403) { window.location.href = 'login.html'; return; }
        const d = await r.json();
        if (!d.exito) { aviso(d.mensaje, 'error'); return; }

        estadisticas.innerHTML = `
            <div class="stat"><b>${d.usuarios.length}</b>Usuarios</div>
            <div class="stat"><b>${d.usuarios.filter(u => u.rol === 'admin').length}</b>Administradores</div>
            <div class="stat"><b>${d.torneos.length}</b>Torneos</div>`;

        tablaUsuarios.innerHTML = `<table><thead><tr>
            <th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Registro</th><th>Acciones</th>
            </tr></thead><tbody>` + d.usuarios.map(u => {
            const esYo = u.id == d.usuarioActualId;
            const acciones = esYo ? '(vos)' : `
                <button class="btn-gris" data-accion="rol" data-id="${u.id}"
                        data-rol="${u.rol === 'admin' ? 'usuario' : 'admin'}">
                    ${u.rol === 'admin' ? 'Quitar admin' : 'Hacer admin'}</button>
                <button class="btn-rojo" data-accion="borrar-usuario" data-id="${u.id}">Eliminar</button>`;
            return `<tr><td>${u.id}</td><td>${escaparHTML(u.nombre_usuario)}</td>
                <td>${escaparHTML(u.email)}</td>
                <td><span class="badge-${u.rol}">${escaparHTML(u.rol)}</span></td>
                <td>${escaparHTML(u.fecha_registro)}</td><td>${acciones}</td></tr>`;
        }).join('') + '</tbody></table>';

        tablaTorneos.innerHTML = d.torneos.length === 0
            ? '<p style="padding:15px">No hay torneos.</p>'
            : `<table><thead><tr><th>ID</th><th>Evento</th><th>Deporte</th><th>Detalle</th>
               <th>Participantes</th><th>Creado por</th><th>Fecha</th><th>Acciones</th>
               </tr></thead><tbody>` + d.torneos.map(t => `<tr>
                <td>${t.id}</td><td>${escaparHTML(t.nombre_evento)}</td>
                <td>${escaparHTML(t.deporte)}</td><td>${escaparHTML(t.detalle || '-')}</td>
                <td>${t.cantidad_participantes}</td>
                <td>${escaparHTML(t.creador || 'Sin usuario')}</td>
                <td>${escaparHTML(t.fecha_creacion)}</td>
                <td><button class="btn-rojo" data-accion="borrar-torneo" data-id="${t.id}">Eliminar</button></td>
                </tr>`).join('') + '</tbody></table>';
    } catch (e) {
        aviso('No se pudieron cargar los datos.', 'error');
    }
}

document.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-accion]');
    if (!b) return;
    const id = Number(b.dataset.id);

    if (b.dataset.accion === 'borrar-torneo' && confirm('¿Eliminar este torneo?')) {
        enviar('php/adminEliminarTorneo.php', { id });
    } else if (b.dataset.accion === 'borrar-usuario' && confirm('¿Eliminar este usuario?')) {
        enviar('php/adminEliminarUsuario.php', { id });
    } else if (b.dataset.accion === 'rol') {
        enviar('php/adminCambiarRol.php', { id, rol: b.dataset.rol });
    }
});

document.getElementById('cerrarSesion').addEventListener('click', async (e) => {
    e.preventDefault();
    await fetch('php/logout.php', { method: 'POST' });
    window.location.href = 'login.html';
});

cargarDatos();
