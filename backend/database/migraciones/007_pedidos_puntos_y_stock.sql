-- Cada pedido guarda cuántos puntos usó y ganó el cliente, para poder devolverlos si se cancela,
-- y si ya se descontaron sus insumos del stock, para no descontarlos dos veces.
ALTER TABLE tbl_pedidos
    ADD COLUMN puntos_usados INT NOT NULL DEFAULT 0,
    ADD COLUMN puntos_ganados INT NOT NULL DEFAULT 0,
    ADD COLUMN stock_descontado TINYINT(1) NOT NULL DEFAULT 0;

-- Los pedidos que ya estaban listos o entregados ya descontaron su stock
UPDATE tbl_pedidos SET stock_descontado = 1 WHERE estado IN ('Listo', 'Entregado');
