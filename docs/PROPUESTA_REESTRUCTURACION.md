# Propuesta de reestructuración — Piccolo Burgers

> Objetivo: dejar el proyecto organizado en **dos carpetas principales, `frontend/` y `backend/`**, que se puedan **desplegar por separado**, que sean fáciles de navegar y **sin romper funcionalidades** durante la migración.

**Estado:** Fase 0 ✅ · Fase 1 ✅ · Fases 2 a 4 pendientes.

En la Fase 1 hubo dos ajustes respecto del plan original: las vistas PHP del sitio de clientes pasaron a `backend/views/customer/` (mientras sigan generándose con PHP son parte del servidor), y la conexión `admin/bd.php` quedó directamente en `backend/src/Core/database.php`, sin archivo puente.

---

## 1. Diagnóstico de la estructura actual

Hoy conviven **tres generaciones de organización** a la vez (una migración a medio camino):

| Carpeta | Estado real | Quién la usa |
|---|---|---|
| `app/` | Código real del backend (bootstrap, controllers, services) | `public/`, `admin/` |
| `frontend/customer/`, `frontend/backoffice/` | Vistas y templates reales | Controllers de `app/` |
| `public/` | Webroot del cliente + puentes (`views/`, `components/`) | Navegador |
| `admin/` | 70 páginas del backoffice con SQL + HTML mezclados, **y la conexión `bd.php`** | Navegador + sitio público |
| `includes/` | Puentes de 2–3 líneas | **Nadie** (0 referencias) |
| `shared/` | Puentes de 2 líneas | **Nadie** (0 referencias) |
| `views/` (raíz) | Puentes de 3 líneas | **Nadie** (0 referencias) |
| `componentes/` | Puentes | Solo `tests/ValidarTelefonoTest.php` |
| `public/views/`, `public/components/` | Puentes | Solo `public/cliente/perfil_cliente.php` |
| `admin/templates/` | Puentes hacia `frontend/backoffice/templates` | Todas las páginas del admin |

### Acoplamientos que hoy impiden desplegar por separado

1. **El frontend renderiza con PHP y consulta la base directamente.** Las vistas de `frontend/customer/views/*.view.php` reciben variables que se obtienen con SQL en el mismo proceso. Un frontend así **no se puede desplegar sin el backend y sin acceso a MySQL**.
2. **La conexión a la BD vive en `admin/bd.php`** y la usan también el sitio público (`HomeController`, `CartController`, `login_cliente.php`, `registro_cliente.php`, etc.).
3. **El admin escribe imágenes en `public/img/menu` y `public/img/banners`** (rutas relativas `../../../public/img/...`). Si se separan los despliegues, las imágenes subidas quedan en un servidor y se muestran en otro.
4. **Flujos del cliente viven dentro de `admin/`**: `admin/password/recuperar_password_cliente.php`, `procesar_recuperacion_cliente.php` y `admin/obtener_pedidos_cliente.php`.
5. **Sesiones PHP compartidas**: login de cliente y carrito dependen de `$_SESSION` en el mismo servidor.

### Otros hallazgos menores
- `admin/password/test.php` es un archivo de prueba expuesto públicamente (`echo "Test OK"`). Conviene eliminarlo.
- El `README.md` menciona `config/app.php`, que no existe.
- Hay dos estilos de controller en paralelo (`HomeController.php` + `index_controller.php`, `CartController.php` + `carrito_controller.php`).

---

## 2. Decisión clave: ¿qué significa "desplegar por separado"?

Con PHP renderizando las vistas del lado del servidor, hay dos caminos. Conviene elegir uno explícitamente (y justificarlo en la tesis):

| | **Opción A — Desacople real (recomendada)** | **Opción B — Separación lógica** |
|---|---|---|
| Frontend | HTML + CSS + JS estático que consume una API JSON | Sigue siendo PHP (vistas) |
| Backend | API REST JSON + panel de administración | Lógica, servicios, BD |
| ¿Se despliegan por separado? | **Sí**: el frontend puede ir a Nginx/Netlify/Vercel/GitHub Pages; el backend a cualquier hosting PHP | **No**: siguen siendo un único despliegue, solo cambia la organización de carpetas |
| Esfuerzo | Medio: reescribir ~6 pantallas del cliente a JS + ~12 endpoints | Bajo: mover archivos y actualizar rutas |
| Valor para la tesis | Arquitectura cliente-servidor desacoplada, defendible | Solo orden de carpetas |

