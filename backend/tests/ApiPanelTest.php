<?php

declare(strict_types=1);

/**
 * Recorre la API del panel (/api/v1/admin/...): sesión, permisos por rol, token CSRF y la
 * secciones de proveedores, materias primas, compras (incluido el ajuste del stock) y menú (receta y foto).
 *
 * Uso:
 *   PANEL_USUARIOS="admin:Usuario:clave,delivery:Delivery:clave" php tests/ApiPanelTest.php http://localhost:8080/api/v1
 *
 * Crea y borra un proveedor de prueba, así que conviene correrlo contra una base de desarrollo.
 */

$base = rtrim($argv[1] ?? 'http://localhost:8080/api/v1', '/');
$fallas = 0;
$pruebas = 0;

function verificar(bool $condicion, string $descripcion): void
{
    global $fallas, $pruebas;
    $pruebas++;
    echo ($condicion ? '  ok    ' : '  FALLA ') . $descripcion . "\n";
    if (!$condicion) {
        $fallas++;
    }
}

/** @return array{status: int, cuerpo: array} */
function api(string $metodo, string $ruta, string $cookies, ?array $datos = null, ?string $csrf = null): array
{
    global $base;
    $encabezados = ['Accept: application/json'];
    if ($datos !== null) {
        $encabezados[] = 'Content-Type: application/json';
    }
    if ($csrf !== null) {
        $encabezados[] = 'X-CSRF-Token: ' . $csrf;
    }

    $curl = curl_init($base . $ruta);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_COOKIEFILE => $cookies,
        CURLOPT_HTTPHEADER => $encabezados,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($datos !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    $respuesta = (string) curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    return ['status' => $status, 'cuerpo' => json_decode($respuesta, true) ?: []];
}

$usuarios = [];
foreach (array_filter(explode(',', (string) getenv('PANEL_USUARIOS'))) as $definicion) {
    [$rol, $usuario, $clave] = array_pad(explode(':', $definicion, 3), 3, '');
    $usuarios[$rol] = [$usuario, $clave];
}
if (!isset($usuarios['admin'])) {
    fwrite(STDERR, "Definí PANEL_USUARIOS con al menos un usuario admin (ver el comentario al principio).\n");
    exit(1);
}

echo "API del panel: $base\n\nSesión\n";
$cookies = tempnam(sys_get_temp_dir(), 'pb_api_');

$r = api('GET', '/admin/sesion', $cookies);
verificar($r['status'] === 200 && $r['cuerpo']['datos'] === null, 'sin sesión, GET /admin/sesion devuelve null');

$r = api('GET', '/admin/proveedores', $cookies);
verificar($r['status'] === 401, 'sin sesión, las secciones responden 401');

$r = api('POST', '/admin/sesion', $cookies, ['usuario' => $usuarios['admin'][0], 'password' => 'incorrecta']);
verificar($r['status'] === 401, 'una contraseña incorrecta se rechaza');

$r = api('POST', '/admin/sesion', $cookies, ['usuario' => $usuarios['admin'][0], 'password' => $usuarios['admin'][1]]);
$csrf = (string) ($r['cuerpo']['datos']['csrf_token'] ?? '');
verificar($r['status'] === 200 && ($r['cuerpo']['datos']['rol'] ?? '') === 'admin' && strlen($csrf) === 64, 'inicia sesión y recibe el token CSRF');

$r = api('GET', '/admin/sesion', $cookies);
verificar(($r['cuerpo']['datos']['usuario'] ?? '') === $usuarios['admin'][0], 'GET /admin/sesion muestra el usuario');

echo "\nProveedores\n";
$r = api('GET', '/admin/proveedores', $cookies);
verificar($r['status'] === 200 && is_array($r['cuerpo']['datos']), 'lista los proveedores');

$nuevo = ['nombre' => 'Proveedor de prueba API', 'telefono' => '3511234567', 'email' => ''];
$r = api('POST', '/admin/proveedores', $cookies, $nuevo);
verificar($r['status'] === 403, 'sin token CSRF no se puede crear');

$r = api('POST', '/admin/proveedores', $cookies, ['nombre' => '', 'telefono' => '1', 'email' => 'x'], $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['nombre'], $r['cuerpo']['errores']['telefono'], $r['cuerpo']['errores']['email']), 'datos inválidos devuelven 422 con el error de cada campo');

