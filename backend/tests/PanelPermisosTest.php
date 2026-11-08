<?php

declare(strict_types=1);

/**
 * Verifica los permisos del panel: entra con un usuario de cada rol y revisa a qué
 * pantallas puede y no puede entrar, y que los envíos sin token CSRF se rechacen.
 *
 * Uso:
 *   PANEL_USUARIOS="admin:Usuario:clave,empleado:Empleado:clave,delivery:Delivery:clave" \
 *   php tests/PanelPermisosTest.php http://localhost:8080/admin
 *
 * Los roles que no estén en PANEL_USUARIOS se saltean. Las pantallas esperadas salen
 * de config/permisos.php, así la prueba sigue a la configuración.
 */

$base = rtrim($argv[1] ?? 'http://localhost:8080/admin', '/');
$permisos = require __DIR__ . '/../config/permisos.php';
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

function pedir(string $url, string $cookies, string $metodo = 'GET', ?array $campos = null, array $encabezados = []): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_COOKIEFILE => $cookies,
        CURLOPT_HTTPHEADER => $encabezados,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($campos !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($campos));
    }
    $cuerpo = (string) curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $destino = (string) curl_getinfo($curl, CURLINFO_REDIRECT_URL);
    curl_close($curl);

    return ['status' => $status, 'cuerpo' => $cuerpo, 'destino' => $destino];
}

/** Pantallas que conviene revisar (solo lectura: se piden con GET). */
$pantallas = [
    'index.php', 'panel_cocina.php', 'panel_delivery.php', 'clientes.php',
    'seccion/menu/index.php', 'seccion/ventas/index.php', 'seccion/compras/index.php',
    'seccion/usuarios/index.php', 'seccion/premios/index.php', 'seccion/banners/index.php',
    'seccion/pool/index.php', 'seccion/bebidas/index.php', 'seccion/estadisticasPool/index.php',
    'seccion/puntos/index.php', 'seccion/poolParametros/index.php',
    'seccion/bebidasParametros/index.php', 'seccion/estadisticasBebidas/index.php',
    'seccion/proveedores/index.php', 'seccion/proveedores/crear.php',
    'seccion/materiasPrimas/index.php', 'seccion/materiasPrimas/crear.php', 'seccion/compras/crear.php',
    'seccion/menu/crear.php',
    'seccion/ventas/export_excel.php', 'seccion/ventas/export_pdf.php',
    'seccion/ventas/index.php?desde=2001-05-02&hasta=2001-05-01', // fechas al revés: avisa y muestra el mes
];

$usuarios = array_filter(explode(',', (string) getenv('PANEL_USUARIOS')));
if (!$usuarios) {
    fwrite(STDERR, "Definí PANEL_USUARIOS (ver el comentario al principio del archivo).\n");
    exit(1);
}

echo "Panel: $base\n";

// Sin sesión, ninguna pantalla protegida se abre
$sinSesion = tempnam(sys_get_temp_dir(), 'pb_');
$r = pedir("$base/seccion/menu/index.php", $sinSesion);
verificar($r['status'] === 302 && str_contains($r['destino'], 'login.php'), 'sin sesión, el panel redirige al login');
$r = pedir("$base/api/disponibilidad_menu.php", $sinSesion, 'POST', [], ['Accept: application/json']);
verificar($r['status'] === 401, 'sin sesión, los endpoints internos responden 401');
@unlink($sinSesion);

foreach ($usuarios as $definicion) {
    [$rol, $usuario, $clave] = array_pad(explode(':', $definicion, 3), 3, '');
    echo "\nRol $rol ($usuario)\n";

    $cookies = tempnam(sys_get_temp_dir(), 'pb_');
    pedir("$base/login.php", $cookies);
    $r = pedir("$base/login.php", $cookies, 'POST', ['usuario' => $usuario, 'password' => $clave]);
    verificar($r['status'] === 302 && str_contains($r['destino'], 'index.php'), 'inicia sesión');

    foreach ($pantallas as $pantalla) {
        $roles = $permisos['roles'][$pantalla] ?? null;
        if ($roles === null) {
            foreach ($permisos['roles'] as $ruta => $permitidos) {
                if (str_ends_with($ruta, '/') && str_starts_with($pantalla, $ruta)) {
                    $roles = $permitidos;
                }
            }
        }
        $puede = in_array($rol, $roles ?? ['admin'], true);
        $r = pedir("$base/$pantalla", $cookies);
        // Si puede entrar, además la pantalla no tiene que mostrar errores de PHP
        $sinErrores = !preg_match('/(Fatal error|Warning|Uncaught [A-Za-z]+)(<\/b>)?:/', $r['cuerpo']);
        verificar(
            $puede ? $r['status'] === 200 && $sinErrores : $r['status'] === 403,
            ($puede ? 'entra a ' : 'no entra a ') . $pantalla . " (respondió {$r['status']})"
        );
    }

    // Un envío sin token CSRF se rechaza, aunque el rol tenga permiso
    $r = pedir("$base/actualizar_estado.php", $cookies, 'POST', ['id' => 0, 'estado' => 'Listo']);
    verificar($r['status'] === 403, 'un envío sin token CSRF se rechaza');

    pedir("$base/cerrar.php", $cookies);
    @unlink($cookies);
}

echo "\n" . ($pruebas - $fallas) . " de $pruebas verificaciones correctas.\n";
exit($fallas > 0 ? 1 : 0);
