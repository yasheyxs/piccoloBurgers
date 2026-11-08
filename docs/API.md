# API de Piccolo Burgers (v1)

La API permite usar el sistema desde cualquier frontend: consultar el menú, armar el carrito, confirmar pedidos y manejar la cuenta del cliente. También tiene rutas para el panel de administración, que se van sumando sección por sección.

- **Base:** `http://localhost:8080/api/v1` con Docker, o `http://localhost/piccoloBurgers/backend/public/api/v1` con XAMPP.
- **Formato:** todas las respuestas son JSON. Las acciones que envían datos (`POST`, `PUT`) reciben JSON con `Content-Type: application/json`.
- **Sesión:** la cuenta del cliente usa una cookie de sesión. Desde el navegador hay que enviar las peticiones con `credentials: 'include'`.

## Respuestas

Todas las respuestas tienen la misma forma:

```json
{ "exito": true, "datos": { } }
```

```json
{ "exito": false, "mensaje": "Revisá los datos ingresados.", "errores": { "email": "Ingresá un email válido." } }
```

`errores` aparece cuando hay problemas en campos puntuales, para mostrarlos al lado de cada uno en el formulario.

| Código | Cuándo |
|---|---|
| 200 / 201 | Todo bien (201 cuando se creó algo) |
| 401 | Hace falta iniciar sesión, o los datos de acceso son incorrectos |
| 404 / 405 | La ruta no existe o no acepta ese método |
| 403 | Panel: el rol no tiene acceso a la sección, o falta el token CSRF |
| 409 | Conflicto: no hay stock suficiente, el teléfono o email ya están registrados, o no se puede borrar algo en uso |
| 410 | El enlace para recuperar la contraseña venció |
| 415 | El cuerpo no se envió como JSON |
| 422 | Datos inválidos (ver `errores`) |
| 429 | Demasiados intentos de inicio de sesión fallidos: hay que esperar unos minutos |
| 500 | Error inesperado del servidor |

## Catálogo

### `GET /menu`
Productos visibles del menú, paginados.

| Parámetro | Descripción |
|---|---|
| `categoria` | Una de las categorías de `categorias` |
| `busqueda` | Texto libre. Entiende búsquedas comunes: "gaseosa" muestra bebidas, "papas" muestra acompañamientos |
| `offset`, `limit` | Paginación (por defecto 0 y 8; máximo 50 por página) |

```json
{
  "exito": true,
  "datos": {
    "productos": [
      { "id": 19, "nombre": "Aros de cebolla", "ingredientes": "Aros de cebolla x8 unidades", "precio": 5600.0,
        "categoria": "Acompañamientos", "imagen": "http://localhost:8080/img/menu/1754020686_12.png", "disponibles": 4 }
    ],
    "total": 36,
    "categoria": null,
    "categorias": ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"]
  }
}
```

`disponibles` es la cantidad que se puede pedir ahora, según los insumos en stock y lo que otros clientes tienen reservado en su carrito.

### `GET /menu/disponibilidad?ids=12,13`
Unidades disponibles de varios productos: `[{ "productoId": 12, "disponibles": 3 }]`.

### `GET /inicio`
Banner principal y testimonios de la página de inicio.

### `GET /puntos/configuracion`
Reglas del programa de puntos: `minimoPuntos` para poder canjear, `valorPunto` en pesos, `maximoPorcentaje` del total que se puede pagar con puntos y `puntosPorCada` (se gana 1 punto cada esa cantidad de pesos).

### `POST /comentarios`
Formulario de contacto. Cuerpo: `{ "nombre", "correo", "mensaje" }`.

## Carrito

El carrito reserva las unidades por unos minutos mientras el cliente arma su pedido, así dos personas no compran el último producto al mismo tiempo. Se asocia a la sesión, no hace falta iniciar sesión.

Cada respuesta del carrito devuelve el estado completo:

