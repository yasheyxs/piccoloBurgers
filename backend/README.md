# Backend

Todo lo que corre en el servidor: la API que usa el sitio de clientes, el panel de administración, la lógica de negocio, la configuración y la base de datos.

## Estructura

| Carpeta | Contenido |
|---|---|
| `public/` | Lo único accesible desde el navegador: la API (`api/v1/`), el panel (`admin/`) y las fotos que se suben desde el panel (`img/`). |
| `public/admin/` | Panel de administración: pantallas, `seccion/` (menú, ventas, pool, etc.), `password/`, `api/` y `assets/`. |
| `public/api/v1/` | API JSON para el frontend (ver [`docs/API.md`](../docs/API.md)). |
| `src/` | Código PHP del sistema: `Core/` (arranque, conexión, helpers), `Http/` (API y panel), `Services/` (reglas de negocio). |
| `views/admin/` | Plantillas del panel. |
| `config/` | Configuración de la aplicación y del correo. |
| `database/` | Base de datos de ejemplo (`piccolodb.sql`). |
| `tests/` | Pruebas. |
| `vendor/` | Dependencias de Composer. |

## Configuración

Copiar `.env.example` como `.env` (Docker) o `.env.local` (XAMPP) y completar los datos de MySQL y del correo. Si existen los dos, `.env.local` tiene prioridad; las variables definidas por Docker o por el hosting tienen prioridad sobre ambos.

## Cómo levantarlo

**Con Docker**: el `docker-compose.yml` de la raíz del proyecto levanta el backend junto con el frontend y MySQL (ver el README principal). La imagen del backend también se puede construir sola con el `Dockerfile` de esta carpeta.

**Con XAMPP**: copiar o enlazar el proyecto completo en `htdocs/piccoloBurgers`, importar `database/piccolodb.sql` en una base llamada `piccolodb` y entrar a:

- Panel: http://localhost/piccoloBurgers/backend/public/admin/
- API: http://localhost/piccoloBurgers/backend/public/api/v1/menu

## Pruebas

```bash
php tests/ValidarTelefonoTest.php
php tests/ApiTest.php http://localhost:8080/api/v1
```

El segundo recorre la API completa y crea datos de prueba, así que conviene correrlo contra una base de desarrollo.

## Despliegue

En un hosting, el directorio raíz del sitio (document root) tiene que apuntar a `backend/public`. Así nada fuera de esa carpeta queda expuesto.

Si el frontend se publica en otro dominio, configurar `FRONTEND_URL` y `SESSION_SAMESITE=None` (ver `.env.example`).
