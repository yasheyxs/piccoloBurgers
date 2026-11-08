<?php

declare(strict_types=1);

/**
 * Recorre la API como lo haría un cliente: menú, carrito, registro, pedido, historial y cuenta.
 *
 * Uso:  php tests/ApiTest.php http://localhost:8080/api/v1
 *
 * Crea un cliente, un comentario y un pedido de prueba, así que conviene correrlo
 * contra una base de desarrollo, nunca contra la de producción.
 */

$base = rtrim($argv[1] ?? (getenv('API_URL') ?: 'http://localhost:8080/api/v1'), '/');
$cookies = tempnam(sys_get_temp_dir(), 'piccolo_api_');
$fallas = 0;
$pruebas = 0;

function pedir(string $metodo, string $ruta, ?array $cuerpo = null, array $opciones = []): array
{
    global $base, $cookies;

    $curl = curl_init($base . $ruta);
    $encabezados = $opciones['encabezados'] ?? [];
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_COOKIEFILE => $cookies,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($cuerpo !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo));
        $encabezados[] = 'Content-Type: ' . ($opciones['tipo'] ?? 'application/json');
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $encabezados);

    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        fwrite(STDERR, "No se pudo conectar con $base: " . curl_error($curl) . PHP_EOL);
        exit(1);
    }

    $tamanoEncabezados = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    return [
        'status' => $status,
        'encabezados' => substr($respuesta, 0, $tamanoEncabezados),
        'json' => json_decode(substr($respuesta, $tamanoEncabezados), true),
    ];
}

