const formularioLogin = document.getElementById('formularioLogin');
const mensajeLogin = document.getElementById('mensajeLogin');

if (formularioLogin) {
	formularioLogin.addEventListener('submit', async (evento) => {
		evento.preventDefault();
		const botonEnviar = formularioLogin.querySelector('button[type="submit"]');
		botonEnviar.disabled = true;

		try {
			const respuesta = await fetch('php/login.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					nombreUsuario: formularioLogin.nombreUsuario.value.trim(),
					password: formularioLogin.password.value,
				}),
			});
			const resultado = await respuesta.json();
			mensajeLogin.textContent = resultado.mensaje;

			if (resultado.exito) {
				window.location.href = 'index.html';
			} else {
				botonEnviar.disabled = false;
			}
		} catch {
			mensajeLogin.textContent = 'No se pudo conectar con el servidor.';
			botonEnviar.disabled = false;
		}
	});
}