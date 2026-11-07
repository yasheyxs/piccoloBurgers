# Código del backend

- `Core/` arranque de la aplicación (`bootstrap.php`), conexión a la base (`database.php`) y funciones comunes (`helpers.php`).
- `Http/PublicSite/` controladores y handlers del sitio de clientes.
- `Http/Api/` la API JSON: enrutador, respuestas, sesión, CORS y controladores (`CatalogoController`, `PedidosController`, `CuentaController`).
- `Http/Admin/` utilidades del panel de administración.
- `Services/` reglas de negocio compartidas entre el sitio, el panel y la API (menú, pedidos, puntos, pool, bebidas, reservas, seguridad, etc.).
- `Repositories/` reservado para las consultas a la base agrupadas por entidad.

Las páginas que ve el navegador viven en `../public/` y usan el código de esta carpeta.
