const btnRegistro = document.getElementById('btnRegistro');
const mensajeRegistro = document.getElementById('mensajeRegistro');
const inputUsuario = document.getElementById('nombreUsuario');
const inputEmail = document.getElementById('email');
const inputPassword = document.getElementById('password');
const inputPassword2 = document.getElementById('password2');

function mostrarMensaje (elemento, texto, tipo) {
    if (!elemento) return;
    elemento.textContent = texto;
    elemento.classList.remove("visible", "error", "exito");
    void elemento.offsetWidth;
    elemento.classList.add("visible", tipo);
}

async function registrarUsuario() {
    const nombreUsuario = inputUsuario.value.trim();
    const email = inputEmail.value.trim();
    const password = inputPassword.value.trim();
    const password2 = inputPassword2.value.trim();

    if (!nombreUsuario || !email || !password || !password2) {
        mostrarMensaje(mensajeRegistro, 'Completa todos los campos.', 'error');
        return;
    }

    btnRegistro.disabled = true;
    btnRegistro.textContent = "Creando cuenta...";

    try {
        const respuesta = await fetch('registro.php' , {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombreUsuario, email, password, password2}),
        });
    
        const resultado = await respuesta.json();

        if (resultado.exito) {
            mostrarMensaje(mensajeRegistro, resultado.mensaje, 'exito');
            setTimeout(() => {
                window.location.href = "login.html";
            }, 1200);
        } else {
            mostrarMensaje(mensajeRegistro, resultado.mensaje, 'error');
        }
    } catch (error){
        mostrarMensaje(mensajeRegistro, "No se pudo conectar con el servidor.", 'error');
    } finally {
        btnRegistro.disabled = false
        btnRegistro.textContent = "Crear cuenta";
    }
}

if (btnRegistro) {
    btnRegistro.addEventListener('click', registrarUsuario);
}