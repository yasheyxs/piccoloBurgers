<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\CanjeController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\CanjeRepositorio;
use Piccolo\Repositories\ClientesRepositorio;

final class CanjeTest extends BaseDePruebas
{
    private CanjeController $canje;

    protected function setUp(): void
    {
        parent::setUp();
        $this->canje = new CanjeController(new ClientesRepositorio($this->conexion), new CanjeRepositorio($this->conexion), $this->conexion);
        // 1 punto = $20, mínimo 50 puntos, hasta el 25 % de la venta
        $this->conexion->exec('UPDATE tbl_configuracion_puntos SET minimo_puntos = 50, valor_punto = 20, maximo_porcentaje = 0.25 WHERE id = 1');
    }

    private function cliente(int $puntos, string $telefono = '+543519990101'): int
    {
        return $this->insertar('tbl_clientes', [
            'nombre' => 'Prueba canje', 'telefono' => $telefono, 'email' => 'canje' . substr($telefono, -4) . '@ejemplo.test', 'password' => 'x', 'puntos' => $puntos,
        ]);
    }

    private function puntos(int $cliente): int
    {
        return (int) $this->conexion->query("SELECT puntos FROM tbl_clientes WHERE ID = $cliente")->fetchColumn();
    }

    /** @return array<int, array{0: string, 1: int, 2: int}> tipo, puntos y saldo de cada movimiento, en orden */
    private function movimientos(int $cliente): array
    {
        return $this->conexion->query("SELECT tipo, puntos, saldo_resultante FROM movimientos_puntos WHERE cliente_id = $cliente ORDER BY id")
            ->fetchAll(\PDO::FETCH_NUM);
    }

    private function venta(int $cliente, int $producto, int $puntos, int $cantidad = 1): array
    {
        $resumen = $this->canje->registrarVenta($cliente, [
            'items' => [['id' => $producto, 'cantidad' => $cantidad]],
            'puntos_utilizados' => $puntos, 'metodo_pago' => 'Efectivo', 'tipo_entrega' => 'Retiro',
        ]);
        $this->anotar('tbl_pedidos', $resumen['pedido_id']);

        return $resumen;
    }

    public function testVentaConLosPuntosElegidos(): void
    {
        $producto = $this->crearProductoConStock(10000, 10)['producto'];
        $cliente = $this->cliente(300);

        // Se eligen 60 puntos ($1.200), menos que el tope de 125: paga $8.800 y gana 5 puntos
        $resumen = $this->venta($cliente, $producto, 60);

        $this->assertSame([10000.0, 1200.0, 8800.0], [$resumen['total_original'], $resumen['descuento'], $resumen['total_final']]);
        $this->assertSame([60, 5, 245], [$resumen['puntos_usados'], $resumen['puntos_ganados'], $resumen['puntos_actuales']]);
        $this->assertSame(245, $this->puntos($cliente));
        $this->assertEquals([['canje', -60, 240], ['compra', 5, 245]], $this->movimientos($cliente));

        $pedido = $this->conexion->query("SELECT nombre, telefono, cliente_id, nota FROM tbl_pedidos WHERE ID = {$resumen['pedido_id']}")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(['Prueba canje', '+543519990101', $cliente], [$pedido['nombre'], $pedido['telefono'], (int) $pedido['cliente_id']]);
        $this->assertStringContainsString('canje de puntos', $pedido['nota']);
    }

    public function testVentaSinPuntosSoloSuma(): void
    {
        $producto = $this->crearProductoConStock(3000, 10)['producto'];
        $cliente = $this->cliente(10);

        $resumen = $this->venta($cliente, $producto, 0);

        $this->assertSame([0, 2, 12], [$resumen['puntos_usados'], $resumen['puntos_ganados'], $this->puntos($cliente)]);
    }

    public function testRechazaPuntosFueraDeLasReglas(): void
    {
        $producto = $this->crearProductoConStock(10000, 10)['producto'];
        $cliente = $this->cliente(200);

        foreach ([
            [126, 'hasta 125 puntos'],          // más que el 25 % de la venta
            [30, 'mínimo de puntos'],           // menos que el mínimo
        ] as [$puntos, $mensaje]) {
            try {
                $this->venta($cliente, $producto, $puntos);
                $this->fail("Debería rechazar $puntos puntos");
            } catch (ApiException $error) {
                $this->assertSame(422, $error->status());
                $this->assertStringContainsString($mensaje, $error->getMessage());
            }
        }

        $otro = $this->cliente(80, '+543519990102');
        try {
            $this->venta($otro, $producto, 100);
            $this->fail('Debería rechazar más puntos de los que tiene');
        } catch (ApiException $error) {
            $this->assertStringContainsString('no tiene puntos suficientes', $error->getMessage());
        }

        $this->assertSame([200, 80], [$this->puntos($cliente), $this->puntos($otro)], 'no se tocó ningún saldo');
        $this->assertSame(0, (int) $this->conexion->query("SELECT COUNT(*) FROM tbl_pedidos WHERE cliente_id IN ($cliente, $otro)")->fetchColumn());
    }

    public function testSinStockNoSeVende(): void
    {
        $producto = $this->crearProductoConStock(1000, 1)['producto'];
        $cliente = $this->cliente(0);

        $this->expectException(ApiException::class);
        $this->venta($cliente, $producto, 0, 2);
    }

    public function testCanjeaPremiosMientrasAlcancenLosPuntos(): void
    {
        $premio = $this->insertar('premios', ['nombre' => 'Prueba premio', 'descripcion' => '', 'costo_puntos' => 100]);
        $cliente = $this->cliente(250);

        $resumen = $this->canje->canjearPremios($cliente, ['premios' => [['id' => $premio, 'cantidad' => 2]]]);

        $this->assertSame([200, 50], [$resumen['puntos_usados'], $resumen['puntos_actuales']]);
        $this->assertSame('Canje de premios: 2x Prueba premio', $resumen['descripcion']);
        $this->assertEquals([['canje', -200, 50]], $this->movimientos($cliente));

        try {
            $this->canje->canjearPremios($cliente, ['premios' => [['id' => $premio, 'cantidad' => 1]]]);
            $this->fail('No le alcanzan los puntos');
        } catch (ApiException $error) {
            $this->assertSame(422, $error->status());
        }
        $this->assertSame(50, $this->puntos($cliente));
        $this->assertCount(1, $this->movimientos($cliente));
    }

    public function testRechazaPremiosInvalidos(): void
    {
        $cliente = $this->cliente(1000);
        foreach ([[], [['id' => 999999, 'cantidad' => 1]], [['id' => 1, 'cantidad' => 0]]] as $premios) {
            try {
                $this->canje->canjearPremios($cliente, ['premios' => $premios]);
                $this->fail('Debería rechazar ' . json_encode($premios));
            } catch (ApiException $error) {
                $this->assertSame(['premios'], array_keys($error->errores()));
            }
        }
    }

    public function testUnClienteQueNoExisteDa404(): void
    {
        try {
            $this->canje->datos(999999);
            $this->fail('Debería avisar que no existe');
        } catch (ApiException $error) {
            $this->assertSame(404, $error->status());
        }
    }
}
