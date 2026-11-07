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
| `database/` | Migraciones de la base (`migraciones/`) y datos de ejemplo. |
| `bin/` | Scripts de consola, como `migrar.php`. |
| `tests/` | Pruebas: `Unit/` y `Integracion/` (PHPUnit), y los recorridos de la API y del panel. |
| `vendor/` | Dependencias de Composer. |

## Configuración

Copiar `.env.example` como `.env` (Docker) o `.env.local` (XAMPP) y completar los datos de MySQL y del correo. Si existen los dos, `.env.local` tiene prioridad; las variables definidas por Docker o por el hosting tienen prioridad sobre ambos.

## Cómo levantarlo

**Con Docker**: el `docker-compose.yml` de la raíz del proyecto levanta el backend junto con el frontend y MySQL (ver el README principal). La imagen del backend también se puede construir sola con el `Dockerfile` de esta carpeta.

**Con XAMPP**: copiar o enlazar el proyecto completo en `htdocs/piccoloBurgers`, crear una base vacía llamada `piccolodb` y, dentro de `backend/`, correr `composer install` y `php bin/migrar.php --con-datos`. Después entrar a:

- Panel: http://localhost/piccoloBurgers/backend/public/admin/
- API: http://localhost/piccoloBurgers/backend/public/api/v1/menu

## Base de datos

La estructura de la base se arma con las migraciones de `database/migraciones/`: archivos numerados que se aplican una sola vez y en orden. El script `bin/migrar.php` aplica las que falten y las anota en la tabla `tbl_migraciones`. En Docker corre solo cada vez que arranca el backend.

```bash
php bin/migrar.php               # aplica las migraciones pendientes
php bin/migrar.php --con-datos   # además carga los datos de ejemplo si la base está vacía
```

Para cambiar la base (una tabla o columna nueva, por ejemplo) se agrega un archivo nuevo con el número siguiente, como `005_descripcion.sql`, y nunca se edita uno que ya se aplicó. Si el cambio necesita lógica (revisar si algo existe antes de crearlo) puede ser un `.php` que devuelve una función que recibe la conexión, como `002_pool_y_bebidas.php`.

Una base que ya existía antes de las migraciones se reconoce sola: se toma el esquema inicial como aplicado y se agregan solo las tablas nuevas.

## Pruebas

```bash
composer pruebas     # pruebas unitarias
composer analizar    # análisis estático con PHPStan (nivel 3)
php tests/ApiTest.php http://localhost:8080/api/v1
PANEL_USUARIOS="admin:Usuario:Usuario1!,empleado:Empleado:Empleado1!,delivery:Delivery:Delivery1!" php tests/PanelPermisosTest.php http://localhost:8080/admin
```

- `ApiTest` recorre la API completa y crea datos de prueba, así que conviene correrlo contra una base de desarrollo.
- `PanelPermisosTest` entra al panel con cada rol indicado y verifica a qué pantallas puede entrar y a cuáles no, según `config/permisos.php`, y que los envíos sin token CSRF se rechacen.
- Las pruebas de `tests/Integracion/` usan MySQL y solo corren contra una base cuyo nombre termine en `_pruebas`, para no tocar nunca la real:

```bash
MYSQL_DATABASE=piccolodb_pruebas php bin/migrar.php --con-datos
MYSQL_DATABASE=piccolodb_pruebas vendor/bin/phpunit --testsuite integracion
```

## Permisos del panel

Todas las pantallas de `public/admin/` cargan primero `src/Http/Admin/acceso.php`, que controla la sesión, el rol y el token CSRF. Los roles permitidos de cada pantalla están en `config/permisos.php`; una pantalla que no figure ahí solo la puede abrir un administrador. Al agregar una pantalla nueva:

1. Ponerle como primera línea el `require_once` de `acceso.php` (copiarlo de cualquier otra pantalla).
2. Si la tienen que ver otros roles además de admin, agregarla en `config/permisos.php`.

El token CSRF se agrega solo a los formularios y a los `fetch()` del panel (`admin/assets/js/csrf.js`).

## Despliegue

En un hosting, el directorio raíz del sitio (document root) tiene que apuntar a `backend/public`. Así nada fuera de esa carpeta queda expuesto.

Con cada actualización hay que correr `php bin/migrar.php` para aplicar los cambios de la base (sin `--con-datos`).

Si el frontend se publica en otro dominio, configurar `FRONTEND_URL` y `SESSION_SAMESITE=None` (ver `.env.example`).
