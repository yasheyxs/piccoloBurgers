// Pantalla del menú: tabla de productos con búsqueda y orden
$(function () {
  initDataTable("#tablaMenu", { info: true, pageLength: 10, lengthMenu: [10, 25, 50, 100] });
});
