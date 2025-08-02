-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 07:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `piccolodb`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_banners`
--

CREATE TABLE `tbl_banners` (
  `ID` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_banners`
--

INSERT INTO `tbl_banners` (`ID`, `titulo`, `descripcion`, `link`) VALUES
(1, 'Piccolo Burgers', '100% cargadas de sabor', '#menu');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_clientes`
--

CREATE TABLE `tbl_clientes` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `puntos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_clientes`
--

INSERT INTO `tbl_clientes` (`ID`, `nombre`, `telefono`, `email`, `password`, `fecha_registro`, `puntos`) VALUES
(2, 'Juanita', '123445444141', '', '$2y$10$1/J42AZMSpKzryAUXUC3tOS9Ri/ULK8t1nNjSeJgyBm2a9O3NIFoC', '2025-06-20 03:08:38', 25),
(3, 'fdsdfds', '2342342', '', '$2y$10$FF/cUd0GvtPrl5kvQtfSduqkKNKnDkWLdJvT4HNjXK4BH2ewI9muW', '2025-06-25 23:25:13', 0),
(4, 'Yass', '123', '', '$2y$10$8Xdt5f5TSd91nY6auzztgOr3IFIvlMmn5YjA9d53KwMmYD1mRDYPa', '2025-07-29 19:11:37', 48);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_comentarios`
--

CREATE TABLE `tbl_comentarios` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `mensaje` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_menu`
--

CREATE TABLE `tbl_menu` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `ingredientes` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `precio` varchar(255) NOT NULL,
  `categoria` varchar(30) NOT NULL DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_menu`
--

