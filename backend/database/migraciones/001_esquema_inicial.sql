-- Esquema inicial de la base: tablas, índices, relaciones y la vista de stock.
-- Es el punto de partida; los cambios posteriores van en migraciones nuevas, nunca editando esta.

CREATE TABLE `movimientos_puntos` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `tipo` enum('compra','canje','ajuste') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `puntos` int NOT NULL,
  `saldo_resultante` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `premios` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `costo_puntos` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_banners` (
  `ID` int NOT NULL,
  `imagen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_clientes` (
  `ID` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `puntos` int DEFAULT '0',
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_comentarios` (
  `ID` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mensaje` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_compras` (
  `ID` int NOT NULL,
  `fecha` date NOT NULL,
  `proveedor_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_compras_detalle` (
  `ID` int NOT NULL,
  `compra_id` int NOT NULL,
  `materia_prima_id` int NOT NULL,
  `cantidad` decimal(10,0) NOT NULL,
  `precio_unitario` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_configuracion_puntos` (
  `id` int NOT NULL,
  `minimo_puntos` int NOT NULL,
  `valor_punto` decimal(10,2) NOT NULL,
  `maximo_porcentaje` decimal(5,4) NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_materias_primas` (
  `ID` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `proveedor_id` int NOT NULL,
  `unidades_por_pack` int NOT NULL DEFAULT '1',
  `stock_minimo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_menu` (
  `ID` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ingredientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `precio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'General',
  `visible_en_menu` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_menu_materias_primas` (
  `ID` int NOT NULL,
  `menu_id` int NOT NULL,
  `materia_prima_id` int NOT NULL,
  `cantidad` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pedidos` (
  `ID` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `total` decimal(10,0) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_entrega` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'En preparación',
  `cliente_id` int DEFAULT NULL,
  `referencias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `esta_pago` enum('No','Si') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pedidos_detalle` (
  `ID` int NOT NULL,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `precio` decimal(10,0) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_proveedores` (
  `ID` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_reservas_virtuales` (
  `ID` int NOT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `menu_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_testimonios` (
  `ID` int NOT NULL,
  `opinion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_usuarios` (
  `ID` int NOT NULL,
  `usuario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('admin','empleado','delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'empleado',
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `movimientos_puntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

ALTER TABLE `premios`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_banners`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_clientes`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `telefono` (`telefono`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `tbl_comentarios`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_compras`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_compras_detalle`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_configuracion_puntos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_materias_primas`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_menu`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_menu_materias_primas`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_menu_mp` (`menu_id`,`materia_prima_id`),
  ADD KEY `fk_mmp_mp` (`materia_prima_id`);

ALTER TABLE `tbl_pedidos`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_pedidos_detalle`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_producto` (`producto_id`);

ALTER TABLE `tbl_proveedores`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_reservas_virtuales`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `session_menu_unique` (`session_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`);

ALTER TABLE `tbl_testimonios`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `movimientos_puntos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `premios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_banners`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_clientes`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_comentarios`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_compras`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_compras_detalle`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_materias_primas`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_menu`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_menu_materias_primas`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_pedidos`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_pedidos_detalle`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_proveedores`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_reservas_virtuales`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_testimonios`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_usuarios`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

CREATE VIEW `vw_stock_materias_primas` AS SELECT `mp`.`ID` AS `ID`, `mp`.`nombre` AS `nombre`, `mp`.`unidad_medida` AS `unidad_medida`, `mp`.`cantidad` AS `stock_pack`, `mp`.`unidades_por_pack` AS `unidades_por_pack`, (`mp`.`cantidad` * `mp`.`unidades_por_pack`) AS `stock_unidades_estimado` FROM `tbl_materias_primas` AS `mp` ;

ALTER TABLE `movimientos_puntos`
  ADD CONSTRAINT `movimientos_puntos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `tbl_clientes` (`ID`);

ALTER TABLE `tbl_menu_materias_primas`
  ADD CONSTRAINT `fk_mmp_menu` FOREIGN KEY (`menu_id`) REFERENCES `tbl_menu` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mmp_mp` FOREIGN KEY (`materia_prima_id`) REFERENCES `tbl_materias_primas` (`ID`) ON UPDATE CASCADE;

ALTER TABLE `tbl_pedidos_detalle`
  ADD CONSTRAINT `tbl_pedidos_detalle_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `tbl_pedidos` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_pedidos_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `tbl_menu` (`ID`) ON DELETE CASCADE;

ALTER TABLE `tbl_reservas_virtuales`
  ADD CONSTRAINT `tbl_reservas_virtuales_menu_fk` FOREIGN KEY (`menu_id`) REFERENCES `tbl_menu` (`ID`) ON DELETE CASCADE;
