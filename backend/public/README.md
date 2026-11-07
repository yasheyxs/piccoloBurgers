# Carpeta pública del backend

Es lo único que se sirve al navegador desde el backend.

- `api/v1/`: la API que usa el sitio de clientes (ver [`docs/API.md`](../../docs/API.md)).
- `admin/`: panel de administración. `admin/api/` tiene los endpoints internos que usa el canje de puntos del panel.
- `img/`: fotos que se suben desde el panel (`menu/`, `banners/`) e imágenes de reemplazo para las que falten.
- `index.php`: redirige al panel. El sitio de clientes vive en `frontend/`.

La lógica está en `../src/` y las plantillas del panel en `../views/admin/`.
