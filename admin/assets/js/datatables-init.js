(function (global) {
  'use strict';

  var defaultOptions = {
    paging: true,
    searching: true,
    info: false,
    lengthChange: true,
    responsive: true,
    fixedHeader: true,
    language: {
      decimal: "",
      emptyTable: "No hay datos disponibles en la tabla",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      lengthMenu: "Mostrar registros: _MENU_",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "No se encontraron registros coincidentes",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      aria: {
        sortAscending: ": activar para ordenar la columna ascendente",
        sortDescending: ": activar para ordenar la columna descendente"
      }
    }
  };

  function initDataTable(selector, options) {
    var $ = global.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) {
      if (global.console && typeof global.console.warn === 'function') {
        console.warn('initDataTable requiere que jQuery y DataTables estén cargados.');
      }
      return null;
    }

    var $table = selector && selector.jquery ? selector : $(selector);

    if (!$table || !$table.length) {
      return null;
    }

    if ($.fn.DataTable.isDataTable($table)) {
      $table.DataTable().clear().destroy();
    }

    var settings = $.extend(true, {}, defaultOptions, options || {});
    return $table.DataTable(settings);
  }

  global.initDataTable = initDataTable;
})(window);
