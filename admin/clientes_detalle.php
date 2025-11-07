<?php
include("../admin/bd.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($clienteId <= 0) {
    header('Location: clientes.php');
    exit();
}

try {
    $stmtCliente = $conexion->prepare("SELECT c.*, COALESCE(p.total_pedidos, 0) AS total_pedidos, p.ultimo_pedido
    FROM tbl_clientes c
    LEFT JOIN (
      SELECT cliente_id, COUNT(*) AS total_pedidos, MAX(fecha) AS ultimo_pedido
      FROM tbl_pedidos
      GROUP BY cliente_id
    ) p ON p.cliente_id = c.ID
    WHERE c.ID = :id
    LIMIT 1");
    $stmtCliente->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmtCliente->execute();
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $_SESSION['error_clientes'] = 'No encontramos al cliente que intentaste consultar.';
        header('Location: clientes.php');
        exit();
    }

    $stmtPedidoMayor = $conexion->prepare("SELECT ID, total, fecha, metodo_pago, tipo_entrega, estado
    FROM tbl_pedidos
    WHERE cliente_id = :id
    ORDER BY total DESC, fecha DESC
    LIMIT 1");
    $stmtPedidoMayor->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmtPedidoMayor->execute();
    $pedidoMasGrande = $stmtPedidoMayor->fetch(PDO::FETCH_ASSOC) ?: null;

    $stmtPedidos = $conexion->prepare("SELECT ID, total, fecha, metodo_pago, tipo_entrega, estado
    FROM tbl_pedidos
    WHERE cliente_id = :id
    ORDER BY fecha DESC");
    $stmtPedidos->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmtPedidos->execute();
    $historialPedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

    $stmtMovimientos = $conexion->prepare("SELECT created_at, tipo, descripcion, puntos, saldo_resultante
    FROM movimientos_puntos
    WHERE cliente_id = :id
    ORDER BY created_at DESC");
    $stmtMovimientos->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmtMovimientos->execute();
    $historialMovimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    error_log('No se pudo obtener la información del cliente: ' . $error->getMessage());
    $_SESSION['error_clientes'] = 'Ocurrió un error al cargar la información del cliente.';
    header('Location: clientes.php');
    exit();
}

include("../admin/templates/header.php");
?>

<br>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">Detalle del cliente</h5>
        <a href="clientes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>
            Volver al listado
        </a>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-uppercase text-muted">Información personal</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4">Registro</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($cliente['fecha_registro'])) { ?>
                                <?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])); ?>
                            <?php } else { ?>
                                <span class="text-muted">Sin registro</span>
                            <?php } ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-uppercase text-muted">Resumen de actividad</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Pedidos realizados</dt>
                        <dd class="col-sm-7"><?= (int) ($cliente['total_pedidos'] ?? 0); ?></dd>
                        <dt class="col-sm-5">Último pedido</dt>
                        <dd class="col-sm-7">
                            <?php if (!empty($cliente['ultimo_pedido'])) { ?>
                                <?= date('d/m/Y H:i', strtotime($cliente['ultimo_pedido'])); ?>
                            <?php } else { ?>
                                <span class="text-muted">Sin pedidos</span>
                            <?php } ?>
                        </dd>
                        <dt class="col-sm-5">Pedido más grande</dt>
                        <dd class="col-sm-7">
                            <?php if ($pedidoMasGrande) { ?>
                                <span class="fw-semibold">$<?= number_format((float) $pedidoMasGrande['total'], 2, ',', '.'); ?></span>
                                <small class="d-block text-muted">#<?= htmlspecialchars((string) $pedidoMasGrande['ID'], ENT_QUOTES, 'UTF-8'); ?> · <?= date('d/m/Y H:i', strtotime($pedidoMasGrande['fecha'])); ?></small>
                            <?php } else { ?>
                                <span class="text-muted">Sin pedidos</span>
                            <?php } ?>
                        </dd>
                        <dt class="col-sm-5">Puntos actuales</dt>
                        <dd class="col-sm-7 fw-semibold"><?= number_format((int) ($cliente['puntos'] ?? 0), 0, ',', '.'); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Historial de pedidos</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaHistorialPedidos" class="table table-sm table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Método de pago</th>
                                <th>Tipo de entrega</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialPedidos as $pedido) { ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $pedido['ID'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                    <td>$<?= number_format((float) $pedido['total'], 2, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars(ucfirst($pedido['estado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars(ucfirst($pedido['metodo_pago'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars(ucfirst($pedido['tipo_entrega'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Historial de movimientos de puntos</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaHistorialPuntos" class="table table-sm table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo de movimiento</th>
                                <th>Descripción</th>
                                <th>Puntos (+ / -)</th>
                                <th>Saldo restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialMovimientos as $movimiento) { ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($movimiento['created_at'])) { ?>
                                            <?= date('d/m/Y H:i', strtotime($movimiento['created_at'])); ?>
                                        <?php } else { ?>
                                            <span class="text-muted">Sin registro</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst($movimiento['tipo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($movimiento['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php $puntosMovimiento = (int) ($movimiento['puntos'] ?? 0); ?>
                                    <td class="fw-semibold <?= $puntosMovimiento >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?= $puntosMovimiento >= 0 ? '+' : ''; ?><?= $puntosMovimiento; ?>
                                    </td>
                                    <td><?= number_format((int) ($movimiento['saldo_resultante'] ?? 0), 0, ',', '.'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDataTable('#tablaHistorialPedidos');
        initDataTable('#tablaHistorialPuntos');
    });
</script>

<?php include("../admin/templates/footer.php"); ?>