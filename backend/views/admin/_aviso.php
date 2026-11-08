<?php
/** @var array{tipo: string, mensaje: string}|null $aviso */
?>
<?php if (!empty($aviso)): ?>
  <div class="alert alert-<?= piccolo_e($aviso['tipo']) ?> alert-dismissible fade show" role="alert">
    <?= piccolo_e($aviso['mensaje']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>
