<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\EstadisticasController;
use Piccolo\Http\Api\ApiException;

/**
 * Las jornadas se crean en 2002, un año sin otros datos, así los totales no dependen de la base.
 */
final class EstadisticasTest extends BaseDePruebas
{
    private EstadisticasController $estadisticas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->estadisticas = new EstadisticasController($this->conexion);
    }

    protected function tearDown(): void
    {
        if (isset($this->conexion)) {
            $this->conexion->exec("DELETE v FROM bebida_ventas v JOIN bebidas b ON b.id = v.bebida_id WHERE b.nombre LIKE 'Prueba %'");
            $this->conexion->exec("DELETE FROM bebidas WHERE nombre LIKE 'Prueba %'");
        }
        parent::tearDown();
    }

    /** Jornada cerrada con sus totales ya guardados (como queda al cerrarla). */
    private function jornada(string $apertura, int $azul, int $rojo, float $valorFicha = 1000): int
    {
        return $this->insertar('pool_jornadas', [
            'estado' => 'cerrada', 'fecha_apertura' => $apertura, 'fecha_cierre' => $apertura,
            'fichas_vendidas_azul' => $azul, 'fichas_vendidas_rojo' => $rojo,
            'monto_vendido_azul' => $azul * $valorFicha, 'monto_vendido_rojo' => $rojo * $valorFicha,
            'jugadores_atendidos' => 2, 'turnos_completados' => 2, 'duracion_minutos' => 90,
        ]);
    }

    public function testResumeLasJornadasDelPoolDelPeriodo(): void
    {
        $vieja = $this->jornada('2002-03-01 20:00:00', 3, 1);
        $nueva = $this->jornada('2002-03-31 20:00:00', 2, 4);
        $this->jornada('2002-04-01 20:00:00', 50, 50); // fuera del período

        $datos = $this->estadisticas->pool(['desde' => '2002-03-01', 'hasta' => '2002-03-31']);

        $this->assertSame([2, 10, 10000.0], [$datos['resumen']['jornadas'], $datos['resumen']['vendidas'], $datos['resumen']['monto']]);
        $this->assertSame([5, 5], [$datos['resumen']['mesas']['azul']['vendidas'], $datos['resumen']['mesas']['rojo']['vendidas']]);
        $this->assertSame([$nueva, $vieja], array_column($datos['jornadas'], 'id'), 'de la más nueva a la más vieja');
        $this->assertSame($nueva, $datos['seleccionada']['id'], 'sin elegir, muestra la última');
        $this->assertSame('1 h 30 min', $datos['seleccionada']['duracion']);

        $elegida = $this->estadisticas->pool(['desde' => '2002-03-01', 'hasta' => '2002-03-31', 'jornada' => $vieja]);
        $this->assertSame($vieja, $elegida['seleccionada']['id']);
    }

    public function testResumeLasBebidasPorBebidaYTipo(): void
    {
        $jornada = $this->jornada('2002-06-10 20:00:00', 0, 0);
        $this->conexion->exec("INSERT INTO bebidas (nombre, valor_jarra, valor_promo, jarras_por_promo, activa) VALUES ('Prueba stout', 2000, 5000, 3, 1)");
        $bebida = (int) $this->conexion->lastInsertId();
        $vender = $this->conexion->prepare(
            'INSERT INTO bebida_ventas (jornada_id, bebida_id, bebida_nombre, persona, tipo, cantidad_total, cantidad_entregada, unidades_por_item, valor_unitario)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $vender->execute([$jornada, $bebida, 'Prueba stout', 'Ana', 'jarra', 2, 2, 1, 2000]);
        $vender->execute([$jornada, $bebida, 'Prueba stout', 'Beto', 'promo', 1, 1, 3, 5000]);

        $datos = $this->estadisticas->bebidas(['desde' => '2002-06-01', 'hasta' => '2002-06-30']);

        $this->assertSame([1, 5, 3, 2, 9000.0, 2], [
            $datos['resumen']['jornadas'], $datos['resumen']['vendidas'], $datos['resumen']['entregadas'],
            $datos['resumen']['pendientes'], $datos['resumen']['monto'], $datos['resumen']['personas'],
        ]);
        $this->assertSame([['Prueba stout', 'jarra', 2], ['Prueba stout', 'promo', 3]], array_map(
            static fn(array $b): array => [$b['bebida'], $b['tipo'], $b['vendidas']],
            $datos['por_bebida']
        ));
    }

    public function testUnPeriodoSinJornadasYFechasInvalidas(): void
    {
        $vacio = $this->estadisticas->pool(['desde' => '2002-09-01', 'hasta' => '2002-09-30']);
        $this->assertSame([0, null], [$vacio['resumen']['jornadas'], $vacio['seleccionada']]);

        $this->expectException(ApiException::class);
        $this->estadisticas->bebidas(['desde' => '2002-09-30', 'hasta' => '2002-09-01']);
    }
}
