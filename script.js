const botonAbrir = document.getElementById('botonAbrir');
const botonCerrar = document.getElementById('botonCerrar');
const sidebar = document.getElementById('miSidebar');


botonAbrir.addEventListener("click", () => {
    sidebar.classList.add("activo");
});

botonCerrar.addEventListener("click", () => {
    sidebar.classList.remove("activo");
})