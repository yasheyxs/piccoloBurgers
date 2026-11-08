// Estadísticas del pool y de bebidas: tabla de jornadas y dos gráficos (las jornadas, de la más vieja a la más nueva)
$(function () {
  "use strict";

  var datos = JSON.parse(document.getElementById("datosEstadisticas").textContent);
  var pool = datos.tipo === "pool";

  initDataTable("#tablaJornadas", { order: [[1, "desc"]] });

  if (pool) {
    piccoloGrafico(document.getElementById("graficoMesas"), {
      type: "bar",
      data: {
        labels: ["Pool azul", "Pool rojo"],
        datasets: [{
          label: "Fichas vendidas",
          data: [datos.mesas.azul.vendidas, datos.mesas.rojo.vendidas],
          backgroundColor: ["#2563eb", "#dc2626"],
        }],
      },
      options: { responsive: true, plugins: { legend: { display: false, labels: {} } }, scales: { x: {}, y: { beginAtZero: true } } },
    });
  } else {
    piccoloGrafico(document.getElementById("graficoMesas"), {
      type: "bar",
      data: {
        labels: datos.porBebida.map(function (b) { return b.nombre; }),
        datasets: [{ label: "Jarras vendidas", data: datos.porBebida.map(function (b) { return b.valor; }), backgroundColor: "#0d9488" }],
      },
      options: { responsive: true, plugins: { legend: { display: false, labels: {} } }, scales: { x: {}, y: { beginAtZero: true } } },
    });
  }

  piccoloGrafico(document.getElementById("graficoJornadas"), {
    type: "line",
    data: {
      labels: datos.jornadas.map(function (j) { return "#" + j.id; }),
      datasets: [{
        label: pool ? "Fichas vendidas" : "Total vendido",
        data: datos.jornadas.map(function (j) { return j.valor; }),
        borderColor: pool ? "#2563eb" : "#f59e0b",
        backgroundColor: pool ? "rgba(37, 99, 235, 0.18)" : "rgba(245, 158, 11, 0.18)",
        tension: 0.25,
      }],
    },
    options: { responsive: true, plugins: { legend: { labels: {} } }, scales: { x: {}, y: { beginAtZero: true } } },
  });
});
