<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelefonoTest extends TestCase
{
    public static function telefonosValidos(): array
    {
        return [
            'Argentina' => ['54', '3511234567', '+543511234567'],
            'Uruguay 8 dígitos' => ['598', '91234567', '+59891234567'],
            'Uruguay 9 dígitos' => ['598', '912345678', '+598912345678'],
            'Brasil' => ['55', '11987654321', '+5511987654321'],
            'Chile' => ['56', '912345678', '+56912345678'],
            'Paraguay' => ['595', '981234567', '+595981234567'],
            'Bolivia' => ['591', '71234567', '+59171234567'],
            'Perú' => ['51', '912345678', '+51912345678'],
            'Estados Unidos' => ['1', '2125551234', '+12125551234'],
            'España' => ['34', '612345678', '+34612345678'],
            'con guiones y espacios' => ['+54', '351-123 4567', '+543511234567'],
            'ya con el código de país' => ['54', '+543511234567', '+543511234567'],
        ];
    }

    #[DataProvider('telefonosValidos')]
    public function testFormateaLosNumerosValidos(string $codigo, string $numero, string $esperado): void
    {
        $this->assertSame($esperado, validarTelefono($codigo, $numero));
    }

    public static function telefonosInvalidos(): array
    {
        return [
            'código no admitido' => ['999', '1234567890'],
            'código con letras' => ['ABC', '1234567890'],
            'número vacío' => ['54', ''],
            'Argentina corto' => ['54', '123456789'],
            'Uruguay corto' => ['598', '1234567'],
            'Brasil largo' => ['55', '123456789012'],
            'Chile corto' => ['56', '12345678'],
            'Estados Unidos largo' => ['1', '12345678901'],
        ];
    }

    #[DataProvider('telefonosInvalidos')]
    public function testRechazaLosNumerosInvalidos(string $codigo, string $numero): void
    {
        $this->assertFalse(validarTelefono($codigo, $numero));
    }
}
