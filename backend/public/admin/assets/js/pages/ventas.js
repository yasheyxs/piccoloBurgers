// Pantalla de ventas: torta de productos más vendidos y barras apiladas por mes (pedidos, fichas y bebidas)
(function () {
  "use strict";

  var datos = JSON.parse(document.getElementById("datosVentas").textContent);

  piccoloGrafico(document.getElementById("graficoProductos"), {
    type: "pie",
    data: {
      labels: datos.productos.map(function (p) { return p.nombre; }),
      datasets: [{
        data: datos.productos.map(function (p) { return p.cantidad; }),
        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF", "#FF9F40", "#C9CBCF", "#8DD17E", "#E377C2", "#17BECF"],
      }],
    },
    options: { responsive: true, plugins: { legend: { position: "bottom", labels: {} } } },
  });

  var series = [
    { campo: "pedidos", nombre: "Pedidos", color: "#36A2EB" },
    { campo: "fichas", nombre: "Fichas de pool", color: "#4BC0C0" },
    { campo: "bebidas", nombre: "Bebidas", color: "#FFCE56" },
  ];

  piccoloGrafico(document.getElementById("graficoMensual"), {
    type: "bar",
    data: {
      labels: datos.mensual.map(function (m) { return m.nombre; }),
      datasets: series.map(function (s) {
        return {
          label: s.nombre,
          data: datos.mensual.map(function (m) { return m[s.campo]; }),
          backgroundColor: s.color,
        };
      }),
    },
    options: {
      responsive: true,
      plugins: { legend: { position: "bottom", labels: {} } },
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
    },
  });
})();
