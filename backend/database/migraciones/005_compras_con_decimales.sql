-- Las compras guardaban cantidad y precio sin decimales: 2,5 kg quedaba como 3 en el detalle,
-- aunque al stock se le sumaba 2,5. Ahora se guardan con dos decimales, igual que el stock.
ALTER TABLE tbl_compras_detalle
    MODIFY cantidad DECIMAL(10,2) NOT NULL,
    MODIFY precio_unitario DECIMAL(10,2) NOT NULL;
