<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['admin_usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no válida."], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/bd.php';

function piccolo_normalizar_clave_pago(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $valor = mb_strtolower($valor, 'UTF-8');
    } else {
        $valor = strtolower($valor);
    }

    $valor = str_replace(
        [
            'á', 'à', 'ä', 'â', 'Á', 'À', 'Ä', 'Â',
            'é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê',
            'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î',
            'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô',
            'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û'
        ],
        [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u'
        ],
        $valor
    );

    $valor = preg_replace('/[^a-z0-9]/', '', $valor) ?? '';

    return $valor;
}

function piccolo_obtener_mapa_valores_pago(PDO $conexion): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = [
        'si' => 'Si',
        'no' => 'No',
    ];

    try {
        $consulta = $conexion->query("SHOW COLUMNS FROM tbl_pedidos LIKE 'esta_pago'");
        $columna = $consulta !== false ? $consulta->fetch(PDO::FETCH_ASSOC) : false;

        if ($columna && isset($columna['Type'])) {
            if (preg_match_all("/'((?:''|[^'])*)'/", $columna['Type'], $coincidencias)) {
                $mapa = [];

                foreach ($coincidencias[1] as $valorCrudo) {
                    $valorPermitido = str_replace("''", "'", $valorCrudo);
                    $clave = piccolo_normalizar_clave_pago($valorPermitido);

                    if ($clave !== '') {
                        $mapa[$clave] = $valorPermitido;
                    }
                }

                if (isset($mapa['si'], $mapa['no'])) {
                    $cache = $mapa;
                } else {
                    $cache = array_merge($cache, $mapa);
                }
            }
        }
    } catch (Exception $e) {
        // Se mantiene el valor por defecto en caso de error al consultar el esquema.
    }

    return $cache;
}

function piccolo_resolver_valor_pago(PDO $conexion, string $valorDeseado): ?string
{
    $clave = piccolo_normalizar_clave_pago($valorDeseado);
    if ($clave === '') {
        return null;
    }

    $mapa = piccolo_obtener_mapa_valores_pago($conexion);
    if (isset($mapa[$clave])) {
        return $mapa[$clave];
    }

    if ($clave === 'si') {
        return 'Si';
    }

    if ($clave === 'no') {
        return 'No';
    }

    return null;
}

try {
    $sentencia = $conexion->prepare('SELECT rol FROM tbl_usuarios WHERE usuario = :usuario LIMIT 1');
    $sentencia->execute([':usuario' => $_SESSION['admin_usuario']]);
    $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudo validar la sesión."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$usuario) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario no autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

$rol = $usuario['rol'] ?? '';
$rolesPermitidos = ['admin', 'empleado', 'delivery'];
if (!in_array($rol, $rolesPermitidos, true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tenés permisos para realizar esta acción."], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenSesion = $_SESSION['csrf_token'] ?? '';
$tokenRecibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!$tokenSesion || !$tokenRecibido || !hash_equals($tokenSesion, $tokenRecibido)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Token CSRF inválido."], JSON_UNESCAPED_UNICODE);
    exit;
}

$pedido_id = $_POST["pedido_id"] ?? null;
$nuevo_estado = $_POST["nuevo_estado"] ?? null;
$estado_pago_recibido = $_POST["esta_pago"] ?? $_POST["estado_pago"] ?? null;

if (!$pedido_id) {// Validar que se reciba el ID del pedido

    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$pedido_id = intval($pedido_id);
$estados_validos = ["En preparación", "Listo", "En camino", "Entregado", "Cancelado"];
$actualizaciones = [];
$valores = [];

if ($nuevo_estado !== null) {
    $nuevo_estado = trim((string)$nuevo_estado);
    $estados_validos = ["En preparación", "Listo", "En camino", "Entregado", "Cancelado"];
    if (!in_array($nuevo_estado, $estados_validos, true)) {
        echo json_encode(["success" => false, "message" => "Estado inválido."]);
        exit;
    }

    $actualizaciones[] = "estado = ?";
    $valores[] = $nuevo_estado;
}

$estado_pago = null;
if ($estado_pago_recibido !== null) {
    $estado_pago = piccolo_resolver_valor_pago($conexion, (string)$estado_pago_recibido);
    if ($estado_pago === null) {
        echo json_encode(["success" => false, "message" => "Valor de pago inválido."]);
        exit;
    }
}

$estado_pago_por_estado = null;
if ($nuevo_estado !== null) {
    if (in_array($nuevo_estado, ['Listo', 'Entregado'], true)) {
        $estado_pago_por_estado = piccolo_resolver_valor_pago($conexion, 'Si');
    } elseif ($nuevo_estado === 'Cancelado') {
        $estado_pago_por_estado = piccolo_resolver_valor_pago($conexion, 'No');
    }
}

$estado_pago_final = $estado_pago_por_estado ?? $estado_pago;
if ($estado_pago_final !== null) {
    $actualizaciones[] = "esta_pago = ?";
    $valores[] = $estado_pago_final;
}

if (count($actualizaciones) === 0) {
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

try {
    $sql = "UPDATE tbl_pedidos SET " . implode(', ', $actualizaciones) . " WHERE ID = ?";
    $valores[] = $pedido_id;

    $stmt = $conexion->prepare($sql);
    $stmt->execute($valores);

    echo json_encode(["success" => true, "message" => "Actualización realizada correctamente."]);
} catch (Exception $e) {// Capturar cualquier error al actualizar el estado
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
    exit;
}

if (isset($nuevo_estado) && $nuevo_estado === "Listo") {
    $stmt = $conexion->prepare("
        SELECT pd.producto_id, pd.cantidad, mp.materia_prima_id, mp.cantidad AS requerido
        FROM tbl_pedidos_detalle pd
        JOIN tbl_menu_materias_primas mp ON mp.menu_id = pd.producto_id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($insumos as $insumo) {
        $consumo = $insumo['requerido'] * $insumo['cantidad'];
        $stmtUpdate = $conexion->prepare("
            UPDATE tbl_materias_primas
            SET cantidad = GREATEST(0, cantidad - ?)
            WHERE ID = ?
        ");
        $stmtUpdate->execute([$consumo, $insumo['materia_prima_id']]);
    }
}