$r = api('POST', '/admin/proveedores', $cookies, $nuevo, $csrf);
$id = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && $id > 0 && $r['cuerpo']['datos']['email'] === null, 'crea un proveedor (201)');

$r = api('PUT', "/admin/proveedores/$id", $cookies, ['nombre' => 'Proveedor editado API', 'telefono' => '3519999999', 'email' => 'compras@prueba.test'], $csrf);
verificar($r['status'] === 200 && ($r['cuerpo']['datos']['nombre'] ?? '') === 'Proveedor editado API', 'edita el proveedor');

$r = api('GET', "/admin/proveedores/$id", $cookies);
verificar(($r['cuerpo']['datos']['email'] ?? '') === 'compras@prueba.test', 'muestra los datos guardados');

$r = api('DELETE', "/admin/proveedores/$id", $cookies, null, $csrf);
verificar($r['status'] === 200, 'borra el proveedor');

$r = api('GET', "/admin/proveedores/$id", $cookies);
verificar($r['status'] === 404, 'un proveedor borrado da 404');

echo "\nMaterias primas y compras\n";
$proveedor = api('POST', '/admin/proveedores', $cookies, ['nombre' => 'Proveedor compras API', 'telefono' => '3511234567'], $csrf)['cuerpo']['datos']['id'] ?? 0;

$r = api('POST', '/admin/materias-primas', $cookies, ['nombre' => 'Insumo de prueba API', 'unidad_medida' => 'kg', 'cantidad' => '1,5', 'stock_minimo' => 2, 'proveedor_id' => $proveedor], $csrf);
$materia = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && ($r['cuerpo']['datos']['cantidad'] ?? null) === 1.5 && ($r['cuerpo']['datos']['estado_stock'] ?? '') === 'bajo', 'crea una materia prima (acepta "1,5" y marca el stock bajo)');

$r = api('GET', '/admin/materias-primas?criticos=1', $cookies);
verificar(in_array($materia, array_column($r['cuerpo']['datos'] ?? [], 'id'), true), 'aparece entre los insumos críticos');

$r = api('POST', '/admin/compras', $cookies, ['fecha' => date('Y-m-d'), 'proveedor_id' => $proveedor, 'items' => []], $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['items']), 'una compra sin materias primas se rechaza');

$r = api('POST', '/admin/compras', $cookies, ['fecha' => date('Y-m-d'), 'proveedor_id' => $proveedor, 'items' => [['materia_prima_id' => $materia, 'cantidad' => 2.5, 'precio_unitario' => 100]]], $csrf);
$compra = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && ($r['cuerpo']['datos']['total'] ?? null) === 250.0, 'registra una compra (201) con su total');

$r = api('GET', "/admin/materias-primas/$materia", $cookies);
verificar(($r['cuerpo']['datos']['cantidad'] ?? null) === 4.0, 'la compra se sumó al stock');

$r = api('DELETE', "/admin/materias-primas/$materia", $cookies, null, $csrf);
verificar($r['status'] === 409, 'no deja borrar una materia prima con compras (409)');

$r = api('GET', '/admin/compras/resumen', $cookies);
verificar($r['status'] === 200 && isset($r['cuerpo']['datos']['gasto_mes']), 'muestra el resumen de gastos');

$r = api('DELETE', "/admin/compras/$compra", $cookies, null, $csrf);
$stock = api('GET', "/admin/materias-primas/$materia", $cookies)['cuerpo']['datos']['cantidad'] ?? null;
verificar($r['status'] === 200 && $stock === 1.5, 'borrar la compra descuenta del stock lo que había sumado');

$r = api('DELETE', "/admin/materias-primas/$materia", $cookies, null, $csrf);
$r2 = api('DELETE', "/admin/proveedores/$proveedor", $cookies, null, $csrf);
verificar($r['status'] === 200 && $r2['status'] === 200, 'sin compras, la materia prima y el proveedor se pueden borrar');

echo "\nMenú\n";
$r = api('GET', '/admin/menu/categorias', $cookies);
verificar(in_array('Hamburguesas', $r['cuerpo']['datos'] ?? [], true), 'lista las categorías');

