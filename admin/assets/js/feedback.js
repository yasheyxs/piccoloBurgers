(function () {
  const fallbackMessages = {
    danger: 'Ocurrio un problema inesperado. Intentalo nuevamente.',
    warning: 'Revisa la informacion antes de continuar.',
    success: 'Operacion realizada correctamente.',
    info: 'Informacion actualizada.',
    connection: 'No pudimos conectar con el servidor. Verifica tu conexion e intentalo nuevamente.',
    empty: 'No hay registros para mostrar.'
  };

  function sanitizeMessage(message, type) {
    const text = String(message || '').trim();
    if (!text) {
      return fallbackMessages[type] || fallbackMessages.danger;
    }

    const technicalPatterns = [
      /SQLSTATE/i,
      /PDOException/i,
      /Fatal error/i,
      /Stack trace/i,
      /syntax error/i,
      /Warning:/i,
      /Notice:/i
    ];

    return technicalPatterns.some((pattern) => pattern.test(text))
      ? fallbackMessages.danger
      : text;
  }

  window.showAdminFeedback = function showAdminFeedback(target, message, type = 'danger') {
    const element = typeof target === 'string' ? document.querySelector(target) : target;
    if (!element) {
      return;
    }

    const alertType = type === 'connection' || type === 'empty' ? 'warning' : type;
    element.className = `alert alert-${alertType} alert-dismissible fade show admin-feedback`;
    element.innerHTML = `
      <span>${sanitizeMessage(message, type)}</span>
      <button type="button" class="btn-close" aria-label="Cerrar"></button>
    `;
    element.classList.remove('d-none');

    const close = () => {
      element.classList.add('d-none');
      element.classList.remove('show');
    };

    element.querySelector('.btn-close')?.addEventListener('click', close, { once: true });
    setTimeout(close, 5000);
  };
})();