**Recomendación: Opción A, ejecutada por fases** (sección 5), de modo que el sistema funcione al terminar cada fase. Las fases 0 y 1 equivalen a la Opción B, así que si el tiempo no alcanza, igual queda un proyecto ordenado.

**El panel de administración queda en `backend/`.** Son 70 páginas server-rendered, de uso interno y muy ligadas a la BD; pasarlas a SPA excede el alcance. Se sirve desde el mismo despliegue del backend en `/admin/`. (Se puede mencionar como trabajo futuro.)

---

## 3. Estructura objetivo

```
piccoloBurgers/
├── backend/                         ← DESPLIEGUE 1: PHP 8.3 + Apache (api.piccolo… o /api)
│   ├── public/                      ← único webroot del backend
│   │   ├── index.php                (opcional: front controller / router)
│   │   ├── api/
│   │   │   └── v1/                  endpoints JSON que consume el frontend
│   │   │       ├── menu.php               (lista + filtros + disponibilidad)
│   │   │       ├── home.php               (banners, testimonios, combos)
│   │   │       ├── carrito.php            (actualizar / validar stock / reservas)
│   │   │       ├── pedidos.php            (crear pedido, historial del cliente)
│   │   │       ├── puntos.php             (configuración y saldo de puntos)
│   │   │       ├── comentarios.php        (formulario de contacto)
│   │   │       └── auth/
│   │   │           ├── login.php, logout.php, registro.php, me.php
│   │   │           └── recuperar.php, reset.php
│   │   ├── admin/                   ← backoffice (antes /admin)
│   │   │   ├── index.php, login.php, panel_cocina.php, panel_delivery.php …
│   │   │   ├── clientes/            (clientes.php, crear, editar, detalle, canje)
│   │   │   ├── password/            (solo usuarios internos)
│   │   │   ├── seccion/             (banners, menu, ventas, compras, pool, …)
│   │   │   └── assets/              (css/js del backoffice)
│   │   └── uploads/                 ← imágenes subidas (menu, banners, premios)
│   │
│   ├── src/                         ← antes app/  (namespace Piccolo\)
│   │   ├── Core/                    bootstrap.php, helpers.php, Database.php (antes admin/bd.php)
│   │   ├── Http/
│   │   │   ├── Api/                 handlers JSON (carrito, menu, pedidos, auth…)
│   │   │   ├── Admin/               lógica que se vaya extrayendo de las páginas del admin
│   │   │   └── Middleware/          cors.php, auth.php, json.php
│   │   ├── Services/                puntos, pool, bebidas, reservas, email, estado_pago…
│   │   │   └── Security/            password_utils, validar_telefono
│   │   └── Repositories/            consultas por entidad (Menu, Clientes, Pedidos…)
│   ├── views/
│   │   └── admin/                   header.php, footer.php, header_public.php
│   ├── config/                      config.php, mailer.php
│   ├── database/                    piccolodb.sql (+ migrations/ a futuro)
│   ├── tests/                       ValidarTelefonoTest.php
│   ├── composer.json / composer.lock / vendor/
│   ├── Dockerfile
│   ├── apache.conf                  (antes apache/000-default.conf)
│   ├── .env.example
│   └── README.md
│
├── frontend/                        ← DESPLIEGUE 2: estático (Nginx, Netlify, Vercel…)
│   ├── public/                      ← todo lo que se sirve tal cual
│   │   ├── index.html
│   │   ├── carrito.html
│   │   ├── confirmar-pedido.html
│   │   ├── cliente/
│   │   │   ├── login.html, registro.html, perfil.html
│   │   │   └── recuperar-password.html, reset-password.html
│   │   ├── img/                     imágenes fijas del sitio (favicon, logos, fondos)
│   │   └── assets/
│   │       ├── css/                 index.css, carrito.css, confirmar_pedido.css, custom.css
│   │       └── js/
│   │           ├── config.js        API_BASE_URL (única línea que cambia por entorno)
│   │           ├── api.js           cliente fetch común (credentials, errores, JSON)
│   │           ├── components/      navbar, carrito-button, whatsapp-button, scroll-button
│   │           └── pages/           index.js, carrito.js, carrito_reservas.js, confirmar_pedido.js, perfil.js…
│   ├── Dockerfile                   (nginx:alpine copiando public/)
│   ├── nginx.conf                   (opcional: proxy /api → backend)
│   └── README.md
│
├── docs/                            documentación de la tesis (arquitectura, diagramas, esta propuesta)
├── docker-compose.yml               mysql + backend + frontend + phpmyadmin
├── .gitignore
└── README.md                        índice general: qué hay en cada carpeta y cómo levantarlo
```

