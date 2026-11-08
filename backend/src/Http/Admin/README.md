# Panel de administración: reglas

Acá vive la lógica del panel. Las pantallas (`../../../public/admin/`) y la API (`../Api/Admin/`) solo la llaman.

- **`XController.php`**: las reglas de cada sección (validar, permisos finos, qué se guarda). Avisan los problemas con `ApiException` (422 con el error de cada campo, 404, 409…).
- **Piezas compartidas**:
  - `Datos`: lee textos, números, ids y fechas de formularios y de la API.
  - `Periodo`: lee y valida el período "desde/hasta" de los reportes.
  - `ImagenSubida`: guarda imágenes de forma segura.
  - `DatosSensibles`: pide la contraseña propia antes de mostrar montos.
- **`acceso.php`** y **`sesion_panel.php`**: sesión, permisos por rol (`config/permisos.php`), token CSRF y ayudas para armar pantallas (`piccolo_admin_pagina()`).
- **`telefonos.php`**: códigos de país para los teléfonos de los clientes.
- **`carrito_actualizar_handler.php`** y **`disponibilidad_menu_handler.php`**: reservas de stock que usa la pantalla de canje de puntos.

Las consultas a la base están en `../../Repositories/`. El paso a paso para agregar una sección está en `backend/README.md`.
