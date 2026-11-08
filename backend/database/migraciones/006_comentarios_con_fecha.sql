-- Los comentarios del sitio se aceptan de hasta 600 caracteres, pero la columna guardaba 255:
-- un mensaje largo hacía fallar el envío. Además se guarda la fecha, para ver los más nuevos primero.
-- Los comentarios que ya estaban quedan sin fecha (no se sabe cuándo llegaron).
ALTER TABLE tbl_comentarios
    MODIFY mensaje VARCHAR(600) NOT NULL,
    ADD COLUMN fecha DATETIME NULL DEFAULT NULL;

ALTER TABLE tbl_comentarios
    MODIFY fecha DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
