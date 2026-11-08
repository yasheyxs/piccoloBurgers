<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

/**
 * Reglas de la sección Compras. Registrar una compra suma lo comprado al stock; borrarla lo
 * descuenta. La usan las pantallas del panel y la API (/api/v1/admin/compras).
 */
final class ComprasController
{
    public function __construct(
        private ComprasRepositorio $repositorio,
        private ProveedoresRepositorio $proveedores,
        private MateriasPrimasRepositorio $materiasPrimas,
    ) {
    }

    /** @return array<int, array{id: int, fecha: string, proveedor_id: int, proveedor: ?string, telefono: ?string, total: float}> */
    public function listar(): array
    {
        return array_map(static fn(array $fila): array => self::formatoCompra($fila) + ['total' => round((float) $fila['total'], 2)], $this->repositorio->listar());
    }

    /** @return array<string, mixed> la compra con sus ítems y el total */
    public function obtener(int $id): array
    {
        $compra = $this->repositorio->buscar($id);
        if ($compra === null) {
            throw ApiException::noEncontrado('La compra no existe o ya fue eliminada.');
        }

        $items = array_map(static function (array $fila): array {
            $cantidad = (float) $fila['cantidad'];
            $precio = (float) $fila['precio_unitario'];
            return [
                'materia_prima_id' => (int) $fila['materia_prima_id'],
                'materia_prima' => $fila['materia_prima'] !== null ? (string) $fila['materia_prima'] : null,
                'unidad_medida' => $fila['unidad_medida'] !== null ? (string) $fila['unidad_medida'] : null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => round($cantidad * $precio, 2),
            ];
        }, $this->repositorio->detalle($id));

        return self::formatoCompra($compra) + [
            'items' => $items,
            'total' => round(array_sum(array_column($items, 'subtotal')), 2),
        ];
    }

    /**
     * @param array{fecha?: string, proveedor_id?: int|string, items?: array<int, array{materia_prima_id?: int|string, cantidad?: mixed, precio_unitario?: mixed}>} $datos
     * @return array<string, mixed> la compra registrada
     */
    public function registrar(array $datos): array
    {
        $errores = [];

        $fecha = Datos::fecha($datos['fecha'] ?? '');
        if ($fecha === null) {
            $errores['fecha'] = 'Ingresá una fecha válida.';
        } elseif ($fecha > date('Y-m-d')) {
            $errores['fecha'] = 'La fecha no puede ser posterior a hoy.';
        }

        $proveedorId = Datos::id($datos['proveedor_id'] ?? '');
        if ($proveedorId === 0 || $this->proveedores->buscar($proveedorId) === null) {
            $errores['proveedor_id'] = 'Elegí un proveedor.';
        }

        $items = [];
        $filas = is_array($datos['items'] ?? null) ? array_values($datos['items']) : [];
        $existentes = $this->materiasPrimas->existentes(array_map(static fn($f) => is_array($f) ? Datos::id($f['materia_prima_id'] ?? 0) : 0, $filas));
        foreach ($filas as $i => $fila) {
            $fila = is_array($fila) ? $fila : [];
            $materiaId = Datos::id($fila['materia_prima_id'] ?? 0);
            $cantidad = Datos::decimal($fila['cantidad'] ?? '');
            $precio = Datos::decimal($fila['precio_unitario'] ?? '');

            if (!in_array($materiaId, $existentes, true)) {
                $errores["items.$i.materia_prima_id"] = 'Elegí una materia prima.';
            }
            if ($cantidad === null || $cantidad <= 0 || $cantidad >= 100000000) {
                $errores["items.$i.cantidad"] = 'La cantidad tiene que ser mayor a 0.';
            }
            if ($precio === null || $precio <= 0 || $precio >= 100000000) {
                $errores["items.$i.precio_unitario"] = 'El precio tiene que ser mayor a 0.';
            }

            $items[] = ['materia_prima_id' => $materiaId, 'cantidad' => round((float) $cantidad, 2), 'precio_unitario' => round((float) $precio, 2)];
        }
        if (!$filas) {
            $errores['items'] = 'Agregá al menos una materia prima a la compra.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return $this->obtener($this->repositorio->registrar((string) $fecha, $proveedorId, $items));
    }

    /**
     * Borra la compra y descuenta del stock lo que había sumado. Si parte de esa mercadería
     * ya se usó (el stock actual es menor a lo comprado), no se puede borrar.
     */
    public function eliminar(int $id): void
    {
        $this->obtener($id);

        $faltantes = array_filter($this->repositorio->stockParaDeshacer($id), static fn(array $f): bool => $f['stock'] + 1e-9 < $f['comprado']);
        if ($faltantes) {
            $nombres = implode(', ', array_column($faltantes, 'materia_prima'));
            throw new ApiException("No se puede eliminar: parte de lo comprado ya se usó ($nombres) y el stock quedaría negativo.", 409);
        }

        $this->repositorio->eliminar($id);
    }

    /** @return array<string, mixed> gasto del mes y del año, y los proveedores y materias primas con más compras */
    public function resumen(): array
    {
        return $this->repositorio->resumen();
    }

    /** @return array{id: int, fecha: string, proveedor_id: int, proveedor: ?string, telefono: ?string} */
    private static function formatoCompra(array $fila): array
    {
        return [
            'id' => (int) $fila['ID'],
            'fecha' => (string) $fila['fecha'],
            'proveedor_id' => (int) $fila['proveedor_id'],
            'proveedor' => $fila['proveedor'] !== null ? (string) $fila['proveedor'] : null,
            'telefono' => $fila['telefono'] !== null ? (string) $fila['telefono'] : null,
        ];
    }
}