INSERT INTO `tbl_menu` (`ID`, `nombre`, `ingredientes`, `foto`, `precio`, `categoria`) VALUES
(12, 'Jamón y queso', 'Carne, jamón, queso y mayonesa', '1754019339_5.png', '9000', 'Hamburguesas'),
(13, 'Clásica', 'Carne, queso tybo, tomate, lechuga, mostaza y ketchup', '1754019355_1.png', '8900', 'Hamburguesas'),
(14, 'Completa', 'Carne, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1754019362_2.png', '9000', 'Hamburguesas'),
(15, 'Vegetariana', 'Milanesa veggie, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1754019377_3.png', '9000', 'Hamburguesas'),
(16, 'Cheese', 'Carne, queso cheddar, mostaza y ketchup', '1754019388_4.png', '8300', 'Hamburguesas'),
(17, 'Fritas', 'Papas fritas', '1754020670_13.png', '7200', 'Acompañamientos'),
(18, 'Fritas con cheddar y bacon', 'Papas fritas con cheddar y panceta ahumada', '1754020695_14.png', '8500', 'Acompañamientos'),
(19, 'Aros de cebolla', 'Aros de cebolla x8 unidades', '1754020686_12.png', '5600', 'Acompañamientos'),
(20, 'Tequeños x6', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x6 unidades', '1754111154_Clásica.png', '8000', 'Acompañamientos'),
(21, 'Tequeños x12', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x12 unidades', '1754111146_Clásica.png', '10000', 'Acompañamientos'),
(22, 'Super lomo', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa', '1754109263_20.png', '11000', 'Lomitos y Sándwiches'),
(23, 'Lomo Piccolo', 'Triple carne, queso tybo, 2 huevos fritos, panceta ahumada, cebolla caramelizada, morrones confitados y salsa Piccolo', '1754109280_21.png', '13000', 'Lomitos y Sándwiches'),
(24, 'Lomoburger', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa. Lo delicioso del lomo, en pan de hamburguesa.', '1754109304_19.png', '10000', 'Lomitos y Sándwiches'),
(25, 'Fuggazetta', 'Muzza y cebolla caramelizada', '1754109317_29.png', '13000', 'Pizzas'),
(26, 'Napolitana', 'Muzza, tomate fresco, aceite de ajo', '1754109327_28.png', '13000', 'Pizzas'),
(27, 'Pepperoni', 'Muzza y pepperoni', '1754109355_26.png', '14000', 'Pizzas'),
(28, 'Pepsi', 'Lata de pepsi', '1754112409_34.png', '1500', 'Bebidas'),
(29, 'Coca-cola', 'Coca-cola 1,5lts', '1754112420_44.png', '3800', 'Bebidas'),
(30, 'Sweet Onion', 'Carne, queso tybo, cebolla caramelizada y mayonesa', '1754019494_6.png', '9500', 'Hamburguesas'),
(31, 'Morrón', 'Carne, queso tybo, morrón confitado y mayonesa', '1754019527_7.png', '9500', 'Hamburguesas'),
(32, 'Monstruosa', 'Triple carne, queso cheddar, panceta ahumada y salsa piccolo', '1754019605_10.png', '12000', 'Hamburguesas'),
(33, 'BBQ', 'Doble carne, queso cheddar, panceta ahumada y salsa BBQ', '1754019638_8.png', '10500', 'Hamburguesas'),
(34, 'Gran Piccolo', 'Doble carne, queso cheddar, panceta ahumada, huevo frito, cebolla caramelizada, morrones confitados y salsa piccolo', '1754019684_9.png', '11000', 'Hamburguesas'),
(35, 'Chicken', 'Medallón de pollo, queso tybo, tomate, lechuga, pepinillos, cebolla y mayonesa', '1754020099_Clásica.png', '9500', 'Hamburguesas'),
(36, 'Pizza Anchoas', 'Muzza y anchoas', '1754109472_32.png', '14500', 'Pizzas'),
(37, 'Roque', 'Muzza, roquefort', '1754109502_31.png', '13000', 'Pizzas'),
(38, 'Empanada árabe x1', 'Empanada árabe', '1754109584_15.png', '1500', 'Acompañamientos'),
(39, 'Empanadas árabes x6', 'Media docena de empanadas árabes', '1754109614_15.png', '7000', 'Acompañamientos'),
(40, 'Empanadas árabes x12', 'Docena de empanadas árabes', '1754109637_15.png', '11000', 'Acompañamientos'),
(41, 'Empanada dulce x1', 'Empanada dulce', '1754109663_17.png', '1500', 'Acompañamientos'),
(42, 'Empanadas dulces x6', 'Media docena de empanadas dulces', '1754109694_17.png', '7000', 'Acompañamientos'),
(43, 'Empanadas dulces x12', 'Docena de empanadas dulces', '1754109734_17.png', '11000', 'Acompañamientos'),
(44, 'Empanada salada x1', 'Empanada salada', '1754109761_16.png', '1500', 'Acompañamientos'),
(45, 'Empanadas saladas x6', 'Media docena de empanadas saladas', '1754109790_16.png', '7000', 'Acompañamientos'),
(46, 'Empanadas saladas x12', 'Docena de empanadas saladas', '1754109818_16.png', '11000', 'Acompañamientos'),
(47, 'Empanada de jamón y queso x1', 'Empanada dulce', '1754109860_18.png', '1500', 'Acompañamientos'),
(48, 'Empanadas de jamón y queso x6', 'Media docena de empanadas de jamón y queso', '1754109898_18.png', '7000', 'Acompañamientos'),
(49, 'Empanadas de jamón y queso x12', 'Docena de empanadas de jamón y queso', '1754109926_18.png', '11000', 'Acompañamientos'),
(50, 'Especial', 'Muzza, jamón cocido', '1754110143_27.png', '13000', 'Pizzas'),
(51, 'Lomo de bondiola', 'Bondiola desmenuzada, queso tybo, panceta ahumada, huevo revuelto, cebolla morada, morrones confitados y mayonesa', '1754110631_22.png', '13000', 'Lomitos y Sándwiches'),
(52, 'Pizza Piccolo', 'Muzza, panceta ahumada, huevos fritos, cebolla caramelizada, morrones confitados, salsa piccolo', '1754110697_30.png', '16400', 'Pizzas'),
(53, 'Nuggets x6', 'Nuggets de pollo x6', '1754110767_24.png', '6200', 'Acompañamientos'),
(54, 'Nuggets x12', 'Nuggets de pollo x12', '1754110789_24.png', '6500', 'Acompañamientos'),
(55, 'Lomo de pollo', 'Pollo, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110831_25.png', '10000', 'Lomitos y Sándwiches'),
(56, 'Sándwich de milanesa', 'Milanesa, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110872_23.png', '11000', 'Lomitos y Sándwiches'),
(57, 'Sándwich de suprema', 'Suprema, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110909_23.png', '11000', 'Lomitos y Sándwiches'),
(58, 'Paso de los Toros', 'Lata de Paso de los Toros', '1754112454_35.png', '1500', 'Bebidas'),
(59, '7up', 'Lata de 7up', '1754112494_36.png', '1500', 'Bebidas'),
(60, 'Mirinda', 'Lata de Mirinda', '1754112517_37.png', '1500', 'Bebidas'),
(61, 'Lata de Cerveza', 'Lata de cerveza Brahma o Quilmes por disponibilidad o elección', '1754112561_38.png', '4500', 'Bebidas'),
(62, 'Stella', 'Lata de Stella', '1754112587_39.png', '4500', 'Bebidas'),
(63, 'Porrón de Cerveza', 'Porrón Brahma o Quilmes por disponibilidad o elección', '1754112636_40.png', '6000', 'Bebidas'),
(64, 'Porrón Stella', 'Porrón Stella', '1754112658_41.png', '6500', 'Bebidas'),
(65, 'Agua Saborizada', 'Agua saborizada de 1 litro. Sabor por disponibilidad o elección', '1754112697_42.png', '4500', 'Bebidas'),
(66, 'Agua Saborizada', 'Agua saborizada de 500ml. Sabor por disponibilidad o elección', '1754112720_43.png', '1500', 'Bebidas'),
(67, 'Sprite', 'Sprite de 1,5lts', '1754112745_45.png', '4500', 'Bebidas'),
(68, 'Fanta', 'Fanta 1,5lts', '1754112763_46.png', '4500', 'Bebidas');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pedidos`
--

CREATE TABLE `tbl_pedidos` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nota` text DEFAULT NULL,
  `total` decimal(10,0) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` varchar(255) NOT NULL,
  `tipo_entrega` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'En preparación',
  `cliente_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pedidos`
--

INSERT INTO `tbl_pedidos` (`ID`, `nombre`, `telefono`, `email`, `nota`, `total`, `fecha`, `metodo_pago`, `tipo_entrega`, `direccion`, `estado`, `cliente_id`) VALUES
(48, 'Juanita', '123', '', '', 60000, '2025-06-25 21:55:54', 'Tarjeta', 'Retiro', '', 'Cancelado', 2),
(49, 'Juanita', '123', '', '', 30000, '2025-06-25 22:01:28', 'Efectivo', 'Retiro', '', 'Listo', 2),
(50, 'Juanita', '123', '', 'No quiero coca', 1200, '2025-06-26 13:44:51', 'MercadoPago', 'Delivery', '233232323 fsfsdf', 'Listo', 2),
(51, 'Juanita', '123445444141', '', '', 26500, '2025-06-26 13:46:24', 'Efectivo', 'Retiro', '', 'Cancelado', 2),
(52, 'Juanita', '123', '', '', 18500, '2025-06-26 14:40:12', 'Efectivo', 'Delivery', '233232323 fsfsdf', 'Listo', 2),
(53, 'Juanita', '123445444141', 'jazmingaidoyxs@gmail.com', 'asdasd', 8500, '2025-06-26 14:41:48', 'MercadoPago', 'Retiro', '', 'Listo', 2),
(54, 'Juanita', '123', '', 'Sin ketchup!!', 30000, '2025-06-27 04:51:30', 'Efectivo', 'Delivery', '233232323 fsfsdf', 'Cancelado', 2),
(55, 'Juanita', '123', '', 'No quiero aguacate en mi hamburguesa de palta', 7420, '2025-06-27 04:56:26', 'Efectivo', 'Delivery', 'Av. San martin 123', 'Listo', 2),
(56, 'Juana', '123', '', '', 23000, '2025-06-27 16:33:33', 'Efectivo', 'Retiro', '', 'Cancelado', 2),
(57, 'juanita', '123', '', '', 10000, '2025-06-30 17:53:56', 'MercadoPago', 'Delivery', '233232323 fsfsdf', 'Cancelado', 2),
(58, 'Yass', '123', '', '', 10600, '2025-07-29 16:12:22', 'Efectivo', 'Retiro', '', 'Listo', 4),
(59, 'Yass', '123', '', '', 19400, '2025-07-29 16:12:49', 'Tarjeta', 'Delivery', 'Zona 123', 'Listo', 4),
(60, 'Yass', '123', '', '', 21000, '2025-07-29 16:23:18', 'Efectivo', 'Retiro', '', 'Cancelado', 4),
(61, 'Yass', '123', '', '', 14000, '2025-07-31 04:09:25', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', NULL),
(62, 'Yass', '123', '', '', 3800, '2025-07-31 04:13:54', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', NULL),
(63, 'Yass', '123', '', '', 3800, '2025-07-31 04:16:03', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', NULL),
(64, 'Yass', '123', '', '', 26000, '2025-07-31 04:18:36', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', NULL),
(65, 'Yass', '123', '', '', 10500, '2025-08-01 01:29:49', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', NULL),
(66, 'Yass', '123', '', '', 22500, '2025-08-01 01:31:09', 'MercadoPago', 'Delivery', 'Zona 123', 'En preparación', 4);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pedidos_detalle`
--

CREATE TABLE `tbl_pedidos_detalle` (
  `ID` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `precio` decimal(10,0) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pedidos_detalle`
--

INSERT INTO `tbl_pedidos_detalle` (`ID`, `pedido_id`, `producto_id`, `nombre`, `precio`, `cantidad`) VALUES
(65, 58, 24, 'Lomoburger', 9100, 1),
(66, 58, 28, 'Pepsi', 1500, 1),
(67, 59, 18, 'Fritas con cheddar y bacon', 8500, 1),
(68, 59, 23, 'Lomo Piccolo', 10900, 1),
(69, 60, 20, 'Tequeños x6', 8000, 1),
(70, 60, 25, 'Fuggazetta', 13000, 1),
(71, 61, 27, 'Pepperoni', 14000, 1),
(72, 62, 29, 'Coca-cola', 3800, 1),
(73, 63, 29, 'Coca-cola', 3800, 1),
(74, 64, 26, 'Napolitana', 26000, 2),
(75, 65, 33, 'BBQ', 10500, 1),
(76, 66, 32, 'Monstruosa', 12000, 1),
(77, 66, 33, 'BBQ', 10500, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_testimonios`
--

CREATE TABLE `tbl_testimonios` (
  `ID` int(11) NOT NULL,
  `opinion` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_testimonios`
--

INSERT INTO `tbl_testimonios` (`ID`, `opinion`, `nombre`) VALUES
(1, 'Este es un testimonio de prueba', 'Juanita'),
(2, '⭐⭐⭐⭐⭐ Me encantó!', 'Soy un comensal'),
(3, 'Este es el tercer testimonio de prueba!', 'La developer'),
(4, 'ultimo testimoniooo', 'la developer');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `ID` int(11) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`ID`, `usuario`, `password`, `correo`) VALUES
(3, 'Jazmin Gaido', '1f72b11d211305eaa302167bd4e7ee1d', 'jazmingaidoyxs@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_banners`
--
ALTER TABLE `tbl_banners`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `telefono` (`telefono`);

--
-- Indexes for table `tbl_comentarios`
--
ALTER TABLE `tbl_comentarios`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_menu`
--
ALTER TABLE `tbl_menu`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_pedidos`
--
ALTER TABLE `tbl_pedidos`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_pedidos_detalle`
--
ALTER TABLE `tbl_pedidos_detalle`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indexes for table `tbl_testimonios`
--
ALTER TABLE `tbl_testimonios`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_banners`
--
ALTER TABLE `tbl_banners`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_comentarios`
--
ALTER TABLE `tbl_comentarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_menu`
--
ALTER TABLE `tbl_menu`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `tbl_pedidos`
--
ALTER TABLE `tbl_pedidos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `tbl_pedidos_detalle`
--
ALTER TABLE `tbl_pedidos_detalle`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `tbl_testimonios`
--
ALTER TABLE `tbl_testimonios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_pedidos_detalle`
--
ALTER TABLE `tbl_pedidos_detalle`
  ADD CONSTRAINT `tbl_pedidos_detalle_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `tbl_pedidos` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_pedidos_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `tbl_menu` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
