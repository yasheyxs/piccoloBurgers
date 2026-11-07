# API de Piccolo Burgers (v1)

La API permite usar el sistema desde cualquier frontend: consultar el menú, armar el carrito, confirmar pedidos y manejar la cuenta del cliente.

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
| 409 | Conflicto: no hay stock suficiente, o el teléfono o email ya están registrados |
| 410 | El enlace para recuperar la contraseña venció |
| 415 | El cuerpo no se envió como JSON |
| 422 | Datos inválidos (ver `errores`) |
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
| `GET /cuenta` | | Datos del cliente con sesión iniciada |
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

## Pruebas

`backend/tests/ApiTest.php` recorre la API como lo haría un cliente (menú, carrito, registro, pedido, historial y sesión) y verifica cada respuesta:

```bash
php backend/tests/ApiTest.php http://localhost:8080/api/v1
```

Crea un cliente, un comentario y un pedido de prueba, así que conviene correrlo contra una base de desarrollo.
