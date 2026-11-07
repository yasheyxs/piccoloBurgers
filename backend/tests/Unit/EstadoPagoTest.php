<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EstadoPagoTest extends TestCase
{
    public function testNormalizaMayusculasTildesYEspacios(): void
    {
        $this->assertSame('si', piccolo_normalizar_clave_pago('  Sí '));
        $this->assertSame('pagado', piccolo_normalizar_clave_pago('PAGADO'));
        $this->assertSame('', piccolo_normalizar_clave_pago('   '));
    }

    public static function valores(): array
    {
        return [
            ['Si', true, 'Si'],
            ['sí', true, 'Si'],
            ['pagado', true, 'Si'],
            ['1', true, '1'],
            ['No', false, 'No'],
            ['pendiente', false, 'No'],
            ['0', false, '0'],
            ['', false, 'No'],
            ['cualquier cosa', false, 'No'],
        ];
    }

    #[DataProvider('valores')]
    public function testInterpretaElEstadoGuardado(string $valor, bool $pagado, string $canonico): void
    {
        $estado = piccolo_interpretar_estado_pago_para_presentacion($valor);

        $this->assertSame($pagado, $estado['es_pagado']);
        $this->assertSame($pagado ? 'Sí' : 'No', $estado['texto']);
        $this->assertSame($canonico, $estado['valor']);
    }

    public function testCantidadesDelCarritoNuncaSonNegativas(): void
    {
        $this->assertSame(3, normalizarCantidadSolicitada('3'));
        $this->assertSame(2, normalizarCantidadSolicitada(2.9));
        $this->assertSame(0, normalizarCantidadSolicitada(-5));
        $this->assertSame(0, normalizarCantidadSolicitada('abc'));
    }
}
