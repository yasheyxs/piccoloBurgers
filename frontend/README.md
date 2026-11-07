# Frontend

Sitio de los clientes de Piccolo Burgers. Es una aplicación estática (HTML, CSS y JavaScript, sin paso de compilación) que obtiene todos los datos de la API del backend (`/api/v1`, documentada en [`docs/API.md`](../docs/API.md)).

Como no depende de PHP ni de la base de datos, se puede publicar en cualquier servidor de archivos estáticos (Nginx, Netlify, GitHub Pages, etc.).

## Estructura

```
public/
├── index.html               inicio: banner, menú, nosotros, puntos, contacto, ubicación y testimonios
├── carrito.html             carrito y canje de puntos
├── confirmar-pedido.html    datos de entrega y confirmación del pedido
├── cliente/                 login, registro, perfil y recuperación de contraseña
├── img/                     imágenes del sitio
└── assets/
    ├── css/                 estilos (custom.css es el tema general)
    └── js/
        ├── config.js        dirección de la API
        ├── api.js           llamadas a la API y manejo de errores
        ├── layout.js        barra de navegación, botones flotantes y sesión
        ├── carrito.js       estado del carrito compartido entre páginas
        ├── ui.js            formato de precios, avisos y errores de formularios
        └── pages/           un script por página
```

Cada página carga solo su script (`assets/js/pages/...`), que importa los módulos comunes que necesita.

## Cómo probarlo

**Con XAMPP**, con el proyecto en `htdocs/piccoloBurgers`: abrir http://localhost/piccoloBurgers/frontend/public/. La dirección de la API se detecta sola.

**Publicado aparte del backend**: definir la dirección de la API antes de los scripts de cada página:

```html
<script>window.PICCOLO_API_URL = "https://api.midominio.com/api/v1";</script>
```

y en el backend agregar el dominio del frontend en `FRONTEND_URL` (y `SESSION_SAMESITE=None` si son dominios distintos, ver `backend/.env.example`).

Para que el mail de recuperación de contraseña lleve a la página nueva, configurar en el backend `RESET_PASSWORD_URL` con la dirección de `cliente/restablecer.html`.

## Carrito

El carrito vive en el servidor: reserva las unidades mientras el cliente arma su pedido, así dos personas no se llevan el último producto. Además se guarda una copia en el navegador para recuperarlo si la sesión se pierde.
