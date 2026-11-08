<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Http\Api\Formato;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;

/**
 * Reglas de la sección Menú: los productos que se venden y su receta (qué materias primas
 * descuenta cada unidad vendida). La usan las pantallas del panel y la API (/api/v1/admin/menu).
 */
final class MenuController
{
    /** Las fotos del menú se ven chicas en el sitio: con este ancho alcanza y pesan poco */
    public const FOTO_ANCHO_MAXIMO = 1000;

    public function __construct(
        private MenuRepositorio $repositorio,
        private MateriasPrimasRepositorio $materiasPrimas,
        private string $carpetaFotos,
    ) {
    }

    /** @return string[] */
    public static function categorias(): array
    {
        return \menuCategoriasDisponibles();
    }

    /** @return array<int, array<string, mixed>> */
    public function listar(): array
    {
        return array_map([self::class, 'formato'], $this->repositorio->listar());
    }

    /** @return array<string, mixed> el producto con su receta */
    public function obtener(int $id): array
    {
        $producto = $this->repositorio->buscar($id);
        if ($producto === null) {
            throw ApiException::noEncontrado('El producto no existe o ya fue eliminado.');
        }

        return self::formato($producto) + ['receta' => $this->receta($id)];
    }

    /**
     * @param array|null $foto un elemento de $_FILES, si se eligió una foto
     * @return array<string, mixed>
     */
    public function crear(array $datos, ?array $foto = null): array
    {
        $valido = $this->validar($datos);
        $valido['foto'] = ImagenSubida::fueEnviada($foto) ? ImagenSubida::guardar($foto, $this->carpetaFotos, 'producto', 'foto', 0, 0, self::FOTO_ANCHO_MAXIMO) : '';

        return $this->obtener($this->repositorio->crear($valido));
    }

    /**
     * Si llega una foto nueva, reemplaza a la anterior (que se borra del disco).
     *
     * @return array<string, mixed>
     */
    public function actualizar(int $id, array $datos, ?array $foto = null): array
    {
        $actual = $this->obtener($id);
        $valido = $this->validar($datos);
        $valido['foto'] = (string) $actual['foto'];

        if (ImagenSubida::fueEnviada($foto)) {
            $valido['foto'] = ImagenSubida::guardar($foto, $this->carpetaFotos, 'producto', 'foto', 0, 0, self::FOTO_ANCHO_MAXIMO);
        }

        $this->repositorio->actualizar($id, $valido);
        if ($valido['foto'] !== (string) $actual['foto']) {
            ImagenSubida::borrar($this->carpetaFotos, (string) $actual['foto']);
        }

        return $this->obtener($id);
    }

    /**
     * Cambia solo la foto (la API la recibe aparte, porque el resto de los datos viaja en JSON).
     *
     * @return array<string, mixed>
     */
    public function cambiarFoto(int $id, ?array $foto): array
    {
        if (!ImagenSubida::fueEnviada($foto)) {
            throw ApiException::validacion(['foto' => 'Elegí una imagen (campo "foto" del formulario).']);
        }
        $actual = $this->obtener($id);
        $datos = ['nombre' => $actual['nombre'], 'ingredientes' => $actual['ingredientes'], 'precio' => $actual['precio'], 'categoria' => $actual['categoria'], 'visible' => $actual['visible']];

        return $this->actualizar($id, $datos, $foto);
    }

    /**
     * Un producto que ya se vendió no se borra: con él se perdería el detalle de esos pedidos
     * en el historial y las estadísticas. En ese caso conviene ocultarlo del menú.
     */
    public function eliminar(int $id): void
    {
        $producto = $this->obtener($id);

        $ventas = $this->repositorio->vecesVendido($id);
        if ($ventas > 0) {
            throw new ApiException(
                "No se puede eliminar porque figura en $ventas pedido" . ($ventas === 1 ? '' : 's') . ' y se perdería ese historial. '
                . 'Si ya no se vende, ocultalo del menú desde "Editar".',
                409
            );
        }

        $this->repositorio->eliminar($id);
        ImagenSubida::borrar($this->carpetaFotos, (string) $producto['foto']);
    }

