<?php
// inicio de sesión y conexión a la base de datos
session_start();
// Verificar si el cliente ya está autenticado
unset($_SESSION["cliente"]);
// Si no hay sesión de cliente, redirigir al login
header("Location: ../index.php");
exit;

