# Backend PHP

Esta carpeta contiene el backend de la aplicacion.

- `Core/` define bootstrap, rutas, URL helpers y utilidades comunes.
- `Http/PublicSite/` contiene controladores y handlers del sitio del cliente.
- `Http/Admin/` queda reservado para controladores del backoffice.
- `Services/` contiene reglas de negocio y helpers compartidos.
- `Repositories/` queda reservado para consultas a datos por entidad.

Los entrypoints web deben vivir en `public/` o `admin/` y delegar hacia esta carpeta.