    /** @return array<int, array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, stock: float, alcanza: bool}> */
    public function receta(int $id): array
    {
        return array_map(static function (array $fila): array {
            $cantidad = (float) $fila['cantidad'];
            $stock = (float) $fila['stock'];
            return [
                'materia_prima_id' => (int) $fila['materia_prima_id'],
                'nombre' => (string) $fila['nombre'],
                'unidad_medida' => (string) $fila['unidad_medida'],
                'cantidad' => $cantidad,
                'stock' => $stock,
                'alcanza' => $stock >= $cantidad, // si alcanza para vender al menos una unidad
            ];
        }, $this->repositorio->receta($id));
    }

    /**
     * Reemplaza la receta completa.
     *
     * @param array<int, array{materia_prima_id?: mixed, cantidad?: mixed}> $items
     * @return array<int, array<string, mixed>> la receta guardada
     */
    public function guardarReceta(int $id, array $items): array
    {
        $this->obtener($id);

        $items = array_values(array_filter($items, 'is_array'));
        $existentes = $this->materiasPrimas->existentes(array_map(static fn(array $f): int => Datos::id($f['materia_prima_id'] ?? 0), $items));
        $cantidades = [];
        $errores = [];

        foreach ($items as $i => $item) {
            $materiaId = Datos::id($item['materia_prima_id'] ?? 0);
            $cantidad = Datos::decimal($item['cantidad'] ?? '');

            if (!in_array($materiaId, $existentes, true)) {
                $errores["items.$i.materia_prima_id"] = 'Elegí una materia prima.';
            } elseif (isset($cantidades[$materiaId])) {
                $errores["items.$i.materia_prima_id"] = 'Esta materia prima ya está en la receta.';
            }
            if ($cantidad === null || $cantidad <= 0 || $cantidad >= 100000000) {
                $errores["items.$i.cantidad"] = 'La cantidad tiene que ser mayor a 0.';
            }

            $cantidades[$materiaId] = round((float) $cantidad, 2);
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->repositorio->guardarReceta($id, $cantidades);

        return $this->receta($id);
    }

    /**
     * @return array{nombre: string, ingredientes: string, precio: float, categoria: string, visible: bool}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos): array
    {
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $ingredientes = Datos::texto($datos['ingredientes'] ?? '');
        $precio = Datos::decimal($datos['precio'] ?? '');
        $categoria = Datos::texto($datos['categoria'] ?? '');
        $visible = filter_var($datos['visible'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 255) {
            $errores['nombre'] = 'El nombre es demasiado largo.';
        }

        if ($ingredientes === '') {
            $errores['ingredientes'] = 'Contá qué lleva el producto (se muestra en el menú del sitio).';
        } elseif (mb_strlen($ingredientes) > 255) {
            $errores['ingredientes'] = 'La descripción puede tener hasta 255 caracteres.';
        }

        if ($precio === null || $precio < 0 || $precio >= 100000000) {
            $errores['precio'] = 'Ingresá un precio válido.';
        }

        if (!in_array($categoria, self::categorias(), true)) {
            $errores['categoria'] = 'Elegí una categoría.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return [
            'nombre' => $nombre,
            'ingredientes' => $ingredientes,
            'precio' => round((float) $precio, 2),
            'categoria' => $categoria,
            'visible' => $visible,
        ];
    }

    /** @return array<string, mixed> */
    private static function formato(array $fila): array
    {
        $foto = trim((string) $fila['foto']);

        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'ingredientes' => (string) $fila['ingredientes'],
            'precio' => (float) $fila['precio'],
            'categoria' => (string) $fila['categoria'],
            'visible' => (bool) $fila['visible_en_menu'],
            'foto' => $foto !== '' ? $foto : null,
            'imagen' => Formato::urlPublica($foto !== '' ? 'img/menu/' . rawurlencode($foto) : 'img/placeholders/producto.svg'),
            'insumos' => (int) $fila['insumos'],
        ];
    }
}