```json
{
  "productos": [{ "id": 19, "nombre": "Aros de cebolla", "precio": 5600.0, "cantidad": 2, "subtotal": 11200.0 }],
  "total": 11200.0,
  "disponibilidad": [{ "productoId": 19, "disponibles": 2 }]
}
```

| Ruta | Acción |
|---|---|
| `GET /carrito` | Ver el carrito |
| `PUT /carrito/productos/{id}` | Fijar la cantidad de un producto. Cuerpo: `{ "cantidad": 2 }` (0 lo quita) |
| `DELETE /carrito/productos/{id}` | Quitar un producto |
| `PUT /carrito` | Reemplazar todo el carrito. Cuerpo: `{ "productos": [{ "id": 19, "cantidad": 2 }] }` |
| `DELETE /carrito` | Vaciar el carrito |

Si no hay stock suficiente responde `409` con el motivo.

## Pedidos

### `POST /pedidos`
Confirma un pedido. Los precios siempre se toman del menú: del cuerpo solo se usan el producto y la cantidad.

```json
{
  "nombre": "Juana Pérez",
  "codigoPais": "54",
  "telefono": "3511234567",
  "email": "juana@ejemplo.com",
  "nota": "Sin cebolla",
  "metodoPago": "Efectivo",
  "tipoEntrega": "Delivery",
  "direccion": "San Martín 123",
  "referencias": "Portón verde",
  "usarPuntos": true,
  "productos": [{ "id": 19, "cantidad": 2 }]
}
```

- `metodoPago`: `Efectivo`, `Tarjeta` o `MercadoPago`.
- `tipoEntrega`: `Retiro` o `Delivery` (este último requiere `direccion`).
- `usarPuntos` solo aplica si hay una sesión iniciada.

Respuesta (`201`):

```json
{ "id": 120, "productos": [ ], "totalOriginal": 11200.0, "descuento": 2000.0, "total": 9200.0, "puntosUsados": 100, "puntosGanados": 6 }
```

### `GET /pedidos` (requiere sesión)
Historial de pedidos del cliente, del más nuevo al más viejo, con `estado`, `pagado`, `total` y sus `items`.

## Cuenta del cliente

| Ruta | Cuerpo | Descripción |
|---|---|---|
| `POST /cuenta/registro` | `nombre, codigoPais, telefono, email, password, confirmar` | Crea la cuenta y deja la sesión iniciada |
| `POST /cuenta/login` | `codigoPais, telefono, password` | Inicia sesión |
| `POST /cuenta/logout` | `{}` | Cierra sesión |
| `GET /cuenta` | | Datos del cliente con sesión iniciada (401 si no hay sesión) |
| `GET /cuenta/sesion` | | Igual que el anterior, pero sin sesión devuelve `null` en lugar de un error. Útil para saber si mostrar "Iniciar sesión" |
| `PUT /cuenta` | `nombre, codigoPais, telefono, email` | Actualiza los datos |
| `PUT /cuenta/password` | `actual, nueva, confirmar` | Cambia la contraseña |
| `POST /cuenta/recuperar` | `email` | Envía por mail un enlace para crear una nueva contraseña (vence en 30 minutos) |
| `POST /cuenta/restablecer` | `token, password, confirmar` | Crea la nueva contraseña con el token del enlace |

Los datos del cliente se devuelven así (nunca incluyen la contraseña):

```json
{ "id": 9, "nombre": "Cliente", "telefono": "+540000825022", "email": "cliente@ejemplo.com", "puntos": 70, "fechaRegistro": "2025-09-15 00:52:18", "emailPendiente": false }
```

`emailPendiente` es `true` en cuentas viejas que no cargaron un email; conviene pedírselo al cliente.

La contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula, número y símbolo.

## Panel de administración

Rutas bajo `/admin/...`, pensadas para un panel que use la API (por ejemplo, el panel nuevo en React). Usan la misma sesión que las pantallas actuales del panel: iniciar sesión en una también la inicia en la otra.

