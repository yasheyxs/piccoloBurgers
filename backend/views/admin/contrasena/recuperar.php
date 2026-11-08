<?php
/**
 * Pedir el enlace para crear una contraseña nueva.
 *
 * @var string $correo
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $csrf
 */
?>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4 class="mb-3 text-center">Recuperar la contraseña del panel</h4>
      <?php include __DIR__ . '/../_aviso.php'; ?>
      <p class="text-muted">Escribí el correo de tu usuario y te mandamos un enlace para crear una contraseña nueva.</p>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= piccolo_e($csrf) ?>">
        <div class="mb-3">
          <label for="correo" class="form-label">Correo electrónico</label>
          <input type="email" class="form-control<?= isset($errores['correo']) ? ' is-invalid' : '' ?>" name="correo" id="correo" value="<?= piccolo_e($correo) ?>" required autocomplete="email" placeholder="ejemplo@correo.com">
        </div>
        <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
      </form>
      <div class="mt-3 text-center"><a href="../login.php">Volver al ingreso</a></div>
    </div>
  </div>
</div>
