// Receta de un producto: agregar y quitar insumos. Se guarda la receta completa al enviar.
// Las filas nuevas salen del molde <template id="moldeInsumo"> que arma el servidor.
(function () {
  var cuerpo = document.getElementById("receta");
  var molde = document.getElementById("moldeInsumo");
  if (!cuerpo || !molde) {
    return;
  }

  document.getElementById("agregarInsumo").addEventListener("click", function () {
    var indice = Number(cuerpo.dataset.siguiente || 0);
    cuerpo.dataset.siguiente = String(indice + 1);

    var fila = molde.content.firstElementChild.cloneNode(true);
    fila.querySelectorAll("[name]").forEach(function (campo) {
      campo.name = campo.name.replace("__N__", String(indice));
    });
    cuerpo.appendChild(fila);
    fila.querySelector("select").focus();
  });

  cuerpo.addEventListener("click", function (evento) {
    var boton = evento.target.closest(".quitar-insumo");
    if (boton) {
      boton.closest(".fila-receta").remove();
    }
  });
})();
