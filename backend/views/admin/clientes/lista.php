<?php
/**
 * Lista de clientes registrados.
 *
 * @var array<int, array<string, mixed>> $clientes
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Clientes registrados</h5>
    <a class="btn btn-primary btn-sm" href="clientes_crear.php"><i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>Crear cliente</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaClientes" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Puntos</th>
            <th>Pedidos</th>
            <th>Último pedido</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $cliente): ?>
            <tr>
              <td><?= piccolo_e($cliente['nombre']) ?></td>
              <td><?= piccolo_e($cliente['telefono']) ?></td>
              <td data-order="<?= $cliente['puntos'] ?>"><?= number_format($cliente['puntos'], 0, ',', '.') ?></td>
              <td><?= $cliente['cantidad_pedidos'] ?></td>
              <td data-order="<?= piccolo_e($cliente['ultimo_pedido'] ?? '') ?>">
                <?php if ($cliente['ultimo_pedido']): ?>
                  <?= date('d/m/Y H:i', strtotime($cliente['ultimo_pedido'])) ?>
                <?php else: ?>
                  <span class="text-muted">Sin pedidos</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="d-flex gap-2 flex-wrap justify-content-center">
                  <a class="btn btn-outline-primary btn-sm" href="clientes_detalle.php?id=<?= $cliente['id'] ?>" title="Ver detalle" aria-label="Ver detalle"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                  <a class="btn btn-outline-success btn-sm" href="clientes_canje.php?id=<?= $cliente['id'] ?>" title="Canjear puntos" aria-label="Canjear puntos"><i class="fa-solid fa-gift" aria-hidden="true"></i></a>
                  <a class="btn btn-outline-secondary btn-sm" href="clientes_editar.php?id=<?= $cliente['id'] ?>" title="Editar" aria-label="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                  <form method="post" action="clientes.php" class="d-inline" data-confirmar="¿Eliminar a <?= piccolo_e($cliente['nombre']) ?>? Se pierden sus puntos; sus pedidos quedan en el historial.">
                    <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar" aria-label="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/clientes.js"></script>
