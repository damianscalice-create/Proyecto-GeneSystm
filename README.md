# Proyecto-GeneSystm
Repositorio para el proyecto de 3°MM conformado por: Damian Tesauro, Ian Gonzalez, Fernando Silva y Joel Paiva

## Ejecutar con Docker

Se necesita tener Docker Engine y Docker Compose instalados. Desde la carpeta raíz del proyecto, ejecutar:

```sh
docker compose up --build
```

La aplicación quedará disponible en <http://localhost:8080>. La base MySQL se inicializa usando los archivos `.sql` de `SQL/` y conserva sus datos en el volumen `mysql_data`.

Para detener los contenedores, ejecutar `docker compose down`. Para detenerlos y borrar también la base de datos local, ejecutar `docker compose down -v`.

Las credenciales definidas en `docker-compose.yml` son solo para desarrollo local; cambiarlas antes de usar este entorno en un servidor accesible desde Internet.