- **Permisos por rol:** cada sección respeta la misma tabla que las pantallas (`backend/config/permisos.php`). Sin sesión responde `401`; con un rol sin acceso, `403`.
- **Token CSRF:** las acciones que modifican datos (`POST`, `PUT`, `DELETE`) necesitan el encabezado `X-CSRF-Token` con el token que devuelve el inicio de sesión o `GET /admin/sesion`. Sin él responden `403`.
- **Inactividad:** después de 2 horas sin uso la sesión se cierra sola.

### Sesión

| Ruta | Qué hace |
|---|---|
| `GET /admin/sesion` | Usuario con sesión, su rol y el token CSRF. `null` si no hay sesión. |
| `POST /admin/sesion` | Inicia sesión. Recibe `{ "usuario", "password" }`. `401` si los datos no son correctos, `429` después de 5 intentos fallidos. |
| `DELETE /admin/sesion` | Cierra la sesión (con `X-CSRF-Token`). |

```json
{ "exito": true, "datos": { "usuario": "Usuario", "rol": "admin", "csrf_token": "7a32d2…" } }
```

### Proveedores (solo administración)

| Ruta | Qué hace |
|---|---|
| `GET /admin/proveedores` | Lista de proveedores, ordenada por nombre. |
| `GET /admin/proveedores/{id}` | Un proveedor. `404` si no existe. |
| `POST /admin/proveedores` | Crea un proveedor. Recibe `{ "nombre", "telefono", "email" }` (email opcional). Responde `201`. |
| `PUT /admin/proveedores/{id}` | Modifica un proveedor. Mismos campos que al crear. |
| `DELETE /admin/proveedores/{id}` | Borra un proveedor. `409` si tiene materias primas o compras asociadas. |

```json
{ "id": 3, "nombre": "Distribuidora Lozano", "telefono": "+54 0000 580136", "email": null }
```

Los datos inválidos devuelven `422` con el error de cada campo en `errores` (`nombre`, `telefono`, `email`).

### Materias primas (solo administración)

| Ruta | Qué hace |
|---|---|
| `GET /admin/materias-primas` | Lista de materias primas con su stock. Con `?criticos=1`, solo las que están en el stock mínimo o por debajo. |
| `GET /admin/materias-primas/{id}` | Una materia prima. |
| `POST /admin/materias-primas` | Crea una. Recibe `{ "nombre", "unidad_medida", "cantidad", "stock_minimo", "proveedor_id" }` (`stock_minimo` y `proveedor_id` son opcionales). |
| `PUT /admin/materias-primas/{id}` | Modifica una. Mismos campos que al crear. |
| `DELETE /admin/materias-primas/{id}` | Borra una. `409` si la usa algún producto del menú o figura en compras. |

```json
{ "id": 12, "nombre": "Pan de hamburguesa", "unidad_medida": "unidades", "cantidad": 40.0, "stock_minimo": 20.0,
  "estado_stock": "ok", "proveedor_id": 3, "proveedor": "Distribuidora Lozano" }
```

`estado_stock` es `sin_stock`, `bajo` (en el mínimo o por debajo; si no tiene mínimo cargado se toma 1) u `ok`. Los números aceptan coma o punto decimal.

### Compras (solo administración)

Registrar una compra suma lo comprado al stock de cada materia prima; borrarla lo descuenta.

| Ruta | Qué hace |
|---|---|
| `GET /admin/compras` | Historial de compras con proveedor y total, de la más nueva a la más vieja. |
| `GET /admin/compras/resumen` | Gasto del mes y del año, y los 5 proveedores y materias primas con más compras. |
| `GET /admin/compras/{id}` | Una compra con sus ítems y el total. |
| `POST /admin/compras` | Registra una compra. Ver el ejemplo abajo. Responde `201`. |
| `DELETE /admin/compras/{id}` | Borra una compra y descuenta lo comprado del stock. `409` si parte de esa mercadería ya se usó (el stock quedaría negativo). |

