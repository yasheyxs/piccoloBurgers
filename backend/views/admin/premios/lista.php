<?php
/**
 * Premios que los clientes canjean con sus puntos.
 *
 * @var array<int, array<string, mixed>> $premios
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
?>
<div class="py-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Premios</h1>
      <p class="text-muted mb-0">Lo que tus clientes pueden canjear con sus puntos, desde la pantalla de canje de cada cliente.</p>
    </div>
    <div>
      <a class="btn btn-primary" href="crear.php"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nuevo premio</a>
    </div>
  </div>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (!$premios): ?>
        <p class="text-center text-muted my-4">Todavía no cargaste premios.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table id="tablaPremios" class="table table-hover align-middle w-100">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th class="w-50">Descripción</th>
                <th class="text-center">Costo en puntos</th>
                <th class="text-nowrap">Creado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($premios as $premio): ?>
                <tr>
                  <td><?= piccolo_e($premio['nombre']) ?></td>
                  <td>
                    <?php if ($premio['descripcion'] === null): ?>
                      <span class="text-muted">Sin descripción</span>
                    <?php else: ?>
                      <?= nl2br(piccolo_e(mb_strimwidth($premio['descripcion'], 0, 140, '…', 'UTF-8'))) ?>
                    <?php endif; ?>
                  </td>
                  <td class="text-center fw-semibold" data-order="<?= $premio['costo_puntos'] ?>"><?= number_format($premio['costo_puntos'], 0, ',', '.') ?></td>
                  <td class="text-nowrap" data-order="<?= piccolo_e($premio['creado'] ?? '') ?>"><?= $premio['creado'] ? date('d/m/Y H:i', strtotime($premio['creado'])) : '—' ?></td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                      <a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?= $premio['id'] ?>" title="Editar"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span class="visually-hidden">Editar</span></a>
                      <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar el premio «<?= piccolo_e($premio['nombre']) ?>»? Los canjes ya hechos quedan en el historial.">
                        <input type="hidden" name="txtID" value="<?= $premio['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="visually-hidden">Eliminar</span></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  $(function () {
    if (document.getElementById("tablaPremios")) {
      initDataTable("#tablaPremios", { order: [[3, "desc"]], columnDefs: [{ targets: 4, orderable: false, searchable: false }] });
    }
  });
</script>
