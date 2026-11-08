<?php
/**
 * Formulario para agregar o editar un proveedor.
 *
 * @var string $titulo
 * @var string $boton
 * @var array{id?: int, nombre: string, telefono: string, email: ?string} $proveedor
 * @var array<string, string> $errores  errores por campo
 * @var array{tipo: string, mensaje: string}|null $aviso
 */

$campo = static function (string $nombre) use ($errores): string {
    return isset($errores[$nombre]) ? ' is-invalid' : '';
};
?>
<br />
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form action="" method="post" novalidate>
      <?php if (isset($proveedor['id'])): ?>
        <input type="hidden" name="txtID" value="<?= (int) $proveedor['id'] ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control<?= $campo('nombre') ?>" name="nombre" id="nombre" maxlength="50"
          value="<?= piccolo_e($proveedor['nombre']) ?>" placeholder="Nombre del proveedor" required>
        <div class="invalid-feedback"><?= piccolo_e($errores['nombre'] ?? '') ?></div>
      </div>

      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="text" class="form-control<?= $campo('telefono') ?>" name="telefono" id="telefono" maxlength="20"
          value="<?= piccolo_e($proveedor['telefono']) ?>" placeholder="Teléfono de contacto" required>
        <div class="invalid-feedback"><?= piccolo_e($errores['telefono'] ?? '') ?></div>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control<?= $campo('email') ?>" name="email" id="email" maxlength="100"
          value="<?= piccolo_e($proveedor['email'] ?? '') ?>" placeholder="Correo electrónico">
        <div class="invalid-feedback"><?= piccolo_e($errores['email'] ?? '') ?></div>
      </div>

      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div>
