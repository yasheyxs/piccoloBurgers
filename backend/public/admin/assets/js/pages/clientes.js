// Pantallas de clientes: tablas con búsqueda y, en el formulario, el largo del teléfono según el país
$(function () {
  "use strict";

  if (document.getElementById("tablaClientes")) {
    initDataTable("#tablaClientes", { order: [], columnDefs: [{ targets: 5, orderable: false, searchable: false }] });
  }
  ["#tablaHistorialPedidos", "#tablaHistorialPuntos", "#tablaHistorialCanjes"].forEach(function (tabla) {
    if (document.querySelector(tabla)) {
      initDataTable(tabla, { order: [[tabla === "#tablaHistorialPedidos" ? 1 : 0, "desc"]] });
    }
  });

  var codigo = document.getElementById("codigo_pais");
  var telefono = document.getElementById("telefono");
  if (!codigo || !telefono) {
    return;
  }

  function aplicarLargo() {
    var opcion = codigo.options[codigo.selectedIndex];
    var largos = (opcion ? opcion.getAttribute("data-longitudes") || "" : "").split(",").map(Number).filter(Boolean);
    if (largos.length) {
      telefono.maxLength = Math.max.apply(null, largos);
    } else {
      telefono.removeAttribute("maxLength");
    }
  }

  codigo.addEventListener("change", aplicarLargo);
  telefono.addEventListener("input", function () {
    telefono.value = telefono.value.replace(/\D/g, "");
  });
  aplicarLargo();
});
