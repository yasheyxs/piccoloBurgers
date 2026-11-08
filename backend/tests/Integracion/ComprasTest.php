<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

/** Materias primas y compras: el stock tiene que acompañar cada compra que se registra o se borra. */
final class ComprasTest extends BaseDePruebas
{
    private ComprasController $compras;
    private MateriasPrimasController $materias;
    private int $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $proveedores = new ProveedoresRepositorio($this->conexion);
        $repoMaterias = new MateriasPrimasRepositorio($this->conexion);
        $this->compras = new ComprasController(new ComprasRepositorio($this->conexion), $proveedores, $repoMaterias);
        $this->materias = new MateriasPrimasController($repoMaterias, $proveedores);
        $this->proveedor = $this->insertar('tbl_proveedores', ['nombre' => 'Prueba proveedor', 'telefono' => '3511234567']);
    }

    private function nuevaMateria(float $stock): int
    {
        $materia = $this->materias->crear(['nombre' => 'Prueba insumo', 'unidad_medida' => 'kg', 'cantidad' => $stock, 'proveedor_id' => $this->proveedor]);
        $this->anotar('tbl_materias_primas', $materia['id']);

        return $materia['id'];
    }

    private function stock(int $materiaId): float
    {
        return $this->materias->obtener($materiaId)['cantidad'];
    }

    public function testRegistrarUnaCompraSumaAlStockConDecimales(): void
    {
        $materia = $this->nuevaMateria(1);

        $compra = $this->compras->registrar([
            'fecha' => date('Y-m-d'),
            'proveedor_id' => $this->proveedor,
            'items' => [['materia_prima_id' => $materia, 'cantidad' => '2,5', 'precio_unitario' => '100.40']],
        ]);
        $this->anotar('tbl_compras', $compra['id']);

        $this->assertSame(3.5, $this->stock($materia));
        $this->assertSame(2.5, $compra['items'][0]['cantidad'], 'la cantidad se guarda con decimales');
        $this->assertSame(251.0, $compra['total']);
    }

    public function testBorrarUnaCompraDescuentaLoQueHabiaSumado(): void
    {
        $materia = $this->nuevaMateria(0);
        $compra = $this->compras->registrar([
            'fecha' => date('Y-m-d'), 'proveedor_id' => $this->proveedor,
            'items' => [['materia_prima_id' => $materia, 'cantidad' => 4, 'precio_unitario' => 10]],
        ]);
        $this->assertSame(4.0, $this->stock($materia));

        $this->compras->eliminar($compra['id']);

        $this->assertSame(0.0, $this->stock($materia));
        $this->expectException(ApiException::class);
        $this->compras->obtener($compra['id']);
    }

    public function testNoSeBorraUnaCompraCuyaMercaderiaYaSeUso(): void
    {
        $materia = $this->nuevaMateria(0);
        $compra = $this->compras->registrar([
            'fecha' => date('Y-m-d'), 'proveedor_id' => $this->proveedor,
            'items' => [['materia_prima_id' => $materia, 'cantidad' => 4, 'precio_unitario' => 10]],
        ]);
        $this->anotar('tbl_compras', $compra['id']);
        $this->conexion->exec("UPDATE tbl_materias_primas SET cantidad = 1 WHERE ID = $materia"); // se usaron 3

        try {
            $this->compras->eliminar($compra['id']);
            $this->fail('No debería dejar el stock negativo');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }
        $this->assertSame(1.0, $this->stock($materia), 'el stock no cambia');
    }

    public function testUnaCompraSinMateriasPrimasValidasSeRechaza(): void
    {
        try {
            $this->compras->registrar(['fecha' => '2026-02-30', 'proveedor_id' => 999999, 'items' => []]);
            $this->fail('Debería rechazar la compra');
        } catch (ApiException $error) {
            $this->assertSame(422, $error->status());
            $this->assertSame(['fecha', 'proveedor_id', 'items'], array_keys($error->errores()));
        }

        try {
            $this->compras->registrar([
                'fecha' => date('Y-m-d'), 'proveedor_id' => $this->proveedor,
                'items' => [['materia_prima_id' => 999999, 'cantidad' => 0, 'precio_unitario' => 'x']],
            ]);
            $this->fail('Debería rechazar los ítems');
        } catch (ApiException $error) {
            $this->assertSame(['items.0.materia_prima_id', 'items.0.cantidad', 'items.0.precio_unitario'], array_keys($error->errores()));
        }
    }

    public function testNoSeBorraUnaMateriaPrimaEnUso(): void
    {
        $materia = $this->nuevaMateria(10);
        $compra = $this->compras->registrar([
            'fecha' => date('Y-m-d'), 'proveedor_id' => $this->proveedor,
            'items' => [['materia_prima_id' => $materia, 'cantidad' => 1, 'precio_unitario' => 10]],
        ]);
        $this->anotar('tbl_compras', $compra['id']);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(409);
        $this->materias->eliminar($materia);
    }

    public function testInsumosCriticos(): void
    {
        $bajo = $this->nuevaMateria(0.5);
        $this->materias->actualizar($bajo, ['nombre' => 'Prueba insumo', 'unidad_medida' => 'kg', 'cantidad' => 2, 'stock_minimo' => 3]);

        $criticos = array_column($this->materias->listar(true), 'estado_stock', 'id');
        $this->assertSame('bajo', $criticos[$bajo] ?? null);
    }
}
