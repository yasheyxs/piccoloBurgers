<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');

function poolResponder(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function poolValidarAcceso(): void
{
    $rol = $_SESSION['rol'] ?? '';
    if (!isset($_SESSION['admin_logueado']) || !in_array($rol, ['admin', 'empleado'], true)) {
        poolResponder(['exito' => false, 'mensaje' => 'No autorizado.'], 403);
    }
}

function poolValidarCsrf(): void
{
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if ($tokenSesion === '' || !hash_equals((string) $tokenSesion, (string) $token)) {
        poolResponder(['exito' => false, 'mensaje' => 'Token CSRF invalido.'], 403);
    }
}

function poolPayload(): array
{
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?: '[]', true);
    return is_array($payload) ? $payload : [];
}

function poolJornadaActivaObligatoria(PDO $conexion): array
{
    $jornada = poolJornadaActiva($conexion);
    if (!$jornada) {
        poolResponder(['exito' => false, 'mensaje' => 'Abri una jornada antes de operar.'], 409);
    }

    return $jornada;
}

poolValidarAcceso();
asegurarTablaPoolTurnos($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    poolResponder(['exito' => true, 'estado' => poolObtenerEstado($conexion)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    poolResponder(['exito' => false, 'mensaje' => 'Metodo no permitido.'], 405);
}

poolValidarCsrf();

$payload = poolPayload();
$accion = (string) ($payload['accion'] ?? '');

try {
    switch ($accion) {
        case 'abrir_jornada':
            poolAbrirJornada($conexion);
            break;

        case 'cerrar_jornada':
            poolCerrarJornada($conexion);
            break;

        case 'agregar':
            $jornada = poolJornadaActivaObligatoria($conexion);
            $jornadaId = (int) $jornada['id'];
            $pool = poolNormalizar((string) ($payload['pool'] ?? 'azul'));
            $nombre = trim((string) ($payload['nombre'] ?? ''));
            $fichas = (int) ($payload['fichas'] ?? 0);
            $configuracion = poolObtenerConfiguracion($conexion);
            $maxFichasPorRegistro = (int) $configuracion['max_fichas_por_registro'];
            $valorFicha = (float) $configuracion['valor_ficha'];

            if ($nombre === '') {
                poolResponder(['exito' => false, 'mensaje' => 'Ingresa un nombre o identificacion.'], 422);
            }

            if ($fichas < 1 || $fichas > 99) {
                poolResponder(['exito' => false, 'mensaje' => 'Ingresa una cantidad de fichas valida.'], 422);
            }

            $conexion->beginTransaction();
            $orden = poolSiguienteOrden($conexion, $pool, $jornadaId);
            $insert = $conexion->prepare(
                'INSERT INTO pool_turnos (jornada_id, pool, nombre, fichas_total, fichas_consumidas, valor_ficha, orden, jornada_fecha)
                 VALUES (:jornada_id, :pool, :nombre, :fichas_total, 0, :valor_ficha, :orden, :fecha)'
            );

            while ($fichas > 0) {
                $lote = min($maxFichasPorRegistro, $fichas);
                $insert->execute([
                    ':jornada_id' => $jornadaId,
                    ':pool' => $pool,
                    ':nombre' => $nombre,
                    ':fichas_total' => $lote,
                    ':valor_ficha' => $valorFicha,
                    ':orden' => $orden,
                    ':fecha' => poolFechaJornadaActual(),
                ]);
                $orden++;
                $fichas -= $lote;
            }
            $conexion->commit();
            break;

        case 'consumir':
        case 'revertir':
            $jornada = poolJornadaActivaObligatoria($conexion);
            $jornadaId = (int) $jornada['id'];
            $id = (int) ($payload['id'] ?? 0);
            if ($id <= 0) {
                poolResponder(['exito' => false, 'mensaje' => 'Turno invalido.'], 422);
            }

            $turnoStmt = $conexion->prepare(
                'SELECT fichas_total, fichas_consumidas FROM pool_turnos WHERE id = :id AND jornada_id = :jornada_id'
            );
            $turnoStmt->execute([':id' => $id, ':jornada_id' => $jornadaId]);
            $turno = $turnoStmt->fetch(PDO::FETCH_ASSOC);

            if (!$turno) {
                poolResponder(['exito' => false, 'mensaje' => 'No encontramos ese turno en la jornada activa.'], 404);
            }

            $fichasTotal = (int) $turno['fichas_total'];
            $fichasConsumidas = (int) $turno['fichas_consumidas'];

            if ($accion === 'consumir' && $fichasConsumidas >= $fichasTotal) {
                poolResponder(['exito' => false, 'mensaje' => 'Este turno ya alcanzo la cantidad maxima de fichas permitidas.'], 422);
            }

            if ($accion === 'revertir' && $fichasConsumidas <= 0) {
                poolResponder(['exito' => false, 'mensaje' => 'Este turno todavia no tiene fichas consumidas para revertir.'], 422);
            }

            $operador = $accion === 'consumir'
                ? 'LEAST(fichas_total, fichas_consumidas + 1)'
                : 'GREATEST(0, fichas_consumidas - 1)';

            $stmt = $conexion->prepare(
                "UPDATE pool_turnos SET fichas_consumidas = {$operador} WHERE id = :id AND jornada_id = :jornada_id"
            );
            $stmt->execute([':id' => $id, ':jornada_id' => $jornadaId]);
            break;

        case 'transferir':
            $jornada = poolJornadaActivaObligatoria($conexion);
            $jornadaId = (int) $jornada['id'];
            $id = (int) ($payload['id'] ?? 0);
            $poolDestino = poolNormalizar((string) ($payload['poolDestino'] ?? 'azul'));
            if ($id <= 0) {
                poolResponder(['exito' => false, 'mensaje' => 'Turno invalido.'], 422);
            }

            $ordenDestino = poolSiguienteOrden($conexion, $poolDestino, $jornadaId);
            $stmt = $conexion->prepare(
                'UPDATE pool_turnos SET pool = :pool, orden = :orden WHERE id = :id AND jornada_id = :jornada_id'
            );
            $stmt->execute([
                ':pool' => $poolDestino,
                ':orden' => $ordenDestino,
                ':id' => $id,
                ':jornada_id' => $jornadaId,
            ]);
            break;

        case 'mover':
            $jornada = poolJornadaActivaObligatoria($conexion);
            $jornadaId = (int) $jornada['id'];
            $id = (int) ($payload['id'] ?? 0);
            $direccion = (string) ($payload['direccion'] ?? '');
            if ($id <= 0 || !in_array($direccion, ['arriba', 'abajo'], true)) {
                poolResponder(['exito' => false, 'mensaje' => 'Movimiento invalido.'], 422);
            }

            $actualStmt = $conexion->prepare(
                'SELECT id, pool, orden FROM pool_turnos WHERE id = :id AND jornada_id = :jornada_id'
            );
            $actualStmt->execute([':id' => $id, ':jornada_id' => $jornadaId]);
            $actual = $actualStmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                poolResponder(['exito' => false, 'mensaje' => 'Turno no encontrado.'], 404);
            }

            $comparador = $direccion === 'arriba' ? '<' : '>';
            $ordenSql = $direccion === 'arriba' ? 'DESC' : 'ASC';
            $vecinoStmt = $conexion->prepare(
                "SELECT id, orden FROM pool_turnos
                 WHERE pool = :pool AND jornada_id = :jornada_id
                   AND fichas_consumidas < fichas_total
                   AND orden {$comparador} :orden
                 ORDER BY orden {$ordenSql}, id {$ordenSql}
                 LIMIT 1"
            );
            $vecinoStmt->execute([
                ':pool' => $actual['pool'],
                ':jornada_id' => $jornadaId,
                ':orden' => (int) $actual['orden'],
            ]);
            $vecino = $vecinoStmt->fetch(PDO::FETCH_ASSOC);

            if ($vecino) {
                $conexion->beginTransaction();
                $update = $conexion->prepare('UPDATE pool_turnos SET orden = :orden WHERE id = :id');
                $update->execute([':orden' => (int) $vecino['orden'], ':id' => $id]);
                $update->execute([':orden' => (int) $actual['orden'], ':id' => (int) $vecino['id']]);
                $conexion->commit();
            }
            break;

        case 'reordenar':
            $jornada = poolJornadaActivaObligatoria($conexion);
            $jornadaId = (int) $jornada['id'];
            $pool = poolNormalizar((string) ($payload['pool'] ?? 'azul'));
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids)) {
                poolResponder(['exito' => false, 'mensaje' => 'Orden invalido.'], 422);
            }

            $conexion->beginTransaction();
            $update = $conexion->prepare(
                'UPDATE pool_turnos SET orden = :orden
                 WHERE id = :id AND pool = :pool AND jornada_id = :jornada_id'
            );
            $orden = 1;
            foreach ($ids as $id) {
                $update->execute([
                    ':orden' => $orden,
                    ':id' => (int) $id,
                    ':pool' => $pool,
                    ':jornada_id' => $jornadaId,
                ]);
                $orden++;
            }
            $conexion->commit();
            break;

        default:
            poolResponder(['exito' => false, 'mensaje' => 'Accion no reconocida.'], 422);
    }

    poolResponder(['exito' => true, 'estado' => poolObtenerEstado($conexion)]);
} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error en modulo pool: ' . $error->getMessage());
    if ($error instanceof RuntimeException) {
        poolResponder(['exito' => false, 'mensaje' => $error->getMessage()], 409);
    }

    poolResponder(['exito' => false, 'mensaje' => 'No se pudo completar la operacion.'], 500);
}
