<?php

if (!function_exists('validarTelefono')) {
  function validarTelefono($codigo, $numero)
  {
    $codigo = preg_replace('/[^\d]/', '', (string) $codigo);
    $numeroOriginal = (string) $numero;
    $numero = preg_replace('/[^\d]/', '', $numeroOriginal);

    $longitudes = [
      '54' => [10],       // Argentina
      '598' => [8, 9],    // Uruguay
      '55' => [10, 11],   // Brasil
      '56' => [9],        // Chile
      '595' => [9],       // Paraguay
      '591' => [8],       // Bolivia
      '51' => [9],        // Perú
      '1' => [10],        // USA
      '34' => [9],        // España
    ];

    if ($codigo === '' || $numero === '') {
      return false;
    }

    if (!isset($longitudes[$codigo])) {
      return false;
    }

    $longitudesPermitidas = $longitudes[$codigo];
    $numeroNormalizado = $numero;
    $longitudCodigo = strlen($codigo);
    $contienePrefijoInternacional = preg_match('/^\s*(\+|00)/', $numeroOriginal) === 1;

    foreach ($longitudesPermitidas as $longitudValida) {
      if (strlen($numero) === $longitudValida) {
        $numeroNormalizado = $numero;
        break;
      }

      if ($contienePrefijoInternacional) {
        $longitudConCodigo = $longitudCodigo + $longitudValida;
        if (strlen($numero) === $longitudConCodigo && strncmp($numero, $codigo, $longitudCodigo) === 0) {
          $numeroNormalizado = substr($numero, $longitudCodigo);
          break;
        }
      }
    }

    if (!in_array(strlen($numeroNormalizado), $longitudesPermitidas, true)) {
      return false;
    }

    $telefono = '+' . $codigo . $numeroNormalizado;

    return preg_match('/^\+\d{10,15}$/', $telefono) ? $telefono : false;
  }
}
