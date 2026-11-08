<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\UsuariosRepositorio;

/**
 * Reglas de la sección Usuarios del panel. La usan las pantallas y la API (/api/v1/admin/usuarios).
 *
 * - Siempre tiene que quedar al menos un administrador.
 * - Nadie puede borrar su propio usuario (se quedaría afuera en medio de la sesión).
 * - Para tocar una cuenta de administrador, o para darle a alguien ese rol, quien está usando
 *   el panel confirma con su propia contraseña.
 */
final class UsuariosController
{
    public const ROLES = ['admin' => 'Administrador', 'empleado' => 'Empleado', 'delivery' => 'Delivery'];
    private const NO_EXISTE = 'El usuario no existe o ya fue eliminado.';

    public function __construct(private UsuariosRepositorio $repositorio)
    {
        require_once \piccolo_path('src/Services/Security/password_utils.php');
    }

    /**
     * @param int $actualId usuario que está usando el panel
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $actualId): array
    {
        $usuarios = $this->repositorio->listar();
        $admins = count(array_filter($usuarios, static fn(array $u): bool => $u['rol'] === 'admin'));

        return array_map(static function (array $u) use ($actualId, $admins): array {
            $usuario = self::formato($u);
            $usuario['sos_vos'] = $usuario['id'] === $actualId;
            $usuario['se_puede_borrar'] = !$usuario['sos_vos'] && !($usuario['rol'] === 'admin' && $admins <= 1);

            return $usuario;
        }, $usuarios);
    }

    /** @return array{id: int, usuario: string, correo: string, rol: string} */
    public function obtener(int $id): array
    {
        $usuario = $this->repositorio->buscar($id);
        if ($usuario === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return self::formato($usuario);
    }

    /**
     * @param array<string, mixed> $datos usuario, correo, rol, password, confirmar y, si el nuevo es administrador, password_actual
     * @return array{id: int, usuario: string, correo: string, rol: string}
     */
    public function crear(array $datos, int $actualId): array
    {
        $validos = $this->validar($datos);
        $password = is_string($datos['password'] ?? null) ? $datos['password'] : '';
        $errores = [];
        if (!\passwordCumpleRequisitos($password)) {
            $errores['password'] = \mensajeRequisitosPassword();
        } elseif ($password !== ($datos['confirmar'] ?? null)) {
            $errores['confirmar'] = 'Las contraseñas no coinciden.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }
        if ($validos['rol'] === 'admin') {
            $this->confirmarIdentidad($actualId, $datos);
        }

        return $this->obtener($this->repositorio->crear($validos, \generarHashPassword($password)));
    }

    /**
     * @param array<string, mixed> $datos usuario, correo, rol y, si se toca un administrador, password_actual
     * @return array{id: int, usuario: string, correo: string, rol: string}
     */
    public function actualizar(int $id, array $datos, int $actualId): array
    {
        $anterior = $this->obtener($id);
        $validos = $this->validar($datos, $id);

        if ($anterior['rol'] === 'admin' && $validos['rol'] !== 'admin' && $this->repositorio->administradores($id) === 0) {
            throw ApiException::validacion(['rol' => 'Es el único administrador: no se le puede cambiar el rol.']);
        }
        if ($anterior['rol'] === 'admin' || $validos['rol'] === 'admin') {
            $this->confirmarIdentidad($actualId, $datos);
        }

        $this->repositorio->actualizar($id, $validos);

        return $this->obtener($id);
    }

    public function eliminar(int $id, int $actualId): void
    {
        $usuario = $this->obtener($id);
        if ($id === $actualId) {
            throw new ApiException('No podés borrar tu propio usuario.', 409);
        }
        if ($usuario['rol'] === 'admin' && $this->repositorio->administradores($id) === 0) {
            throw new ApiException('No se puede borrar el único administrador.', 409);
        }

        $this->repositorio->eliminar($id);
    }

    /** Pide la contraseña de quien está usando el panel antes de un cambio delicado. */
    private function confirmarIdentidad(int $actualId, array $datos): void
    {
        $password = is_string($datos['password_actual'] ?? null) ? $datos['password_actual'] : '';
        $hash = $this->repositorio->hashPassword($actualId);
        if ($password === '' || $hash === null || !\passwordCoincideConHash($password, $hash)) {
            throw ApiException::validacion(['password_actual' => 'Para cambios en administradores, ingresá tu contraseña.']);
        }
    }

    /**
     * @return array{usuario: string, correo: string, rol: string}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos, int $id = 0): array
    {
        $usuario = Datos::texto($datos['usuario'] ?? '');
        $correo = Datos::texto($datos['correo'] ?? '');
        $rol = Datos::texto($datos['rol'] ?? '');
        $errores = [];

        if (mb_strlen($usuario) < 3 || mb_strlen($usuario) > 50) {
            $errores['usuario'] = 'El nombre de usuario tiene que tener entre 3 y 50 caracteres.';
        } elseif ($this->repositorio->enUso('usuario', $usuario, $id)) {
            $errores['usuario'] = 'Ya hay un usuario con ese nombre.';
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 160) {
            $errores['correo'] = 'Ingresá un correo electrónico válido.';
        } elseif ($this->repositorio->enUso('correo', $correo, $id)) {
            $errores['correo'] = 'Ya hay un usuario con ese correo.';
        }

        if (!isset(self::ROLES[$rol])) {
            $errores['rol'] = 'Elegí un rol de la lista.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return ['usuario' => $usuario, 'correo' => $correo, 'rol' => $rol];
    }

    /** @return array{id: int, usuario: string, correo: string, rol: string} */
    private static function formato(array $fila): array
    {
        return [
            'id' => (int) $fila['ID'],
            'usuario' => (string) $fila['usuario'],
            'correo' => (string) $fila['correo'],
            'rol' => (string) $fila['rol'],
        ];
    }
}
