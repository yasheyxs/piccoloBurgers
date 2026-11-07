<?php

if (!function_exists('asegurarTablaPremios')) {
    function asegurarTablaPremios(PDO $conexion): void
    {
        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS premios (
                id INT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT NULL,
                costo_puntos INT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}
