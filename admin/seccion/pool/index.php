<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$rolActual = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['admin_logueado']) || !in_array($rolActual, ['admin', 'empleado'], true)) {
    header('Location: ' . piccolo_admin_base_url() . 'login.php');
    exit;
}

asegurarTablaPoolTurnos($conexion);
$configuracionPool = poolObtenerConfiguracion($conexion);

$adminPageIdentifier = 'pool-admin';
include __DIR__ . '/../../templates/header.php';
?>

<style>
  .pool-shell {
    --pool-blue: #2563eb;
    --pool-red: #dc2626;
    --pool-chip-on: #f59e0b;
    --pool-chip-off: rgba(100, 116, 139, 0.32);
  }

  .pool-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .pool-panel {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 0.9rem;
    overflow: hidden;
  }

  .pool-panel--azul { border-top: 0.35rem solid var(--pool-blue); }
  .pool-panel--rojo { border-top: 0.35rem solid var(--pool-red); }

  .pool-panel__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    color: var(--admin-text);
    background: var(--admin-surface-alt);
    border-bottom: 1px solid var(--admin-border);
    padding: 1rem;
  }

  .pool-panel__identity {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
  }

  .pool-panel__marker {
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--admin-surface);
    border-radius: 999px;
  }

  .pool-panel--azul .pool-panel__marker { background: var(--pool-blue); }
  .pool-panel--rojo .pool-panel__marker { background: var(--pool-red); }

  .pool-jornada-card {
    border: 1px solid var(--admin-border);
    border-radius: 0.9rem;
    background: var(--admin-surface);
    padding: 1rem;
  }

  .pool-jornada-card__status {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-weight: 700;
  }

  .pool-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.5rem;
    padding: 0.75rem;
  }

  .pool-stat {
    border: 1px solid var(--admin-border);
    border-radius: 0.7rem;
    padding: 0.65rem;
    background: var(--admin-surface-alt);
    min-height: 74px;
  }

  .pool-stat__label {
    display: block;
    color: var(--admin-muted);
    font-size: 0.72rem;
    line-height: 1.1;
    text-transform: uppercase;
  }

  .pool-stat__value {
    color: var(--admin-text);
    font-size: 1.35rem;
    font-weight: 800;
  }

  .pool-privacy-toggle {
    min-width: 46px;
  }

  .pool-stat__value--hidden {
    color: var(--admin-muted);
    letter-spacing: 0.08em;
  }

  .pool-privacy-feedback {
    min-height: 1.5rem;
  }

  .pool-fast-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 92px 124px;
    gap: 0.5rem;
    padding: 0.75rem;
    border-top: 1px solid var(--admin-border);
    border-bottom: 1px solid var(--admin-border);
  }

  .pool-queue {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    min-height: 180px;
    max-height: 62vh;
    overflow-y: auto;
    padding: 0.75rem;
  }

  .pool-turn {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
    border: 1px solid var(--admin-border);
    border-radius: 0.8rem;
    background: var(--admin-surface);
    padding: 0.75rem;
  }

  .pool-turn[draggable="true"] { cursor: grab; }
  .pool-turn.dragging { opacity: 0.45; }

  .pool-turn__name {
    color: var(--admin-text);
    font-weight: 800;
    word-break: break-word;
  }

  .pool-turn__name[data-inline-name] {
    border-radius: 0.35rem;
    cursor: text;
    margin: -0.08rem -0.15rem;
    outline: none;
    padding: 0.08rem 0.15rem;
  }

  .pool-turn__name[data-inline-name]:hover,
  .pool-turn__name[data-inline-name]:focus,
  .pool-turn__name--editing {
    background: var(--admin-surface-alt);
    box-shadow: inset 0 0 0 1px var(--admin-border);
  }

  .pool-turn__meta {
    color: var(--admin-muted);
    font-size: 0.82rem;
  }

  .pool-chips {
    display: flex;
    gap: 0.28rem;
    margin-top: 0.45rem;
  }

  .pool-chip {
    width: 1.05rem;
    height: 1.05rem;
    border-radius: 999px;
    border: 2px solid var(--pool-chip-on);
    background: var(--pool-chip-on);
  }

  .pool-chip--off {
    background: transparent;
    border-color: var(--pool-chip-off);
  }

  .pool-actions {
    display: grid;
    grid-template-areas:
      "chips order"
      "secondary order";
    grid-template-columns: auto 46px;
    gap: 0.4rem;
    justify-content: end;
    align-items: center;
  }

  .pool-actions__chips {
    grid-area: chips;
    display: flex;
    gap: 0.4rem;
    justify-content: end;
  }

  .pool-actions__order {
    grid-area: order;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    justify-self: end;
  }

  .pool-actions__secondary {
    grid-area: secondary;
    display: flex;
    gap: 0.4rem;
    justify-content: end;
  }

  .pool-action {
    width: 48px;
    height: 42px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .pool-action--consume,
  .pool-action--restore {
    font-size: 1.35rem;
    line-height: 1;
  }

  .pool-action--consume {
    background: var(--admin-btn-success-bg);
    border-color: var(--admin-btn-success-bg);
    color: var(--admin-btn-success-text);
  }

  .pool-action--restore {
    background: var(--admin-btn-danger-bg);
    border-color: var(--admin-btn-danger-bg);
    color: var(--admin-btn-danger-text);
  }

  .pool-empty {
    border: 1px dashed var(--admin-border);
    border-radius: 0.8rem;
    color: var(--admin-muted);
    padding: 1.25rem;
    text-align: center;
  }

  @media (max-width: 991.98px) {
    .pool-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 575.98px) {
    .pool-fast-form { grid-template-columns: 1fr; }
    .pool-turn { grid-template-columns: 1fr; }
    .pool-actions {
      grid-template-areas:
        "chips order"
        "secondary order";
      grid-template-columns: minmax(0, 1fr) 46px;
      justify-content: stretch;
    }
    .pool-actions__chips,
    .pool-actions__secondary { justify-content: start; }
  }
</style>

<div class="pool-shell py-4" data-api-url="api.php" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Fichas</h1>
      <p class="text-muted mb-0">Colas independientes, venta y consumo de fichas por mesa.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
      <span class="badge text-bg-secondary fs-6">Jornada <?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="badge text-bg-warning fs-6">Ficha $<?= htmlspecialchars(number_format((float) $configuracionPool['valor_ficha'], 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="badge text-bg-info fs-6">Máx. <?= (int) $configuracionPool['max_fichas_por_registro']; ?> por registro</span>
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
      <section class="pool-panel pool-panel--<?= htmlspecialchars($poolKey, ENT_QUOTES, 'UTF-8'); ?>" data-pool="<?= htmlspecialchars($poolKey, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="pool-panel__top">
          <div>
            <div class="pool-panel__identity">
              <span class="pool-panel__marker" aria-hidden="true"></span>
              <span><?= htmlspecialchars($poolLabel, ENT_QUOTES, 'UTF-8'); ?></span>
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

<script>
(function () {
  const shell = document.querySelector('.pool-shell');
  if (!shell) return;

  const apiUrl = shell.dataset.apiUrl;
  const csrf = shell.dataset.csrf;
  const sensitiveStats = new Set(['vendidas', 'consumidas', 'montoVendido']);
  const privacyToggle = shell.querySelector('[data-privacy-toggle]');
  const privacyForm = shell.querySelector('[data-privacy-form]');
  const privacyPassword = shell.querySelector('#poolPrivacyPassword');
  const privacyFeedback = shell.querySelector('[data-privacy-feedback]');
  let draggedId = null;
  let sensitiveVisible = false;

  function showAlert(message, type = 'danger') {
    const alert = document.getElementById('poolAlert');
    if (window.showAdminFeedback) {
      window.showAdminFeedback(alert, message, type);
      return;
    }

    alert.className = `alert alert-${type}`;
    alert.textContent = message || 'Ocurrió un problema inesperado. Inténtalo nuevamente.';
    alert.classList.remove('d-none');
    if (type !== 'danger') {
      setTimeout(() => alert.classList.add('d-none'), 3500);
    }
  }

  function chipHtml(total, consumed) {
    let html = '';
    for (let i = 1; i <= total; i++) {
      html += `<span class="pool-chip${i <= consumed ? ' pool-chip--off' : ''}" aria-hidden="true"></span>`;
    }
    return html;
  }

  function setSensitiveVisible(visible) {
    sensitiveVisible = visible;

    shell.querySelectorAll('[data-stat]').forEach((element) => {
      if (!sensitiveStats.has(element.dataset.stat) || visible) return;
      element.textContent = '****';
      element.classList.add('pool-stat__value--hidden');
    });

    if (!privacyToggle) return;
    const label = visible ? 'Ocultar información sensible' : 'Mostrar información sensible';
    privacyToggle.title = label;
    privacyToggle.setAttribute('aria-label', label);
    privacyToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
    privacyToggle.innerHTML = visible
      ? '<i class="fa-solid fa-eye" aria-hidden="true"></i>'
      : '<i class="fa-solid fa-eye-slash" aria-hidden="true"></i>';
  }

  function renderPool(pool, payload) {
    const panel = shell.querySelector(`[data-pool="${pool}"]`);
    if (!panel) return;

    const stats = payload.stats || {};
    ['vendidas', 'consumidas', 'pendientes', 'montoVendido'].forEach((key) => {
      const el = panel.querySelector(`[data-stat="${key}"]`);
      if (!el) return;

      if (sensitiveStats.has(key) && (!sensitiveVisible || stats[key] === null || stats[key] === undefined)) {
        el.textContent = '****';
        el.classList.add('pool-stat__value--hidden');
        return;
      }

      el.classList.remove('pool-stat__value--hidden');
      el.textContent = key === 'montoVendido' ? formatMoney(stats[key] ?? 0) : (stats[key] ?? 0);
    });

    const queue = panel.querySelector('[data-queue]');
    const turns = payload.turnos || [];
    queue.innerHTML = '';

    if (!turns.length) {
      queue.innerHTML = '<div class="pool-empty">No hay personas esperando en este pool.</div>';
      return;
    }

    turns.forEach((turn, index) => {
      const otherPool = pool === 'azul' ? 'rojo' : 'azul';
      const node = document.createElement('article');
      node.className = 'pool-turn';
      node.draggable = true;
      node.dataset.id = turn.id;
      node.innerHTML = `
        <div>
          <div class="pool-turn__name" data-inline-name tabindex="0" title="Click para editar nombre">${escapeHtml(turn.nombre)}</div>
          <div class="pool-turn__meta">Turno ${index + 1} · ${turn.fichas_total - turn.fichas_consumidas}/${turn.fichas_total} pendientes</div>
          <div class="pool-chips">${chipHtml(turn.fichas_total, turn.fichas_consumidas)}</div>
        </div>
        <div class="pool-actions">
          <div class="pool-actions__chips">
            <button class="btn btn-sm pool-action pool-action--consume" data-action="consumir" title="Consumir ficha" aria-label="Consumir ficha"><i class="fa-solid fa-plus"></i></button>
            <button class="btn btn-sm pool-action pool-action--restore" data-action="revertir" title="Revertir ficha" aria-label="Revertir ficha"><i class="fa-solid fa-minus"></i></button>
          </div>
          <div class="pool-actions__order">
            <button class="btn btn-outline-secondary btn-sm pool-action" data-action="arriba" title="Subir" aria-label="Subir turno"><i class="fa-solid fa-arrow-up"></i></button>
            <button class="btn btn-outline-secondary btn-sm pool-action" data-action="abajo" title="Bajar" aria-label="Bajar turno"><i class="fa-solid fa-arrow-down"></i></button>
          </div>
          <div class="pool-actions__secondary">
            <button class="btn btn-info btn-sm pool-action" data-action="transferir" data-target="${otherPool}" title="Mover al otro pool" aria-label="Mover al otro pool"><i class="fa-solid fa-right-left"></i></button>
            <button class="btn btn-outline-secondary btn-sm pool-action" data-action="drag" title="Arrastrar" aria-label="Arrastrar"><i class="fa-solid fa-grip-vertical"></i></button>
          </div>
        </div>
      `;
      queue.appendChild(node);
    });
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
  }

  function formatMoney(value) {
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

  async function request(payload = null) {
    const options = payload
      ? {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify(payload),
        }
      : { method: 'GET' };

    let response;
    try {
      response = await fetch(apiUrl, options);
    } catch (error) {
      throw new Error('No pudimos conectar con el servidor. Verifica tu conexión e inténtalo nuevamente.');
    }

    let data;
    try {
      data = await response.json();
    } catch (error) {
      throw new Error('Ocurrió un problema inesperado. Inténtalo nuevamente.');
    }

    if (!data.exito) throw new Error(data.mensaje || 'No se pudo completar la operación.');

    if (data.privacidadAutorizada === true) {
      setSensitiveVisible(true);
    } else if (sensitiveVisible && data.estado) {
      setSensitiveVisible(false);
    }

    renderState(data.estado);
    return data;
  }

  function renderState(state) {
    renderJornada(state.jornada);
    renderPool('azul', state.pools.azul);
    renderPool('rojo', state.pools.rojo);
  }

  function renderJornada(jornada) {
    const abierta = Boolean(jornada);
    const status = shell.querySelector('[data-jornada-status]');
    const detail = shell.querySelector('[data-jornada-detail]');
    const abrir = shell.querySelector('[data-jornada-action="abrir_jornada"]');
    const cerrar = shell.querySelector('[data-jornada-action="cerrar_jornada"]');

    if (status) {
      status.innerHTML = abierta
        ? '<i class="fa-solid fa-circle-play text-success" aria-hidden="true"></i> Jornada activa'
        : '<i class="fa-solid fa-circle-stop text-secondary" aria-hidden="true"></i> Sin jornada activa';
    }

    if (detail) {
      detail.textContent = abierta
        ? `Jornada #${jornada.id} abierta desde ${formatDateTime(jornada.fecha_apertura)}.`
        : 'Abrí una jornada para empezar a registrar turnos.';
    }

    if (abrir) abrir.disabled = abierta;
    if (cerrar) cerrar.disabled = !abierta;

    shell.querySelectorAll('[data-add-form] input, [data-add-form] button').forEach((control) => {
      control.disabled = !abierta;
    });

    shell.querySelectorAll('.pool-turn button').forEach((button) => {
      button.disabled = !abierta;
    });
  }

  function formatDateTime(value) {
    if (!value) return '';
    const parsed = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) return value;
    return parsed.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
  }

  function selectEditableText(element) {
    const range = document.createRange();
    range.selectNodeContents(element);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  }

  function startInlineNameEdit(element) {
    if (element.isContentEditable || element.dataset.saving === 'true') return;

    const turn = element.closest('.pool-turn');
    element.dataset.previousName = element.textContent.trim();
    element.contentEditable = 'true';
    element.classList.add('pool-turn__name--editing');
    if (turn) {
      element.dataset.wasDraggable = turn.draggable ? '1' : '0';
      turn.draggable = false;
    }
    element.focus();
    selectEditableText(element);
  }

  async function finishInlineNameEdit(element, save = true) {
    if (!element.isContentEditable || element.dataset.saving === 'true') return;

    const turn = element.closest('.pool-turn');
    const previous = element.dataset.previousName || '';
    const next = element.textContent.trim();
    element.contentEditable = 'false';
    element.classList.remove('pool-turn__name--editing');
    if (turn && element.dataset.wasDraggable === '1') {
      turn.draggable = true;
    }
    delete element.dataset.wasDraggable;

    if (!save || next === '' || next === previous) {
      element.textContent = previous;
      return;
    }

    element.dataset.saving = 'true';
    try {
      await request({
        accion: 'renombrar',
        id: Number(turn?.dataset.id || 0),
        nombre: next,
      });
    } catch (error) {
      element.textContent = previous;
      showAlert(error.message);
    } finally {
      delete element.dataset.saving;
      delete element.dataset.previousName;
    }
  }

  privacyToggle?.addEventListener('click', () => {
    if (sensitiveVisible) {
      setSensitiveVisible(false);
      return;
    }

    privacyFeedback.textContent = '';
    privacyPassword.value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('poolPrivacyModal')).show();
  });

  document.getElementById('poolPrivacyModal')?.addEventListener('shown.bs.modal', () => {
    privacyPassword?.focus();
  });

  document.getElementById('poolPrivacyModal')?.addEventListener('hidden.bs.modal', () => {
    privacyForm?.reset();
    privacyFeedback.textContent = '';
  });

  privacyForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submitButton = privacyForm.querySelector('[data-privacy-submit]');
    privacyFeedback.textContent = '';
    submitButton.disabled = true;

    try {
      await request({
        accion: 'validar_privacidad',
        password: privacyPassword.value,
      });
      bootstrap.Modal.getOrCreateInstance(document.getElementById('poolPrivacyModal')).hide();
      privacyForm.reset();
    } catch (error) {
      setSensitiveVisible(false);
      privacyFeedback.textContent = error.message;
      privacyPassword.select();
    } finally {
      submitButton.disabled = false;
    }
  });

  shell.addEventListener('click', (event) => {
    const editableName = event.target.closest('[data-inline-name]');
    if (!editableName) return;
    startInlineNameEdit(editableName);
  });

  shell.addEventListener('keydown', (event) => {
    const editableName = event.target.closest('[data-inline-name]');
    if (!editableName) return;

    if (!editableName.isContentEditable && (event.key === 'Enter' || event.key === 'F2')) {
      event.preventDefault();
      startInlineNameEdit(editableName);
      return;
    }

    if (!editableName.isContentEditable) return;

    if (event.key === 'Enter') {
      event.preventDefault();
      editableName.blur();
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      finishInlineNameEdit(editableName, false);
    }
  });

  shell.addEventListener('focusout', (event) => {
    const editableName = event.target.closest('[data-inline-name]');
    if (!editableName) return;
    finishInlineNameEdit(editableName, true);
  });

  shell.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-jornada-action]');
    if (!button) return;

    const accion = button.dataset.jornadaAction;
    if (accion === 'cerrar_jornada' && !confirm('¿Cerrar la jornada activa? No se podrán registrar más operaciones en ella.')) {
      return;
    }

    try {
      await request({ accion });
      showAlert(accion === 'abrir_jornada' ? 'Jornada abierta correctamente.' : 'Jornada cerrada correctamente.', 'success');
    } catch (error) {
      showAlert(error.message);
    }
  });

  shell.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-add-form]');
    if (!form) return;
    event.preventDefault();
    const panel = form.closest('[data-pool]');
    const data = new FormData(form);

    try {
      await request({
        accion: 'agregar',
        pool: panel.dataset.pool,
        nombre: data.get('nombre'),
        fichas: Number(data.get('fichas')),
      });
      form.reset();
      form.elements.fichas.value = 1;
      form.elements.nombre.focus();
    } catch (error) {
      showAlert(error.message);
    }
  });

  shell.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button || button.dataset.action === 'drag') return;

    const turn = button.closest('.pool-turn');
    const id = Number(turn?.dataset.id || 0);
    const action = button.dataset.action;

    try {
      if (action === 'consumir' || action === 'revertir') {
        await request({ accion: action, id });
      } else if (action === 'transferir') {
        await request({ accion: 'transferir', id, poolDestino: button.dataset.target });
      } else if (action === 'arriba' || action === 'abajo') {
        await request({ accion: 'mover', id, direccion: action });
      }
    } catch (error) {
      showAlert(error.message);
    }
  });

  shell.addEventListener('dragstart', (event) => {
    const turn = event.target.closest('.pool-turn');
    if (!turn) return;
    draggedId = turn.dataset.id;
    turn.classList.add('dragging');
  });

  shell.addEventListener('dragend', (event) => {
    event.target.closest('.pool-turn')?.classList.remove('dragging');
    draggedId = null;
  });

  shell.addEventListener('dragover', (event) => {
    const queue = event.target.closest('[data-queue]');
    if (!queue || !draggedId) return;
    event.preventDefault();
    const after = [...queue.querySelectorAll('.pool-turn:not(.dragging)')].find((item) => {
      return event.clientY <= item.getBoundingClientRect().top + item.offsetHeight / 2;
    });
    const dragging = queue.querySelector('.dragging') || shell.querySelector(`.pool-turn[data-id="${draggedId}"]`);
    if (dragging && dragging.parentElement === queue) {
      queue.insertBefore(dragging, after || null);
    }
  });

  shell.addEventListener('drop', async (event) => {
    const queue = event.target.closest('[data-queue]');
    if (!queue) return;
    event.preventDefault();
    const panel = queue.closest('[data-pool]');
    const ids = [...queue.querySelectorAll('.pool-turn')].map((item) => Number(item.dataset.id));
    try {
      await request({ accion: 'reordenar', pool: panel.dataset.pool, ids });
    } catch (error) {
      showAlert(error.message);
    }
  });

  request().catch((error) => showAlert(error.message));
})();
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
