<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel

piccolo_admin_pagina('bebidas/ventas', ['csrf' => piccolo_csrf_token()]);
