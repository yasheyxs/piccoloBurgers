<?php
/**
 * Testimonios que se muestran en la portada del sitio.
 *
 * @var array<int, array{id: int, opinion: string, nombre: string}> $testimonios
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar testimonio</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaTestimonios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light"><tr><th>Opinión</th><th>Nombre</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($testimonios as $testimonio): ?>
            <tr>
              <td><?= piccolo_e($testimonio['opinion']) ?></td>
              <td><?= piccolo_e($testimonio['nombre']) ?></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <a class="btn btn-info btn-sm" href="editar.php?id=<?= $testimonio['id'] ?>">Editar</a>
                  <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar el testimonio de <?= piccolo_e($testimonio['nombre']) ?>?">
                    <input type="hidden" name="id" value="<?= $testimonio['id'] ?>">
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

<script>
  $(function () { initDataTable("#tablaTestimonios", { order: [] }); });
</script>
