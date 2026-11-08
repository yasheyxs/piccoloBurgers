<?php

/**
 * Control de acceso del panel. Todas las pantallas de public/admin/ lo cargan en su primera línea.
 *
 * Para cada petición:
 *  1. Inicia la sesión del panel con una cookie segura.
 *  2. Si la pantalla no es pública, exige sesión y un rol permitido (config/permisos.php).
 *  3. Cierra la sesión después de un tiempo sin actividad.
 *  4. En cualquier envío de datos (POST, PUT, DELETE) exige el token CSRF.
 *
 * Las respuestas de error son JSON para los endpoints que usa JavaScript y una página o
 * redirección al login para las pantallas.
 */

require_once __DIR__ . '/sesion_panel.php';

(static function (): void {
    piccolo_admin_iniciar_sesion();
    $pagina = piccolo_admin_pagina_actual();
    $roles = piccolo_admin_roles_permitidos($pagina);

    if ($roles === null) {
        piccolo_csrf_token();
        return;
    }

    $habiaSesion = !empty($_SESSION['admin_logueado']);
    if (!piccolo_admin_sesion_vigente()) {
        $mensaje = $habiaSesion ? 'Tu sesión se cerró. Volvé a iniciar sesión.' : 'Tenés que iniciar sesión en el panel.';
        piccolo_admin_rechazar(401, $mensaje, $pagina);
    }

    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        piccolo_admin_rechazar(403, 'Tu usuario no tiene acceso a esta sección.', $pagina);
    }

    piccolo_csrf_token();
    $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true) && !piccolo_csrf_valido()) {
        piccolo_admin_rechazar(403, 'La sesión de este formulario venció. Recargá la página e intentá de nuevo.', $pagina);
    }
})();
