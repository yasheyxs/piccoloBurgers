<?php

declare(strict_types=1);

/**
 * Recorre la API del panel (/api/v1/admin/...): sesión, permisos por rol, token CSRF y la
 * sección de proveedores completa (crear, editar, borrar).
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

if (isset($usuarios['delivery'])) {
    echo "\nOtro rol\n";
    $otra = tempnam(sys_get_temp_dir(), 'pb_api_');
    api('POST', '/admin/sesion', $otra, ['usuario' => $usuarios['delivery'][0], 'password' => $usuarios['delivery'][1]]);
    $r = api('GET', '/admin/proveedores', $otra);
    verificar($r['status'] === 403, 'el rol delivery no puede ver proveedores (403)');
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
