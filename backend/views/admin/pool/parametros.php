<?php
/**
 * Parámetros del pool: máximo de fichas por turno y valor de cada ficha.
 *
 * @var array{max_fichas_por_registro: int, valor_ficha: float, actualizado: ?string} $configuracion
 * @var array<string, mixed> $formulario
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback d-block">' . piccolo_e($errores[$campo]) . '</div>' : '';
$numero = static fn(mixed $valor): string => is_numeric($valor) ? rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.') : (string) $valor;
$actualizado = $configuracion['actualizado'] ? poolFechaDbAFechaApp($configuracion['actualizado']) : null;
?>
<div class="py-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Parámetros del pool</h1>
      <p class="text-muted mb-0">Cómo se anotan las fichas y cuánto vale cada una (para las estadísticas y las ventas).</p>
    </div>
    <div class="text-lg-end small text-muted">
      Última actualización<br>
      <span class="fw-semibold"><?= $actualizado ? $actualizado->format('d/m/Y H:i') : 'Sin registros' ?></span>
    </div>
  </div>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form method="post" class="row g-4" novalidate>
        <div class="col-12 col-lg-6">
          <label for="max_fichas_por_registro" class="form-label fw-semibold">Máximo de fichas por turno</label>
          <input type="number" class="form-control<?= $invalido('max_fichas_por_registro') ?>" id="max_fichas_por_registro" name="max_fichas_por_registro" min="1" max="99" step="1" value="<?= piccolo_e($numero($formulario['max_fichas_por_registro'])) ?>" required>
          <?= $error('max_fichas_por_registro') ?>
          <div class="form-text">Si alguien compra más fichas, se anotan en varios turnos seguidos (por ejemplo, 5 fichas con un máximo de 3 son un turno de 3 y otro de 2).</div>
        </div>
        <div class="col-12 col-lg-6">
          <label for="valor_ficha" class="form-label fw-semibold">Valor de cada ficha ($)</label>
          <input type="number" class="form-control<?= $invalido('valor_ficha') ?>" id="valor_ficha" name="valor_ficha" min="0.01" step="0.01" value="<?= piccolo_e($numero($formulario['valor_ficha'])) ?>" required>
          <?= $error('valor_ficha') ?>
          <div class="form-text">Cada venta guarda el valor del momento, así las estadísticas viejas no cambian si lo modificás.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>Deshacer cambios</button>
          <button type="submit" class="btn btn-warning text-dark shadow-sm"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
