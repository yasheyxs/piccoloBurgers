// Catálogo de bebidas: carga los datos de la bebida en la ventana de edición
(function () {
  "use strict";

  var modal = document.getElementById("modalEditarBebida");
  var form = document.getElementById("formEditarBebida");
  if (!modal || !form) {
    return;
  }

  function cargar(bebida) {
    form.elements.id.value = bebida.id || "";
    form.elements.nombre.value = bebida.nombre || "";
    form.elements.valor_jarra.value = bebida.valor_jarra || "";
    form.elements.valor_promo.value = bebida.valor_promo || "";
    form.elements.jarras_por_promo.value = bebida.jarras_por_promo || 3;
    form.elements.activa.checked = bebida.activa === true || bebida.activa === "1";
  }

  document.querySelectorAll("[data-editar-bebida]").forEach(function (boton) {
    boton.addEventListener("click", function () {
      cargar(JSON.parse(boton.getAttribute("data-editar-bebida")));
    });
  });

  // Si no se pudo guardar una edición, se reabre la ventana con lo que se había escrito
  var reabrir = modal.getAttribute("data-reabrir");
  if (reabrir) {
    document.addEventListener("DOMContentLoaded", function () {
      cargar(JSON.parse(reabrir));
      bootstrap.Modal.getOrCreateInstance(modal).show();
    });
  }
})();
