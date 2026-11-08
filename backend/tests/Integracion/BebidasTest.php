<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\BebidasController;
use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\BebidasRepositorio;
use Piccolo\Repositories\PoolRepositorio;

final class BebidasTest extends BaseDePruebas
{
    private BebidasController $bebidas;
    /** @var int[] jornadas abiertas antes de la prueba: se cierran para probar sin interferencias y se reabren al final */
    private array $abiertas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bebidas = new BebidasController(new BebidasRepositorio($this->conexion), new PoolController(new PoolRepositorio($this->conexion), $this->conexion), $this->conexion);
        $this->abiertas = array_map('intval', $this->conexion->query("SELECT id FROM pool_jornadas WHERE estado = 'abierta'")->fetchAll(\PDO::FETCH_COLUMN));
        $this->conexion->exec("UPDATE pool_jornadas SET estado = 'cerrada' WHERE estado = 'abierta'");
    }

    protected function tearDown(): void
    {
        if (isset($this->conexion)) {
            // Primero las ventas de las bebidas de prueba (la base no deja borrar una bebida con ventas)
            $this->conexion->exec("DELETE v FROM bebida_ventas v JOIN bebidas b ON b.id = v.bebida_id WHERE b.nombre LIKE 'Prueba %'");
            $this->conexion->exec("DELETE FROM bebidas WHERE nombre LIKE 'Prueba %'");
            foreach ($this->abiertas as $id) {
                $this->conexion->exec("UPDATE pool_jornadas SET estado = 'abierta' WHERE id = $id");
            }
        }
        parent::tearDown();
    }

    private function abrir(): void
    {
        $this->bebidas->abrirJornada();
        $this->anotar('pool_jornadas', (int) $this->bebidas->estado()['jornada']['id']);
    }

    private function bebida(array $cambios = []): array
    {
        return $this->bebidas->guardarBebida($cambios + [
            'nombre' => 'Prueba IPA', 'valor_jarra' => '2500', 'valor_promo' => '6000', 'jarras_por_promo' => 3, 'activa' => '1',
        ]);
    }

    public function testElCatalogoValidaYNoRepiteNombres(): void
    {
        $bebida = $this->bebida(['valor_jarra' => '2500,50']);
        $this->assertSame([2500.5, true], [$bebida['valor_jarra'], $bebida['activa']]);

        try {
            $this->bebida(['valor_promo' => '0', 'jarras_por_promo' => 100]);
            $this->fail('Debería rechazar los datos');
        } catch (ApiException $error) {
            $this->assertSame(['nombre', 'valor_promo', 'jarras_por_promo'], array_keys($error->errores()));
        }

        $editada = $this->bebidas->guardarBebida(['nombre' => 'Prueba IPA', 'valor_jarra' => 3000, 'valor_promo' => 7000, 'jarras_por_promo' => 2], $bebida['id']);
        $this->assertSame([3000.0, false], [$editada['valor_jarra'], $editada['activa']], 'sin "activa" queda inactiva');
    }

    public function testUnaPromoSeEntregaJarraPorJarra(): void
    {
        $this->abrir();
        $bebida = $this->bebida();
        $this->bebidas->vender(['bebida_id' => $bebida['id'], 'persona' => 'Ana', 'tipo' => 'promo', 'cantidad' => 1]);

        $venta = $this->bebidas->estado(true)['ventas'][0];
        $this->assertSame([3, 6000.0, 'Prueba IPA'], [$venta['pendientes'], $venta['valor_unitario'], $venta['bebida_nombre']]);

        $this->bebidas->entregar($venta['id']);
        $this->bebidas->entregar($venta['id']);
        $this->assertSame(1, $this->bebidas->estado(true)['ventas'][0]['pendientes']);
        $this->bebidas->revertir($venta['id']);
        $this->assertSame(2, $this->bebidas->estado(true)['ventas'][0]['pendientes']);
        $this->bebidas->entregar($venta['id']);
        $this->bebidas->entregar($venta['id']);
        $this->assertSame([], $this->bebidas->estado(true)['ventas'], 'entregada completa sale de la lista');

        $this->expectException(ApiException::class);
        $this->bebidas->entregar($venta['id']);
    }

    public function testLaVentaGuardaElPrecioDelMomento(): void
    {
        $this->abrir();
        $bebida = $this->bebida();
        $this->bebidas->vender(['bebida_id' => $bebida['id'], 'persona' => 'Beto', 'tipo' => 'jarra', 'cantidad' => 2]);
        $this->bebidas->guardarBebida(['nombre' => 'Prueba IPA', 'valor_jarra' => 9999, 'valor_promo' => 9999, 'jarras_por_promo' => 3, 'activa' => true], $bebida['id']);

        $stats = $this->bebidas->estado(true)['stats'];
        $this->assertSame(5000.0, $stats['montoVendido'], 'cambiar el precio no modifica lo ya vendido');
        $this->assertNull($this->bebidas->estado()['stats']['montoVendido'], 'sin confirmar no se ven los montos');
    }

    public function testValidaLaVenta(): void
    {
        try {
            $this->bebidas->vender(['bebida_id' => 1, 'persona' => 'Ana', 'tipo' => 'jarra', 'cantidad' => 1]);
            $this->fail('Sin jornada no se vende');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }

        $this->abrir();
        $inactiva = $this->bebida(['nombre' => 'Prueba inactiva', 'activa' => '0']);
        try {
            $this->bebidas->vender(['bebida_id' => $inactiva['id'], 'persona' => '', 'tipo' => 'balde', 'cantidad' => 0]);
            $this->fail('Debería rechazar la venta');
        } catch (ApiException $error) {
            $this->assertSame(['bebida_id', 'persona', 'tipo', 'cantidad'], array_keys($error->errores()));
        }
    }
}
