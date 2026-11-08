<?php
/**
 * Parámetros del sistema de puntos, con un ejemplo de cómo funcionan.
 *
 * @var array<string, mixed> $configuracion  lo guardado (PuntosController::obtener())
 * @var array<string, mixed> $formulario     lo que se muestra en los campos (lo enviado, si hubo errores)
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback d-block">' . piccolo_e($errores[$campo]) . '</div>' : '';
$numero = static fn(mixed $valor): string => is_numeric($valor) ? rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.') : (string) $valor;
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$ejemplo = $configuracion['ejemplo'];

// La base guarda la hora en su zona; se muestra en la hora del local
$actualizado = null;
if ($configuracion['actualizado']) {
    try {
        $actualizado = (new DateTimeImmutable($configuracion['actualizado'], new DateTimeZone(piccolo_env('DB_TIMEZONE') ?: 'UTC')))
            ->setTimezone(new DateTimeZone(piccolo_env('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires'))
            ->format('d/m/Y H:i');
    } catch (Exception) {
        $actualizado = $configuracion['actualizado'];
    }
}
?>
<div class="py-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Sistema de puntos</h1>
      <p class="text-muted mb-0">Cómo canjean tus clientes los puntos que juntan con cada compra (1 punto cada $1.500 pagados).</p>
    </div>
    <div class="text-lg-end small text-muted">
      Última actualización<br>
      <span class="fw-semibold"><?= $actualizado ? piccolo_e($actualizado) : 'Sin registros' ?></span>
    </div>
  </div>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <form method="post" class="row g-4" novalidate>
        <div class="col-12 col-lg-4">
          <label for="minimo_puntos" class="form-label fw-semibold">Mínimo de puntos para canjear</label>
          <input type="number" class="form-control<?= $invalido('minimo_puntos') ?>" id="minimo_puntos" name="minimo_puntos" min="0" step="1" value="<?= piccolo_e($numero($formulario['minimo_puntos'])) ?>" required>
          <?= $error('minimo_puntos') ?>
          <div class="form-text">Desde cuántos puntos se puede usar el descuento.</div>
        </div>
        <div class="col-12 col-lg-4">
          <label for="valor_punto" class="form-label fw-semibold">Valor de cada punto ($)</label>
          <input type="number" class="form-control<?= $invalido('valor_punto') ?>" id="valor_punto" name="valor_punto" min="0.01" step="0.01" value="<?= piccolo_e($numero($formulario['valor_punto'])) ?>" required>
          <?= $error('valor_punto') ?>
          <div class="form-text">Cuánto descuenta cada punto que se usa.</div>
        </div>
        <div class="col-12 col-lg-4">
          <label for="maximo_porcentaje" class="form-label fw-semibold">Descuento máximo por compra (%)</label>
          <input type="number" class="form-control<?= $invalido('maximo_porcentaje') ?>" id="maximo_porcentaje" name="maximo_porcentaje" min="1" max="100" step="0.1" value="<?= piccolo_e($numero($formulario['maximo_porcentaje'])) ?>" required>
          <?= $error('maximo_porcentaje') ?>
          <div class="form-text">Qué parte de la compra se puede pagar con puntos.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>Deshacer cambios</button>
          <button type="submit" class="btn btn-warning text-dark shadow-sm"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card border-0 admin-surface-panel">
    <div class="card-body">
      <h2 class="h6 mb-2"><i class="fa-solid fa-lightbulb text-warning me-2" aria-hidden="true"></i>Con la configuración guardada</h2>
      <ul class="mb-0 small">
        <li>En una compra de <?= $pesos($ejemplo['compra']) ?>, el cliente puede usar hasta <strong><?= number_format($ejemplo['maximo_puntos'], 0, ',', '.') ?> puntos</strong>, que descuentan <strong><?= $pesos($ejemplo['maximo_descuento']) ?></strong>.</li>
        <li>Para llegar al mínimo de <?= number_format($configuracion['minimo_puntos'], 0, ',', '.') ?> puntos hacen falta unas <strong><?= $ejemplo['compras_para_el_minimo'] ?> compras</strong> de ese monto.</li>
      </ul>
    </div>
  </div>
</div>