```json
{
  "fecha": "2026-11-08",
  "proveedor_id": 3,
  "items": [
    { "materia_prima_id": 12, "cantidad": 50, "precio_unitario": 120.5 },
    { "materia_prima_id": 15, "cantidad": 2.5, "precio_unitario": 3400 }
  ]
}
```

Si algo no es válido responde `422`, y los errores de cada ítem vienen como `items.0.cantidad`, `items.1.materia_prima_id`, etc. La fecha no puede ser posterior a hoy.

### Menú (solo administración)

| Ruta | Qué hace |
|---|---|
| `GET /admin/menu` | Todos los productos, también los ocultos, con la cantidad de insumos de su receta. |
| `GET /admin/menu/categorias` | Las categorías válidas. |
| `GET /admin/menu/{id}` | Un producto con su receta. |
| `POST /admin/menu` | Crea un producto. Recibe `{ "nombre", "ingredientes", "precio", "categoria", "visible" }`. Responde `201`. |
| `PUT /admin/menu/{id}` | Modifica un producto. Mismos campos que al crear. |
| `POST /admin/menu/{id}/foto` | Cambia la foto. Se envía como `multipart/form-data` con el campo `foto` (JPG, PNG o WEBP, hasta 5 MB). |
| `DELETE /admin/menu/{id}` | Borra un producto. `409` si ya figura en pedidos: en ese caso conviene ocultarlo (`visible: false`) para no perder el historial. |
| `GET /admin/menu/{id}/receta` | Qué materias primas descuenta cada unidad vendida y si el stock alcanza. |
| `PUT /admin/menu/{id}/receta` | Reemplaza la receta completa: `{ "items": [{ "materia_prima_id": 4, "cantidad": 0.15 }] }`. |

```json
{ "id": 12, "nombre": "Jamón y queso", "ingredientes": "Carne, jamón, queso y mayonesa", "precio": 7500.0,
  "categoria": "Hamburguesas", "visible": true, "foto": "producto_3f9a1c2b4d5e6f70.jpg",
  "imagen": "http://localhost:8080/img/menu/producto_3f9a1c2b4d5e6f70.jpg", "insumos": 4 }
```

Un producto con `visible: false` no aparece en el sitio ni se puede pedir. Las fotos se guardan con un nombre generado por el sistema y solo se aceptan imágenes reales (se revisa el contenido del archivo, no su nombre).

### Usuarios del panel (solo administración)

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/usuarios` | Los usuarios del panel, sin contraseñas. Cada uno trae `sos_vos` (es quien está usando el panel) y `se_puede_borrar`. |
| `GET /admin/usuarios/{id}` | Un usuario. |
| `POST /admin/usuarios` | Crea un usuario: `{ "usuario", "correo", "rol", "password", "confirmar" }`. El rol es `admin`, `empleado` o `delivery`. Responde `201`. |
| `PUT /admin/usuarios/{id}` | Modifica nombre, correo y rol. La contraseña no se cambia por acá (cada usuario la recupera por correo). |
| `DELETE /admin/usuarios/{id}` | Borra un usuario. `409` si es el propio o el único administrador. |

Para crear un administrador, editar uno o darle ese rol a alguien, hay que mandar también `password_actual` con la contraseña de quien está usando el panel; si falta o no coincide responde `422`. Siempre queda al menos un administrador. Si se borra un usuario o se le cambia el rol, el cambio vale en el momento, aunque tenga una sesión abierta.

### Premios y sistema de puntos (solo administración)

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/premios` | Los premios, del más nuevo al más viejo. |
| `GET /admin/premios/{id}` | Un premio. |
| `POST /admin/premios` | Crea un premio: `{ "nombre", "descripcion", "costo_puntos" }` (la descripción es opcional; el costo, un entero de 1 o más). Responde `201`. |
| `PUT /admin/premios/{id}` | Modifica un premio. Mismos campos. |
| `DELETE /admin/premios/{id}` | Borra un premio. Los canjes ya hechos siguen en el historial de cada cliente. |
| `GET /admin/puntos/configuracion` | Las reglas de canje: `minimo_puntos`, `valor_punto`, `maximo_porcentaje` (de 0 a 100) y un `ejemplo` con lo que pasa en una compra de $10.000. |
| `PUT /admin/puntos/configuracion` | Guarda las reglas. Mismos campos; los decimales aceptan coma. |

