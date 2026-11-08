<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ProveedoresRepositorio;

/**
 * Reglas de la sección Proveedores. La usan las pantallas del panel y la API
 * (/api/v1/admin/proveedores), así las dos validan y responden igual.
 *
 * Los errores se avisan con ApiException: 422 si los datos no son válidos, 404 si el proveedor
 * no existe y 409 si no se puede borrar porque está en uso.
 */
final class ProveedoresController
{
    public function __construct(private ProveedoresRepositorio $repositorio)
    {
    }

    /** @return array<int, array{id: int, nombre: string, telefono: string, email: ?string}> */
    public function listar(): array
    {
        return array_map([self::class, 'formato'], $this->repositorio->listar());
    }

    /** @return array{id: int, nombre: string, telefono: string, email: ?string} */
    public function obtener(int $id): array
    {
        $proveedor = $this->repositorio->buscar($id);
        if ($proveedor === null) {
            throw ApiException::noEncontrado('El proveedor no existe o ya fue eliminado.');
        }

        return self::formato($proveedor);
    }

    /** @return array{id: int, nombre: string, telefono: string, email: ?string} */
    public function crear(array $datos): array
    {
        $id = $this->repositorio->crear($this->validar($datos));

        return $this->obtener($id);
    }

    /** @return array{id: int, nombre: string, telefono: string, email: ?string} */
    public function actualizar(int $id, array $datos): array
    {
        $this->obtener($id);
        $this->repositorio->actualizar($id, $this->validar($datos));

        return $this->obtener($id);
    }

    /** No se borra un proveedor con materias primas o compras: esos registros quedarían sin proveedor. */
    public function eliminar(int $id): void
    {
        $this->obtener($id);

        $usos = $this->repositorio->usos($id);
        if ($usos['materias_primas'] > 0 || $usos['compras'] > 0) {
            $detalle = [];
            if ($usos['materias_primas'] > 0) {
                $detalle[] = $usos['materias_primas'] . ' materia' . ($usos['materias_primas'] === 1 ? ' prima' : 's primas');
            }
            if ($usos['compras'] > 0) {
                $detalle[] = $usos['compras'] . ' compra' . ($usos['compras'] === 1 ? '' : 's');
            }
            throw new ApiException('No se puede eliminar porque está asociado a ' . implode(' y ', $detalle) . '.', 409);
        }

        $this->repositorio->eliminar($id);
    }

    /**
     * @return array{nombre: string, telefono: string, email: ?string}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos): array
    {
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $telefono = Datos::texto($datos['telefono'] ?? '');
        $email = Datos::texto($datos['email'] ?? '');
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (!preg_match('/^[\p{L}\p{N}\s.,&\'"()\-]+$/u', $nombre)) {
            $errores['nombre'] = 'El nombre tiene caracteres que no se permiten.';
        } elseif (mb_strlen($nombre) > 50) {
            $errores['nombre'] = 'El nombre puede tener hasta 50 caracteres.';
        }

        if ($telefono === '') {
            $errores['telefono'] = 'El teléfono es obligatorio.';
        } elseif (!preg_match('/^[0-9+\s\-()]{6,20}$/', $telefono)) {
            $errores['telefono'] = 'El teléfono no tiene un formato válido.';
        }

        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100)) {
            $errores['email'] = 'El correo electrónico no es válido.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return ['nombre' => $nombre, 'telefono' => $telefono, 'email' => $email === '' ? null : $email];
    }

    /** @return array{id: int, nombre: string, telefono: string, email: ?string} */
    private static function formato(array $fila): array
    {
        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'telefono' => (string) $fila['telefono'],
            'email' => $fila['email'] !== null && $fila['email'] !== '' ? (string) $fila['email'] : null,
        ];
    }
}