function verificar(bool $condicion, string $descripcion, $detalle = null): void
{
    global $fallas, $pruebas;
    $pruebas++;

    if ($condicion) {
        echo "  ok   $descripcion\n";
        return;
    }

    $fallas++;
    echo "  FALLA $descripcion\n";
    if ($detalle !== null) {
        echo '        ' . json_encode($detalle, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "API: $base\n\nCatálogo\n";

$r = pedir('GET', '/menu?limit=999');
$productos = $r['json']['datos']['productos'] ?? [];
verificar($r['status'] === 200 && count($productos) > 0, 'el menú devuelve productos', $r['json']);
verificar(count($productos) <= 50, 'el menú no devuelve más de 50 productos por página');
verificar(isset($productos[0]['id'], $productos[0]['precio'], $productos[0]['imagen']), 'cada producto trae id, precio e imagen');

$r = pedir('GET', '/menu?busqueda=gaseosa');
$categorias = array_unique(array_column($r['json']['datos']['productos'] ?? [], 'categoria'));
verificar($categorias === ['Bebidas'], 'buscar "gaseosa" muestra solo bebidas', $categorias);

$r = pedir('GET', '/menu/disponibilidad');
verificar($r['status'] === 422 && isset($r['json']['errores']['ids']), 'disponibilidad sin productos avisa el error', $r['json']);

$r = pedir('GET', '/negocio');
verificar($r['status'] === 200 && preg_match('#^https://wa\.me/549\d+$#', $r['json']['datos']['whatsapp']['url'] ?? '') === 1 && !empty($r['json']['datos']['horarios']), 'GET /negocio trae los datos del local (WhatsApp con el 9 de celular)');

$r = pedir('GET', '/inicio');
verificar($r['status'] === 200 && array_key_exists('testimonios', $r['json']['datos'] ?? []), 'inicio trae banner y testimonios');

$r = pedir('GET', '/puntos/configuracion');
verificar($r['status'] === 200 && isset($r['json']['datos']['valorPunto']), 'configuración de puntos');

$r = pedir('POST', '/comentarios', ['nombre' => '', 'correo' => 'no-es-email', 'mensaje' => '']);
verificar($r['status'] === 422 && count($r['json']['errores'] ?? []) === 3, 'un comentario incompleto devuelve los errores por campo', $r['json']);

$r = pedir('POST', '/comentarios', ['nombre' => 'Test API', 'correo' => 'test@piccolo-falso.test', 'mensaje' => 'Comentario de prueba']);
verificar($r['status'] === 201, 'se puede dejar un comentario', $r['json']);

$r = pedir('POST', '/comentarios', ['nombre' => 'x'], ['tipo' => 'application/x-www-form-urlencoded']);
verificar($r['status'] === 415, 'las acciones solo aceptan JSON');

$r = pedir('DELETE', '/menu');
verificar($r['status'] === 405, 'un método no permitido responde 405');

echo "\nCarrito\n";

$conStock = array_values(array_filter($productos, static fn($p) => $p['disponibles'] > 0));
if (!$conStock) {
    echo "  (no hay productos con stock, se saltean carrito y pedido)\n";
} else {
    $producto = $conStock[0];

    $r = pedir('PUT', '/carrito/productos/' . $producto['id'], ['cantidad' => 1]);
    verificar($r['status'] === 200 && ($r['json']['datos']['productos'][0]['id'] ?? null) === $producto['id'], 'agregar un producto al carrito', $r['json']);
    verificar(($r['json']['datos']['total'] ?? null) == $producto['precio'], 'el total del carrito usa el precio del menú');

    $r = pedir('PUT', '/carrito/productos/' . $producto['id'], ['cantidad' => 9999]);
    verificar($r['status'] === 409, 'no se puede reservar más de lo que hay en stock', $r['json']);

    $r = pedir('PUT', '/carrito/productos/' . $producto['id'], ['cantidad' => -1]);
    verificar($r['status'] === 422, 'una cantidad negativa se rechaza');

    $r = pedir('DELETE', '/carrito/productos/' . $producto['id']);
    verificar($r['status'] === 200 && ($r['json']['datos']['productos'] ?? null) === [], 'quitar el producto del carrito');

    $r = pedir('PUT', '/carrito', ['productos' => [['id' => $producto['id'], 'cantidad' => 1]]]);
    verificar($r['status'] === 200 && count($r['json']['datos']['productos'] ?? []) === 1, 'reemplazar el carrito completo', $r['json']);

    $r = pedir('DELETE', '/carrito');
    verificar($r['status'] === 200 && ($r['json']['datos']['productos'] ?? null) === [], 'vaciar el carrito');
}

echo "\nCuenta\n";

$telefono = '0000' . random_int(100000, 999999);
$email = "cliente$telefono@piccolo-falso.test";
$password = 'Prueba123!';

$r = pedir('POST', '/cuenta/registro', ['nombre' => 'Cliente API', 'codigoPais' => '54', 'telefono' => $telefono, 'email' => $email, 'password' => 'corta', 'confirmar' => 'corta']);
verificar($r['status'] === 422 && isset($r['json']['errores']['password']), 'el registro exige una contraseña segura', $r['json']);

$r = pedir('POST', '/cuenta/registro', ['nombre' => 'Cliente API', 'codigoPais' => '54', 'telefono' => $telefono, 'email' => $email, 'password' => $password, 'confirmar' => $password]);
verificar($r['status'] === 201 && ($r['json']['datos']['email'] ?? null) === $email, 'registrar un cliente nuevo', $r['json']);
verificar(!isset($r['json']['datos']['password']), 'la respuesta nunca incluye la contraseña');

$r = pedir('POST', '/cuenta/registro', ['nombre' => 'Otro', 'codigoPais' => '54', 'telefono' => $telefono, 'email' => "otro$email", 'password' => $password, 'confirmar' => $password]);
verificar($r['status'] === 409, 'no se puede registrar dos veces el mismo teléfono');

$r = pedir('GET', '/cuenta');
verificar($r['status'] === 200 && ($r['json']['datos']['puntos'] ?? null) === 0, 'al registrarse queda con la sesión iniciada');

$r = pedir('PUT', '/cuenta', ['nombre' => 'Cliente API Editado', 'codigoPais' => '54', 'telefono' => $telefono, 'email' => $email]);
verificar($r['status'] === 200 && ($r['json']['datos']['nombre'] ?? null) === 'Cliente API Editado', 'editar los datos del perfil', $r['json']);

$r = pedir('PUT', '/cuenta/password', ['actual' => 'incorrecta', 'nueva' => 'Nueva123!', 'confirmar' => 'Nueva123!']);
verificar($r['status'] === 422 && isset($r['json']['errores']['actual']), 'cambiar la contraseña pide la actual correcta');

$r = pedir('PUT', '/cuenta/password', ['actual' => $password, 'nueva' => 'Nueva123!', 'confirmar' => 'Nueva123!']);
verificar($r['status'] === 200, 'cambiar la contraseña', $r['json']);
$password = 'Nueva123!';

echo "\nPedidos\n";

if (isset($producto)) {
    $r = pedir('POST', '/pedidos', [
        'nombre' => 'Cliente API', 'codigoPais' => '54', 'telefono' => $telefono,
        'metodoPago' => 'Efectivo', 'tipoEntrega' => 'Delivery', 'direccion' => '',
        'productos' => [['id' => $producto['id'], 'cantidad' => 1]],
    ]);
    verificar($r['status'] === 422, 'un delivery sin dirección se rechaza', $r['json']);

    $r = pedir('POST', '/pedidos', [
        'nombre' => 'Cliente API', 'codigoPais' => '54', 'telefono' => $telefono,
        'metodoPago' => 'Efectivo', 'tipoEntrega' => 'Retiro',
        'productos' => [['id' => $producto['id'], 'cantidad' => 1, 'precio' => 1]],
    ]);
    verificar($r['status'] === 201, 'confirmar un pedido', $r['json']);
    verificar(($r['json']['datos']['total'] ?? null) == $producto['precio'], 'el pedido cobra el precio real aunque el navegador mande otro', $r['json']['datos'] ?? null);
    $pedidoId = $r['json']['datos']['id'] ?? 0;

    $r = pedir('GET', '/pedidos');
    verificar($r['status'] === 200 && in_array($pedidoId, array_column($r['json']['datos'] ?? [], 'id'), true), 'el pedido aparece en el historial', $r['json']);
}

echo "\nSesión\n";

$r = pedir('POST', '/cuenta/logout', []);
verificar($r['status'] === 200, 'cerrar sesión');

$r = pedir('GET', '/pedidos');
verificar($r['status'] === 401, 'sin sesión no se ve el historial');

$r = pedir('POST', '/cuenta/login', ['codigoPais' => '54', 'telefono' => $telefono, 'password' => 'incorrecta']);
verificar($r['status'] === 401, 'login con contraseña incorrecta');

$r = pedir('POST', '/cuenta/login', ['codigoPais' => '54', 'telefono' => $telefono, 'password' => $password]);
verificar($r['status'] === 200 && ($r['json']['datos']['telefono'] ?? '') !== '', 'login con la contraseña nueva', $r['json']);

$r = pedir('POST', '/cuenta/recuperar', ['email' => 'nadie@piccolo-falso.test']);
verificar($r['status'] === 200, 'recuperar contraseña no revela si el email existe');

$r = pedir('POST', '/cuenta/restablecer', ['token' => 'token-invalido', 'password' => 'Otra123!', 'confirmar' => 'Otra123!']);
verificar($r['status'] === 410, 'un enlace de recuperación inválido se rechaza');

$r = pedir('OPTIONS', '/menu', null, ['encabezados' => ['Origin: http://otro-sitio.test', 'Access-Control-Request-Method: GET']]);
verificar($r['status'] === 204 && stripos($r['encabezados'], 'Access-Control-Allow-Origin') === false, 'un origen no autorizado no recibe permisos CORS');

@unlink($cookies);

echo "\n" . ($pruebas - $fallas) . " de $pruebas verificaciones correctas.\n";
exit($fallas > 0 ? 1 : 0);
