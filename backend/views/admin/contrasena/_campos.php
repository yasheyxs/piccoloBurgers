<?php
/**
 * Campos "contraseña nueva" y "repetir" (los usan cambiar y restablecer).
 *
 * @var array<string, string> $errores
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback">' . piccolo_e($errores[$campo]) . '</div>' : '';
?>
<div class="mb-3">
  <label for="nueva" class="form-label">Contraseña nueva</label>
  <input type="password" class="form-control<?= $invalido('nueva') ?>" name="nueva" id="nueva" required autocomplete="new-password">
  <?= $error('nueva') ?>
  <div class="form-text">Al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.</div>
</div>
<div class="mb-3">
  <label for="confirmar" class="form-label">Repetir la contraseña nueva</label>
  <input type="password" class="form-control<?= $invalido('confirmar') ?>" name="confirmar" id="confirmar" required autocomplete="new-password">
  <?= $error('confirmar') ?>
</div>
