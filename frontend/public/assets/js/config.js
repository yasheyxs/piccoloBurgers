/**
 * Dirección de la API del backend.
 *
 * - Publicado junto al backend (Docker o un proxy que envíe /api al backend): "/api/v1".
 * - Con XAMPP (proyecto en htdocs/piccoloBurgers) se detecta sola.
 * - Si el frontend se publica en otro dominio, definí window.PICCOLO_API_URL antes de cargar
 *   los scripts, por ejemplo: <script>window.PICCOLO_API_URL = "https://api.midominio.com/api/v1"</script>
 */
function detectarApi() {
  const marca = "/frontend/public/";
  const ruta = window.location.pathname;

  if (ruta.includes(marca)) {
    return ruta.slice(0, ruta.indexOf(marca)) + "/backend/public/api/v1";
  }

  return "/api/v1";
}

export const API_URL = (window.PICCOLO_API_URL || detectarApi()).replace(/\/$/, "");

