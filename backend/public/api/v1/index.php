<?php

/**
 * API v1 de Piccolo Burgers. Todas las rutas entran por este archivo (ver .htaccess)
 * y la documentación está en docs/API.md.
 */

require_once __DIR__ . '/../../../src/Core/bootstrap.php';

use Piccolo\Http\Admin\BebidasController;
use Piccolo\Http\Admin\CanjeController;
use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Admin\PuntosController;
use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\Admin\BebidasApi;
use Piccolo\Http\Api\Admin\CanjeApi;
use Piccolo\Http\Api\Admin\ClientesApi;
use Piccolo\Http\Api\Admin\ComprasApi;
use Piccolo\Http\Api\Admin\MateriasPrimasApi;
use Piccolo\Http\Api\Admin\MenuApi;
use Piccolo\Http\Api\Admin\PoolApi;
use Piccolo\Http\Api\Admin\PremiosApi;
use Piccolo\Http\Api\Admin\ProveedoresApi;
use Piccolo\Http\Api\Admin\SesionPanelController;
use Piccolo\Http\Api\Admin\UsuariosApi;
use Piccolo\Http\Api\Admin\VentasApi;
use Piccolo\Http\Api\CatalogoController;
use Piccolo\Http\Api\Cors;
use Piccolo\Http\Api\CuentaController;
use Piccolo\Http\Api\PedidosController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Router;
use Piccolo\Http\Api\Sesion;
use Piccolo\Repositories\BebidasRepositorio;
use Piccolo\Repositories\CanjeRepositorio;
use Piccolo\Repositories\ClientesRepositorio;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;
use Piccolo\Repositories\PoolRepositorio;
use Piccolo\Repositories\PremiosRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;
use Piccolo\Repositories\UsuariosRepositorio;
use Piccolo\Repositories\VentasRepositorio;

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

$repoProveedores = new ProveedoresRepositorio($conexion);
$repoMaterias = new MateriasPrimasRepositorio($conexion);

$proveedores = new ProveedoresApi(new ProveedoresController($repoProveedores));
$router->get('/admin/proveedores', [$proveedores, 'listar']);
$router->post('/admin/proveedores', [$proveedores, 'crear']);
$router->get('/admin/proveedores/{id}', [$proveedores, 'ver']);
$router->put('/admin/proveedores/{id}', [$proveedores, 'actualizar']);
$router->delete('/admin/proveedores/{id}', [$proveedores, 'eliminar']);

$materias = new MateriasPrimasApi(new MateriasPrimasController($repoMaterias, $repoProveedores));
$router->get('/admin/materias-primas', [$materias, 'listar']);
$router->post('/admin/materias-primas', [$materias, 'crear']);
$router->get('/admin/materias-primas/{id}', [$materias, 'ver']);
$router->put('/admin/materias-primas/{id}', [$materias, 'actualizar']);
$router->delete('/admin/materias-primas/{id}', [$materias, 'eliminar']);

$compras = new ComprasApi(new ComprasController(new ComprasRepositorio($conexion), $repoProveedores, $repoMaterias));
$router->get('/admin/compras', [$compras, 'listar']);
$router->post('/admin/compras', [$compras, 'registrar']);
$router->get('/admin/compras/resumen', [$compras, 'resumen']); // antes de /{id}, para que "resumen" no se tome como un id
$router->get('/admin/compras/{id}', [$compras, 'ver']);
$router->delete('/admin/compras/{id}', [$compras, 'eliminar']);

$menu = new MenuApi(new MenuController(new MenuRepositorio($conexion), $repoMaterias, piccolo_path('public/img/menu')));
$router->get('/admin/menu', [$menu, 'listar']);
$router->post('/admin/menu', [$menu, 'crear']);
$router->get('/admin/menu/categorias', [$menu, 'categorias']); // antes de /{id}
$router->get('/admin/menu/{id}', [$menu, 'ver']);
$router->put('/admin/menu/{id}', [$menu, 'actualizar']);
$router->delete('/admin/menu/{id}', [$menu, 'eliminar']);
$router->post('/admin/menu/{id}/foto', [$menu, 'foto']);
$router->get('/admin/menu/{id}/receta', [$menu, 'receta']);
$router->put('/admin/menu/{id}/receta', [$menu, 'guardarReceta']);

$ventas = new VentasApi(new VentasController(new VentasRepositorio($conexion), $conexion));
$router->get('/admin/ventas', [$ventas, 'resumen']);

