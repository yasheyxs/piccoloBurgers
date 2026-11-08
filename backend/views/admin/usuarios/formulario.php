<?php
/**
 * Formulario para crear o editar un usuario del panel.
 *
 * @var string $titulo
 * @var string $boton
 * @var bool $nuevo
 * @var string|null $rolAnterior  rol guardado (solo al editar)
 * @var array<string, mixed> $usuario
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
use Piccolo\Http\Admin\UsuariosController;

$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback">' . piccolo_e($errores[$campo]) . '</div>' : '';
$rolAnterior ??= null;
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form method="post" novalidate>
      <div class="mb-3">
        <label for="usuario" class="form-label">Nombre de usuario (con el que entra al panel)</label>
        <input type="text" class="form-control<?= $invalido('usuario') ?>" name="usuario" id="usuario" maxlength="50" value="<?= piccolo_e($usuario['usuario'] ?? '') ?>" autocomplete="off" required>
        <?= $error('usuario') ?>
      </div>

      <div class="mb-3">
        <label for="correo" class="form-label">Correo (para recuperar la contraseña)</label>
        <input type="email" class="form-control<?= $invalido('correo') ?>" name="correo" id="correo" maxlength="160" value="<?= piccolo_e($usuario['correo'] ?? '') ?>" required>
        <?= $error('correo') ?>
      </div>

      <div class="mb-3">
        <label for="rol" class="form-label">Rol</label>
        <select class="form-select<?= $invalido('rol') ?>" name="rol" id="rol" required>
          <?php foreach (UsuariosController::ROLES as $valor => $nombre): ?>
            <option value="<?= $valor ?>" <?= ($usuario['rol'] ?? '') === $valor ? 'selected' : '' ?>><?= $nombre ?></option>
          <?php endforeach; ?>
        </select>
        <?= $error('rol') ?>
        <div class="form-text">Administrador entra a todo. Empleado, a cocina, pool y bebidas. Delivery, a sus entregas.</div>
      </div>

      <?php if ($nuevo): ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control<?= $invalido('password') ?>" name="password" id="password" autocomplete="new-password" required>
            <?= $error('password') ?>
            <div class="form-text">Al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.</div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="confirmar" class="form-label">Repetir contraseña</label>
            <input type="password" class="form-control<?= $invalido('confirmar') ?>" name="confirmar" id="confirmar" autocomplete="new-password" required>
            <?= $error('confirmar') ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted">La contraseña no se cambia desde acá: cada usuario puede recuperarla con "¿Olvidaste tu contraseña?" en el ingreso al panel.</p>
      <?php endif; ?>

      <div class="mb-3" id="bloqueConfirmacion" data-rol-anterior="<?= piccolo_e($rolAnterior ?? '') ?>">
        <label for="password_actual" class="form-label">Tu contraseña (solo si el usuario es o va a ser administrador)</label>
        <input type="password" class="form-control<?= $invalido('password_actual') ?>" name="password_actual" id="password_actual" autocomplete="current-password">
        <?= $error('password_actual') ?>
        <div class="form-text">Es para confirmar que sos vos antes de un cambio en un administrador.</div>
      </div>

      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>

<script>
  // La confirmación con tu contraseña solo se muestra cuando hace falta
  (function () {
    var bloque = document.getElementById("bloqueConfirmacion");
    var rol = document.getElementById("rol");
    function actualizar() {
      var hace = rol.value === "admin" || bloque.getAttribute("data-rol-anterior") === "admin";
      bloque.classList.toggle("d-none", !hace && !bloque.querySelector(".is-invalid"));
    }
    rol.addEventListener("change", actualizar);
    actualizar();
  })();
</script>
