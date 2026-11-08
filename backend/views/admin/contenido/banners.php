<?php
/**
 * Banners de la portada del sitio (se muestra el más nuevo).
 *
 * @var array<int, array<string, mixed>> $banners
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span>La portada del sitio muestra el banner más nuevo.</span>
    <a class="btn btn-primary" href="crear.php" role="button">Nuevo banner</a>
  </div>
  <div class="card-body">
    <?php if (!$banners): ?>
      <p class="text-center text-muted my-4">No hay banners: el sitio muestra la imagen de portada por defecto.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle w-100" data-no-datatable>
          <thead class="table-light"><tr><th style="width: 140px">Imagen</th><th>Título</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($banners as $banner): ?>
              <tr>
                <td><img src="<?= piccolo_e($banner['imagen']) ?>" alt="" width="120" class="rounded"></td>
                <td>
                  <?= piccolo_e($banner['titulo']) ?>
                  <?php if ($banner['en_el_sitio']): ?><span class="badge text-bg-success ms-1">En el sitio</span><?php endif; ?>
                </td>
                <td><?= piccolo_e($banner['descripcion']) ?></td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1 flex-wrap">
                    <a class="btn btn-info btn-sm" href="editar.php?id=<?= $banner['id'] ?>">Editar</a>
                    <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar el banner «<?= piccolo_e($banner['titulo']) ?>»?">
                      <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
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
