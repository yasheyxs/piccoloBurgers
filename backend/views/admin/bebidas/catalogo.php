<?php
/**
 * Catálogo de bebidas: alta, edición (en una ventana) y disponibilidad.
 *
 * @var array<int, array{id: int, nombre: string, valor_jarra: float, valor_promo: float, jarras_por_promo: int, activa: bool}> $bebidas
 * @var array<string, mixed>|null $enviada  lo que se envió si no se pudo guardar
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
// Si falló un alta, el formulario de alta vuelve con lo enviado; si falló una edición, se reabre la ventana
$nueva = ($enviada && (int) $enviada['id'] === 0) ? $enviada : ['nombre' => '', 'valor_jarra' => '', 'valor_promo' => '', 'jarras_por_promo' => 3, 'activa' => true];
$edicionFallida = ($enviada && (int) $enviada['id'] > 0) ? $enviada : null;
$invalido = static fn(string $campo): string => ($enviada && (int) $enviada['id'] === 0 && isset($errores[$campo])) ? ' is-invalid' : '';
?>
<div class="py-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Bebidas</h1>
      <p class="text-muted mb-0">Qué bebidas se venden, a qué precio y cuántas jarras trae cada promoción.</p>
    </div>
    <a class="btn btn-outline-primary align-self-lg-start" href="../bebidas/" title="Ir a la venta de bebidas" aria-label="Ir a la venta de bebidas">
      <i class="fa-solid fa-glass-water" aria-hidden="true"></i>
    </a>
  </div>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <div class="row g-4">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent"><h2 class="h5 mb-0">Nueva bebida</h2></div>
        <div class="card-body">
          <form method="post" class="d-grid gap-3" novalidate>
            <input type="hidden" name="id" value="0">
            <div>
              <label class="form-label fw-semibold" for="nombre">Nombre</label>
              <input class="form-control<?= $invalido('nombre') ?>" id="nombre" name="nombre" maxlength="100" value="<?= piccolo_e($nueva['nombre']) ?>" required>
            </div>
            <div>
              <label class="form-label fw-semibold" for="valor_jarra">Valor de la jarra</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control<?= $invalido('valor_jarra') ?>" id="valor_jarra" name="valor_jarra" type="number" min="0.01" step="0.01" value="<?= piccolo_e($nueva['valor_jarra']) ?>" required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="valor_promo">Valor de la promoción</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control<?= $invalido('valor_promo') ?>" id="valor_promo" name="valor_promo" type="number" min="0.01" step="0.01" value="<?= piccolo_e($nueva['valor_promo']) ?>" required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="jarras_por_promo">Jarras por promoción</label>
              <input class="form-control<?= $invalido('jarras_por_promo') ?>" id="jarras_por_promo" name="jarras_por_promo" type="number" min="1" max="99" step="1" value="<?= piccolo_e($nueva['jarras_por_promo']) ?>" required>
              <div class="form-text">Cuántas jarras se entregan por cada promoción vendida.</div>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" id="activa" name="activa" type="checkbox" value="1" <?= $nueva['activa'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="activa">Disponible para vender</label>
            </div>
            <div class="d-flex justify-content-end">
              <button class="btn btn-warning text-dark" type="submit"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent"><h2 class="h5 mb-0">Bebidas cargadas</h2></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle" data-no-datatable>
              <thead><tr><th>Bebida</th><th>Jarra</th><th>Promoción</th><th>Jarras por promo</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($bebidas as $bebida): ?>
                  <tr>
                    <td class="fw-semibold"><?= piccolo_e($bebida['nombre']) ?></td>
                    <td><?= $pesos($bebida['valor_jarra']) ?></td>
                    <td><?= $pesos($bebida['valor_promo']) ?></td>
                    <td><?= $bebida['jarras_por_promo'] ?></td>
                    <td><span class="badge <?= $bebida['activa'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $bebida['activa'] ? 'Activa' : 'Inactiva' ?></span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary" type="button" title="Editar" aria-label="Editar <?= piccolo_e($bebida['nombre']) ?>"
                        data-bs-toggle="modal" data-bs-target="#modalEditarBebida" data-editar-bebida="<?= piccolo_e(json_encode($bebida, JSON_UNESCAPED_UNICODE)) ?>">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$bebidas): ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay bebidas cargadas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEditarBebida" tabindex="-1" aria-labelledby="modalEditarBebidaLabel" aria-hidden="true"
    <?= $edicionFallida ? 'data-reabrir="' . piccolo_e(json_encode($edicionFallida, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>>
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" id="formEditarBebida" novalidate>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="modalEditarBebidaLabel">Editar bebida</h2>
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body d-grid gap-3">
            <input type="hidden" name="id">
            <div>
              <label class="form-label fw-semibold" for="editar_nombre">Nombre</label>
              <input class="form-control" id="editar_nombre" name="nombre" maxlength="100" required>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_valor_jarra">Valor de la jarra</label>
              <div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="editar_valor_jarra" name="valor_jarra" type="number" min="0.01" step="0.01" required></div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_valor_promo">Valor de la promoción</label>
              <div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="editar_valor_promo" name="valor_promo" type="number" min="0.01" step="0.01" required></div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_jarras_por_promo">Jarras por promoción</label>
              <input class="form-control" id="editar_jarras_por_promo" name="jarras_por_promo" type="number" min="1" max="99" step="1" required>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" id="editar_activa" name="activa" type="checkbox" value="1">
              <label class="form-check-label" for="editar_activa">Disponible para vender</label>
            </div>
            <p class="small text-muted mb-0">Las ventas ya hechas conservan el precio que tenían.</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-warning text-dark" type="submit"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/bebidas_catalogo.js"></script>
