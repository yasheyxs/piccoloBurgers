# Cliente publico

Esta carpeta contiene las rutas web que usan los clientes.

- `index.php`, `carrito.php`, `confirmar_pedido.php` y `guardar_pedido.php` son endpoints publicos.
- `cliente/` contiene login, registro, perfil e historial del cliente.
- `api/` contiene endpoints AJAX usados por el carrito y la disponibilidad.
- `assets/` e `img/` contienen recursos publicos servidos por el navegador.
- `views/` y `components/` son puentes de compatibilidad.
- Las vistas y componentes reales viven en `../frontend/customer`.

Las URLs existentes siguen entrando por `/public/...`.
