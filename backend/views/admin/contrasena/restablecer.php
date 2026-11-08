<?php
/**
 * Crear una contraseña nueva con el enlace del correo.
 *
 * @var string $token
 * @var string|null $usuario  usuario del enlace, o null si venció
 * @var bool $listo
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $csrf
 */
?>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4 class="mb-3 text-center">Crear una contraseña nueva</h4>
      <?php if ($listo): ?>
        <div class="alert alert-success text-center">Listo, ya podés entrar con tu contraseña nueva.</div>
        <div class="text-center"><a href="../login.php" class="btn btn-primary">Ir al ingreso</a></div>
      <?php elseif ($usuario === null): ?>
        <div class="alert alert-warning text-center">El enlace venció o ya se usó. Pedí uno nuevo.</div>
        <div class="text-center"><a href="recuperar_password_usuario.php" class="btn btn-primary">Pedir otro enlace</a></div>
      <?php else: ?>
        <?php include __DIR__ . '/../_aviso.php'; ?>
        <p class="text-muted text-center">Usuario: <strong><?= piccolo_e($usuario) ?></strong></p>
        <form method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= piccolo_e($csrf) ?>">
          <input type="hidden" name="token" value="<?= piccolo_e($token) ?>">
          <?php include __DIR__ . '/_campos.php'; ?>
          <button type="submit" class="btn btn-success w-100">Guardar contraseña</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
