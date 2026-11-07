# Backend

Todo lo que corre en el servidor: el sitio de clientes (por ahora generado con PHP), el panel de administración, la lógica de negocio, la configuración y la base de datos.

## Estructura

| Carpeta | Contenido |
|---|---|
| `public/` | Lo único accesible desde el navegador. Páginas del sitio, `api/`, `assets/`, `img/` y el panel en `admin/`. |
| `public/admin/` | Panel de administración: pantallas, `seccion/` (menú, ventas, pool, etc.), `password/` y `assets/`. |
| `public/api/v1/` | API JSON para el frontend (ver [`docs/API.md`](../docs/API.md)). |
| `src/` | Código PHP del sistema: `Core/` (arranque, conexión, helpers), `Http/` (controladores del sitio y de la API), `Services/` (reglas de negocio). |
| `views/` | Plantillas PHP: `customer/` para el sitio y `admin/` para el panel. |
| `config/` | Configuración de la aplicación y del correo. |
| `database/` | Base de datos de ejemplo (`piccolodb.sql`). |
| `tests/` | Pruebas. |
| `vendor/` | Dependencias de Composer. |

## Configuración

Copiar `.env.example` como `.env` (Docker) o `.env.local` (XAMPP) y completar los datos de MySQL y del correo. Si existen los dos, `.env.local` tiene prioridad; las variables definidas por Docker o por el hosting tienen prioridad sobre ambos.

## Cómo levantarlo

**Con Docker** (desde esta carpeta):

```bash
docker compose up -d --build
```

- Sitio: http://localhost:8080/
- Panel: http://localhost:8080/admin/
- phpMyAdmin: http://localhost:8081/

**Con XAMPP**: copiar o enlazar el proyecto completo en `htdocs/piccoloBurgers`, importar `database/piccolodb.sql` en una base llamada `piccolodb` y entrar a:

- Sitio: http://localhost/piccoloBurgers/
- Panel: http://localhost/piccoloBurgers/backend/public/admin/

Las direcciones anteriores (`/piccoloBurgers/public/...` y `/piccoloBurgers/admin/...`) redirigen solas a las nuevas.

## Pruebas

```bash
php tests/ValidarTelefonoTest.php
php tests/ApiTest.php http://localhost:8080/api/v1
```

El segundo recorre la API completa y crea datos de prueba, así que conviene correrlo contra una base de desarrollo.

## Despliegue

En un hosting, el directorio raíz del sitio (document root) tiene que apuntar a `backend/public`. Así nada fuera de esa carpeta queda expuesto.
