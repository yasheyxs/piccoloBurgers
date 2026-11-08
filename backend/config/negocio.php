<?php

/**
 * Datos del local que se muestran en el sitio y en el panel. Es el único lugar donde están:
 * el sitio los pide a la API (GET /api/v1/negocio) y el panel los lee de acá.
 *
 * Más adelante se van a poder editar desde el panel; mientras tanto, se cambian en este archivo.
 */

return [
    'nombre' => 'Piccolo Burgers',
    'nombre_corto' => 'Piccolo Burgers VDR',
    'eslogan' => '100% cargadas de sabor',
    'servicio' => 'Servicio a mesa y Take Away',

    'direccion' => [
        'calle' => '25 de Mayo 1295',
        'localidad' => 'Villa del Rosario',
        'provincia' => 'Córdoba',
        'pais' => 'Argentina',
        'referencia' => 'a pasos del centro',
    ],
    // Mapa de Google para insertar en el sitio ("Compartir" → "Insertar un mapa" → la dirección del src)
    'mapa' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3400.107065015397!2d-63.53723899007664!3d-31.548676202549203!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94332a4ac325a7ad%3A0x91ff9ca646897a8f!2s25%20de%20Mayo%201295%2C%20X5963%20Villa%20del%20Rosario%2C%20C%C3%B3rdoba!5e0!3m2!1ses-419!2sar!4v1756841415057!5m2!1ses-419!2sar',

    'horarios' => [
        ['dias' => 'Martes a domingo y feriados', 'horas' => '20:00 a 00:30 hs'],
    ],
    'cerrado' => 'Lunes cerrado',

    // Número completo para WhatsApp: código de país + 9 (celular) + número, sin espacios ni signos
    'whatsapp' => '5493573438947',
    'whatsapp_visible' => '+54 9 3573 438947',
    'redes' => [
        'facebook' => 'https://www.facebook.com/profile.php?id=100087896013957',
        'instagram' => 'https://www.instagram.com/piccoloburgers/',
    ],

    // Datos para pagar por transferencia o Mercado Pago
    'pago' => [
        'alias' => 'piccolovdr',
        'titular' => 'Mario Alberto Gaido',
    ],
    'costo_delivery' => 'entre $1000 y $1500 según la zona',
];
