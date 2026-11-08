<?php
/**
 * Lista de productos del menú.
 *
 * @var array<int, array<string, mixed>> $productos
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php">Agregar producto</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMenu" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Ingredientes</th>
            <th>Precio</th>
            <th>Categoría</th>
            <th>Insumos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $producto): ?>
            <tr>
              <td>
                <?= piccolo_e($producto['nombre']) ?>
                <?php if (!$producto['visible']): ?>
                  <span class="badge bg-secondary ms-1" title="No aparece en el menú del sitio">Oculto</span>
                <?php endif; ?>
              </td>
              <td><?= piccolo_e($producto['ingredientes']) ?></td>
              <td data-order="<?= $producto['precio'] ?>">$<?= number_format($producto['precio'], 0, ',', '.') ?></td>
              <td><?= piccolo_e($producto['categoria']) ?></td>
              <td>
                <?php if ($producto['insumos'] > 0): ?>
                  <?= $producto['insumos'] ?> insumo<?= $producto['insumos'] === 1 ? '' : 's' ?>
                <?php else: ?>
                  <span class="text-danger">Sin insumos</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                  <a class="btn btn-secondary btn-sm" href="materias_primas.php?menu_id=<?= $producto['id'] ?>">Insumos</a>
                  <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $producto['id'] ?>">Editar</a>
                  <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar este producto del menú?">
                    <input type="hidden" name="txtID" value="<?= $producto['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
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

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/menu.js"></script>
