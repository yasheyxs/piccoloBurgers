<?php
/**
 * Lista de proveedores.
 *
 * @var array<int, array{id: int, nombre: string, telefono: string, email: ?string}> $proveedores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar proveedor</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaProveedores" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proveedores as $proveedor): ?>
            <tr>
              <td><?= piccolo_e($proveedor['nombre']) ?></td>
              <td><?= piccolo_e($proveedor['telefono']) ?></td>
              <td><?= $proveedor['email'] !== null ? piccolo_e($proveedor['email']) : '—' ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $proveedor['id'] ?>">Editar</a>
                <form method="post" action="index.php" class="d-inline" data-confirmar="¿Estás seguro de eliminar este proveedor?">
                  <input type="hidden" name="txtID" value="<?= $proveedor['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/proveedores.js"></script>