$repoClientes = new ClientesRepositorio($conexion);
$clientes = new ClientesApi(new ClientesController($repoClientes));
$router->get('/admin/clientes', [$clientes, 'listar']);
$router->post('/admin/clientes', [$clientes, 'crear']);
$router->get('/admin/clientes/codigos-pais', [$clientes, 'codigosPais']); // antes de /{id}
$router->get('/admin/clientes/{id}', [$clientes, 'ver']);
$router->put('/admin/clientes/{id}', [$clientes, 'actualizar']);
$router->delete('/admin/clientes/{id}', [$clientes, 'eliminar']);

$canje = new CanjeApi(new CanjeController($repoClientes, new CanjeRepositorio($conexion), $conexion));
$router->get('/admin/clientes/{id}/canje', [$canje, 'datos']);
$router->post('/admin/clientes/{id}/canje/venta', [$canje, 'venta']);
$router->post('/admin/clientes/{id}/canje/premios', [$canje, 'premios']);

$usuarios = new UsuariosApi(new UsuariosController(new UsuariosRepositorio($conexion)));
$router->get('/admin/usuarios', [$usuarios, 'listar']);
$router->post('/admin/usuarios', [$usuarios, 'crear']);
$router->get('/admin/usuarios/{id}', [$usuarios, 'ver']);
$router->put('/admin/usuarios/{id}', [$usuarios, 'actualizar']);
$router->delete('/admin/usuarios/{id}', [$usuarios, 'eliminar']);

$premios = new PremiosApi(new PremiosController(new PremiosRepositorio($conexion)), new PuntosController($conexion));
$router->get('/admin/premios', [$premios, 'listar']);
$router->post('/admin/premios', [$premios, 'crear']);
$router->get('/admin/premios/{id}', [$premios, 'ver']);
$router->put('/admin/premios/{id}', [$premios, 'actualizar']);
$router->delete('/admin/premios/{id}', [$premios, 'eliminar']);
$router->get('/admin/puntos/configuracion', [$premios, 'verConfiguracion']);
$router->put('/admin/puntos/configuracion', [$premios, 'guardarConfiguracion']);

$poolController = new PoolController(new PoolRepositorio($conexion), $conexion);
$pool = new PoolApi($poolController);
$router->get('/admin/pool', [$pool, 'estado']);
$router->post('/admin/pool/datos-sensibles', [$pool, 'sensibles']);
$router->post('/admin/pool/jornada', [$pool, 'abrir']);
$router->get('/admin/pool/jornada/totales', [$pool, 'totales']);
$router->post('/admin/pool/jornada/cierre', [$pool, 'cerrar']);
$router->post('/admin/pool/turnos', [$pool, 'agregar']);
$router->put('/admin/pool/turnos/{id}', [$pool, 'renombrar']);
$router->post('/admin/pool/turnos/{id}/consumir', [$pool, 'consumir']);
$router->post('/admin/pool/turnos/{id}/revertir', [$pool, 'revertir']);
$router->post('/admin/pool/turnos/{id}/transferir', [$pool, 'transferir']);
$router->post('/admin/pool/turnos/{id}/mover', [$pool, 'mover']);
$router->put('/admin/pool/orden', [$pool, 'reordenar']);
$router->get('/admin/pool/configuracion', [$pool, 'verConfiguracion']);
$router->put('/admin/pool/configuracion', [$pool, 'guardarConfiguracion']);

$bebidas = new BebidasApi(new BebidasController(new BebidasRepositorio($conexion), $poolController, $conexion));
$router->get('/admin/bebidas', [$bebidas, 'estado']);
$router->post('/admin/bebidas/datos-sensibles', [$bebidas, 'sensibles']);
$router->post('/admin/bebidas/ventas', [$bebidas, 'vender']);
$router->put('/admin/bebidas/ventas/{id}', [$bebidas, 'renombrar']);
$router->post('/admin/bebidas/ventas/{id}/entregar', [$bebidas, 'entregar']);
$router->post('/admin/bebidas/ventas/{id}/revertir', [$bebidas, 'revertir']);
$router->get('/admin/bebidas/catalogo', [$bebidas, 'catalogo']);
$router->post('/admin/bebidas/catalogo', [$bebidas, 'crearBebida']);
$router->put('/admin/bebidas/catalogo/{id}', [$bebidas, 'actualizarBebida']);

$router->despachar(Peticion::desdeServidor('/api/v1'));
