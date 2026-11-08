# Convenciones del proyecto

Cómo está escrito el código y dónde va cada cosa, para que cualquier cambio siga el mismo estilo que el resto.

## Idioma y nombres

- Todo en español: variables, funciones, clases, tablas, comentarios, mensajes y textos de commits.
- **PHP y JavaScript**: `camelCase` para variables y funciones (`totalPedidos`, `cargarNegocio()`), `PascalCase` para clases (`VentasController`). Las funciones globales viejas del backend usan el prefijo `piccolo_` (`piccolo_e()`, `piccolo_negocio()`).
- **Base de datos**: tablas `tbl_*` y columnas en `snake_case` (`fecha_registro`), como ya están.
- **API**: el sitio usa claves en `camelCase` (`/api/v1/menu`, `/pedidos`); el panel (`/api/v1/admin/...`) usa las mismas claves que la base, en `snake_case`.
- Los comentarios explican **por qué** se hace algo, no lo que ya dice el código.

## Formato

El archivo `.editorconfig` de la raíz lo aplica cualquier editor:

- UTF-8, finales de línea LF (lo asegura también `.gitattributes`) y una línea vacía al final.
- PHP con 4 espacios (PSR-12); HTML, JavaScript, CSS y las plantillas del panel con 2.
- PHP nuevo con `declare(strict_types=1);` y tipos en parámetros y resultados.

## Dónde va cada cosa

| Qué | Dónde |
| --- | --- |
| Consultas a la base | `backend/src/Repositories/` |
| Reglas del panel (validar, permisos finos, qué se guarda) | `backend/src/Http/Admin/*Controller.php` |
| Rutas de la API | `backend/src/Http/Api/` y su registro en `backend/public/api/v1/index.php` |
| Lógica compartida con el sitio (pedidos, puntos, menú, pool) | `backend/src/Services/` |
| Pantallas del panel (archivos cortos) | `backend/public/admin/` |
| HTML del panel | `backend/views/admin/` |
| JavaScript y CSS de cada pantalla del panel | `backend/public/admin/assets/js/pages/` y `assets/css/` |
| Datos del local (dirección, horarios, WhatsApp, redes, pago) | `backend/config/negocio.php`, servidos por `GET /api/v1/negocio` |
| Quién entra a cada pantalla del panel | `backend/config/permisos.php` |
| Cambios en la estructura de la base | `backend/database/migraciones/` |
| Sitio de clientes | `frontend/public/` (HTML) y `frontend/public/assets/js/` (un módulo por página en `pages/`) |

El paso a paso para armar una sección nueva del panel está en [`backend/README.md`](../backend/README.md#cómo-está-armada-una-sección-del-panel).

## Reglas que se repiten en todo el código

- **Escapar todo lo que se muestra**: en PHP con `piccolo_e()`; en JavaScript con `textContent` o `escapar()`. Nunca armar HTML con datos sin escapar.
- **Consultas siempre con parámetros** (`prepare` + `execute`), nunca pegando valores en el SQL.
- **Errores para el usuario con `ApiException`**: 422 con el error de cada campo, 404, 409 si no se puede por una regla, 403 si el rol no puede. Los errores técnicos van al registro del servidor (`error_log`), nunca a la pantalla.
- **Formularios y `fetch()` del panel** llevan el token CSRF solo (`csrf.js`); las pantallas sin sesión lo agregan a mano.
- **Montos de dinero** con dos decimales; los decimales que escribe una persona pueden llevar coma (`Datos::decimal()`).
- **Fechas de reportes** con `Periodo::leer()`: `desde` y `hasta` en AAAA-MM-DD.
- **Imágenes subidas** con `ImagenSubida`: solo JPG, PNG o WEBP reales, con nombre generado. Si PHP tiene la extensión `gd`, se guardan achicadas y en WEBP (las fotos del menú hasta 1000 px de ancho y los banners hasta 1920).
- **Caché del navegador**: las fotos subidas no cambian nunca (cambiar una foto cambia su nombre), así que se guardan un año; estilos y scripts se revisan en cada visita. Ver `backend/public/img/.htaccess` y `frontend/nginx.conf.template`.
- **Datos del local**: nunca escritos en el código; se leen de `piccolo_negocio()` (backend) o `cargarNegocio()` (sitio).

## Base de datos

Un cambio de estructura es siempre un archivo nuevo en `database/migraciones/`, con el número siguiente (`008_descripcion.sql`). Nunca se edita una migración que ya se aplicó. Docker las aplica solo al arrancar; en XAMPP, con `php bin/migrar.php`.

## Pruebas

Cada cambio de reglas viene con su prueba:

- **Unitarias** (`backend/tests/Unit/`) para cálculos que no usan la base.
- **De integración** (`backend/tests/Integracion/`) para reglas que usan la base; crean sus propios datos y los borran al terminar.
- **Recorridos** (`ApiTest`, `ApiPanelTest`, `PanelPermisosTest`) para la API y los permisos del panel.

GitHub las corre todas en cada PR (ver `.github/workflows/pruebas.yml`).

## Commits y PRs

- Una rama por tema (`feature/...`) y un PR por sección o arreglo.
- El título y la descripción del PR, en castellano y sin jerga: qué cambia, qué se arregló, cómo se probó.
