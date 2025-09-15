<?php
session_start();
require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../componentes/validar_telefono.php';
require_once __DIR__ . '/../../includes/client/perfil_cliente_controller.php';
require __DIR__ . '/../../views/client/perfil_cliente.view.php';
