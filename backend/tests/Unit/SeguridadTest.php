<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SeguridadTest extends TestCase
{
    public function testRequisitosDeContrasena(): void
    {
        $this->assertTrue(passwordCumpleRequisitos('Cliente1!'));
        $this->assertFalse(passwordCumpleRequisitos('Corta1!'), 'menos de 8 caracteres');
        $this->assertFalse(passwordCumpleRequisitos('sinmayuscula1!'));
        $this->assertFalse(passwordCumpleRequisitos('SINMINUSCULA1!'));
        $this->assertFalse(passwordCumpleRequisitos('SinNumero!'));
        $this->assertFalse(passwordCumpleRequisitos('SinSimbolo1'));
    }

    public function testLasContrasenasSeGuardanConHashModerno(): void
    {
        $hash = generarHashPassword('Cliente1!');

        $this->assertNotSame('Cliente1!', $hash);
        $this->assertTrue(esHashPasswordModerno($hash));
        $this->assertTrue(passwordCoincideConHash('Cliente1!', $hash));
        $this->assertFalse(passwordCoincideConHash('otra', $hash));
        $this->assertFalse(passwordDebeRehash($hash));
    }

    public function testUnHashViejoEnMd5SePideActualizar(): void
    {
        $md5 = md5('Cliente1!');

        $this->assertTrue(passwordCoincideConHash('Cliente1!', $md5));
        $this->assertTrue(passwordDebeRehash($md5));
        $this->assertFalse(passwordCoincideConHash('Cliente1!', ''));
    }

    public function testLaClaveDelLimiteDeIntentosNoDistingueMayusculas(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $clave = piccoloLoginClave('panel', 'Usuario');

        $this->assertSame($clave, piccoloLoginClave('panel', '  usuario '));
        $this->assertNotSame($clave, piccoloLoginClave('cliente', 'Usuario'), 'cada tipo de cuenta cuenta aparte');

        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $this->assertNotSame($clave, piccoloLoginClave('panel', 'Usuario'), 'cada IP cuenta aparte');
    }
}
