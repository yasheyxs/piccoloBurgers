<?php

declare(strict_types=1);

// Arranque de PHPUnit: dependencias, helpers y los servicios que se prueban
require_once __DIR__ . '/../src/Core/bootstrap.php';
require_once __DIR__ . '/../src/Services/pedidos.php';
require_once __DIR__ . '/../src/Services/reservas_virtuales.php';
require_once __DIR__ . '/../src/Services/estado_pago_helpers.php';
require_once __DIR__ . '/../src/Services/Security/password_utils.php';
require_once __DIR__ . '/../src/Services/Security/limite_intentos.php';
require_once __DIR__ . '/Integracion/BaseDePruebas.php';
