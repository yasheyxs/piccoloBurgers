// Pantalla del pool: jornada, fila de cada mesa, fichas, orden y datos sensibles (montos)
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
  const closePromptModal = document.getElementById('poolClosePromptModal');
  const closeAdjustModal = document.getElementById('poolCloseAdjustModal');
  const closeAdjustForm = shell.querySelector('[data-close-adjust-form]');
  const closeDirectButton = shell.querySelector('[data-close-direct]');
  const closeEditButton = shell.querySelector('[data-close-edit]');
  const closeAdjustSubmit = shell.querySelector('[data-close-adjust-submit]');
  let draggedId = null;
  let sensitiveVisible = false;
  let closeTotals = null;

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
            <button class="btn btn-sm pool-action pool-action--decrease" data-action="consumir" title="Restar ficha" aria-label="Restar ficha"><i class="fa-solid fa-minus"></i></button>
            <button class="btn btn-sm pool-action pool-action--increase" data-action="revertir" title="Sumar ficha" aria-label="Sumar ficha"><i class="fa-solid fa-plus"></i></button>
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

  function setClosingBusy(busy) {
    [closeDirectButton, closeEditButton, closeAdjustSubmit].forEach((button) => {
      if (button) button.disabled = busy;
    });
  }

  async function openClosePrompt() {
    setClosingBusy(true);
    try {
      const data = await request({ accion: 'totales_cierre' });
      closeTotals = data.totales || { fichasVendidas: 0, bebidasVendidas: 0 };
      bootstrap.Modal.getOrCreateInstance(closePromptModal).show();
    } catch (error) {
      showAlert(error.message);
    } finally {
      setClosingBusy(false);
    }
  }

  function openCloseAdjust() {
    if (!closeTotals) return;
    closeAdjustForm.elements.fichasVendidas.value = Number(closeTotals.fichasVendidas || 0);
    closeAdjustForm.elements.bebidasVendidas.value = Number(closeTotals.bebidasVendidas || 0);
    bootstrap.Modal.getOrCreateInstance(closePromptModal).hide();
    bootstrap.Modal.getOrCreateInstance(closeAdjustModal).show();
  }

  async function closeJornada(totales = null) {
    setClosingBusy(true);
    try {
      await request({
        accion: 'cerrar_jornada',
        ...(totales ? { totales } : {}),
      });
      bootstrap.Modal.getOrCreateInstance(closePromptModal).hide();
      bootstrap.Modal.getOrCreateInstance(closeAdjustModal).hide();
      closeTotals = null;
      showAlert('Jornada cerrada correctamente.', 'success');
    } catch (error) {
      showAlert(error.message);
    } finally {
      setClosingBusy(false);
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

  closeDirectButton?.addEventListener('click', () => closeJornada());
  closeEditButton?.addEventListener('click', openCloseAdjust);
  closeAdjustForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    closeJornada({
      fichasVendidas: Number(closeAdjustForm.elements.fichasVendidas.value || 0),
      bebidasVendidas: Number(closeAdjustForm.elements.bebidasVendidas.value || 0),
    });
  });

  shell.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-jornada-action]');
    if (!button) return;

    const accion = button.dataset.jornadaAction;
    if (accion === 'cerrar_jornada') {
      await openClosePrompt();
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