### Por qué es navegable
- **Dos puertas de entrada claras**: todo lo que ve el cliente está en `frontend/public/`; todo lo que corre en el servidor está en `backend/`.
- **Un solo webroot por despliegue** (`backend/public`, `frontend/public`): lo que no está ahí no es accesible por URL, sin necesidad de reglas `.htaccess` de bloqueo.
- **Desaparecen las 5 carpetas puente** (`includes/`, `shared/`, `views/`, `componentes/`, `public/views`, `public/components`, `admin/templates`).
- **El nombre de la carpeta dice la capa**: `src/Http` (entrada), `src/Services` (reglas de negocio), `src/Repositories` (datos), `views/admin` (presentación del backoffice).

---

## 4. Mapa de archivos (actual → nuevo)

### Backend

| Actual | Nuevo |
|---|---|
| `app/Core/*` | `backend/src/Core/*` |
| `admin/bd.php` | `backend/src/Core/Database.php` (+ `admin/bd.php` temporal que lo incluya) |
| `app/Services/**` | `backend/src/Services/**` |
| `app/Http/PublicSite/Api/*_handler.php` | `backend/src/Http/Api/*` |
| `app/Http/PublicSite/filtrar_menu_handler.php` | `backend/src/Http/Api/MenuHandler` |
| `app/Http/PublicSite/guardar_pedido_handler.php` | `backend/src/Http/Api/PedidosHandler` |
| `app/Http/PublicSite/index_controller.php` | se divide en `Api/HomeHandler` + `Api/ComentariosHandler` |
| `app/Http/PublicSite/carrito_controller.php`, `CartController.php`, `CheckoutController.php`, `HomeController.php`, `confirmar_pedido_controller.php` | se reemplazan por endpoints JSON (se eliminan al final) |
| `public/api/*.php`, `public/filtrar_menu.php`, `public/guardar_pedido.php` | `backend/public/api/v1/*.php` |
| `public/cliente/login_cliente.php`, `registro_cliente.php`, `logout_cliente.php` (parte PHP) | `backend/public/api/v1/auth/*.php` |
| `admin/obtener_pedidos_cliente.php`, `public/cliente/obtener_pedidos_cliente.php` | `backend/public/api/v1/pedidos.php?cliente=me` |
| `admin/password/*_cliente.php` | `backend/public/api/v1/auth/recuperar.php` y `reset.php` |
| `admin/*.php` (resto), `admin/seccion/**`, `admin/password/*_usuario.php`, `admin/assets/**`, `admin/helpers/**` | `backend/public/admin/**` (helpers → `backend/src/Http/Admin/`) |
| `admin/clientes*.php` | `backend/public/admin/clientes/` |
| `frontend/backoffice/templates/*` | `backend/views/admin/*` |
| `public/img/menu`, `public/img/banners` (subidas) | `backend/public/uploads/menu`, `.../banners` |
| `config/`, `database/`, `tests/`, `composer.*`, `vendor/` | `backend/…` |
| `Dockerfile`, `apache/000-default.conf` | `backend/Dockerfile`, `backend/apache.conf` |
| `admin/password/test.php` | **eliminar** |

### Frontend

