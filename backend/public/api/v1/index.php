<?php

/**
 * API v1 de Piccolo Burgers. Todas las rutas entran por este archivo (ver .htaccess)
 * y la documentación está en docs/API.md.
 */

require_once __DIR__ . '/../../../src/Core/bootstrap.php';

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\Admin\ProveedoresApi;
use Piccolo\Http\Api\Admin\SesionPanelController;
use Piccolo\Http\Api\CatalogoController;
use Piccolo\Http\Api\Cors;
use Piccolo\Http\Api\CuentaController;
use Piccolo\Http\Api\PedidosController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Router;
use Piccolo\Http\Api\Sesion;
use Piccolo\Repositories\ProveedoresRepositorio;

if (Cors::aplicar()) {
    exit;
}

require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/reservas_virtuales.php');
require_once piccolo_path('src/Services/menu_catalogo.php');
require_once piccolo_path('src/Services/puntos_config.php');
require_once piccolo_path('src/Services/pedidos.php');
require_once piccolo_path('src/Services/estado_pago_helpers.php');
require_once piccolo_path('src/Services/Security/password_utils.php');
require_once piccolo_path('src/Services/Security/validar_telefono.php');
require_once piccolo_path('src/Services/Security/limite_intentos.php');
require_once piccolo_path('src/Services/Security/login_panel.php');
require_once piccolo_path('src/Http/Admin/sesion_panel.php');

Sesion::iniciar();

$catalogo = new CatalogoController($conexion);
$pedidos = new PedidosController($conexion);
$cuenta = new CuentaController($conexion);

$router = new Router();

// Catálogo e información del sitio
$router->get('/menu', [$catalogo, 'menu']);
$router->get('/menu/disponibilidad', [$catalogo, 'disponibilidad']);
$router->get('/inicio', [$catalogo, 'inicio']);
$router->get('/puntos/configuracion', [$catalogo, 'configuracionPuntos']);
$router->post('/comentarios', [$catalogo, 'comentar']);

// Carrito y pedidos
$router->get('/carrito', [$pedidos, 'verCarrito']);
$router->put('/carrito', [$pedidos, 'sincronizarCarrito']);
$router->delete('/carrito', [$pedidos, 'vaciarCarrito']);
$router->put('/carrito/productos/{id}', [$pedidos, 'fijarCantidad']);
$router->delete('/carrito/productos/{id}', [$pedidos, 'quitarProducto']);
$router->post('/pedidos', [$pedidos, 'crear']);
$router->get('/pedidos', [$pedidos, 'historial']);

// Cuenta del cliente
$router->post('/cuenta/registro', [$cuenta, 'registrar']);
$router->post('/cuenta/login', [$cuenta, 'iniciarSesion']);
$router->post('/cuenta/logout', [$cuenta, 'cerrarSesion']);
$router->get('/cuenta', [$cuenta, 'ver']);
$router->get('/cuenta/sesion', [$cuenta, 'sesion']);
$router->put('/cuenta', [$cuenta, 'actualizar']);
$router->put('/cuenta/password', [$cuenta, 'cambiarPassword']);
$router->post('/cuenta/recuperar', [$cuenta, 'recuperar']);
$router->post('/cuenta/restablecer', [$cuenta, 'restablecer']);

// Panel de administración (sesión del panel, permisos por rol y token CSRF)
$sesionPanel = new SesionPanelController($conexion);
$router->get('/admin/sesion', [$sesionPanel, 'ver']);
$router->post('/admin/sesion', [$sesionPanel, 'iniciar']);
$router->delete('/admin/sesion', [$sesionPanel, 'cerrar']);

$proveedores = new ProveedoresApi(new ProveedoresController(new ProveedoresRepositorio($conexion)));
$router->get('/admin/proveedores', [$proveedores, 'listar']);
$router->post('/admin/proveedores', [$proveedores, 'crear']);
$router->get('/admin/proveedores/{id}', [$proveedores, 'ver']);
$router->put('/admin/proveedores/{id}', [$proveedores, 'actualizar']);
$router->delete('/admin/proveedores/{id}', [$proveedores, 'eliminar']);

$router->despachar(Peticion::desdeServidor('/api/v1'));