```json
{ "minimo_puntos": 50, "valor_punto": 20.0, "maximo_porcentaje": 25.0, "actualizado": "2026-11-08 04:00:00",
  "ejemplo": { "compra": 10000, "maximo_puntos": 125, "maximo_descuento": 2500.0, "compras_para_el_minimo": 9 } }
```

### Clientes (solo administración)

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/clientes` | Todos los clientes, con sus puntos, la cantidad de pedidos y la fecha del último. |
| `GET /admin/clientes/codigos-pais` | Los códigos de país que se aceptan para el teléfono. |
| `GET /admin/clientes/{id}` | Un cliente con su pedido más grande, sus pedidos y sus movimientos de puntos. |
| `POST /admin/clientes` | Crea un cliente. Recibe `{ "nombre", "email", "codigo_pais", "telefono", "puntos" }` (el teléfono va sin el código de país). Responde `201` con `{ "cliente": {...}, "password_provisoria": "Pb-k7mqx2ra5" }`. |
| `PUT /admin/clientes/{id}` | Modifica un cliente. Mismos campos que al crear. Si cambian los puntos, la diferencia queda anotada como ajuste. |
| `DELETE /admin/clientes/{id}` | Borra el cliente y sus movimientos de puntos. Sus pedidos quedan en el historial, sin cliente asociado. |

```json
{ "id": 15, "nombre": "Ana Pérez", "telefono": "+543511234567", "codigo_pais": "54", "numero": "3511234567",
  "email": "ana@ejemplo.com", "puntos": 40, "registrado": "2026-11-08 03:22:39",
  "cantidad_pedidos": 3, "ultimo_pedido": "2026-11-07 21:10:00" }
```

La contraseña provisoria se genera al azar y solo se devuelve al crear el cliente: conviene dársela en el momento. El cliente puede cambiarla desde su cuenta o con "¿Olvidaste tu contraseña?". No se aceptan un email ni un teléfono que ya tenga otro cliente (`422`).

### Canje de puntos (solo administración)

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/clientes/{id}/canje` | El cliente, las reglas de puntos (`minimo_puntos`, `valor_punto`, `maximo_porcentaje`), los métodos de pago, los tipos de entrega, los productos visibles y los premios. |
| `POST /admin/clientes/{id}/canje/venta` | Registra una venta con descuento. Recibe `{ "items": [{ "id": 12, "cantidad": 2 }], "puntos_utilizados": 60, "metodo_pago", "tipo_entrega", "direccion", "referencias", "nota" }`. Responde `201` con el total, el descuento, los puntos usados y ganados, y el saldo nuevo. |
| `POST /admin/clientes/{id}/canje/premios` | Canjea premios: `{ "premios": [{ "id": 3, "cantidad": 1 }] }`. Responde `201` con los puntos usados y el saldo nuevo. |

La venta se registra igual que un pedido del sitio (mismos controles de stock, métodos de pago y entrega), pero con los puntos que se eligen: no más de los que tiene el cliente, no más del tope por venta y, si se usan, al menos el mínimo. Si algo no cumple las reglas responde `422` y no se toca el saldo. Todo cambio de puntos, también en los pedidos del sitio, queda anotado en el historial del cliente.

### Pool (administración y empleados)