| Actual | Nuevo |
|---|---|
| `frontend/customer/views/index.view.php` | `frontend/public/index.html` + `assets/js/pages/index.js` |
| `frontend/customer/views/carrito.view.php` | `frontend/public/carrito.html` + `pages/carrito.js` |
| `frontend/customer/views/confirmar_pedido.view.php` | `frontend/public/confirmar-pedido.html` + `pages/confirmar_pedido.js` |
| `frontend/customer/views/partials/head.php`, `navbar.php` | `assets/js/components/navbar.js` (o HTML repetido en cada página) |
| `frontend/customer/components/*.php` | `assets/js/components/*.js` |
| `public/cliente/login_cliente.php`, `registro_cliente.php`, `perfil_cliente.php` (parte HTML) | `frontend/public/cliente/*.html` + `pages/*.js` |
| `admin/password/recuperar_password_cliente.php` (HTML) | `frontend/public/cliente/recuperar-password.html` |
| `public/assets/css/*` | `frontend/public/assets/css/*` |
| `public/assets/js/*` | `frontend/public/assets/js/pages/*` |
| `public/img/favicon.png` y otras imágenes fijas | `frontend/public/img/` |

### Se eliminan (al final)
`includes/`, `shared/`, `views/`, `componentes/`, `public/views/`, `public/components/`, `admin/templates/`, `app/` (vacía), `frontend/customer/`, `frontend/backoffice/`, `.htaccess` raíz, `public/.htaccess`.

---

## 5. Plan de migración por fases (sin romper nada)

Cada fase termina con el sistema **funcionando y probado**. Antes de empezar: **inicializar Git** (`git init`, primer commit con todo tal cual está). Hoy el proyecto no está versionado y es lo que permite volver atrás si algo falla.

### Fase 0 — Limpieza segura (1–2 h, riesgo casi nulo)
1. `git init` + commit inicial.
2. Eliminar `admin/password/test.php`.
3. Eliminar carpetas puente **sin uso**: `includes/`, `shared/`, `views/` (raíz).
4. Cambiar `tests/ValidarTelefonoTest.php` para que incluya `app/Services/Security/validar_telefono.php` y eliminar `componentes/`.
5. Cambiar en `public/cliente/perfil_cliente.php` los includes `../views/partials/navbar.php` y `../components/*.php` por `PICCOLO_FRONTEND_CUSTOMER . '/...'`, y eliminar `public/views/` y `public/components/`.
6. Corregir el README (`config/app.php` → `config/config.php` y `.env`).

✅ Verificación: recorrer inicio, carrito, confirmar pedido, login/registro/perfil del cliente, login admin y 3–4 secciones del admin; correr el test.

### Fase 1 — Mover a `backend/` sin cambiar comportamiento (medio día)
1. Crear `backend/` y mover ahí `app/` (→ `src/`), `config/`, `database/`, `tests/`, `composer.*`, `vendor/`, `Dockerfile`, `apache/`.
2. Mover `admin/` → `backend/public/admin/` y `public/` → `backend/public/` (en esta fase el sitio del cliente PHP vive temporalmente dentro del backend).
3. Crear `backend/src/Core/Database.php` con el contenido de `bd.php`; dejar `backend/public/admin/bd.php` con una sola línea que lo incluya, para no tocar las 71 páginas que lo usan.
4. Mover `frontend/backoffice/templates` → `backend/views/admin/` y apuntar los 3 puentes de `admin/templates/` ahí (después, reemplazo masivo y se borran).
5. Actualizar: `composer.json` (`"Piccolo\\": "src/"`, `files: src/Core/helpers.php`), `PICCOLO_ROOT` en `helpers.php`, `DocumentRoot` en `apache.conf` → `backend/public`, `docker-compose.yml` (`build: ./backend`), rutas de subida de imágenes del admin.
6. `composer dump-autoload`.

✅ Verificación: mismo recorrido que en Fase 0, más subir una imagen de menú y una de banner, y exportar un PDF y un Excel.

> Si el tiempo de la tesis es corto, **acá ya se tiene la Opción B**: todo el servidor en `backend/`, ordenado, sin puentes.

### Fase 2 — Construir la API JSON (1–2 semanas)
1. Crear `backend/src/Http/Middleware/` con: respuesta JSON estándar (`{exito, datos, mensaje}`), CORS (orígenes permitidos desde `.env`: `FRONTEND_URL`) y verificación de sesión del cliente.
2. Crear los endpoints de `backend/public/api/v1/` **reutilizando** los handlers y services existentes (la lógica ya está en `filtrar_menu_handler`, `guardar_pedido_handler`, `puntos_config`, `reservas_virtuales`, etc.).
3. Los endpoints viejos (`public/api/*.php`, `guardar_pedido.php`, `filtrar_menu.php`) siguen funcionando en paralelo.

