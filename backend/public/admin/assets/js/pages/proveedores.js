// Pantalla de proveedores: tabla con búsqueda y confirmación antes de borrar
$(function () {
  initDataTable("#tablaProveedores");

  document.querySelectorAll("form[data-confirmar]").forEach(function (formulario) {
    formulario.addEventListener("submit", function (evento) {
      if (!window.confirm(formulario.dataset.confirmar)) {
        evento.preventDefault();
      }
    });
  });
});
