const formularioLogin = document.querySelector('.cajaLogin');

if (formularioLogin) {
	formularioLogin.addEventListener('submit', () => {
		formularioLogin.querySelector('button[type="submit"]').disabled = true;
	});
}