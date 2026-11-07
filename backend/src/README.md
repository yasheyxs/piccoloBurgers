# Código del backend

- `Core/` arranque de la aplicación (`bootstrap.php`), conexión a la base (`database.php`) y funciones comunes (`helpers.php`, incluida `piccolo_env()` para leer la configuración).
- `Http/Api/` la API JSON: enrutador, respuestas, sesión, CORS y controladores (`CatalogoController`, `PedidosController`, `CuentaController`).
- `Http/Admin/` utilidades y endpoints internos del panel de administración.
- `Services/` reglas de negocio compartidas entre la API y el panel (menú, pedidos, puntos, pool, bebidas, reservas, seguridad, etc.).
- `Repositories/` reservado para las consultas a la base agrupadas por entidad.

Las rutas que ve el navegador viven en `../public/` y usan el código de esta carpeta.
