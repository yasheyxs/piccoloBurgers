<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ProveedoresRepositorio;

final class ProveedoresTest extends BaseDePruebas
{
    private ProveedoresController $proveedores;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proveedores = new ProveedoresController(new ProveedoresRepositorio($this->conexion));
    }

    public function testCreaEditaYBorraUnProveedor(): void
    {
        $creado = $this->proveedores->crear(['nombre' => 'Prueba S.R.L.', 'telefono' => '+54 351 123-4567', 'email' => '']);
        $this->anotar('tbl_proveedores', $creado['id']);
        $this->assertSame('Prueba S.R.L.', $creado['nombre']);
        $this->assertNull($creado['email'], 'un email vacío se guarda como "sin email"');

        $editado = $this->proveedores->actualizar($creado['id'], ['nombre' => 'Prueba Editada', 'telefono' => '3511234567', 'email' => 'compras@prueba.test']);
        $this->assertSame('compras@prueba.test', $editado['email']);

        $this->proveedores->eliminar($creado['id']);
        $this->expectException(ApiException::class);
        $this->proveedores->obtener($creado['id']);
    }

    public function testInformaCadaCampoInvalido(): void
    {
        try {
            $this->proveedores->crear(['nombre' => '<b>', 'telefono' => '12', 'email' => 'mal']);
            $this->fail('Debería rechazar los datos');
        } catch (ApiException $error) {
            $this->assertSame(422, $error->status());
            $this->assertSame(['nombre', 'telefono', 'email'], array_keys($error->errores()));
        }
    }

    public function testNoBorraUnProveedorConMateriasPrimas(): void
    {
        $proveedor = $this->insertar('tbl_proveedores', ['nombre' => 'Prueba proveedor', 'telefono' => '3511234567']);
        $this->insertar('tbl_materias_primas', [
            'nombre' => 'Prueba insumo', 'unidad_medida' => 'unidad', 'cantidad' => 1,
            'proveedor_id' => $proveedor, 'unidades_por_pack' => 1, 'stock_minimo' => 0,
        ]);

        try {
            $this->proveedores->eliminar($proveedor);
            $this->fail('No debería borrar un proveedor en uso');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
            $this->assertStringContainsString('1 materia prima', $error->getMessage());
        }

        $this->assertSame('Prueba proveedor', $this->proveedores->obtener($proveedor)['nombre']);
    }

    public function testUnProveedorInexistenteDa404(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);
        $this->proveedores->actualizar(999999, ['nombre' => 'X', 'telefono' => '3511234567']);
    }
}
