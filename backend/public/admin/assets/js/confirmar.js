// Pide confirmación antes de enviar los formularios marcados con data-confirmar="mensaje"
// (por ejemplo, los botones de borrar). Sirve para todas las pantallas del panel.
document.addEventListener("submit", function (evento) {
  var formulario = evento.target;
  if (formulario instanceof HTMLFormElement && formulario.dataset.confirmar && !window.confirm(formulario.dataset.confirmar)) {
    evento.preventDefault();
  }
});
