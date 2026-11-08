<?php
/**
 * Operaciones de la pantalla del pool (assets/js/pages/pool.js). Recibe {accion, ...} en JSON
 * y responde {exito, estado} o {exito: false, mensaje}. Las reglas están en PoolController.
 */
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PoolRepositorio;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$responder = static function (int $status, array $cuerpo): never {
    http_response_code($status);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$pool = new PoolController(new PoolRepositorio($conexion), $conexion);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $responder(200, ['exito' => true, 'estado' => $pool->estado()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $responder(405, ['exito' => false, 'mensaje' => 'Método no permitido.']);
}

$datos = json_decode((string) file_get_contents('php://input'), true);
$datos = is_array($datos) ? $datos : [];
$id = (int) ($datos['id'] ?? 0);

try {
    switch ($datos['accion'] ?? '') {
        case 'validar_privacidad':
            $estado = $pool->mostrarSensibles((int) ($_SESSION['admin_id'] ?? 0), (string) ($datos['password'] ?? ''));
            $responder(200, ['exito' => true, 'privacidadAutorizada' => true, 'estado' => $estado]);
            // no break: $responder termina la ejecución
        case 'totales_cierre':
            $responder(200, ['exito' => true, 'totales' => $pool->totalesCierre(), 'estado' => $pool->estado()]);
            // no break
        case 'abrir_jornada':
            $pool->abrirJornada();
            break;
        case 'cerrar_jornada':
            $pool->cerrarJornada(is_array($datos['totales'] ?? null) ? $datos['totales'] : []);
            break;
        case 'agregar':
            $pool->agregar($datos);
            break;
        case 'renombrar':
            $pool->renombrar($id, $datos);
            break;
        case 'consumir':
            $pool->consumir($id);
            break;
        case 'revertir':
            $pool->revertir($id);
            break;
        case 'transferir':
            $pool->transferir($id, $datos);
            break;
        case 'mover':
            $pool->mover($id, $datos);
            break;
        case 'reordenar':
            $pool->reordenar($datos);
            break;
        default:
            throw new ApiException('Acción no reconocida.', 400);
    }
    $responder(200, ['exito' => true, 'estado' => $pool->estado()]);
} catch (ApiException $error) {
    $mensaje = $error->errores() ? implode(' ', $error->errores()) : $error->getMessage();
    $responder($error->status(), ['exito' => false, 'mensaje' => $mensaje]);
} catch (Throwable $error) {
    error_log('Error en el pool: ' . $error->getMessage());
    $responder(500, ['exito' => false, 'mensaje' => 'No se pudo completar la operación. Intentá de nuevo.']);
}
