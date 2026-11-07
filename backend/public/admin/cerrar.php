<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
piccolo_admin_iniciar_sesion();
session_destroy();
header("Location:./login.php");
echo "Salir... cerrar sesión";