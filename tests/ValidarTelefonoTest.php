<?php

declare(strict_types=1);

require_once __DIR__ . '/../componentes/validar_telefono.php';

/**
 * Simple assertion helper that throws when a condition is not met.
 */
function assertCondition(bool $condition, string $message): void
{
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

try {
  // Unsupported country codes should fail validation.
  assertCondition(
    validarTelefono('999', '1234567890') === false,
    'Unsupported country code should return false.'
  );

  assertCondition(
    validarTelefono('ABC', '1234567890') === false,
    'Non numeric country codes should be rejected.'
  );

  // Invalid number lengths for supported country codes should fail validation.
  $invalidLengthCases = [
    ['54', '123456789'],      // Argentina requires 10 digits
    ['598', '1234567'],       // Uruguay requires 8 or 9 digits
    ['55', '123456789012'],   // Brasil allows 10 or 11 digits
    ['56', '12345678'],       // Chile requires 9 digits
    ['595', '12345678'],      // Paraguay requires 9 digits
    ['591', '1234567'],       // Bolivia requires 8 digits
    ['51', '12345678'],       // Perú requires 9 digits
    ['1', '12345678901'],     // USA allows 10 digits
    ['34', '12345678'],       // España requires 9 digits
  ];

  foreach ($invalidLengthCases as [$codigo, $numero]) {
    assertCondition(
      validarTelefono($codigo, $numero) === false,
      "Invalid length for country code {$codigo} should return false."
    );
  }

  // Valid cases to ensure the helper continues to work as expected.
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
      "Valid number for country code {$codigo} should return formatted phone number."
    );
  }

  // Ensure that extraneous characters are stripped before validation.
  $formatted = validarTelefono('+54', '351-123 4567');
  assertCondition(
    $formatted === '+543511234567',
    'The validator should strip non-digit characters before validation.'
  );

  echo "All validarTelefono tests passed.\n";
  exit(0);
} catch (Throwable $throwable) {
  fwrite(STDERR, 'Test failure: ' . $throwable->getMessage() . "\n");
  exit(1);
}
