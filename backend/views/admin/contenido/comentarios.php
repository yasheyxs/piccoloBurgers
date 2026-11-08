<?php
/**
 * Bandeja de comentarios del formulario de contacto del sitio.
 * Los escribe cualquier visitante, así que todo se muestra escapado.
 *
 * @var array<int, array{id: int, nombre: string, correo: string, mensaje: string, fecha: ?string}> $comentarios
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">Comentarios recibidos desde el sitio</div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaComentarios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light"><tr><th>Fecha</th><th>Nombre</th><th>Correo</th><th>Mensaje</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($comentarios as $comentario): ?>
            <tr>
              <td data-order="<?= $comentario['id'] ?>" class="text-nowrap"><?= $comentario['fecha'] ? date('d/m/Y H:i', strtotime($comentario['fecha'])) : '<span class="text-muted">—</span>' ?></td>
              <td><?= piccolo_e($comentario['nombre']) ?></td>
              <td><a href="mailto:<?= piccolo_e(rawurlencode($comentario['correo'])) ?>"><?= piccolo_e($comentario['correo']) ?></a></td>
              <td style="white-space: pre-line"><?= piccolo_e($comentario['mensaje']) ?></td>
              <td>
                <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar este comentario?">
                  <input type="hidden" name="id" value="<?= $comentario['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  $(function () { initDataTable("#tablaComentarios", { order: [[0, "desc"]] }); });
</script>
