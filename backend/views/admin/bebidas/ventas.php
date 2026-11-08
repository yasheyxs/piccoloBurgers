<?php
/**
 * Pantalla de bebidas: jornada (compartida con el pool), venta de jarras y promociones, y entregas.
 * La maneja assets/js/pages/bebidas.js, que habla con seccion/bebidas/api.php.
 *
 * @var string $csrf
 * @var string $url_base
 */
?>
<link rel="stylesheet" href="<?= piccolo_e($url_base) ?>assets/css/bebidas.css">

<div class="drink-shell py-4" data-api-url="api.php" data-csrf="<?= piccolo_e($csrf) ?>">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Bebidas</h1>
      <p class="text-muted mb-0">Venta y entrega rápida de jarras y promociones.</p>
    </div>
    <div class="d-flex gap-2 align-items-start flex-wrap">
      <a class="btn btn-outline-secondary" href="../bebidasParametros/"
        title="Parámetros de Bebidas" aria-label="Parámetros de Bebidas">
        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
      </a>
      <button class="btn btn-outline-secondary" type="button" data-privacy-toggle
        title="Mostrar información sensible" aria-label="Mostrar información sensible" aria-pressed="false">
        <i class="fa-solid fa-eye-slash"></i>
      </button>
    </div>
  </div>

  <div id="drinkAlert" class="alert d-none" role="alert"></div>

  <div class="drink-jornada p-3 d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
      <div class="fw-bold" data-jornada-status><i class="fa-solid fa-circle-stop text-secondary me-1"></i> Sin jornada activa</div>
      <div class="text-muted small mt-1" data-jornada-detail>Abrí una jornada para registrar ventas.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="button" data-jornada-action="abrir_jornada">
        <i class="fa-solid fa-play me-1"></i> Abrir jornada
      </button>
      <button class="btn btn-danger" type="button" data-jornada-action="cerrar_jornada" disabled>
        <i class="fa-solid fa-lock me-1"></i> Cerrar jornada
      </button>
    </div>
  </div>

  <div class="drink-summary mb-4">
    <div class="drink-stat"><span class="drink-stat__label">Vendidas</span><span class="drink-stat__value drink-stat__value--hidden" data-stat="vendidas">****</span></div>
    <div class="drink-stat"><span class="drink-stat__label">Entregadas</span><span class="drink-stat__value drink-stat__value--hidden" data-stat="entregadas">****</span></div>
    <div class="drink-stat"><span class="drink-stat__label">Pendientes</span><span class="drink-stat__value" data-stat="pendientes">0</span></div>
    <div class="drink-stat"><span class="drink-stat__label">Personas</span><span class="drink-stat__value" data-stat="personas">0</span></div>
    <div class="drink-stat"><span class="drink-stat__label">Total vendido</span><span class="drink-stat__value drink-stat__value--hidden" data-stat="montoVendido">****</span></div>
  </div>

  <div class="drink-form-card p-3 mb-4">
    <form class="drink-form" data-add-form>
      <input class="form-control form-control-lg" name="persona" maxlength="120" placeholder="Persona" autocomplete="off" required>
      <select class="form-select form-select-lg" name="bebidaId" data-bebida-select required></select>
      <select class="form-select form-select-lg" name="tipo" required>
        <option value="jarra">Jarra</option>
        <option value="promo">Promo</option>
      </select>
      <input class="form-control form-control-lg" name="cantidad" type="number" min="1" max="99" value="1" required>
      <button class="btn btn-primary btn-lg" type="submit"><i class="fa-solid fa-plus me-1"></i> Vender</button>
    </form>
  </div>

  <div class="drink-queue" data-queue aria-live="polite"></div>

  <div class="modal fade" id="drinkPrivacyModal" tabindex="-1" aria-labelledby="drinkPrivacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form data-privacy-form>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="drinkPrivacyModalLabel">Mostrar información sensible</h2>
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">Ingresa la contraseña de tu usuario actual.</p>
            <input class="form-control" name="password" type="password" autocomplete="current-password" required data-privacy-password>
            <div class="drink-privacy-feedback text-danger small mt-2" data-privacy-feedback role="alert"></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" type="submit" data-privacy-submit><i class="fa-solid fa-eye me-1"></i> Mostrar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="drinkClosePromptModal" tabindex="-1" aria-labelledby="drinkClosePromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fs-5" id="drinkClosePromptModalLabel">Cerrar jornada</h2>
          <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">¿Necesitas editar las cantidades totales vendidas antes de cerrar?</p>
          <p class="text-muted small mb-0">El ajuste se aplicará solo a estadísticas generales, sin modificar personas, pools ni bebidas específicas.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-outline-danger" type="button" data-close-direct>Cerrar sin editar</button>
          <button class="btn btn-primary" type="button" data-close-edit>Editar totales</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="drinkCloseAdjustModal" tabindex="-1" aria-labelledby="drinkCloseAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form data-close-adjust-form>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="drinkCloseAdjustModalLabel">Ajustar totales de cierre</h2>
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">Edita el total final vendido. Las diferencias se guardan como ajuste global de la jornada.</p>
            <div class="mb-3">
              <label class="form-label" for="drinkCloseFichas">Total de fichas vendidas</label>
              <input class="form-control form-control-lg" id="drinkCloseFichas" name="fichasVendidas" type="number" min="0" step="1" required>
            </div>
            <div>
              <label class="form-label" for="drinkCloseBebidas">Total de bebidas vendidas (jarras)</label>
              <input class="form-control form-control-lg" id="drinkCloseBebidas" name="bebidasVendidas" type="number" min="0" step="1" required>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger" type="submit" data-close-adjust-submit>
              <i class="fa-solid fa-lock me-1"></i> Cerrar jornada
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/bebidas.js"></script>
