<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Http\Api\Peticion;

/**
 * Control de acceso de la API del panel: las mismas reglas que las pantallas (src/Http/Admin/acceso.php).
 *
 * Cada ruta indica a qué pantalla equivale (por ejemplo "seccion/proveedores/index.php"), así los
 * permisos por rol salen de la misma tabla, config/permisos.php.
 */
final class AccesoPanel
{
    /** @throws ApiException 401 sin sesión, 403 sin permiso o sin token CSRF */
    public static function exigir(Peticion $peticion, string $pantalla): void
    {
        if (!\piccolo_admin_sesion_vigente()) {
            throw new ApiException('Tenés que iniciar sesión en el panel.', 401);
        }

        $roles = \piccolo_admin_roles_permitidos($pantalla) ?? [];
        if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
            throw new ApiException('Tu usuario no tiene acceso a esta sección.', 403);
        }

        if (!in_array($peticion->metodo(), ['GET', 'HEAD', 'OPTIONS'], true) && !\piccolo_csrf_valido()) {
            throw new ApiException('Falta el token de seguridad (X-CSRF-Token) o venció. Pedí uno nuevo en GET /admin/sesion.', 403);
        }
    }

    /** El {id} de la ruta como número; si no es un número válido responde 404 con el mensaje indicado. */
    public static function idDeRuta(Peticion $peticion, string $noEncontrado): int
    {
        $id = $peticion->parametroRuta('id');
        if (!ctype_digit($id) || (int) $id <= 0) {
            throw ApiException::noEncontrado($noEncontrado);
        }

        return (int) $id;
    }
}
