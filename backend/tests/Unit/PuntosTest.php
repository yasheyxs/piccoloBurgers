<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PuntosTest extends TestCase
{
    /** Configuración de ejemplo: mínimo 50 puntos, cada punto vale $20, descuento máximo 25 %. */
    private const CONFIGURACION = ['minimo_puntos' => 50, 'valor_punto' => 20, 'maximo_porcentaje' => 0.25];

    public function testSinElMinimoDePuntosNoHayCanje(): void
    {
        $canje = pedidoCalcularCanjePuntos(10000, 49, self::CONFIGURACION);

        $this->assertSame(0, $canje['puntos_usados']);
        $this->assertSame(0.0, $canje['descuento']);
    }

    public function testElDescuentoNoSuperaElPorcentajeMaximo(): void
    {
        // 25 % de $10.000 = $2.500 → 125 puntos, aunque el cliente tenga 1000
        $canje = pedidoCalcularCanjePuntos(10000, 1000, self::CONFIGURACION);

        $this->assertSame(125, $canje['puntos_usados']);
        $this->assertSame(2500.0, $canje['descuento']);
    }

    public function testSeUsanSoloLosPuntosQueTieneElCliente(): void
    {
        $canje = pedidoCalcularCanjePuntos(10000, 60, self::CONFIGURACION);

        $this->assertSame(60, $canje['puntos_usados']);
        $this->assertSame(1200.0, $canje['descuento']);
    }

    public function testElDescuentoMaximoSeRedondeaACientos(): void
    {
        // 25 % de $9.950 = $2.487,50 → se toma $2.400 → 120 puntos
        $canje = pedidoCalcularCanjePuntos(9950, 1000, self::CONFIGURACION);

        $this->assertSame(120, $canje['puntos_usados']);
        $this->assertSame(2400.0, $canje['descuento']);
    }

    public function testUnPedidoChicoNoAlcanzaParaCanjear(): void
    {
        // 25 % de $300 = $75, que redondeado a cientos es $0
        $canje = pedidoCalcularCanjePuntos(300, 1000, self::CONFIGURACION);

        $this->assertSame(0, $canje['puntos_usados']);
        $this->assertSame(0.0, $canje['descuento']);
    }

    public function testUnaConfiguracionInvalidaUsaValoresSeguros(): void
    {
        // Porcentaje en 0 o negativo vuelve al 25 %; nunca se descuenta más que el total
        $canje = pedidoCalcularCanjePuntos(10000, 1000, ['minimo_puntos' => 0, 'valor_punto' => 20, 'maximo_porcentaje' => 0]);
        $this->assertSame(2500.0, $canje['descuento']);

        $canje = pedidoCalcularCanjePuntos(10000, 100000, ['minimo_puntos' => 0, 'valor_punto' => 20, 'maximo_porcentaje' => 5]);
        $this->assertLessThanOrEqual(10000.0, $canje['descuento']);
    }

    public function testPuntosGanadosUnoCadaMilQuinientos(): void
    {
        $this->assertSame(0, pedidoPuntosGanados(1499.99));
        $this->assertSame(1, pedidoPuntosGanados(1500));
        $this->assertSame(6, pedidoPuntosGanados(9999));
        $this->assertSame(0, pedidoPuntosGanados(-100));
    }
}
