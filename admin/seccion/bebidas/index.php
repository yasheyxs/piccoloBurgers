<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/bebidas_schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$rolActual = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['admin_logueado']) || !in_array($rolActual, ['admin', 'empleado'], true)) {
    header('Location: ' . piccolo_admin_base_url() . 'login.php');
    exit;
}

asegurarTablasBebidas($conexion);
$adminPageIdentifier = 'bebidas-admin';
include __DIR__ . '/../../templates/header.php';
?>

<style>
  .drink-shell { --drink-accent: #0d9488; }
  .drink-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .75rem;
  }
  .drink-stat, .drink-card, .drink-form-card, .drink-jornada {
    border: 1px solid var(--admin-border);
    background: var(--admin-surface);
    border-radius: .9rem;
  }
  .drink-stat { padding: .8rem; background: var(--admin-surface-alt); }
  .drink-stat__label { display: block; color: var(--admin-muted); font-size: .72rem; text-transform: uppercase; }
  .drink-stat__value { color: var(--admin-text); font-size: 1.45rem; font-weight: 800; }
  .drink-stat__value--hidden { color: var(--admin-muted); letter-spacing: .08em; }
  .drink-form { display: grid; grid-template-columns: 2fr 1.3fr 1fr 90px auto; gap: .65rem; }
  .drink-queue { display: flex; flex-direction: column; gap: .65rem; }
  .drink-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; padding: .85rem; }
  .drink-card__person { font-size: 1.05rem; font-weight: 800; color: var(--admin-text); }
  .drink-card__person[data-inline-name] {
    border-radius: .35rem;
    cursor: text;
    margin: -.08rem -.15rem;
    outline: none;
    padding: .08rem .15rem;
  }
  .drink-card__person[data-inline-name]:hover,
  .drink-card__person[data-inline-name]:focus,
  .drink-card__person--editing {
    background: var(--admin-surface-alt);
    box-shadow: inset 0 0 0 1px var(--admin-border);
  }
  .drink-card__meta { color: var(--admin-muted); font-size: .88rem; }
  .drink-pending { color: var(--drink-accent); font-weight: 800; }
  .drink-chips { display: flex; flex-wrap: wrap; gap: .28rem; margin-top: .5rem; }
  .drink-chip {
    width: 1.05rem;
    height: 1.05rem;
    border: 2px solid #f59e0b;
    border-radius: 999px;
    background: #f59e0b;
  }
  .drink-actions { display: flex; gap: .4rem; align-items: center; }
  .drink-action {
    width: 48px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1;
  }
  .drink-action--deliver {
    background: var(--admin-btn-success-bg);
    border-color: var(--admin-btn-success-bg);
    color: var(--admin-btn-success-text);
  }
  .drink-action--revert {
    background: var(--admin-btn-danger-bg);
    border-color: var(--admin-btn-danger-bg);
    color: var(--admin-btn-danger-text);
  }
  .drink-empty { border: 1px dashed var(--admin-border); border-radius: .9rem; padding: 2rem; text-align: center; color: var(--admin-muted); }
  .drink-privacy-feedback { min-height: 1.4rem; }
  @media (max-width: 991.98px) {
    .drink-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .drink-form { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 575.98px) {
    .drink-summary, .drink-form { grid-template-columns: 1fr; }
    .drink-card { grid-template-columns: 1fr; }
    .drink-actions { justify-content: flex-start; }
  }
</style>

<div class="drink-shell py-4" data-api-url="api.php" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
</div>

<script>
(() => {
  const shell = document.querySelector('.drink-shell');
  if (!shell) return;
  const apiUrl = shell.dataset.apiUrl;
  const csrf = shell.dataset.csrf;
  const sensitiveStats = new Set(['vendidas', 'entregadas', 'montoVendido']);
  const privacyToggle = shell.querySelector('[data-privacy-toggle]');
  const privacyForm = shell.querySelector('[data-privacy-form]');
  const privacyPassword = shell.querySelector('[data-privacy-password]');
  const privacyFeedback = shell.querySelector('[data-privacy-feedback]');
  let sensitiveVisible = false;
  let latestState = null;

  const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  }[char]));

  function formatMoney(value) {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(Number(value || 0));
  }

  function chipHtml(pending) {
    return Array.from(
      { length: Math.max(0, Number(pending || 0)) },
      () => '<span class="drink-chip" aria-hidden="true"></span>'
    ).join('');
  }

  function showAlert(message, type = 'danger') {
    const alert = document.getElementById('drinkAlert');
    if (window.showAdminFeedback) return window.showAdminFeedback(alert, message, type);
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.classList.remove('d-none');
  }

  function setSensitiveVisible(visible) {
    sensitiveVisible = visible;
    if (!visible) {
      shell.querySelectorAll('[data-sensitive]').forEach(element => {
        element.textContent = '****';
        element.classList.add('drink-stat__value--hidden');
      });
    }
    const label = visible ? 'Ocultar información sensible' : 'Mostrar información sensible';
    privacyToggle.title = label;
    privacyToggle.setAttribute('aria-label', label);
    privacyToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
    privacyToggle.innerHTML = visible ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
  }

  function renderState(state) {
    latestState = state;
    const jornada = state.jornada;
    const status = shell.querySelector('[data-jornada-status]');
    const detail = shell.querySelector('[data-jornada-detail]');
    status.innerHTML = jornada
      ? '<i class="fa-solid fa-circle-play text-success me-1"></i> Jornada activa'
      : '<i class="fa-solid fa-circle-stop text-secondary me-1"></i> Sin jornada activa';
    detail.textContent = jornada ? `Jornada #${jornada.id} compartida con POOLS.` : 'Abrí una jornada para registrar ventas.';
    shell.querySelector('[data-jornada-action="abrir_jornada"]').disabled = Boolean(jornada);
    shell.querySelector('[data-jornada-action="cerrar_jornada"]').disabled = !jornada;

    const select = shell.querySelector('[data-bebida-select]');
    const current = select.value;
    select.innerHTML = state.bebidas.map(bebida =>
      `<option value="${bebida.id}">${escapeHtml(bebida.nombre)}</option>`
    ).join('');
    if ([...select.options].some(option => option.value === current)) select.value = current;

    shell.querySelectorAll('[data-add-form] input, [data-add-form] select, [data-add-form] button').forEach(control => {
      control.disabled = !jornada || !state.bebidas.length;
    });

    Object.entries(state.stats).forEach(([key, value]) => {
      const element = shell.querySelector(`[data-stat="${key}"]`);
      if (!element) return;
      if (sensitiveStats.has(key) && (!sensitiveVisible || value === null)) {
        element.textContent = '****';
        element.dataset.sensitive = 'true';
        element.classList.add('drink-stat__value--hidden');
      } else {
        element.textContent = key === 'montoVendido' ? formatMoney(value) : value;
        element.classList.remove('drink-stat__value--hidden');
      }
    });

    const queue = shell.querySelector('[data-queue]');
    queue.innerHTML = '';
    if (!state.ventas.length) {
      queue.innerHTML = '<div class="drink-empty">No hay bebidas pendientes de entrega.</div>';
      return;
    }

    state.ventas.forEach(venta => {
      const card = document.createElement('article');
      card.className = 'drink-card';
      card.dataset.id = venta.id;
      const privateDetail = sensitiveVisible && venta.cantidad_total !== null
        ? ` Vendidas: ${venta.cantidad_total}. Jarras entregadas: ${venta.cantidad_entregada}. Valor unitario: ${formatMoney(venta.valor_unitario)}.`
        : '';
      const promoDetail = venta.tipo === 'promo'
        ? ` (${venta.unidades_por_item} jarras por promoción)`
        : '';
      card.innerHTML = `
        <div>
          <div class="drink-card__person" data-inline-name tabindex="0" title="Click para editar nombre">${escapeHtml(venta.persona)}</div>
          <div class="drink-card__meta">${escapeHtml(venta.bebida_nombre)} · ${venta.tipo === 'promo' ? 'Promoción' : 'Jarra'}${promoDetail}.${privateDetail}</div>
          <div class="drink-pending mt-1">${venta.pendientes} pendiente${venta.pendientes === 1 ? '' : 's'}</div>
          <div class="drink-chips">${chipHtml(venta.pendientes)}</div>
        </div>
        <div class="drink-actions">
          <button class="btn drink-action drink-action--revert" data-action="entregar" title="Entregar una jarra" aria-label="Entregar una jarra"><i class="fa-solid fa-minus"></i></button>
          <button class="btn drink-action drink-action--deliver" data-action="revertir" title="Revertir una entrega" aria-label="Revertir una entrega"><i class="fa-solid fa-plus"></i></button>
        </div>`;
      queue.appendChild(card);
    });
  }

  async function request(payload = null) {
    const response = await fetch(apiUrl, payload ? {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(payload)
    } : { method: 'GET' });
    const data = await response.json();
    if (!data.exito) throw new Error(data.mensaje || 'No se pudo completar la operación.');
    if (data.privacidadAutorizada) setSensitiveVisible(true);
    else if (sensitiveVisible) setSensitiveVisible(false);
    renderState(data.estado);
    return data;
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

    element.dataset.previousName = element.textContent.trim();
    element.contentEditable = 'true';
    element.classList.add('drink-card__person--editing');
    element.focus();
    selectEditableText(element);
  }

  async function finishInlineNameEdit(element, save = true) {
    if (!element.isContentEditable || element.dataset.saving === 'true') return;

    const card = element.closest('.drink-card');
    const previous = element.dataset.previousName || '';
    const next = element.textContent.trim();
    element.contentEditable = 'false';
    element.classList.remove('drink-card__person--editing');

    if (!save || next === '' || next === previous) {
      element.textContent = previous;
      return;
    }

    element.dataset.saving = 'true';
    try {
      await request({
        accion: 'renombrar',
        id: Number(card?.dataset.id || 0),
        nombre: next
      });
    } catch (error) {
      element.textContent = previous;
      showAlert(error.message);
    } finally {
      delete element.dataset.saving;
      delete element.dataset.previousName;
    }
  }

  shell.addEventListener('submit', async event => {
    const form = event.target.closest('[data-add-form]');
    if (!form) return;
    event.preventDefault();
    const data = new FormData(form);
    try {
      await request({
        accion: 'agregar',
        persona: data.get('persona'),
        bebidaId: Number(data.get('bebidaId')),
        tipo: data.get('tipo'),
        cantidad: Number(data.get('cantidad'))
      });
      form.reset();
      form.elements.cantidad.value = 1;
      form.elements.persona.focus();
    } catch (error) { showAlert(error.message); }
  });

  shell.addEventListener('click', event => {
    const editableName = event.target.closest('[data-inline-name]');
    if (!editableName) return;
    startInlineNameEdit(editableName);
  });

  shell.addEventListener('keydown', event => {
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

  shell.addEventListener('focusout', event => {
    const editableName = event.target.closest('[data-inline-name]');
    if (!editableName) return;
    finishInlineNameEdit(editableName, true);
  });

  shell.addEventListener('click', async event => {
    const jornadaButton = event.target.closest('[data-jornada-action]');
    if (jornadaButton) {
      const accion = jornadaButton.dataset.jornadaAction;
      if (accion === 'cerrar_jornada' && !confirm('¿Cerrar la jornada compartida? También se cerrará para POOLS.')) return;
      try { await request({ accion }); } catch (error) { showAlert(error.message); }
      return;
    }
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const id = Number(button.closest('.drink-card')?.dataset.id || 0);
    const action = button.dataset.action;
    try {
      await request({
        accion: action,
        id
      });
    } catch (error) { showAlert(error.message); }
  });

  privacyToggle.addEventListener('click', () => {
    if (sensitiveVisible) {
      setSensitiveVisible(false);
      if (latestState) renderState(latestState);
      return;
    }
    privacyFeedback.textContent = '';
    privacyForm.reset();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('drinkPrivacyModal')).show();
  });

  privacyForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = privacyForm.querySelector('[data-privacy-submit]');
    submit.disabled = true;
    privacyFeedback.textContent = '';
    try {
      await request({ accion: 'validar_privacidad', password: privacyPassword.value });
      bootstrap.Modal.getOrCreateInstance(document.getElementById('drinkPrivacyModal')).hide();
      privacyForm.reset();
    } catch (error) {
      setSensitiveVisible(false);
      if (latestState) renderState(latestState);
      privacyFeedback.textContent = error.message;
      privacyPassword.select();
    } finally { submit.disabled = false; }
  });

  request().catch(error => showAlert(error.message));
})();
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