$r = api('POST', '/admin/menu', $cookies, ['nombre' => 'Producto de prueba API', 'ingredientes' => 'Pan', 'precio' => '1999,90', 'categoria' => 'Hamburguesas', 'visible' => false], $csrf);
$producto = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && ($r['cuerpo']['datos']['precio'] ?? null) === 1999.9 && ($r['cuerpo']['datos']['visible'] ?? null) === false, 'crea un producto oculto (201)');

$insumo = api('POST', '/admin/materias-primas', $cookies, ['nombre' => 'Insumo receta API', 'unidad_medida' => 'kg', 'cantidad' => 3], $csrf)['cuerpo']['datos']['id'] ?? 0;
$r = api('PUT', "/admin/menu/$producto/receta", $cookies, ['items' => [['materia_prima_id' => $insumo, 'cantidad' => 0.5]]], $csrf);
verificar($r['status'] === 200 && count($r['cuerpo']['datos'] ?? []) === 1 && ($r['cuerpo']['datos'][0]['alcanza'] ?? false) === true, 'guarda la receta');

$subir = function (string $contenido, string $tipo) use ($base, $cookies, $producto, $csrf): array {
    $archivo = tempnam(sys_get_temp_dir(), 'pb_img_');
    file_put_contents($archivo, $contenido);
    $curl = curl_init("$base/admin/menu/$producto/foto");
    curl_setopt_array($curl, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $cookies, CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-CSRF-Token: ' . $csrf],
        CURLOPT_POSTFIELDS => ['foto' => new CURLFile($archivo, $tipo, 'foto.png')],
    ]);
    $cuerpo = json_decode((string) curl_exec($curl), true) ?: [];
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    @unlink($archivo);
    return ['status' => $status, 'cuerpo' => $cuerpo];
};
$r = $subir('<?php echo "no";', 'image/png');
verificar($r['status'] === 422, 'rechaza un archivo que no es una imagen aunque se llame .png');
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$r = $subir($png, 'image/png');
$foto = (string) ($r['cuerpo']['datos']['foto'] ?? '');
verificar($r['status'] === 200 && preg_match('/^producto_[0-9a-f]{16}\.png$/', $foto) === 1, 'sube una foto real con un nombre generado por el sistema');

$r = api('DELETE', "/admin/menu/$producto", $cookies, null, $csrf);
$r2 = api('DELETE', "/admin/materias-primas/$insumo", $cookies, null, $csrf);
verificar($r['status'] === 200 && $r2['status'] === 200, 'borra el producto sin ventas (y su receta)');

echo "
Ventas
";
$r = api('GET', '/admin/ventas', $cookies);
$datos = $r['cuerpo']['datos'] ?? [];
verificar($r['status'] === 200 && ($datos['desde'] ?? '') === date('Y-m-01') && count($datos['mensual']['meses'] ?? []) === 12, 'sin fechas, muestra el mes actual y los 12 meses del año');
$r = api('GET', '/admin/ventas?desde=2001-01-01&hasta=2001-12-31', $cookies);
verificar($r['status'] === 200 && (float) ($r['cuerpo']['datos']['total'] ?? -1) === 0.0 && array_key_exists('producto_estrella', $r['cuerpo']['datos'] ?? []) && $r['cuerpo']['datos']['producto_estrella'] === null, 'un año sin ventas da todo en cero');
$r = api('GET', '/admin/ventas?desde=2024-05-02&hasta=2024-05-01', $cookies);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['hasta']), 'rechaza un período al revés (422)');

