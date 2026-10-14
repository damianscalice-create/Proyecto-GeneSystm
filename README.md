# Proyecto-GeneSystm
Repositorio para el proyecto de 3°MM conformado por: Damian Tesauro.

Esta es una pagina web con un Sistema de Gestion Deportiva Modular, un sistema para organizar torneos, ya sean deportivos, de cartas o electronicos/E-Sports.

## Gestor de torneos

El gestor de `public/torneos.html` guarda los torneos en MySQL y requiere una sesión iniciada. En la base `torneos_db`, aplicar primero `SQL/schema_usuarios.sql` y luego `SQL/schema_gestor_torneos.sql`. La conexión PDO se configura en `config/config.php`.

Iniciar Apache y MySQL/MariaDB desde WAMP y abrir la página mediante `http://localhost/Proyecto-GeneSystm/public/torneos.html`; no abrirla como archivo local. Los torneos antiguos guardados en el navegador no se importan automáticamente ni se eliminan.
=======
## Ejecutar con Docker

Se necesita tener Docker Engine y Docker Compose instalados. Desde la carpeta raíz del proyecto, ejecutar:

```sh
docker compose up --build
```

La aplicación quedará disponible en <http://localhost:8080>. La base MySQL se inicializa usando los archivos `.sql` de `SQL/` y conserva sus datos en el volumen `mysql_data`.

Para detener los contenedores, ejecutar `docker compose down`. Para detenerlos y borrar también la base de datos local, ejecutar `docker compose down -v`.

Las credenciales definidas en `docker-compose.yml` son solo para desarrollo local; cambiarlas antes de usar este entorno en un servidor accesible desde Internet.
