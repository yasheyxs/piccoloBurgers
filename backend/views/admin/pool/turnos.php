<?php
/**
 * Pantalla del pool: jornada, fila de cada mesa y alta de turnos.
 * La maneja assets/js/pages/pool.js, que habla con seccion/pool/api.php.
 *
 * @var array{max_fichas_por_registro: int, valor_ficha: float} $configuracion
 * @var string $csrf
 * @var string $url_base
 */
?>
<link rel="stylesheet" href="<?= piccolo_e($url_base) ?>assets/css/pool.css">
<div class="pool-shell py-4" data-api-url="api.php" data-csrf="<?= piccolo_e($csrf) ?>">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Fichas</h1>
      <p class="text-muted mb-0">Colas independientes, venta y consumo de fichas por mesa.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
      <span class="badge text-bg-secondary fs-6">Jornada <?= date('d/m/Y') ?></span>
      <span class="badge text-bg-warning fs-6">Ficha $<?= number_format($configuracion['valor_ficha'], 2, ',', '.') ?></span>
      <span class="badge text-bg-info fs-6">Máx. <?= $configuracion['max_fichas_por_registro'] ?> por registro</span>
      <a class="btn btn-outline-secondary pool-privacy-toggle" href="../poolParametros/"
        title="Parámetros de POOLS" aria-label="Parámetros de POOLS">
        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
      </a>
      <button class="btn btn-outline-secondary pool-privacy-toggle" type="button" data-privacy-toggle
        title="Mostrar información sensible" aria-label="Mostrar información sensible" aria-pressed="false">
        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
      </button>
    </div>
  </div>

  <div id="poolAlert" class="alert d-none" role="alert"></div>

  <div class="modal fade" id="poolPrivacyModal" tabindex="-1" aria-labelledby="poolPrivacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form data-privacy-form>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="poolPrivacyModalLabel">Mostrar información sensible</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted mb-3">Ingresa la contraseña de tu usuario para ver los totales vendidos y consumidos.</p>
            <label class="form-label" for="poolPrivacyPassword">Contraseña</label>
            <input class="form-control" id="poolPrivacyPassword" name="password" type="password"
              autocomplete="current-password" required>
            <div class="pool-privacy-feedback text-danger small mt-2" data-privacy-feedback role="alert"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" data-privacy-submit>
              <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Mostrar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="poolClosePromptModal" tabindex="-1" aria-labelledby="poolClosePromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fs-5" id="poolClosePromptModalLabel">Cerrar jornada</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">¿Necesitas editar las cantidades totales vendidas antes de cerrar?</p>
          <p class="text-muted small mb-0">El ajuste se aplicará solo a estadísticas generales, sin modificar personas, pools ni bebidas específicas.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-outline-danger" data-close-direct>Cerrar sin editar</button>
          <button type="button" class="btn btn-primary" data-close-edit>Editar totales</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="poolCloseAdjustModal" tabindex="-1" aria-labelledby="poolCloseAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form data-close-adjust-form>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="poolCloseAdjustModalLabel">Ajustar totales de cierre</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">Edita el total final vendido. Las diferencias se guardan como ajuste global de la jornada.</p>
            <div class="mb-3">
              <label class="form-label" for="poolCloseFichas">Total de fichas vendidas</label>
              <input class="form-control form-control-lg" id="poolCloseFichas" name="fichasVendidas" type="number" min="0" step="1" required>
            </div>
            <div>
              <label class="form-label" for="poolCloseBebidas">Total de bebidas vendidas (jarras)</label>
              <input class="form-control form-control-lg" id="poolCloseBebidas" name="bebidasVendidas" type="number" min="0" step="1" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger" data-close-adjust-submit>
              <i class="fa-solid fa-lock me-1" aria-hidden="true"></i> Cerrar jornada
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="pool-jornada-card d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <span class="pool-jornada-card__status" data-jornada-status>
        <i class="fa-solid fa-circle-stop text-secondary" aria-hidden="true"></i>
        Sin jornada activa
      </span>
      <div class="text-muted small mt-1" data-jornada-detail>Abrí una jornada para empezar a registrar turnos.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-primary" type="button" data-jornada-action="abrir_jornada">
        <i class="fa-solid fa-play me-1" aria-hidden="true"></i> Abrir jornada
      </button>
      <button class="btn btn-danger" type="button" data-jornada-action="cerrar_jornada" disabled>
        <i class="fa-solid fa-lock me-1" aria-hidden="true"></i> Cerrar jornada
      </button>
    </div>
  </div>

  <div class="pool-grid">
    <?php foreach (['azul' => 'Pool Azul', 'rojo' => 'Pool Rojo'] as $poolKey => $poolLabel): ?>
      <section class="pool-panel pool-panel--<?= $poolKey ?>" data-pool="<?= $poolKey ?>">
        <div class="pool-panel__top">
          <div>
            <div class="pool-panel__identity">
              <span class="pool-panel__marker" aria-hidden="true"></span>
              <span><?= $poolLabel ?></span>
            </div>
            <h2 class="h4 mb-0 mt-1"><?= $poolKey === 'azul' ? 'Lado Azul' : 'Lado Rojo'; ?></h2>
          </div>
          <i class="fa-solid fa-circle-dot fa-2x" aria-hidden="true"></i>
        </div>

        <div class="pool-stats">
          <div class="pool-stat"><span class="pool-stat__label">Vendidas</span><span class="pool-stat__value pool-stat__value--hidden" data-stat="vendidas">****</span></div>
          <div class="pool-stat"><span class="pool-stat__label">Consumidas</span><span class="pool-stat__value pool-stat__value--hidden" data-stat="consumidas">****</span></div>
          <div class="pool-stat"><span class="pool-stat__label">Pendientes</span><span class="pool-stat__value" data-stat="pendientes">0</span></div>
          <div class="pool-stat"><span class="pool-stat__label">Vendido $</span><span class="pool-stat__value pool-stat__value--hidden" data-stat="montoVendido">****</span></div>
        </div>

        <form class="pool-fast-form" data-add-form>
          <input class="form-control form-control-lg" name="nombre" type="text" maxlength="120" placeholder="Nombre o identificación" autocomplete="off" required>
          <input class="form-control form-control-lg" name="fichas" type="number" min="1" max="99" value="1" required>
          <button class="btn btn-primary btn-lg" type="submit">
            <i class="fa-solid fa-plus me-1"></i> Agregar
          </button>
        </form>

        <div class="pool-queue" data-queue aria-live="polite"></div>
      </section>
    <?php endforeach; ?>
  </div>
</div>


<script src="<?= piccolo_e($url_base) ?>assets/js/pages/pool.js"></script>
