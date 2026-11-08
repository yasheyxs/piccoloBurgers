// Gráficos del panel (Chart.js) que siguen el tema claro u oscuro.
// Uso: piccoloGrafico(document.getElementById("canvas"), { type: "bar", data: {...}, options: {...} })
(function (global) {
  "use strict";

  var graficos = [];

  function colores() {
    var oscuro = document.body.classList.contains("admin-dark");
    return {
      texto: oscuro ? "#e8ecf4" : "#212529",
      grilla: oscuro ? "rgba(232, 236, 244, 0.18)" : "rgba(33, 37, 41, 0.12)",
    };
  }

  function aplicarTema(grafico) {
    var c = colores();
    var plugins = grafico.options.plugins || {};
    if (plugins.legend && plugins.legend.labels) {
      plugins.legend.labels.color = c.texto;
    }
    Object.keys(grafico.options.scales || {}).forEach(function (eje) {
      var escala = grafico.options.scales[eje];
      escala.ticks = Object.assign({}, escala.ticks, { color: c.texto });
      escala.grid = Object.assign({}, escala.grid, { color: c.grilla });
    });
    grafico.update("none");
  }

  global.piccoloGrafico = function (canvas, configuracion) {
    if (!canvas || typeof global.Chart !== "function") {
      return null;
    }
    var grafico = new global.Chart(canvas, configuracion);
    graficos.push(grafico);
    aplicarTema(grafico);
    return grafico;
  };

  document.addEventListener("theme:changed", function () {
    graficos.forEach(aplicarTema);
  });
})(window);
