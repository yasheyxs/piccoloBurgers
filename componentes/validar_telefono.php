<?php

if (!function_exists('validarTelefono')) {
  function validarTelefono($codigo, $numero)
  {
    $codigo = preg_replace('/[^\d]/', '', $codigo);
    $numero = preg_replace('/[^\d]/', '', $numero);
    $telefono = '+' . $codigo . $numero;

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

    if (!isset($longitudes[$codigo])) {
      return false;
    }

    if (!in_array(strlen($numero), $longitudes[$codigo], true)) {
      return false;
    }

    return preg_match('/^\+\d{10,15}$/', $telefono) ? $telefono : false;
  }
}