echo "
Clientes
";
$r = api('POST', '/admin/clientes', $cookies, ['nombre' => 'Cliente API', 'email' => 'cliente.api@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '3519990077', 'puntos' => 20], $csrf);
$cliente = (int) ($r['cuerpo']['datos']['cliente']['id'] ?? 0);
$provisoria = (string) ($r['cuerpo']['datos']['password_provisoria'] ?? '');
verificar($r['status'] === 201 && $cliente > 0 && $provisoria !== '12345' && strlen($provisoria) >= 8, 'crea un cliente con una contraseña provisoria al azar');
$r = api('POST', '/admin/clientes', $cookies, ['nombre' => 'Repetido', 'email' => 'cliente.api@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '3519990077'], $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['email'], $r['cuerpo']['errores']['telefono']), 'no repite email ni teléfono (422)');
$r = api('PUT', "/admin/clientes/$cliente", $cookies, ['nombre' => 'Cliente API', 'email' => 'cliente.api@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '3519990077', 'puntos' => 5], $csrf);
$r2 = api('GET', "/admin/clientes/$cliente", $cookies);
$movs = $r2['cuerpo']['datos']['movimientos'] ?? [];
verificar($r['status'] === 200 && ($r['cuerpo']['datos']['puntos'] ?? null) === 5 && count($movs) === 2 && ($movs[0]['puntos'] ?? 0) === -15, 'al cambiar los puntos anota la diferencia');
$r = api('GET', '/admin/clientes/codigos-pais', $cookies);
verificar($r['status'] === 200 && isset($r['cuerpo']['datos']['54']), 'lista los códigos de país');

echo "
Canje de puntos
";
api('PUT', "/admin/clientes/$cliente", $cookies, ['nombre' => 'Cliente API', 'email' => 'cliente.api@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '3519990077', 'puntos' => 100000], $csrf);
$r = api('GET', "/admin/clientes/$cliente/canje", $cookies);
$canje = $r['cuerpo']['datos'] ?? [];
verificar($r['status'] === 200 && ($canje['cliente']['puntos'] ?? 0) === 100000 && !empty($canje['menu']) && isset($canje['config']['valor_punto']), 'trae el cliente, las reglas de puntos y los productos');
$premio = $canje['premios'][0] ?? null;
if ($premio) {
    $r = api('POST', "/admin/clientes/$cliente/canje/premios", $cookies, ['premios' => [['id' => $premio['id'], 'cantidad' => 1]]], $csrf);
    verificar($r['status'] === 201 && ($r['cuerpo']['datos']['puntos_actuales'] ?? 0) === 100000 - $premio['costo_puntos'], 'canjea un premio y descuenta su costo');
}
$r = api('POST', "/admin/clientes/$cliente/canje/premios", $cookies, ['premios' => []], $csrf);
verificar($r['status'] === 422, 'sin premios elegidos se rechaza (422)');
$r = api('POST', "/admin/clientes/$cliente/canje/venta", $cookies, ['items' => [['id' => $canje['menu'][0]['id'] ?? 0, 'cantidad' => 1]], 'puntos_utilizados' => 999999, 'metodo_pago' => 'Efectivo', 'tipo_entrega' => 'Retiro'], $csrf);
verificar($r['status'] === 422, 'una venta con más puntos que el tope se rechaza (422)');
$r = api('POST', "/admin/clientes/$cliente/canje/venta", $cookies, ['items' => [['id' => $canje['menu'][0]['id'] ?? 0, 'cantidad' => 1]], 'puntos_utilizados' => 0, 'metodo_pago' => 'Bitcoin', 'tipo_entrega' => 'Retiro'], $csrf);
verificar($r['status'] === 422, 'un método de pago inexistente se rechaza (422)');

$r = api('DELETE', "/admin/clientes/$cliente", $cookies, null, $csrf);
$r2 = api('GET', "/admin/clientes/$cliente", $cookies);
verificar($r['status'] === 200 && $r2['status'] === 404, 'borra el cliente');

echo "
Usuarios del panel
";
$r = api('GET', '/admin/usuarios', $cookies);
$yo = array_values(array_filter($r['cuerpo']['datos'] ?? [], fn($u) => $u['sos_vos'] ?? false))[0] ?? null;
verificar($r['status'] === 200 && $yo !== null && $yo['se_puede_borrar'] === false, 'lista los usuarios y marca el propio como protegido');
$r = api('DELETE', '/admin/usuarios/' . ($yo['id'] ?? 0), $cookies, null, $csrf);
verificar($r['status'] === 409, 'no deja borrar el propio usuario (409)');
$nuevo = ['usuario' => 'empleado_api', 'correo' => 'empleado.api@ejemplo.test', 'rol' => 'empleado', 'password' => 'Empleado9!', 'confirmar' => 'Empleado9!'];
$r = api('POST', '/admin/usuarios', $cookies, ['password' => 'corta'] + $nuevo, $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['password']), 'pide una contraseña segura (422)');
$r = api('POST', '/admin/usuarios', $cookies, $nuevo, $csrf);
$empleado = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && $empleado > 0, 'crea un empleado');
$r = api('PUT', "/admin/usuarios/$empleado", $cookies, ['rol' => 'admin'] + $nuevo, $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['password_actual']), 'para hacerlo administrador pide tu contraseña (422)');

// Una sesión abierta del empleado se corta en cuanto se lo borra
$suya = tempnam(sys_get_temp_dir(), 'pb_api_');
api('POST', '/admin/sesion', $suya, ['usuario' => 'empleado_api', 'password' => 'Empleado9!']);
$r = api('GET', '/admin/sesion', $suya);
verificar(($r['cuerpo']['datos']['rol'] ?? '') === 'empleado', 'el empleado nuevo puede entrar');
$r = api('DELETE', "/admin/usuarios/$empleado", $cookies, null, $csrf);
$r2 = api('GET', '/admin/sesion', $suya);
verificar($r['status'] === 200 && array_key_exists('datos', $r2['cuerpo']) && $r2['cuerpo']['datos'] === null, 'al borrarlo, su sesión abierta deja de valer');
@unlink($suya);

echo "\nPremios y puntos\n";
$r = api('POST', '/admin/premios', $cookies, ['nombre' => 'Premio API', 'descripcion' => '', 'costo_puntos' => '0'], $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['costo_puntos']), 'un premio sin costo se rechaza (422)');
$r = api('POST', '/admin/premios', $cookies, ['nombre' => 'Premio API', 'descripcion' => 'Algo rico', 'costo_puntos' => 150], $csrf);
$premioApi = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && ($r['cuerpo']['datos']['costo_puntos'] ?? 0) === 150, 'crea un premio');
$r = api('PUT', "/admin/premios/$premioApi", $cookies, ['nombre' => 'Premio API', 'descripcion' => '', 'costo_puntos' => 200], $csrf);
verificar($r['status'] === 200 && ($r['cuerpo']['datos']['costo_puntos'] ?? 0) === 200 && array_key_exists('descripcion', $r['cuerpo']['datos']) && $r['cuerpo']['datos']['descripcion'] === null, 'lo modifica');
$r = api('DELETE', "/admin/premios/$premioApi", $cookies, null, $csrf);
verificar($r['status'] === 200 && api('GET', "/admin/premios/$premioApi", $cookies)['status'] === 404, 'lo borra');
$r = api('GET', '/admin/puntos/configuracion', $cookies);
$config = $r['cuerpo']['datos'] ?? [];
verificar($r['status'] === 200 && isset($config['minimo_puntos'], $config['ejemplo']['maximo_puntos']), 'muestra la configuración de puntos con un ejemplo');
$r = api('PUT', '/admin/puntos/configuracion', $cookies, ['minimo_puntos' => -1, 'valor_punto' => 0, 'maximo_porcentaje' => 150], $csrf);
verificar($r['status'] === 422 && count($r['cuerpo']['errores'] ?? []) === 3, 'rechaza valores fuera de rango (422)');
$r = api('PUT', '/admin/puntos/configuracion', $cookies, ['minimo_puntos' => $config['minimo_puntos'] ?? 50, 'valor_punto' => $config['valor_punto'] ?? 20, 'maximo_porcentaje' => $config['maximo_porcentaje'] ?? 25], $csrf);
verificar($r['status'] === 200 && ($r['cuerpo']['datos']['maximo_porcentaje'] ?? null) == ($config['maximo_porcentaje'] ?? 25), 'guarda la configuración');

echo "\nPool\n";
$r = api('GET', '/admin/pool', $cookies);
verificar($r['status'] === 200 && array_key_exists('montoVendido', $r['cuerpo']['datos']['pools']['azul']['stats'] ?? []) && $r['cuerpo']['datos']['pools']['azul']['stats']['montoVendido'] === null, 'muestra las mesas sin los montos');
$r = api('POST', '/admin/pool/datos-sensibles', $cookies, ['password' => 'incorrecta'], $csrf);
verificar($r['status'] === 422, 'con una contraseña incorrecta no muestra los montos (422)');
$r = api('POST', '/admin/pool/datos-sensibles', $cookies, ['password' => $usuarios['admin'][1]], $csrf);
verificar($r['status'] === 200 && is_numeric($r['cuerpo']['datos']['pools']['azul']['stats']['montoVendido'] ?? null), 'con tu contraseña muestra los montos');
$r = api('POST', '/admin/pool/turnos/999999/consumir', $cookies, null, $csrf);
verificar(in_array($r['status'], [404, 409], true), 'un turno que no existe no se toca');
$r = api('PUT', '/admin/pool/configuracion', $cookies, ['max_fichas_por_registro' => 0, 'valor_ficha' => 0], $csrf);
verificar($r['status'] === 422 && count($r['cuerpo']['errores'] ?? []) === 2, 'rechaza parámetros fuera de rango (422)');

echo "\nBebidas\n";
$r = api('GET', '/admin/bebidas', $cookies);
verificar($r['status'] === 200 && array_key_exists('montoVendido', $r['cuerpo']['datos']['stats'] ?? []) && $r['cuerpo']['datos']['stats']['montoVendido'] === null, 'muestra las bebidas sin los montos');
$r = api('POST', '/admin/bebidas/catalogo', $cookies, ['nombre' => '', 'valor_jarra' => 0, 'valor_promo' => 0, 'jarras_por_promo' => 0], $csrf);
verificar($r['status'] === 422 && count($r['cuerpo']['errores'] ?? []) === 4, 'una bebida sin datos se rechaza con el error de cada campo (422)');
$nombreBebida = 'Bebida API ' . bin2hex(random_bytes(3));
$r = api('POST', '/admin/bebidas/catalogo', $cookies, ['nombre' => $nombreBebida, 'valor_jarra' => '1500,5', 'valor_promo' => 4000, 'jarras_por_promo' => 3, 'activa' => false], $csrf);
$bebidaApi = (int) ($r['cuerpo']['datos']['id'] ?? 0);
verificar($r['status'] === 201 && ($r['cuerpo']['datos']['valor_jarra'] ?? 0) === 1500.5 && ($r['cuerpo']['datos']['activa'] ?? true) === false, 'agrega una bebida (inactiva, con decimales con coma)');
$r = api('POST', '/admin/bebidas/catalogo', $cookies, ['nombre' => $nombreBebida, 'valor_jarra' => 1, 'valor_promo' => 1, 'jarras_por_promo' => 1], $csrf);
verificar($r['status'] === 422 && isset($r['cuerpo']['errores']['nombre']), 'no repite el nombre (422)');
$r = api('POST', '/admin/bebidas/ventas', $cookies, ['bebida_id' => $bebidaApi, 'persona' => 'Ana', 'tipo' => 'jarra', 'cantidad' => 1], $csrf);
verificar(in_array($r['status'], [409, 422], true), 'no se vende una bebida inactiva ni sin jornada');

if (isset($usuarios['delivery'])) {
    echo "\nOtro rol\n";
    $otra = tempnam(sys_get_temp_dir(), 'pb_api_');
    api('POST', '/admin/sesion', $otra, ['usuario' => $usuarios['delivery'][0], 'password' => $usuarios['delivery'][1]]);
    $r = api('GET', '/admin/proveedores', $otra);
    verificar($r['status'] === 403, 'el rol delivery no puede ver proveedores (403)');
    $r = api('GET', '/admin/ventas', $otra);
    verificar($r['status'] === 403, 'ni las ventas (403)');
    $r = api('GET', '/admin/clientes', $otra);
    verificar($r['status'] === 403, 'ni los clientes (403)');
    $r = api('GET', '/admin/usuarios', $otra);
    verificar($r['status'] === 403, 'ni los usuarios (403)');
    $r = api('GET', '/admin/premios', $otra);
    verificar($r['status'] === 403, 'ni los premios (403)');
    $r = api('GET', '/admin/pool', $otra);
    verificar($r['status'] === 403, 'ni el pool (403)');
    $r = api('GET', '/admin/bebidas', $otra);
    verificar($r['status'] === 403, 'ni las bebidas (403)');
    @unlink($otra);
}

echo "\nCierre\n";
$r = api('DELETE', '/admin/sesion', $cookies);
verificar($r['status'] === 403, 'cerrar sesión también pide el token CSRF');

$r = api('DELETE', '/admin/sesion', $cookies, null, $csrf);
verificar($r['status'] === 200, 'cierra la sesión');

$r = api('GET', '/admin/proveedores', $cookies);
verificar($r['status'] === 401, 'después de cerrar, las secciones responden 401');
@unlink($cookies);

echo "\n" . ($pruebas - $fallas) . " de $pruebas verificaciones correctas.\n";
exit($fallas > 0 ? 1 : 0);
