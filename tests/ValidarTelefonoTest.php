<?php

declare(strict_types=1);

require_once __DIR__ . '/../componentes/validar_telefono.php';

function assertCondition(bool $condition, string $message): void
{
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

try {
  // Códigos no soportados o inválidos fallan la validación
  assertCondition(
    validarTelefono('999', '1234567890') === false,
    'códigos de país no admitidos deberían devolver falso.'
  );

  assertCondition(
    validarTelefono('ABC', '1234567890') === false,
    'Códigos de país no numéricos son rechazados.'
  );

  // Largos invalidos según el código de país fallan la validación
  $invalidLengthCases = [
    ['54', '123456789'],      // Argentina requiere 10 digitos
    ['598', '1234567'],       // Uruguay requiere 8 or 9 digitos
    ['55', '123456789012'],   // Brasil requiere 10 or 11 digitos
    ['56', '12345678'],       // Chile requiere 9 digitos
    ['595', '12345678'],      // Paraguay requiere 9 digitos
    ['591', '1234567'],       // Bolivia requiere 8 digitos
    ['51', '12345678'],       // Perú requiere 9 digitos
    ['1', '12345678901'],     // USA requiere 10 digitos
    ['34', '12345678'],       // España requiere 9 digitos
  ];

  foreach ($invalidLengthCases as [$codigo, $numero]) {
    assertCondition(
      validarTelefono($codigo, $numero) === false,
      "Largos invalidos según el código de país {$codigo} debería devolver falso."
    );
  }

  // casos válidos devuelven el número formateado correctamente
  $validCases = [
    ['54', '3511234567', '+543511234567'],
    ['598', '91234567', '+59891234567'],
    ['598', '912345678', '+598912345678'],
    ['55', '11987654321', '+5511987654321'],
    ['56', '912345678', '+56912345678'],
    ['595', '981234567', '+595981234567'],
    ['591', '71234567', '+59171234567'],
    ['51', '912345678', '+51912345678'],
    ['1', '2125551234', '+12125551234'],
    ['34', '612345678', '+34612345678'],
  ];

  foreach ($validCases as [$codigo, $numero, $esperado]) {
    assertCondition(
      validarTelefono($codigo, $numero) === $esperado,
      "Largos válidos según el código de país {$codigo} debería devolver el número reformateado."
    );
  }

  // asegura que los caracteres no numéricos son eliminados antes de la validación
  $formatted = validarTelefono('+54', '351-123 4567');
  assertCondition(
    $formatted === '+543511234567',
    'El validador debe desacerse de carácteres no numéricos antes de la validación.'
  );

  echo "todos los tests para validar telefono pasaron.\n";
  exit(0);
} catch (Throwable $throwable) {
  fwrite(STDERR, 'Fallo del test: ' . $throwable->getMessage() . "\n");
  exit(1);
}
