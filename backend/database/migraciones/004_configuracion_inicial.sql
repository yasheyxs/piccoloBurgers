-- Valores iniciales de configuración, para que el sistema funcione desde el primer día.
-- Si ya existen no se tocan.

-- Puntos: mínimo para canjear, valor de cada punto y descuento máximo (25 %)
INSERT IGNORE INTO tbl_configuracion_puntos (id, minimo_puntos, valor_punto, maximo_porcentaje)
VALUES (1, 50, 20, 0.25);
