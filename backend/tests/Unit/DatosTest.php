<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use Piccolo\Http\Admin\Datos;
use Piccolo\Http\Admin\MateriasPrimasController;
use PHPUnit\Framework\TestCase;

final class DatosTest extends TestCase
{
    public function testDecimalesConComaOPunto(): void
    {
        $this->assertSame(2.5, Datos::decimal('2,5'));
        $this->assertSame(2.5, Datos::decimal(' 2.5 '));
        $this->assertSame(3.0, Datos::decimal(3));
        $this->assertNull(Datos::decimal(''));
        $this->assertNull(Datos::decimal('dos'));
        $this->assertNull(Datos::decimal(['2']));
    }

    public function testIdsSoloEnterosPositivos(): void
    {
        $this->assertSame(7, Datos::id('7'));
        $this->assertSame(7, Datos::id(7));
        $this->assertSame(0, Datos::id('-7'));
        $this->assertSame(0, Datos::id('7a'));
        $this->assertSame(0, Datos::id(null));
    }

    public function testFechasValidas(): void
    {
        $this->assertSame('2026-02-28', Datos::fecha('2026-02-28'));
        $this->assertNull(Datos::fecha('2026-02-30'), 'el 30 de febrero no existe');
        $this->assertNull(Datos::fecha('28/02/2026'));
        $this->assertNull(Datos::fecha(''));
    }

    public function testEstadoDelStock(): void
    {
        $this->assertSame('sin_stock', MateriasPrimasController::estadoStock(0, 5));
        $this->assertSame('bajo', MateriasPrimasController::estadoStock(5, 5));
        $this->assertSame('ok', MateriasPrimasController::estadoStock(6, 5));
        $this->assertSame('bajo', MateriasPrimasController::estadoStock(1, null), 'sin mínimo cargado se toma 1');
        $this->assertSame('ok', MateriasPrimasController::estadoStock(1.5, null));
    }
}