La jornada se abre al empezar el día y se cierra al terminar; mientras está abierta, cada mesa (`azul` o `rojo`) tiene su fila de turnos. Las operaciones de la fila responden el estado actualizado.

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/pool` | Jornada activa y fila de cada mesa. Las fichas vendidas y los montos vienen en `null`. |
| `POST /admin/pool/datos-sensibles` | Con `{ "password" }` (la tuya) devuelve el estado con fichas vendidas y montos. `422` si no coincide; después de varios intentos se bloquea unos minutos (`429`). |
| `POST /admin/pool/jornada` | Abre la jornada. `409` si ya hay una abierta. |
| `GET /admin/pool/jornada/totales` | Fichas y bebidas vendidas en la jornada, para revisar antes de cerrar. |
| `POST /admin/pool/jornada/cierre` | Cierra la jornada. Opcional: `{ "fichasVendidas", "bebidasVendidas" }` corregidos a mano; la diferencia queda como ajuste. |
| `POST /admin/pool/turnos` | Anota fichas: `{ "pool": "azul", "nombre": "Ana", "fichas": 5 }`. Si superan el máximo por turno se reparten en varios turnos seguidos. |
| `PUT /admin/pool/turnos/{id}` | Cambia el nombre: `{ "nombre" }`. |
| `POST /admin/pool/turnos/{id}/consumir` | Usa una ficha. `409` si ya usó todas. |
| `POST /admin/pool/turnos/{id}/revertir` | Devuelve una ficha usada. `409` si no usó ninguna. |
| `POST /admin/pool/turnos/{id}/transferir` | Lo pasa al final de la otra mesa: `{ "pool": "rojo" }`. |
| `POST /admin/pool/turnos/{id}/mover` | Lo sube o baja un lugar: `{ "direccion": "arriba" }`. |
| `PUT /admin/pool/orden` | Ordena una mesa: `{ "pool": "azul", "ids": [8, 5, 9] }`. |
| `GET` y `PUT /admin/pool/configuracion` | Máximo de fichas por turno y valor de cada ficha: `{ "max_fichas_por_registro", "valor_ficha" }` (solo administración). |

Sin jornada abierta, las operaciones de la fila responden `409`. Un turno que no es de la jornada activa responde `404`.

### Bebidas (administración y empleados)

La jornada es la misma que la del pool: se abre y se cierra con `/admin/pool/jornada`. Una venta es una jarra o una promoción (varias jarras) para una persona, y se entrega jarra por jarra.

| Ruta | Qué hace |
| --- | --- |
| `GET /admin/bebidas` | Jornada activa, bebidas disponibles, resumen y ventas pendientes de entregar. Cantidades vendidas, precios y montos vienen en `null`. |
| `POST /admin/bebidas/datos-sensibles` | Con `{ "password" }` (la tuya) devuelve el estado completo. Mismas reglas que en el pool. |
| `POST /admin/bebidas/ventas` | Registra una venta: `{ "bebida_id": 2, "persona": "Ana", "tipo": "promo", "cantidad": 1 }`. Guarda el nombre y el precio del momento. `409` sin jornada abierta. |
| `PUT /admin/bebidas/ventas/{id}` | Cambia el nombre de la persona: `{ "nombre" }`. |
| `POST /admin/bebidas/ventas/{id}/entregar` | Anota una jarra entregada. `409` si ya se entregó todo. |
| `POST /admin/bebidas/ventas/{id}/revertir` | Deshace una entrega. `409` si no hay entregas. |
| `GET /admin/bebidas/catalogo` | Todas las bebidas, también las inactivas (solo administración). |
| `POST /admin/bebidas/catalogo` | Agrega una bebida: `{ "nombre", "valor_jarra", "valor_promo", "jarras_por_promo", "activa" }` (solo administración). |
| `PUT /admin/bebidas/catalogo/{id}` | Modifica una bebida. Las ventas ya hechas conservan su precio (solo administración). |

### Estadísticas de pool y bebidas (solo administración)

`GET /admin/estadisticas/pool` y `GET /admin/estadisticas/bebidas` reciben `?desde=AAAA-MM-DD&hasta=AAAA-MM-DD&jornada=ID` (todo opcional; sin fechas, el mes actual; sin jornada, la última del período). Si las fechas no son válidas o están al revés responden `422`, igual que las ventas.

Devuelven `resumen` (totales del período), `jornadas` (de la más nueva a la más vieja) y `seleccionada` (la jornada detallada, o `null` si no hubo). El pool trae además los totales por mesa (`mesas.azul` y `mesas.rojo`); bebidas trae `por_bebida`, lo vendido de cada bebida por tipo (jarra o promoción). Los totales incluyen las correcciones que se cargan al cerrar la jornada.

### Ventas (solo administración)

`GET /admin/ventas?desde=2026-11-01&hasta=2026-11-30` devuelve el mismo reporte que la pantalla de ventas (y que el Excel y el PDF). Sin fechas, va del primer día del mes a hoy. Si alguna fecha no es válida o el período está al revés, responde `422` con el error en `desde` o `hasta`.

```json
{ "desde": "2026-11-01", "hasta": "2026-11-30", "total": 152300.0,
  "por_origen": { "pedidos": 120300.0, "fichas": 18000.0, "bebidas": 14000.0 },
  "cantidad_pedidos": 9, "ticket_promedio": 13366.67,
  "producto_estrella": { "nombre": "Lomo de pollo", "cantidad": 6, "monto": 75000.0 },
  "productos": [ { "nombre": "Lomo de pollo", "cantidad": 6, "monto": 75000.0 } ],
  "metodos_pago": [ { "nombre": "Efectivo", "pedidos": 5, "monto": 70100.0 } ],
  "tipos_entrega": [ { "nombre": "Retiro", "pedidos": 6, "monto": 80000.0 } ],
  "mensual": { "anio": 2026, "meses": [ { "mes": 1, "nombre": "Enero", "pedidos": 0.0, "fichas": 0.0, "bebidas": 0.0, "total": 0.0 } ] } }
