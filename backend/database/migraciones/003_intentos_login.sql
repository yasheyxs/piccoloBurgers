-- Intentos fallidos de inicio de sesión, para bloquear temporalmente a quien prueba contraseñas.
CREATE TABLE IF NOT EXISTS tbl_intentos_login (
    clave CHAR(64) NOT NULL,
    intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    primer_intento DATETIME NOT NULL,
    bloqueado_hasta DATETIME NULL,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
