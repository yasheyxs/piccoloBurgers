<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;

final class MenuTest extends BaseDePruebas
{
    private MenuController $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->menu = new MenuController(new MenuRepositorio($this->conexion), new MateriasPrimasRepositorio($this->conexion), sys_get_temp_dir());
    }

    private function nuevoProducto(array $cambios = []): array
    {
        $producto = $this->menu->crear($cambios + ['nombre' => 'Prueba producto', 'ingredientes' => 'Pan y carne', 'precio' => '2500,50', 'categoria' => 'Hamburguesas']);
        $this->anotar('tbl_menu', $producto['id']);

        return $producto;
    }

    public function testCreaUnProductoConPrecioDecimal(): void
    {
        $producto = $this->nuevoProducto();

        $this->assertSame(2500.5, $producto['precio']);
        $this->assertTrue($producto['visible'], 'por defecto se muestra en el menú');
        $this->assertSame([], $producto['receta']);
        $this->assertStringEndsWith('img/placeholders/producto.svg', $producto['imagen'], 'sin foto usa la imagen de reemplazo');
    }

    public function testValidaCadaCampo(): void
    {
        try {
            $this->menu->crear(['nombre' => '', 'ingredientes' => '', 'precio' => '-1', 'categoria' => 'Postres']);
            $this->fail('Debería rechazar los datos');
        } catch (ApiException $error) {
            $this->assertSame(['nombre', 'ingredientes', 'precio', 'categoria'], array_keys($error->errores()));
        }
    }

    public function testUnProductoOcultoNoSeVendeEnElSitio(): void
    {
        $producto = $this->nuevoProducto(['visible' => '0']);

        $this->assertFalse($producto['visible']);
        $this->assertArrayNotHasKey($producto['id'], obtenerProductosMenuPorId($this->conexion, [$producto['id']]));
    }

    public function testLaRecetaSeReemplazaCompleta(): void
    {
        $producto = $this->nuevoProducto();
        $a = $this->insertar('tbl_materias_primas', ['nombre' => 'Prueba A', 'unidad_medida' => 'kg', 'cantidad' => 10, 'proveedor_id' => 0]);
        $b = $this->insertar('tbl_materias_primas', ['nombre' => 'Prueba B', 'unidad_medida' => 'kg', 'cantidad' => 0.5, 'proveedor_id' => 0]);

        $receta = $this->menu->guardarReceta($producto['id'], [
            ['materia_prima_id' => $a, 'cantidad' => '0,25'],
            ['materia_prima_id' => $b, 'cantidad' => 1],
        ]);
        $this->assertCount(2, $receta);
        $porId = array_column($receta, null, 'materia_prima_id');
        $this->assertSame(0.25, $porId[$a]['cantidad']);
        $this->assertFalse($porId[$b]['alcanza'], 'con 0,5 de stock no alcanza para una unidad que usa 1');

        $receta = $this->menu->guardarReceta($producto['id'], [['materia_prima_id' => $b, 'cantidad' => 0.1]]);
        $this->assertSame([$b], array_column($receta, 'materia_prima_id'), 'lo que no se envía se quita de la receta');
    }

    public function testNoAceptaInsumosRepetidosNiCantidadesEnCero(): void
    {
        $producto = $this->nuevoProducto();
        $a = $this->insertar('tbl_materias_primas', ['nombre' => 'Prueba A', 'unidad_medida' => 'kg', 'cantidad' => 10, 'proveedor_id' => 0]);

        try {
            $this->menu->guardarReceta($producto['id'], [
                ['materia_prima_id' => $a, 'cantidad' => 1],
                ['materia_prima_id' => $a, 'cantidad' => 0],
            ]);
            $this->fail('Debería rechazar la receta');
        } catch (ApiException $error) {
            $this->assertSame(['items.1.materia_prima_id', 'items.1.cantidad'], array_keys($error->errores()));
        }
    }

    public function testNoSeBorraUnProductoQueYaSeVendio(): void
    {
        $producto = $this->nuevoProducto();
        $pedido = $this->insertar('tbl_pedidos', [
            'nombre' => 'Prueba', 'telefono' => '+543511234567', 'total' => 100, 'metodo_pago' => 'Efectivo',
            'tipo_entrega' => 'Retiro', 'estado' => 'Listo', 'esta_pago' => 'Si',
        ]);
        $this->conexion->prepare('INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)')
            ->execute([$pedido, $producto['id'], 'Prueba producto', 100, 1]);

        try {
            $this->menu->eliminar($producto['id']);
            $this->fail('No debería borrar un producto con ventas');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }
        $this->assertSame(1, (int) $this->conexion->query("SELECT COUNT(*) FROM tbl_pedidos_detalle WHERE pedido_id = $pedido")->fetchColumn(), 'el historial queda intacto');
    }
}
