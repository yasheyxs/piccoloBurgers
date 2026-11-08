<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\VentasRepositorio;

/**
 * Las ventas se prueban en un año sin otros datos (2001), así los totales no dependen
 * de los pedidos de ejemplo que pueda tener la base.
 */
final class VentasTest extends BaseDePruebas
{
    private VentasController $ventas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ventas = new VentasController(new VentasRepositorio($this->conexion), $this->conexion);
    }

    /** @param array<int, array{0: string, 1: int, 2: float}> $items nombre, cantidad, precio */
    private function pedido(string $fecha, float $total, array $items, string $estado = 'Entregado', string $metodo = 'Efectivo', string $entrega = 'Retiro'): void
    {
        $id = $this->insertar('tbl_pedidos', [
            'nombre' => 'Prueba', 'telefono' => '+543511234567', 'total' => $total, 'metodo_pago' => $metodo,
            'tipo_entrega' => $entrega, 'estado' => $estado, 'esta_pago' => 'Si', 'fecha' => $fecha,
        ]);
        foreach ($items as [$nombre, $cantidad, $precio]) {
            // El detalle guarda su propio nombre y precio: el reporte agrupa por ese nombre, no por el producto
            $this->conexion->prepare('INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, (SELECT MIN(ID) FROM tbl_menu), ?, ?, ?)')
                ->execute([$id, $nombre, $precio, $cantidad]);
        }
    }

    public function testResumeElPeriodoSinContarCancelados(): void
    {
        $this->pedido('2001-03-01 00:00:00', 3000, [['Prueba doble', 2, 1500]]);
        $this->pedido('2001-03-31 23:59:59', 900, [['Prueba doble', 1, 1000]], 'Listo', 'Mercado Pago', 'Delivery'); // 100 de descuento por puntos
        $this->pedido('2001-03-15 12:00:00', 5000, [['Prueba simple', 5, 1000]], 'Cancelado');
        $this->pedido('2001-04-01 00:00:00', 7000, [['Prueba simple', 7, 1000]]);

        $resumen = $this->ventas->resumen(['desde' => '2001-03-01', 'hasta' => '2001-03-31']);

        $this->assertSame(3900.0, $resumen['total'], 'cuenta lo cobrado, con descuentos, y los dos días de borde completos');
        $this->assertSame(2, $resumen['cantidad_pedidos'], 'el cancelado no es una venta');
        $this->assertSame(1950.0, $resumen['ticket_promedio']);
        $this->assertSame(['nombre' => 'Prueba doble', 'cantidad' => 3, 'monto' => 4000.0], $resumen['producto_estrella']);
        $this->assertCount(1, $resumen['productos']);
        $this->assertSame(['Efectivo', 'Mercado Pago'], array_column($resumen['metodos_pago'], 'nombre'));
        $this->assertSame([1, 1], array_column($resumen['tipos_entrega'], 'pedidos'));
    }

    public function testLaComparacionMensualTieneLosDoceMeses(): void
    {
        $this->pedido('2001-03-10 10:00:00', 1000, [['Prueba', 1, 1000]]);
        $this->pedido('2001-04-10 10:00:00', 2500, [['Prueba', 1, 2500]]);

        $mensual = $this->ventas->resumen(['desde' => '2001-01-01', 'hasta' => '2001-12-31'])['mensual'];

        $this->assertSame(2001, $mensual['anio']);
        $this->assertCount(12, $mensual['meses']);
        $this->assertSame('Marzo', $mensual['meses'][2]['nombre']);
        $this->assertSame([0.0, 0.0, 1000.0, 2500.0], array_slice(array_column($mensual['meses'], 'total'), 0, 4));
    }

    public function testUnPeriodoSinVentasDevuelveCeros(): void
    {
        $resumen = $this->ventas->resumen(['desde' => '2001-06-01', 'hasta' => '2001-06-30']);

        $this->assertSame(0.0, $resumen['total']);
        $this->assertSame(0, $resumen['cantidad_pedidos']);
        $this->assertNull($resumen['producto_estrella']);
    }

    public function testRechazaFechasInvalidasOAlReves(): void
    {
        foreach ([
            [['desde' => '2001-02-30'], ['desde']],
            [['desde' => 'ayer', 'hasta' => '2001-13-01'], ['desde', 'hasta']],
            [['desde' => '2001-05-02', 'hasta' => '2001-05-01'], ['hasta']],
        ] as [$filtros, $campos]) {
            try {
                $this->ventas->periodo($filtros);
                $this->fail('Debería rechazar ' . json_encode($filtros));
            } catch (ApiException $error) {
                $this->assertSame(422, $error->status());
                $this->assertSame($campos, array_keys($error->errores()));
            }
        }
    }

    public function testSinFechasUsaElMesActual(): void
    {
        $this->assertSame(['desde' => date('Y-m-01'), 'hasta' => date('Y-m-d')], $this->ventas->periodo([]));
    }
}