✅ Verificación: probar cada endpoint con Postman/Thunder Client o `curl` y guardar la colección en `docs/` (también sirve como anexo de la tesis).

### Fase 3 — Frontend estático, página por página (1–2 semanas)
Orden sugerido, de menor a mayor riesgo: **inicio → carrito → confirmar pedido → login/registro → perfil → recuperar contraseña**.
1. Crear `frontend/public/` con `assets/js/config.js` (`API_BASE_URL`) y `api.js` (fetch con `credentials: 'include'`).
2. Por cada página: pasar el HTML de la `.view.php` a `.html`, reemplazar los `<?= ... ?>` por datos que se cargan con `fetch` y reutilizar el CSS/JS existente.
3. Mientras una página no está migrada, el link sigue apuntando a la versión PHP.

✅ Verificación: después de cada página, hacer el flujo completo (agregar al carrito, confirmar pedido, ver el pedido en el panel de cocina/delivery del admin).

### Fase 4 — Separar despliegues y cerrar
1. `frontend/Dockerfile` (nginx) y servicio `frontend` en `docker-compose.yml`.
2. Eliminar del backend las vistas PHP del cliente, los controllers viejos y los endpoints duplicados.
3. Actualizar READMEs (raíz, `backend/`, `frontend/`) y `docs/arquitectura.md` con el diagrama.

---

## 6. Despliegue resultante

```
                 ┌──────────────────────────┐        ┌───────────────────────────────┐
  Cliente  ───▶  │ frontend (estático)      │  JSON  │ backend (PHP + Apache)        │ ──▶ MySQL
                 │ piccolo.com              │ ─────▶ │ api.piccolo.com/api/v1/...    │
                 │ Nginx / Netlify / Vercel │        │ api.piccolo.com/admin/  ◀── Personal
                 └──────────────────────────┘        │ api.piccolo.com/uploads/...   │
                                                     └───────────────────────────────┘
```

Dos formas de conectarlos (definir en `frontend/public/assets/js/config.js`):

- **Mismo dominio con proxy (más simple):** Nginx del frontend redirige `/api`, `/admin` y `/uploads` al backend. No hace falta CORS y las cookies de sesión funcionan sin cambios.
- **Dominios distintos (`piccolo.com` + `api.piccolo.com`):** CORS con `Access-Control-Allow-Credentials: true` y cookie de sesión con `SameSite=None; Secure` (exige HTTPS).

`docker-compose.yml` local:

| Servicio | Puerto | Origen |
|---|---|---|
| `frontend` | 8080 | `./frontend` |
| `backend` | 8000 | `./backend` |
| `mysql` | 3306 | `backend/database/piccolodb.sql` |
| `phpmyadmin` | 8081 | — |

---

## 7. Riesgos y cómo cubrirlos

| Riesgo | Mitigación |
|---|---|
| Romper un `require` al mover archivos | Moverlos en Fase 1 con puentes temporales de una línea; buscar `require`/`include` con rutas relativas antes de borrar cada puente |
| Imágenes subidas que no se ven en el frontend | Guardarlas en `backend/public/uploads/`; la API devuelve la URL absoluta de cada imagen |
| Sesión del cliente perdida entre dominios | Usar el proxy del mismo dominio, o configurar cookie `SameSite=None; Secure` + CORS con credenciales |
| SEO / primera carga más lenta del sitio estático | Para un restaurante es aceptable; se puede mencionar como trabajo futuro (pre-render) |
| Romper XAMPP local | `backend/public` puede servirse como `http://localhost/piccoloBurgers/backend/public/`; `piccolo_admin_base_path()` y `piccolo_public_base_path()` ya detectan la ruta base |
| No poder volver atrás | Git desde la Fase 0; un commit por fase |

---

## 8. Resumen

- **Hoy:** 10 carpetas en la raíz, 5 de ellas puentes sin lógica; frontend y backend acoplados por PHP, `admin/bd.php` y `public/img`.
- **Propuesta:** `backend/` (API JSON + backoffice + BD) y `frontend/` (sitio estático del cliente), cada uno con su Dockerfile y un único webroot.
- **Camino:** 5 fases incrementales; las fases 0–1 ya dejan el proyecto ordenado en pocas horas, y las fases 2–4 logran el despliegue independiente.
