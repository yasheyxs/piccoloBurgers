// Formulario de compra: agregar y quitar filas de materias primas.
// Las filas nuevas salen del molde <template id="moldeMateria"> que arma el servidor,
// así los nombres de las materias primas nunca se insertan como HTML desde JavaScript.
(function () {
  var contenedor = document.getElementById("materias");
  var molde = document.getElementById("moldeMateria");
  if (!contenedor || !molde) {
    return;
  }

  document.getElementById("agregarMateria").addEventListener("click", function () {
    var indice = Number(contenedor.dataset.siguiente || 0);
    contenedor.dataset.siguiente = String(indice + 1);

    var fila = molde.content.firstElementChild.cloneNode(true);
    fila.querySelectorAll("[name]").forEach(function (campo) {
      campo.name = campo.name.replace("__N__", String(indice));
    });
    contenedor.appendChild(fila);
    fila.querySelector("select").focus();
  });

  contenedor.addEventListener("click", function (evento) {
    var boton = evento.target.closest(".quitar-materia");
    if (boton && contenedor.querySelectorAll(".fila-materia").length > 1) {
      boton.closest(".fila-materia").remove();
    }
  });
})();
