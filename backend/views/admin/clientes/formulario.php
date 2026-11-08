<?php
/**
 * Formulario para crear o editar un cliente.
 *
 * @var string $titulo
 * @var string $boton
 * @var bool $nuevo
 * @var array<string, mixed> $cliente  nombre, email, codigo_pais, telefono (sin código de país), puntos
 * @var array<string, array{bandera: string, longitudes: int[]}> $codigos
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback d-block">' . piccolo_e($errores[$campo]) . '</div>' : '';
$codigoElegido = (string) ($cliente['codigo_pais'] ?? '54');
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0"><?= piccolo_e($titulo) ?></h5>
    <a href="clientes.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Volver al listado</a>
  </div>
  <div class="card-body">
    <form method="post" class="row g-3" novalidate>
      <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control<?= $invalido('nombre') ?>" id="nombre" name="nombre" maxlength="120" value="<?= piccolo_e($cliente['nombre'] ?? '') ?>" required>
        <?= $error('nombre') ?>
      </div>
      <div class="col-md-6">
        <label for="telefono" class="form-label">Teléfono</label>
        <div class="input-group">
          <label class="input-group-text" for="codigo_pais">Cód.</label>
          <select class="form-select<?= $invalido('codigo_pais') ?>" id="codigo_pais" name="codigo_pais" style="max-width: 110px;" required>
            <?php foreach ($codigos as $codigo => $datos): ?>
              <option value="<?= piccolo_e($codigo) ?>" data-longitudes="<?= piccolo_e(implode(',', $datos['longitudes'])) ?>" <?= (string) $codigo === $codigoElegido ? 'selected' : '' ?>><?= piccolo_e($datos['bandera'] . ' +' . $codigo) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="tel" class="form-control<?= $invalido('telefono') ?>" id="telefono" name="telefono" value="<?= piccolo_e($cliente['telefono'] ?? '') ?>" required inputmode="numeric" autocomplete="off" placeholder="Ej: 3511234567">
        </div>
        <?= $error('codigo_pais') ?><?= $error('telefono') ?>
      </div>
      <div class="col-md-6">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control<?= $invalido('email') ?>" id="email" name="email" maxlength="160" value="<?= piccolo_e($cliente['email'] ?? '') ?>" required>
        <?= $error('email') ?>
      </div>
      <div class="col-md-6">
        <label for="puntos" class="form-label"><?= $nuevo ? 'Puntos iniciales' : 'Puntos' ?></label>
        <input type="number" min="0" step="1" class="form-control<?= $invalido('puntos') ?>" id="puntos" name="puntos" value="<?= piccolo_e($cliente['puntos'] ?? '0') ?>" required>
        <?= $error('puntos') ?>
        <?php if (!$nuevo): ?><div class="form-text">Si cambiás los puntos, la diferencia queda anotada como ajuste en su historial.</div><?php endif; ?>
      </div>
      <?php if ($nuevo): ?>
        <div class="col-12">
          <div class="alert alert-info mb-0" role="alert">
            Al crearlo se genera una contraseña provisoria que vas a ver una sola vez, para dársela al cliente.
          </div>
        </div>
      <?php endif; ?>
      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      </div>
    </form>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/clientes.js"></script>
