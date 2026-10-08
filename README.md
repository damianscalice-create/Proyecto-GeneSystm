# Proyecto-GeneSystm
Repositorio para el proyecto de 3°MM conformado por: Damian Tesauro, Ian Gonzalez, Fernando Silva y Joel Paiva

## Gestor de torneos

El gestor de `public/torneos.html` guarda los torneos en MySQL y requiere una sesión iniciada. En la base `torneos_db`, aplicar primero `SQL/schema_usuarios.sql` y luego `SQL/schema_gestor_torneos.sql`. La conexión PDO se configura en `config/config.php`.

Iniciar Apache y MySQL/MariaDB desde WAMP y abrir la página mediante `http://localhost/Proyecto-GeneSystm/public/torneos.html`; no abrirla como archivo local. Los torneos antiguos guardados en el navegador no se importan automáticamente ni se eliminan.
