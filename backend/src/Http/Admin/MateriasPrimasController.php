<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

/**
 * Reglas de la sección Materias primas (insumos y su stock). La usan las pantallas del panel
 * y la API (/api/v1/admin/materias-primas).
 */
final class MateriasPrimasController
{
    public function __construct(
        private MateriasPrimasRepositorio $repositorio,
        private ProveedoresRepositorio $proveedores,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listar(bool $soloCriticos = false): array
    {
        return array_map([self::class, 'formato'], $this->repositorio->listar($soloCriticos));
    }

    /** @return array<string, mixed> */
    public function obtener(int $id): array
    {
        $materia = $this->repositorio->buscar($id);
        if ($materia === null) {
            throw ApiException::noEncontrado('La materia prima no existe o ya fue eliminada.');
        }

        return self::formato($materia);
    }

    /** @return array<string, mixed> */
    public function crear(array $datos): array
    {
        return $this->obtener($this->repositorio->crear($this->validar($datos)));
    }

    /** @return array<string, mixed> */
    public function actualizar(int $id, array $datos): array
    {
        $this->obtener($id);
        $this->repositorio->actualizar($id, $this->validar($datos));

        return $this->obtener($id);
    }

    /** No se borra una materia prima que usa algún producto del menú o que figura en compras. */
    public function eliminar(int $id): void
    {
        $this->obtener($id);

        $usos = $this->repositorio->usos($id);
        $detalle = [];
        if ($usos['productos'] > 0) {
            $detalle[] = $usos['productos'] . ' producto' . ($usos['productos'] === 1 ? '' : 's') . ' del menú';
        }
        if ($usos['compras'] > 0) {
            $detalle[] = $usos['compras'] . ' compra' . ($usos['compras'] === 1 ? '' : 's');
        }
        if ($detalle) {
            throw new ApiException('No se puede eliminar porque la usan ' . implode(' y ', $detalle) . '.', 409);
        }

        $this->repositorio->eliminar($id);
    }

    /**
     * @return array{nombre: string, unidad_medida: string, cantidad: float, stock_minimo: ?float, proveedor_id: int}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos): array
    {
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $unidad = Datos::texto($datos['unidad_medida'] ?? '');
        $cantidad = Datos::decimal($datos['cantidad'] ?? '');
        $textoMinimo = Datos::texto($datos['stock_minimo'] ?? '');
        $stockMinimo = Datos::decimal($textoMinimo);
        $proveedorId = Datos::id($datos['proveedor_id'] ?? 0);
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 50) {
            $errores['nombre'] = 'El nombre puede tener hasta 50 caracteres.';
        }

        if ($unidad === '') {
            $errores['unidad_medida'] = 'La unidad de medida es obligatoria (por ejemplo: kg, litros, unidades).';
        } elseif (mb_strlen($unidad) > 50) {
            $errores['unidad_medida'] = 'La unidad puede tener hasta 50 caracteres.';
        }

        if ($cantidad === null || $cantidad < 0 || $cantidad >= 100000000) {
            $errores['cantidad'] = 'Ingresá una cantidad en stock válida (0 o más).';
        }

        if ($textoMinimo !== '' && ($stockMinimo === null || $stockMinimo < 0 || $stockMinimo >= 100000000)) {
            $errores['stock_minimo'] = 'El stock mínimo tiene que ser un número de 0 o más.';
        }

        if ($proveedorId > 0 && $this->proveedores->buscar($proveedorId) === null) {
            $errores['proveedor_id'] = 'El proveedor elegido no existe.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return [
            'nombre' => $nombre,
            'unidad_medida' => $unidad,
            'cantidad' => round((float) $cantidad, 2),
            'stock_minimo' => $stockMinimo === null ? null : round($stockMinimo, 2),
            'proveedor_id' => $proveedorId, // 0 = sin proveedor
        ];
    }

    /**
     * Estado del stock: sin stock, bajo (en el mínimo o por debajo; sin mínimo cargado se toma 1) o normal.
     */
    public static function estadoStock(float $cantidad, ?float $stockMinimo): string
    {
        if ($cantidad <= 0) {
            return 'sin_stock';
        }

        return $cantidad <= ($stockMinimo ?? 1) ? 'bajo' : 'ok';
    }

    /** @return array<string, mixed> */
    private static function formato(array $fila): array
    {
        $cantidad = (float) $fila['cantidad'];
        $minimo = $fila['stock_minimo'] !== null ? (float) $fila['stock_minimo'] : null;
        $proveedorId = (int) $fila['proveedor_id'];

        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'unidad_medida' => (string) $fila['unidad_medida'],
            'cantidad' => $cantidad,
            'stock_minimo' => $minimo,
            'estado_stock' => self::estadoStock($cantidad, $minimo),
            'proveedor_id' => $proveedorId > 0 ? $proveedorId : null,
            'proveedor' => $fila['proveedor'] !== null ? (string) $fila['proveedor'] : null,
        ];
    }
}