```

Los pedidos cancelados no cuentan. El monto de cada pedido es lo que se cobró, con el descuento por puntos ya aplicado; el `monto` de cada producto es a precio de lista. `mensual` trae los 12 meses del año de la fecha `hasta`. `producto_estrella` es `null` si no hubo ventas.

## Configuración

Variables en `backend/.env` (ver `backend/.env.example`):

| Variable | Para qué sirve |
|---|---|
| `FRONTEND_URL` | Direcciones desde donde un frontend en otro dominio puede usar la API, separadas por coma. Por ejemplo `http://localhost:5173` |
| `SESSION_SAMESITE` | `Lax` si el frontend comparte dominio con la API. `None` si está en otro dominio (en ese caso hace falta HTTPS) |
| `RESET_PASSWORD_URL` | Página donde el cliente crea su nueva contraseña. Si está vacía se usa la del sitio actual |

## Seguridad

- Las acciones solo aceptan JSON. Un formulario de otro sitio no puede enviar JSON, así que esto también evita que otra página haga pedidos en nombre del cliente (CSRF).
- La cookie de sesión no es accesible desde JavaScript (`HttpOnly`).
- Los errores inesperados no muestran detalles internos al cliente: quedan registrados en el log del servidor.
- Pedir la recuperación de contraseña responde lo mismo exista o no la cuenta, para no revelar qué emails están registrados.
- Después de 5 intentos de inicio de sesión fallidos en 15 minutos, ese teléfono queda bloqueado desde esa conexión durante 15 minutos (`429`).

## Pruebas

`backend/tests/ApiTest.php` recorre la API como lo haría un cliente (menú, carrito, registro, pedido, historial y sesión) y verifica cada respuesta:

```bash
php backend/tests/ApiTest.php http://localhost:8080/api/v1
```

Crea un cliente, un comentario y un pedido de prueba, así que conviene correrlo contra una base de desarrollo.

`backend/tests/ApiPanelTest.php` hace lo mismo con la API del panel: sesión, permisos por rol, token CSRF y las secciones de proveedores, materias primas, compras (incluido el ajuste del stock) y menú (receta y fotos).

```bash
PANEL_USUARIOS="admin:Usuario:Usuario1!,delivery:Delivery:Delivery1!" php backend/tests/ApiPanelTest.php http://localhost:8080/api/v1
```
