<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/bebidas_schema.php';
require_once __DIR__ . '/../../../app/Services/Security/password_utils.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function bebidaResponder(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bebidaValidarAcceso(): void
{
    $rol = $_SESSION['rol'] ?? '';
    if (!isset($_SESSION['admin_logueado']) || !in_array($rol, ['admin', 'empleado'], true)) {
        bebidaResponder(['exito' => false, 'mensaje' => 'No autorizado.'], 403);
    }
}

function bebidaValidarCsrf(): void
{
    $sesion = (string) ($_SESSION['csrf_token'] ?? '');
    $recibido = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sesion === '' || !hash_equals($sesion, $recibido)) {
        bebidaResponder(['exito' => false, 'mensaje' => 'Token CSRF inválido.'], 403);
    }
}

function bebidaPayload(): array
{
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    return is_array($payload) ? $payload : [];
}

function bebidaEstadoPublico(PDO $conexion): array
{
    $estado = bebidaObtenerEstado($conexion);
    $estado['stats']['vendidas'] = null;
    $estado['stats']['entregadas'] = null;
    $estado['stats']['montoVendido'] = null;

    foreach ($estado['bebidas'] as &$bebida) {
        $bebida['valor_jarra'] = null;
        $bebida['valor_promo'] = null;
    }
    unset($bebida);

    foreach ($estado['ventas'] as &$venta) {
        $venta['cantidad_total'] = null;
        $venta['cantidad_entregada'] = null;
        $venta['valor_unitario'] = null;
    }
    unset($venta);

    return $estado;
}

bebidaValidarAcceso();
asegurarTablasBebidas($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    bebidaResponder(['exito' => true, 'estado' => bebidaEstadoPublico($conexion)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bebidaResponder(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
}

bebidaValidarCsrf();
$payload = bebidaPayload();
$accion = (string) ($payload['accion'] ?? '');

try {
    switch ($accion) {
        case 'validar_privacidad':
            $password = (string) ($payload['password'] ?? '');
            $usuario = (string) ($_SESSION['admin_usuario'] ?? '');
            $stmt = $conexion->prepare('SELECT password FROM tbl_usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $hash = (string) ($stmt->fetchColumn() ?: '');

            if ($password === '' || $usuario === '' || !passwordCoincideConHash($password, $hash)) {
                bebidaResponder(['exito' => false, 'mensaje' => 'La contraseña es incorrecta.'], 401);
            }
            bebidaResponder([
                'exito' => true,
                'privacidadAutorizada' => true,
                'estado' => bebidaObtenerEstado($conexion),
            ]);
            break;

        case 'abrir_jornada':
            poolAbrirJornada($conexion);
            break;

        case 'cerrar_jornada':
            poolCerrarJornada($conexion);
            break;

        case 'agregar':
            $jornada = poolJornadaActiva($conexion);
            if (!$jornada) {
                throw new RuntimeException('Abrí una jornada antes de registrar ventas.');
            }
            bebidaRegistrarVenta(
                $conexion,
                (int) $jornada['id'],
                (int) ($payload['bebidaId'] ?? 0),
                (string) ($payload['persona'] ?? ''),
                (string) ($payload['tipo'] ?? 'jarra'),
                (int) ($payload['cantidad'] ?? 0)
            );
            break;

        case 'renombrar':
            $jornada = poolJornadaActiva($conexion);
            if (!$jornada) {
                throw new RuntimeException('No hay una jornada activa.');
            }

            $id = (int) ($payload['id'] ?? 0);
            $nombre = trim((string) ($payload['nombre'] ?? ''));
            $largoNombre = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);

            if ($id <= 0) {
                throw new RuntimeException('Venta inválida.');
            }

            if ($nombre === '') {
                throw new RuntimeException('Ingresa un nombre o identificación.');
            }

            if ($largoNombre > 120) {
                throw new RuntimeException('El nombre no puede superar 120 caracteres.');
            }

            $stmt = $conexion->prepare(
                'UPDATE bebida_ventas SET persona = :persona WHERE id = :id AND jornada_id = :jornada_id'
            );
            $stmt->execute([
                ':persona' => $nombre,
                ':id' => $id,
                ':jornada_id' => (int) $jornada['id'],
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('No encontramos esa venta en la jornada activa.');
            }
            break;

        case 'entregar':
        case 'revertir':
            $jornada = poolJornadaActiva($conexion);
            if (!$jornada) {
                throw new RuntimeException('No hay una jornada activa.');
            }
            $id = (int) ($payload['id'] ?? 0);
            $stmt = $conexion->prepare(
                'SELECT cantidad_total, cantidad_entregada, unidades_por_item
                 FROM bebida_ventas WHERE id = :id AND jornada_id = :jornada_id'
            );
            $stmt->execute([':id' => $id, ':jornada_id' => (int) $jornada['id']]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$venta) {
                throw new RuntimeException('No encontramos esa venta en la jornada activa.');
            }

            $cantidadAEntregar = (int) $venta['cantidad_total'] * max(1, (int) $venta['unidades_por_item']);
            if ($accion === 'entregar' && (int) $venta['cantidad_entregada'] >= $cantidadAEntregar) {
                throw new RuntimeException('La venta ya fue entregada por completo.');
            }
            if ($accion === 'revertir' && (int) $venta['cantidad_entregada'] <= 0) {
                throw new RuntimeException('No hay entregas para revertir.');
            }

            $expresion = $accion === 'entregar'
                ? 'LEAST(cantidad_total * unidades_por_item, cantidad_entregada + 1)'
                : 'GREATEST(0, cantidad_entregada - 1)';
            $update = $conexion->prepare(
                "UPDATE bebida_ventas SET cantidad_entregada = {$expresion}
                 WHERE id = :id AND jornada_id = :jornada_id"
            );
            $update->execute([':id' => $id, ':jornada_id' => (int) $jornada['id']]);
            break;

        default:
            bebidaResponder(['exito' => false, 'mensaje' => 'Acción no reconocida.'], 422);
    }

    bebidaResponder(['exito' => true, 'estado' => bebidaEstadoPublico($conexion)]);
} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error en modulo bebidas: ' . $error->getMessage());
    bebidaResponder([
        'exito' => false,
        'mensaje' => $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo completar la operación.',
    ], $error instanceof RuntimeException ? 409 : 500);
}
