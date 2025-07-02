-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 04:31 PM
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
(3, 'fdsdfds', '2342342', '', '$2y$10$FF/cUd0GvtPrl5kvQtfSduqkKNKnDkWLdJvT4HNjXK4BH2ewI9muW', '2025-06-25 23:25:13', 0);

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
(12, 'Jamón y queso', 'Carne, jamón, queso y mayonesa', '1751433252_landscape-placeholder-svgrepo-com.png', '9000', 'Hamburguesas'),
(13, 'Clásica', 'Carne, queso tybo, tomate, lechuga, mostaza y ketchup', '1751433428_landscape-placeholder-svgrepo-com.png', '8900', 'Hamburguesas'),
(14, 'Completa', 'Carne, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1751433455_landscape-placeholder-svgrepo-com.png', '9000', 'Hamburguesas'),
(15, 'Vegetariana', 'Milanesa veggie, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1751433483_landscape-placeholder-svgrepo-com.png', '9000', 'Hamburguesas'),
(16, 'Cheese', 'Carne, queso cheddar, mostaza y ketchup', '1751433500_landscape-placeholder-svgrepo-com.png', '8300', 'Hamburguesas'),
(17, 'Fritas', 'Papas fritas', '1751433539_landscape-placeholder-svgrepo-com.png', '7200', 'Acompañamientos'),
(18, 'Fritas con cheddar y bacon', 'Papas fritas con cheddar y panceta ahumada', '1751433576_landscape-placeholder-svgrepo-com.png', '8500', 'Acompañamientos'),
(19, 'Aros de cebolla', 'Aros de cebolla x8 unidades', '1751433602_landscape-placeholder-svgrepo-com.png', '5600', 'Acompañamientos'),
(20, 'Tequeños x6', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x6 unidades', '1751433663_landscape-placeholder-svgrepo-com.png', '8000', 'Acompañamientos'),
(21, 'Tequeños x12', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x12 unidades', '1751433689_landscape-placeholder-svgrepo-com.png', '10000', 'Acompañamientos'),
(22, 'Super lomo', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa', '1751433787_landscape-placeholder-svgrepo-com.png', '10000', 'Lomitos y Sándwiches'),
(23, 'Lomo Piccolo', 'Triple carne, queso tybo, 2 huevos fritos, panceta ahumada, cebolla caramelizada, morrones confitados y salsa Piccolo', '1751433852_landscape-placeholder-svgrepo-com.png', '10900', 'Lomitos y Sándwiches'),
(24, 'Lomoburger', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa. Lo delicioso del lomo, en pan de hamburguesa.', '1751433892_landscape-placeholder-svgrepo-com.png', '9100', 'Lomitos y Sándwiches'),
(25, 'Fuggazetta', 'Muzza y cebolla caramelizada', '1751434056_landscape-placeholder-svgrepo-com.png', '13000', 'Pizzas'),
(26, 'Napolitana', 'Muzza, tomate fresco, aceite de ajo', '1751434104_landscape-placeholder-svgrepo-com.png', '13000', 'Pizzas'),
(27, 'Pepperoni', 'Muzza y pepperoni', '1751434140_landscape-placeholder-svgrepo-com.png', '14000', 'Pizzas'),
(28, 'Pepsi', 'Lata de pepsi', '1751434162_landscape-placeholder-svgrepo-com.png', '1500', 'Bebidas'),
(29, 'Coca-cola', 'Coca-cola 1,5lts', '1751434182_landscape-placeholder-svgrepo-com.png', '3800', 'Bebidas');

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
(56, 'Juana', '123', '', '', 23000, '2025-06-27 16:33:33', 'Efectivo', 'Retiro', '', 'En preparación', 2),
(57, 'juanita', '123', '', '', 10000, '2025-06-30 17:53:56', 'MercadoPago', 'Delivery', '233232323 fsfsdf', 'En preparación', 2);

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_comentarios`
--
ALTER TABLE `tbl_comentarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_menu`
--
ALTER TABLE `tbl_menu`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tbl_pedidos`
--
ALTER TABLE `tbl_pedidos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `tbl_pedidos_detalle`
--
ALTER TABLE `tbl_pedidos_detalle`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
