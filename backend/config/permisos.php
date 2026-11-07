<?php

/**
 * Quién puede entrar a cada pantalla del panel (rutas relativas a public/admin/).
 *
 * - "publicas": no requieren sesión (login y recuperación de contraseña).
 * - "roles": roles permitidos por pantalla. Una ruta terminada en "/" vale para todo lo que está adentro.
 * - Cualquier pantalla que no figure acá solo la puede abrir un administrador.
 *
 * El menú del panel usa esta misma tabla, así lo que se muestra y lo que se permite siempre coinciden.
 */

return [
    'publicas' => [
        'login.php',
        'cerrar.php',
        'password/recuperar_password_usuario.php',
        'password/procesar_recuperacion_usuario.php',
        'password/reset_password.php',
        'password/actualizar_password.php',
    ],

    'roles' => [
        'index.php' => ['admin', 'empleado', 'delivery'],
        'password/cambiar_password.php' => ['admin', 'empleado', 'delivery'],
        'password/procesar_cambio.php' => ['admin', 'empleado', 'delivery'],
        'actualizar_estado.php' => ['admin', 'empleado', 'delivery'],

        'panel_cocina.php' => ['admin', 'empleado'],
        'seccion/pool/' => ['admin', 'empleado'],
        'seccion/bebidas/' => ['admin', 'empleado'],

        'panel_delivery.php' => ['admin', 'delivery'],
        'obtener_pedidos_delivery.php' => ['admin', 'delivery'],
    ],
];
