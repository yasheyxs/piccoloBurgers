<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PremiosRepositorio;

/**
 * Reglas de la sección Premios (lo que los clientes canjean con sus puntos).
 * La usan las pantallas del panel y la API (/api/v1/admin/premios).
 */
final class PremiosController
{
    private const NO_EXISTE = 'El premio no existe o ya fue eliminado.';

    public function __construct(private PremiosRepositorio $repositorio)
    {
    }

    /** @return array<int, array{id: int, nombre: string, descripcion: ?string, costo_puntos: int, creado: ?string}> */
    public function listar(): array
    {
        return array_map([self::class, 'formato'], $this->repositorio->listar());
    }

    /** @return array{id: int, nombre: string, descripcion: ?string, costo_puntos: int, creado: ?string} */
    public function obtener(int $id): array
    {
        $premio = $this->repositorio->buscar($id);
        if ($premio === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return self::formato($premio);
    }

    /** @return array{id: int, nombre: string, descripcion: ?string, costo_puntos: int, creado: ?string} */
    public function crear(array $datos): array
    {
        return $this->obtener($this->repositorio->crear($this->validar($datos)));
    }

    /** @return array{id: int, nombre: string, descripcion: ?string, costo_puntos: int, creado: ?string} */
    public function actualizar(int $id, array $datos): array
    {
        $this->obtener($id);
        $this->repositorio->actualizar($id, $this->validar($datos));

        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $this->obtener($id);
        $this->repositorio->eliminar($id);
    }

    /**
     * @return array{nombre: string, descripcion: ?string, costo_puntos: int}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos): array
    {
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $descripcion = Datos::texto($datos['descripcion'] ?? '');
        $costo = filter_var(Datos::texto($datos['costo_puntos'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre puede tener hasta 100 caracteres.';
        }
        if (mb_strlen($descripcion) > 1000) {
            $errores['descripcion'] = 'La descripción puede tener hasta 1000 caracteres.';
        }
        if ($costo === false) {
            $errores['costo_puntos'] = 'El costo tiene que ser un número entero de puntos, 1 o más.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return ['nombre' => $nombre, 'descripcion' => $descripcion !== '' ? $descripcion : null, 'costo_puntos' => (int) $costo];
    }

    /** @return array{id: int, nombre: string, descripcion: ?string, costo_puntos: int, creado: ?string} */
    private static function formato(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'descripcion' => $fila['descripcion'] !== null && $fila['descripcion'] !== '' ? (string) $fila['descripcion'] : null,
            'costo_puntos' => (int) $fila['costo_puntos'],
            'creado' => $fila['created_at'] !== null ? (string) $fila['created_at'] : null,
        ];
    }
}
