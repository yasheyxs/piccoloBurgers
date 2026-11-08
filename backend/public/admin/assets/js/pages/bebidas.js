// Pantalla de bebidas: jornada, ventas, entregas y datos sensibles (montos)
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
  const closePromptModal = document.getElementById('drinkClosePromptModal');
  const closeAdjustModal = document.getElementById('drinkCloseAdjustModal');
  const closeAdjustForm = shell.querySelector('[data-close-adjust-form]');
  const closeDirectButton = shell.querySelector('[data-close-direct]');
  const closeEditButton = shell.querySelector('[data-close-edit]');
  const closeAdjustSubmit = shell.querySelector('[data-close-adjust-submit]');
  let sensitiveVisible = false;
  let latestState = null;
  let closeTotals = null;

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

  function setClosingBusy(busy) {
    [closeDirectButton, closeEditButton, closeAdjustSubmit].forEach(button => {
      if (button) button.disabled = busy;
    });
  }

  async function openClosePrompt() {
    setClosingBusy(true);
    try {
      const data = await request({ accion: 'totales_cierre' });
      closeTotals = data.totales || { fichasVendidas: 0, bebidasVendidas: 0 };
      bootstrap.Modal.getOrCreateInstance(closePromptModal).show();
    } catch (error) { showAlert(error.message); }
    finally { setClosingBusy(false); }
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
        ...(totales ? { totales } : {})
      });
      bootstrap.Modal.getOrCreateInstance(closePromptModal).hide();
      bootstrap.Modal.getOrCreateInstance(closeAdjustModal).hide();
      closeTotals = null;
      showAlert('Jornada cerrada correctamente.', 'success');
    } catch (error) { showAlert(error.message); }
    finally { setClosingBusy(false); }
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

  closeDirectButton?.addEventListener('click', () => closeJornada());
  closeEditButton?.addEventListener('click', openCloseAdjust);
  closeAdjustForm?.addEventListener('submit', event => {
    event.preventDefault();
    closeJornada({
      fichasVendidas: Number(closeAdjustForm.elements.fichasVendidas.value || 0),
      bebidasVendidas: Number(closeAdjustForm.elements.bebidasVendidas.value || 0)
    });
  });

  shell.addEventListener('click', async event => {
    const jornadaButton = event.target.closest('[data-jornada-action]');
    if (jornadaButton) {
      const accion = jornadaButton.dataset.jornadaAction;
      if (accion === 'cerrar_jornada') {
        await openClosePrompt();
        return;
      }
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
