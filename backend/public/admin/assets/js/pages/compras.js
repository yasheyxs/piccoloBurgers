// Pantalla de compras: historial con búsqueda y gráficos de gasto por proveedor y materias primas
$(function () {
  initDataTable("#tablaCompras", { pageLength: 5, lengthMenu: [5, 10, 25, 50], order: [[0, "desc"]] });

  var resumen = JSON.parse(document.getElementById("datosCompras").textContent);

  piccoloGrafico(document.getElementById("graficoProveedores"), {
    type: "bar",
    data: {
      labels: resumen.proveedores.map(function (p) { return p.nombre; }),
      datasets: [{
        label: "Compras por proveedor",
        data: resumen.proveedores.map(function (p) { return p.total; }),
        backgroundColor: "rgba(54, 162, 235, 0.6)",
      }],
    },
    options: { responsive: true, plugins: { legend: { labels: {} } }, scales: { x: {}, y: { beginAtZero: true } } },
  });

  piccoloGrafico(document.getElementById("graficoMaterias"), {
    type: "pie",
    data: {
      labels: resumen.materias_primas.map(function (m) { return m.nombre; }),
      datasets: [{
        label: "Cantidad comprada",
        data: resumen.materias_primas.map(function (m) { return m.cantidad; }),
        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"],
      }],
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: {} } } },
  });
});
