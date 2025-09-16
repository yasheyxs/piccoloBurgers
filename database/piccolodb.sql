-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 11:12 PM
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
  `puntos` int(11) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_clientes`
--

INSERT INTO `tbl_clientes` (`ID`, `nombre`, `telefono`, `email`, `password`, `fecha_registro`, `puntos`, `reset_token`, `token_expira`) VALUES
(2, 'Juanita', '123445444141', '', '$2y$10$1/J42AZMSpKzryAUXUC3tOS9Ri/ULK8t1nNjSeJgyBm2a9O3NIFoC', '2025-06-20 03:08:38', 25, NULL, NULL),
(3, 'fdsdfds', '2342342', '', '$2y$10$FF/cUd0GvtPrl5kvQtfSduqkKNKnDkWLdJvT4HNjXK4BH2ewI9muW', '2025-06-25 23:25:13', 0, NULL, NULL),
(4, 'Yass', '+541111111111', NULL, '$2y$10$LPVkO1.2qyrK9e2CQUMHjep6njTTBbrxEfZWepeqzLPxybfh8Z8tm', '2025-07-29 19:11:37', 159, NULL, NULL),
(5, 'Morrón', '543573451913', '', '$2y$10$Wn4qn9S/jOeR6r0hhrQpteS/PAWBflgTRVmKIp3y2kwJ9PPdU6m1e', '2025-09-04 19:37:49', 69, NULL, NULL),
(6, 'Prueba', '1', '', '$2y$10$E8RuOLWFLib6fjtM0lX.4.m9orxYcwt016xtZbUxJhpbwnP6dy1.e', '2025-09-04 19:50:00', 0, NULL, NULL),
(7, 'Prueba', '+541234567890', '', '$2y$10$Q7P352516qnETmMYuOML3ewRcSrYT7IPPtglMHWAHpf6wIHOuKwZm', '2025-09-05 04:46:49', 0, 'b82d236b117aaa01935b7c8c4ef2780ef2d87d05a6f128031f22fd1fa014afeb', '2025-09-06 09:18:42'),
(8, 'Uu', '+541231231231', '', '$2y$10$1BbVcAM0MK.lbk7/O3.xDuOtiy9mftPtMgl879AIul5MA4N/jbsvu', '2025-09-08 23:29:28', 18, NULL, NULL),
(9, 'Cliente', '+541234512345', '', '$2y$10$JUXGr5Wq2JPrzsJcaLXmRuApQUE32wjS9FLCMkiA1RcyzPFs02eoq', '2025-09-15 03:52:18', 0, NULL, NULL);

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

--
-- Dumping data for table `tbl_comentarios`
--

INSERT INTO `tbl_comentarios` (`ID`, `nombre`, `correo`, `mensaje`) VALUES
(14, 'Yass', 'Feedback@gmail.com', 'feed'),
(15, 'Yass', 'Feedback@gmail.com', 'aaa'),
(16, 'Yass', 'Feedback@gmail.com', 'aaa'),
(17, 'Yass', 'Feedback@gmail.com', '3123');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_compras`
--

CREATE TABLE `tbl_compras` (
  `ID` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `proveedor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_compras`
--

INSERT INTO `tbl_compras` (`ID`, `fecha`, `proveedor_id`) VALUES
(4, '2025-09-03', 17),
(5, '2025-09-03', 18),
(6, '2025-09-03', 24),
(7, '2025-09-03', 20),
(8, '2025-09-03', 20),
(9, '2025-09-04', 20),
(10, '2025-09-04', 21),
(11, '2025-09-04', 21),
(12, '2025-09-04', 19);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_compras_detalle`
--

CREATE TABLE `tbl_compras_detalle` (
  `ID` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `materia_prima_id` int(11) NOT NULL,
  `cantidad` decimal(10,0) NOT NULL,
  `precio_unitario` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_compras_detalle`
--

INSERT INTO `tbl_compras_detalle` (`ID`, `compra_id`, `materia_prima_id`, `cantidad`, `precio_unitario`) VALUES
(1, 4, 24, 2, 4000),
(2, 5, 9, 2, 8000),
(3, 5, 17, 3, 1234),
(4, 6, 1, 2, 4000),
(5, 6, 2, 3, 1200),
(6, 6, 4, 6, 2000),
(7, 7, 10, 10, 1000),
(8, 8, 11, 10, 1000),
(9, 9, 12, 1, 4999),
(10, 9, 13, 2, 4999),
(11, 9, 10, 3, 4999),
(12, 10, 5, 1, 6000),
(13, 11, 6, 1, 8000),
(14, 12, 14, 1, 12000);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_materias_primas`
--

CREATE TABLE `tbl_materias_primas` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `unidades_por_pack` int(11) NOT NULL DEFAULT 1,
  `stock_minimo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_materias_primas`
--

INSERT INTO `tbl_materias_primas` (`ID`, `nombre`, `unidad_medida`, `cantidad`, `proveedor_id`, `unidades_por_pack`, `stock_minimo`) VALUES
(1, 'Tomate', 'kg', 3.44, 20, 1, NULL),
(2, 'Lechuga', 'kg', 3.45, 0, 1, NULL),
(3, 'Morrón', 'kg', 2.08, 24, 1, NULL),
(4, 'Cebolla', 'kg', 2.86, 1, 1, NULL),
(5, 'Carne molida de cerdo', 'kg', 2.98, 21, 1, NULL),
(6, 'Carne molida de vaca', 'kg', 1.54, 21, 1, NULL),
(7, 'Carne de lomo', 'kg', 2.06, 1, 1, NULL),
(8, 'Pan de lomo', 'unidad', 36.00, 0, 1, NULL),
(9, 'Pan de hamburguesa', 'unidad', 41.00, 0, 1, NULL),
(10, 'Mayonesa', 'litro', 4.39, 19, 1, NULL),
(11, 'Mostaza', 'litro', 2.75, 1, 1, NULL),
(12, 'Ketchup', 'litro', 3.65, 1, 1, NULL),
(13, 'Salsa BBQ', 'litro', 1.06, 1, 1, NULL),
(14, 'Queso Tybo', 'kg', 1.03, 1, 1, NULL),
(15, 'Queso Cheddar', 'kg', 2.21, 1, 1, NULL),
(16, 'Queso Cheddar Litro', 'litro', 2.23, 1, 1, NULL),
(17, 'Jamón cocido', 'kg', 3.36, 1, 1, NULL),
(18, 'Huevo', 'unidad', 35.00, 0, 1, NULL),
(19, 'Panceta', 'kg', 1.85, 1, 1, NULL),
(20, 'Salsa Piccolo', 'litro', 1.73, 1, 1, NULL),
(21, 'Pepinillos', 'kg', 2.08, 0, 1, NULL),
(22, 'Bondiola', 'kg', 4.30, 1, 1, NULL),
(23, 'Cebolla morada', 'kg', 2.19, 1, 1, NULL),
(24, 'Milanesa Vegetariana', 'unidad', 38.00, 1, 1, NULL),
(25, 'Milanesa de pollo', 'kg', 50.00, 0, 1, NULL),
(26, 'Milanesa de carne', 'kg', 1.66, 1, 1, NULL),
(27, 'Prepizza', 'unidad', 35.00, 1, 1, NULL),
(28, 'Aceituna', 'tarro', 4.19, 1, 1, NULL),
(30, 'Aceite de ajo', 'litro', 1.16, 1, 1, NULL),
(31, 'Aceite de girasol', 'litro', 3.95, 1, 1, NULL),
(32, 'Salsa para pizza', 'litro', 3.30, 1, 1, NULL),
(33, 'Anchoas', 'lata', 2.00, 0, 1, NULL),
(34, 'Pepperoni', 'kg', 3.65, 1, 1, NULL),
(35, 'Discos de empanada', 'unidad', 30.00, 1, 1, NULL),
(101, 'Pepsi (lata)', 'unidad', 31.00, 0, 1, 10.00),
(102, 'Coca-cola 1.5L', 'unidad', 49.00, 0, 1, 10.00),
(103, 'Paso de los Toros (lata)', 'unidad', 35.00, 0, 1, 10.00),
(104, '7up (lata)', 'unidad', 41.00, 0, 1, 10.00),
(105, 'Mirinda (lata)', 'unidad', 49.00, 0, 1, 10.00),
(106, 'Cerveza (lata)', 'unidad', 50.00, 0, 1, 10.00),
(107, 'Stella (lata)', 'unidad', 31.00, 0, 1, 10.00),
(108, 'Cerveza (porrón)', 'unidad', 38.00, 0, 1, 10.00),
(109, 'Stella (porrón)', 'unidad', 48.00, 0, 1, 10.00),
(110, 'Agua saborizada 1L', 'unidad', 34.00, 0, 1, 10.00),
(111, 'Agua saborizada 500ml', 'unidad', 35.00, 0, 1, 10.00),
(112, 'Sprite 1.5L', 'unidad', 50.00, 0, 1, 10.00),
(113, 'Fanta 1.5L', 'unidad', 30.00, 0, 1, 10.00),
(114, 'Queso Muzzarella', 'kg', 3.32, 1, 1, 1.00),
(115, 'Queso Roquefort', 'kg', 4.68, 1, 1, 1.00),
(116, 'Azúcar', 'kg', 4.41, 1, 1, 1.00),
(117, 'Milanesa de carne', 'unidad', 33.00, 1, 1, 5.00),
(118, 'Suprema de pollo', 'unidad', 45.00, 1, 1, 5.00),
(119, 'Nugget de pollo', 'unidad', 33.00, 1, 1, 10.00),
(120, 'Papas fritas', 'kg', 6.70, 17, 1, NULL),
(121, 'Aros de cebolla', 'kg', 7.00, 17, 1, NULL);

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
  `categoria` varchar(30) NOT NULL DEFAULT 'General',
  `visible_en_menu` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_menu`
--

INSERT INTO `tbl_menu` (`ID`, `nombre`, `ingredientes`, `foto`, `precio`, `categoria`, `visible_en_menu`) VALUES
(12, 'Jamón y queso', 'Carne, jamón, queso y mayonesa', '1754019339_5.png', '9000', 'Hamburguesas', 1),
(13, 'Clásica', 'Carne, queso tybo, tomate, lechuga, mostaza y ketchup', '1754019355_1.png', '8900', 'Hamburguesas', 1),
(14, 'Completa', 'Carne, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1754019362_2.png', '9000', 'Hamburguesas', 1),
(15, 'Vegetariana', 'Milanesa veggie, queso tybo, tomate, lechuga, pepinillos y mayonesa', '1754019377_3.png', '9000', 'Hamburguesas', 1),
(16, 'Cheese', 'Carne, queso cheddar, mostaza y ketchup', '1754019388_4.png', '8300', 'Hamburguesas', 1),
(17, 'Fritas', 'Papas fritas', '1754020670_13.png', '7200', 'Acompañamientos', 1),
(18, 'Fritas con cheddar y bacon', 'Papas fritas con cheddar y panceta ahumada', '1754020695_14.png', '8500', 'Acompañamientos', 1),
(19, 'Aros de cebolla', 'Aros de cebolla x8 unidades', '1754020686_12.png', '5600', 'Acompañamientos', 1),
(20, 'Tequeños x6', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x6 unidades', '1754111154_Clásica.png', '8000', 'Acompañamientos', 1),
(21, 'Tequeños x12', 'Aperitivo venezolano consistente en palitos de queso envueltos en una masa de harina de trigo, fritos. x12 unidades', '1754111146_Clásica.png', '10000', 'Acompañamientos', 1),
(22, 'Super lomo', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa', '1754109263_20.png', '11000', 'Lomitos y Sándwiches', 1),
(23, 'Lomo Piccolo', 'Triple carne, queso tybo, 2 huevos fritos, panceta ahumada, cebolla caramelizada, morrones confitados y salsa Piccolo', '1754109280_21.png', '13000', 'Lomitos y Sándwiches', 1),
(24, 'Lomoburger', 'Carne, jamón, queso tybo, huevo, tomate, lechuga y mayonesa. Lo delicioso del lomo, en pan de hamburguesa.', '1754109304_19.png', '10000', 'Lomitos y Sándwiches', 1),
(25, 'Fuggazetta', 'Muzza y cebolla caramelizada', '1754109317_29.png', '13000', 'Pizzas', 1),
(26, 'Napolitana', 'Muzza, tomate fresco, aceite de ajo', '1754109327_28.png', '13000', 'Pizzas', 1),
(27, 'Pepperoni', 'Muzza y pepperoni', '1754109355_26.png', '14000', 'Pizzas', 1),
(28, 'Pepsi', 'Lata de pepsi', '1754112409_34.png', '1500', 'Bebidas', 1),
(29, 'Coca-cola', 'Coca-cola 1,5lts', '1754112420_44.png', '3800', 'Bebidas', 1),
(30, 'Sweet Onion', 'Carne, queso tybo, cebolla caramelizada y mayonesa', '1754019494_6.png', '9500', 'Hamburguesas', 1),
(31, 'Morrón', 'Carne, queso tybo, morrón confitado y mayonesa', '1754019527_7.png', '9500', 'Hamburguesas', 1),
(32, 'Monstruosa', 'Triple carne, queso cheddar, panceta ahumada y salsa piccolo', '1754019605_10.png', '12000', 'Hamburguesas', 1),
(33, 'BBQ', 'Doble carne, queso cheddar, panceta ahumada y salsa BBQ', '1754019638_8.png', '10500', 'Hamburguesas', 1),
(34, 'Gran Piccolo', 'Doble carne, queso cheddar, panceta ahumada, huevo frito, cebolla caramelizada, morrones confitados y salsa piccolo', '1754019684_9.png', '11000', 'Hamburguesas', 1),
(35, 'Chicken', 'Medallón de pollo, queso tybo, tomate, lechuga, pepinillos, cebolla y mayonesa', '1754020099_Clásica.png', '9500', 'Hamburguesas', 1),
(36, 'Pizza Anchoas', 'Muzza y anchoas', '1754109472_32.png', '14500', 'Pizzas', 1),
(37, 'Roque', 'Muzza, roquefort', '1754109502_31.png', '13000', 'Pizzas', 1),
(38, 'Empanada árabe x1', 'Empanada árabe', '1754109584_15.png', '1500', 'Acompañamientos', 1),
(39, 'Empanadas árabes x6', 'Media docena de empanadas árabes', '1754109614_15.png', '7000', 'Acompañamientos', 1),
(40, 'Empanadas árabes x12', 'Docena de empanadas árabes', '1754109637_15.png', '11000', 'Acompañamientos', 1),
(41, 'Empanada dulce x1', 'Empanada dulce', '1754109663_17.png', '1500', 'Acompañamientos', 1),
(42, 'Empanadas dulces x6', 'Media docena de empanadas dulces', '1754109694_17.png', '7000', 'Acompañamientos', 1),
(43, 'Empanadas dulces x12', 'Docena de empanadas dulces', '1754109734_17.png', '11000', 'Acompañamientos', 1),
(44, 'Empanada salada x1', 'Empanada salada', '1754109761_16.png', '1500', 'Acompañamientos', 1),
(45, 'Empanadas saladas x6', 'Media docena de empanadas saladas', '1754109790_16.png', '7000', 'Acompañamientos', 1),
(46, 'Empanadas saladas x12', 'Docena de empanadas saladas', '1754109818_16.png', '11000', 'Acompañamientos', 1),
(47, 'Empanada de jamón y queso x1', 'Empanada dulce', '1754109860_18.png', '1500', 'Acompañamientos', 1),
(48, 'Empanadas de jamón y queso x6', 'Media docena de empanadas de jamón y queso', '1754109898_18.png', '7000', 'Acompañamientos', 1),
(49, 'Empanadas de jamón y queso x12', 'Docena de empanadas de jamón y queso', '1754109926_18.png', '11000', 'Acompañamientos', 1),
(50, 'Especial', 'Muzza, jamón cocido', '1754110143_27.png', '13000', 'Pizzas', 1),
(51, 'Lomo de bondiola', 'Bondiola desmenuzada, queso tybo, panceta ahumada, huevo revuelto, cebolla morada, morrones confitados y mayonesa', '1754110631_22.png', '13000', 'Lomitos y Sándwiches', 1),
(52, 'Pizza Piccolo', 'Muzza, panceta ahumada, huevos fritos, cebolla caramelizada, morrones confitados, salsa piccolo', '1754110697_30.png', '16400', 'Pizzas', 1),
(53, 'Nuggets x6', 'Nuggets de pollo x6', '1754110767_24.png', '6200', 'Acompañamientos', 1),
(54, 'Nuggets x12', 'Nuggets de pollo x12', '1754110789_24.png', '6500', 'Acompañamientos', 1),
(55, 'Lomo de pollo', 'Pollo, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110831_25.png', '10000', 'Lomitos y Sándwiches', 1),
(56, 'Sándwich de milanesa', 'Milanesa, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110872_23.png', '11000', 'Lomitos y Sándwiches', 1),
(57, 'Sándwich de suprema', 'Suprema, jamón cocido, queso tybo, huevo, tomate, lechuga y mayonesa', '1754110909_23.png', '11000', 'Lomitos y Sándwiches', 1),
(58, 'Paso de los Toros', 'Lata de Paso de los Toros', '1754112454_35.png', '1500', 'Bebidas', 1),
(59, '7up', 'Lata de 7up', '1754112494_36.png', '1500', 'Bebidas', 1),
(60, 'Mirinda', 'Lata de Mirinda', '1754112517_37.png', '1500', 'Bebidas', 1),
(61, 'Lata de Cerveza', 'Lata de cerveza Brahma o Quilmes por disponibilidad o elección', '1754112561_38.png', '4500', 'Bebidas', 1),
(62, 'Stella', 'Lata de Stella', '1754112587_39.png', '4500', 'Bebidas', 1),
(63, 'Porrón de Cerveza', 'Porrón Brahma o Quilmes por disponibilidad o elección', '1754112636_40.png', '6000', 'Bebidas', 1),
(64, 'Porrón Stella', 'Porrón Stella', '1754112658_41.png', '6500', 'Bebidas', 1),
(65, 'Agua Saborizada', 'Agua saborizada de 1 litro. Sabor por disponibilidad o elección', '1754112697_42.png', '4500', 'Bebidas', 1),
(66, 'Agua Saborizada', 'Agua saborizada de 500ml. Sabor por disponibilidad o elección', '1754112720_43.png', '1500', 'Bebidas', 1),
(67, 'Sprite', 'Sprite de 1,5lts', '1754112745_45.png', '4500', 'Bebidas', 1),
(68, 'Fanta', 'Fanta 1,5lts', '1754112763_46.png', '4500', 'Bebidas', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_menu_materias_primas`
--

CREATE TABLE `tbl_menu_materias_primas` (
  `ID` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `materia_prima_id` int(11) NOT NULL,
  `cantidad` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_menu_materias_primas`
--

INSERT INTO `tbl_menu_materias_primas` (`ID`, `menu_id`, `materia_prima_id`, `cantidad`) VALUES
(3, 12, 14, 0.0200),
(4, 12, 5, 0.0600),
(5, 12, 6, 0.1000),
(6, 12, 17, 0.0200),
(7, 12, 10, 0.0300),
(8, 12, 9, 1.0000),
(9, 13, 9, 1.0000),
(10, 13, 5, 0.0600),
(11, 13, 6, 0.1000),
(12, 13, 14, 0.0500),
(13, 13, 1, 0.0300),
(14, 13, 2, 0.0300),
(15, 13, 11, 0.0200),
(16, 13, 12, 0.0200),
(17, 14, 9, 1.0000),
(18, 14, 5, 0.0600),
(19, 14, 6, 0.1000),
(21, 14, 1, 0.0300),
(22, 14, 2, 0.0300),
(23, 14, 21, 0.0200),
(25, 15, 9, 1.0000),
(26, 15, 24, 1.0000),
(27, 15, 14, 0.0500),
(28, 15, 1, 0.0300),
(29, 15, 2, 0.0300),
(30, 15, 21, 0.0200),
(31, 16, 9, 1.0000),
(32, 16, 5, 0.0600),
(33, 16, 6, 0.1000),
(34, 16, 15, 0.0500),
(35, 16, 11, 0.0200),
(36, 16, 12, 0.0200),
(39, 18, 15, 0.0500),
(40, 18, 19, 0.0500),
(42, 22, 8, 1.0000),
(45, 22, 17, 0.0500),
(46, 22, 14, 0.0500),
(47, 22, 18, 1.0000),
(48, 22, 1, 0.0300),
(49, 22, 2, 0.0300),
(50, 23, 8, 1.0000),
(53, 23, 14, 0.0500),
(54, 23, 18, 2.0000),
(55, 23, 19, 0.0500),
(56, 35, 9, 1.0000),
(58, 35, 14, 0.0500),
(59, 35, 1, 0.0300),
(60, 35, 2, 0.0300),
(61, 35, 21, 0.0200),
(62, 28, 101, 1.0000),
(63, 29, 102, 1.0000),
(64, 58, 103, 1.0000),
(65, 59, 104, 1.0000),
(66, 60, 105, 1.0000),
(67, 61, 106, 1.0000),
(68, 62, 107, 1.0000),
(69, 63, 108, 1.0000),
(70, 64, 109, 1.0000),
(71, 65, 110, 1.0000),
(72, 66, 111, 1.0000),
(73, 67, 112, 1.0000),
(74, 68, 113, 1.0000),
(75, 20, 114, 0.1200),
(76, 21, 114, 0.2400),
(77, 24, 9, 1.0000),
(80, 24, 17, 0.0500),
(81, 24, 14, 0.0500),
(82, 24, 18, 1.0000),
(83, 24, 1, 0.0300),
(84, 24, 2, 0.0300),
(85, 24, 10, 0.0300),
(87, 25, 4, 0.0800),
(89, 26, 1, 0.0900),
(93, 30, 9, 1.0000),
(94, 30, 5, 0.0600),
(95, 30, 6, 0.1000),
(96, 30, 14, 0.0500),
(97, 30, 4, 0.0500),
(98, 30, 10, 0.0300),
(99, 31, 9, 1.0000),
(100, 31, 5, 0.0600),
(101, 31, 6, 0.1000),
(102, 31, 14, 0.0500),
(103, 31, 3, 0.0400),
(104, 31, 10, 0.0300),
(105, 32, 9, 1.0000),
(106, 32, 5, 0.1800),
(107, 32, 6, 0.3000),
(108, 32, 15, 0.0800),
(109, 32, 19, 0.0500),
(110, 32, 20, 0.0300),
(111, 33, 9, 1.0000),
(112, 33, 5, 0.1200),
(113, 33, 6, 0.2000),
(114, 33, 15, 0.0500),
(115, 33, 19, 0.0500),
(116, 33, 13, 0.0300),
(117, 34, 9, 1.0000),
(118, 34, 5, 0.1200),
(119, 34, 6, 0.2000),
(120, 34, 15, 0.0500),
(121, 34, 19, 0.0500),
(122, 34, 18, 1.0000),
(123, 34, 4, 0.0500),
(124, 34, 3, 0.0400),
(125, 34, 20, 0.0300),
(128, 37, 114, 0.1000),
(129, 37, 115, 0.0500),
(130, 38, 6, 0.1000),
(131, 38, 4, 0.0300),
(132, 38, 1, 0.0200),
(133, 39, 6, 0.6000),
(134, 39, 4, 0.1800),
(135, 39, 1, 0.1200),
(136, 40, 6, 0.4000),
(137, 40, 4, 0.2000),
(139, 41, 6, 0.1000),
(140, 41, 116, 0.0200),
(141, 42, 6, 0.6000),
(142, 42, 116, 0.1200),
(143, 43, 6, 1.2000),
(144, 43, 116, 0.2400),
(145, 44, 6, 0.1000),
(146, 44, 4, 0.0300),
(147, 45, 6, 0.6000),
(148, 45, 4, 0.1800),
(149, 46, 6, 1.2000),
(150, 46, 4, 0.3600),
(151, 47, 17, 0.0500),
(152, 47, 14, 0.0500),
(153, 48, 17, 0.3000),
(154, 48, 14, 0.3000),
(155, 49, 17, 0.6000),
(156, 49, 14, 0.6000),
(157, 50, 114, 0.1200),
(158, 50, 17, 0.0500),
(159, 51, 8, 1.0000),
(160, 51, 22, 0.1200),
(161, 51, 14, 0.0500),
(162, 51, 19, 0.0500),
(163, 51, 18, 1.0000),
(164, 51, 23, 0.0400),
(165, 51, 3, 0.0400),
(166, 51, 10, 0.0300),
(167, 52, 114, 0.1200),
(168, 52, 19, 0.0500),
(169, 52, 18, 1.0000),
(170, 52, 4, 0.0500),
(171, 52, 3, 0.0400),
(172, 52, 20, 0.0300),
(173, 53, 119, 6.0000),
(174, 54, 119, 12.0000),
(175, 55, 8, 1.0000),
(176, 55, 25, 0.1200),
(177, 55, 17, 0.0500),
(178, 55, 14, 0.0500),
(179, 55, 18, 1.0000),
(180, 55, 1, 0.0300),
(181, 55, 2, 0.0300),
(182, 55, 10, 0.0300),
(183, 56, 8, 1.0000),
(184, 56, 117, 1.0000),
(185, 56, 17, 0.0500),
(186, 56, 14, 0.0500),
(187, 56, 18, 1.0000),
(188, 56, 1, 0.0300),
(189, 56, 2, 0.0300),
(190, 56, 10, 0.0300),
(191, 57, 8, 1.0000),
(192, 57, 118, 1.0000),
(193, 57, 17, 0.0500),
(194, 57, 14, 0.0500),
(195, 57, 18, 1.0000),
(196, 57, 1, 0.0300),
(197, 57, 2, 0.0300),
(198, 57, 10, 0.0300),
(199, 36, 114, 0.1000),
(200, 36, 32, 0.0800),
(201, 36, 33, 0.0100),
(202, 25, 114, 0.1000),
(203, 18, 120, 0.1500),
(204, 12, 120, 0.1500),
(205, 14, 10, 0.0200),
(206, 15, 10, 0.0200),
(207, 19, 121, 0.1500),
(208, 17, 120, 0.1500),
(209, 16, 120, 0.1500),
(210, 15, 120, 0.1500),
(211, 14, 120, 0.1500),
(212, 13, 120, 0.1500),
(213, 25, 32, 0.0800),
(214, 24, 7, 0.2500),
(215, 24, 120, 0.1500),
(216, 31, 120, 0.1500),
(217, 30, 120, 0.1500),
(218, 27, 34, 0.0500),
(219, 27, 114, 0.1000),
(220, 27, 32, 0.0800),
(221, 26, 32, 0.0800),
(222, 26, 114, 0.1000),
(223, 23, 120, 0.1500),
(224, 23, 7, 0.2500),
(225, 23, 4, 0.0500),
(226, 23, 3, 0.0500),
(227, 41, 35, 1.0000),
(228, 40, 18, 1.0000),
(229, 40, 3, 0.2000),
(230, 40, 35, 12.0000),
(231, 39, 35, 6.0000),
(232, 38, 35, 1.0000),
(233, 37, 27, 1.0000),
(234, 37, 32, 0.0800),
(235, 36, 27, 1.0000),
(236, 35, 25, 0.1600),
(237, 35, 120, 0.1500),
(238, 34, 120, 0.1500),
(239, 32, 120, 0.1500),
(240, 33, 120, 0.1500),
(241, 26, 27, 1.0000),
(242, 26, 30, 0.0200),
(243, 25, 27, 1.0000),
(244, 22, 120, 0.1500),
(245, 22, 7, 0.2500),
(246, 57, 120, 0.1500),
(247, 56, 120, 0.1500),
(248, 49, 35, 12.0000),
(249, 50, 32, 0.0800),
(250, 47, 35, 1.0000),
(251, 48, 35, 6.0000),
(252, 51, 120, 0.1500),
(253, 52, 27, 1.0000),
(254, 52, 32, 0.0800),
(256, 50, 27, 1.0000),
(258, 55, 120, 0.1500),
(259, 44, 35, 1.0000),
(260, 43, 35, 12.0000),
(261, 42, 35, 6.0000),
(262, 45, 35, 6.0000),
(263, 46, 35, 12.0000);

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
  `cliente_id` int(11) DEFAULT NULL,
  `referencias` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pedidos`
--

INSERT INTO `tbl_pedidos` (`ID`, `nombre`, `telefono`, `email`, `nota`, `total`, `fecha`, `metodo_pago`, `tipo_entrega`, `direccion`, `estado`, `cliente_id`, `referencias`) VALUES
(67, 'Yass', '123', '', '', 4500, '2025-08-02 12:24:58', 'MercadoPago', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(68, 'Yass', '123', '', 'Agua manzana', 4500, '2025-08-02 12:26:41', 'Efectivo', 'Retiro', '', 'Listo', 4, NULL),
(69, 'Yass', '123', '', '', 16600, '2025-09-01 22:33:41', 'Efectivo', 'Retiro', '', 'Cancelado', 4, NULL),
(70, 'Yass', '123', '', 'Todo sin aderezo', 66500, '2025-09-02 04:40:37', 'Efectivo', 'Retiro', '', 'Listo', NULL, NULL),
(71, 'Juana', '123', '', '', 21200, '2025-09-03 19:14:42', 'Efectivo', 'Retiro', '', 'Listo', NULL, NULL),
(72, 'Yass', '123', '', '', 1500, '2025-09-03 19:18:38', 'Efectivo', 'Retiro', '', 'Cancelado', 4, NULL),
(73, 'Yass', '123', '', '', 1500, '2025-09-03 22:02:59', 'Efectivo', 'Retiro', '', 'Cancelado', 4, NULL),
(74, 'Yass', '123', '', '', 3000, '2025-09-03 22:11:49', 'Efectivo', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(75, 'Yass', '123', '', '', 22000, '2025-09-03 22:12:08', 'Efectivo', 'Retiro', '', 'Listo', 4, NULL),
(76, 'Yass', '123', '', '', 1500, '2025-09-03 22:15:01', 'Tarjeta', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(77, 'Yass', '123', '', '', 11000, '2025-09-03 22:16:35', 'MercadoPago', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(78, 'Yass', '123', '', '', 22500, '2025-09-03 22:19:22', 'Efectivo', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(79, 'Yass', '123', '', '', 7000, '2025-09-03 22:20:56', 'Efectivo', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(80, 'Yass', '123', '', '', 21000, '2025-09-03 22:22:56', 'Tarjeta', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(81, 'Yass', '123', '', '', 13000, '2025-09-03 22:25:34', 'Efectivo', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(82, 'Yass', '123', '', '', 10000, '2025-09-03 22:27:54', 'MercadoPago', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(83, 'Yass', '123', '', '', 1500, '2025-09-03 22:29:04', 'Efectivo', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(84, 'Yass', '123', '', '', 4500, '2025-09-03 22:30:02', 'Efectivo', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(85, 'Yass', '123', '', '', 4500, '2025-09-03 22:31:09', 'Tarjeta', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(86, 'Yass', '123', '', '', 5600, '2025-09-03 22:31:31', 'Tarjeta', 'Retiro', '', 'Cancelado', 4, NULL),
(87, 'Yass', '123', '', '', 6500, '2025-09-03 22:45:23', 'MercadoPago', 'Delivery', 'Zona 123', 'Cancelado', 4, NULL),
(88, 'Yass', '123', '', '', 1500, '2025-09-03 22:46:42', 'Tarjeta', 'Delivery', 'Zona 123', 'Listo', 4, NULL),
(89, 'Yass', '123', '', '', 9000, '2025-09-04 00:35:05', 'Efectivo', 'Retiro', '', 'Listo', 4, NULL),
(90, 'Yass', '123', '', '', 9000, '2025-09-04 00:39:01', 'Tarjeta', 'Retiro', '', 'Cancelado', 4, NULL),
(91, 'Morrón', '543573451913', '', 'SALON PICCOLO', 54800, '2025-09-04 16:43:23', 'Efectivo', 'Delivery', 'san martin 1299', 'Cancelado', 5, NULL),
(92, 'Morrón', '543573451913', '', '', 18400, '2025-09-04 16:43:59', 'Tarjeta', 'Retiro', '', 'Listo', 5, NULL),
(93, 'Morrón', '543573451913', '', '', 36800, '2025-09-04 16:47:06', 'MercadoPago', 'Retiro', '', 'Cancelado', 5, NULL),
(94, 'Morrón', '543573451913', '', '', 68000, '2025-09-04 16:47:59', 'Efectivo', 'Retiro', '', 'En preparación', 5, NULL),
(95, 'Uu', '+541231231231', '', '', 1500, '2025-09-08 20:47:11', 'Efectivo', 'Retiro', '', 'Listo', 8, NULL),
(96, 'Uu', '+541231231231', '', '', 1500, '2025-09-08 21:30:08', 'Efectivo', 'Retiro', '', 'Listo', 8, NULL),
(97, 'Uu', '+541231231231', '', '', 13000, '2025-09-08 21:36:25', 'Efectivo', 'Retiro', '', 'En preparación', 8, NULL),
(98, 'Uu', '+541231231231', '', '', 10000, '2025-09-08 21:37:22', 'Efectivo', 'Retiro', '', 'En preparación', 8, NULL),
(99, 'Uu', '+541231231231', '', '', 3000, '2025-09-09 01:54:10', 'Efectivo', 'Delivery', 'Zona 123', 'Listo', 8, NULL),
(100, 'Alma', '123123123', '', '', 1500, '2025-09-16 15:07:54', 'Efectivo', 'Delivery', '123', 'En camino', NULL, 'Uh');

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
(78, 67, 67, 'Sprite', 4500, 1),
(79, 68, 65, 'Agua Saborizada', 4500, 1),
(80, 69, 19, 'Aros de cebolla', 5600, 1),
(81, 69, 57, 'Sándwich de suprema', 11000, 1),
(82, 70, 20, 'Tequeños x6', 8000, 1),
(83, 70, 26, 'Napolitana', 13000, 1),
(84, 70, 33, 'BBQ', 21000, 2),
(85, 70, 47, 'Empanada de jamón y queso x1', 4500, 3),
(86, 70, 55, 'Lomo de pollo', 20000, 2),
(87, 71, 17, 'Fritas', 7200, 1),
(88, 71, 39, 'Empanadas árabes x6', 14000, 2),
(89, 72, 47, 'Empanada de jamón y queso x1', 1500, 1),
(90, 73, 47, 'Empanada de jamón y queso x1', 1500, 1),
(91, 74, 47, 'Empanada de jamón y queso x1', 3000, 2),
(92, 75, 40, 'Empanadas árabes x12', 22000, 2),
(93, 76, 41, 'Empanada dulce x1', 1500, 1),
(94, 77, 49, 'Empanadas de jamón y queso x12', 11000, 1),
(95, 78, 32, 'Monstruosa', 12000, 1),
(96, 78, 33, 'BBQ', 10500, 1),
(97, 79, 39, 'Empanadas árabes x6', 7000, 1),
(98, 80, 55, 'Lomo de pollo', 10000, 1),
(99, 80, 56, 'Sándwich de milanesa', 11000, 1),
(100, 81, 37, 'Roque', 13000, 1),
(101, 82, 24, 'Lomoburger', 10000, 1),
(102, 83, 38, 'Empanada árabe x1', 1500, 1),
(103, 84, 65, 'Agua Saborizada', 4500, 1),
(104, 85, 67, 'Sprite', 4500, 1),
(105, 86, 19, 'Aros de cebolla', 5600, 1),
(106, 87, 54, 'Nuggets x12', 6500, 1),
(107, 88, 66, 'Agua Saborizada', 1500, 1),
(108, 89, 12, 'Jamón y queso', 9000, 1),
(109, 90, 12, 'Jamón y queso', 9000, 1),
(110, 91, 17, 'Fritas', 7200, 1),
(111, 91, 19, 'Aros de cebolla', 5600, 1),
(112, 91, 38, 'Empanada árabe x1', 1500, 1),
(113, 91, 39, 'Empanadas árabes x6', 7000, 1),
(114, 91, 40, 'Empanadas árabes x12', 22000, 2),
(115, 91, 45, 'Empanadas saladas x6', 7000, 1),
(116, 91, 67, 'Sprite', 4500, 1),
(117, 92, 13, 'Clásica', 8900, 1),
(118, 92, 35, 'Chicken', 9500, 1),
(119, 93, 16, 'Cheese', 8300, 1),
(120, 93, 35, 'Chicken', 28500, 3),
(121, 94, 23, 'Lomo Piccolo', 13000, 1),
(122, 94, 24, 'Lomoburger', 10000, 1),
(123, 94, 51, 'Lomo de bondiola', 13000, 1),
(124, 94, 55, 'Lomo de pollo', 10000, 1),
(125, 94, 56, 'Sándwich de milanesa', 11000, 1),
(126, 94, 57, 'Sándwich de suprema', 11000, 1),
(127, 95, 47, 'Empanada de jamón y queso x1', 1500, 1),
(128, 96, 66, 'Agua Saborizada', 1500, 1),
(129, 97, 50, 'Especial', 13000, 1),
(130, 98, 55, 'Lomo de pollo', 10000, 1),
(131, 99, 47, 'Empanada de jamón y queso x1', 3000, 2),
(132, 100, 38, 'Empanada árabe x1', 1500, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_proveedores`
--

CREATE TABLE `tbl_proveedores` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_proveedores`
--

INSERT INTO `tbl_proveedores` (`ID`, `nombre`, `telefono`, `email`) VALUES
(17, 'Distribuidora redolfi s.r.l', '+54 3574 4970244', 'distribuidoraredolfi@gmail.com'),
(18, 'Distribuidora lozano', '+54 3573 500740', 'distribuidoralozano@gmail.com'),
(19, 'Distribuidora \"C\"', '+54 3573 451618', 'distribuidoracladera@gmail.com'),
(20, 'Vironi Super V.D.R', '+54 3573 692673', 'supermercadovironi@gmail.com'),
(21, 'La Nueva', '+54 3573 451913', 'zampettijuanpablo@gmail.com'),
(22, 'Pastas via krupp', '+54 3573 514709', 'pastasviakrupp@gmail.com'),
(23, 'Arrodimez', '+54 3574 401891', 'gomezarrodillense@gmail.com'),
(24, 'Andrada', 'Desconocido', '');

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
(3, 'Este es el tercer testimonio de prueba!', 'La developer'),
(4, 'ultimo testimoniooo', 'la developer'),
(5, '⭐⭐⭐⭐⭐ me encantó todo!', 'Un opinante');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `ID` int(11) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `rol` enum('admin','empleado','delivery') NOT NULL DEFAULT 'empleado',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`ID`, `usuario`, `password`, `correo`, `rol`, `reset_token`, `token_expira`) VALUES
(4, 'Delivery', '202cb962ac59075b964b07152d234b70', 'delivery@gmail.com', 'delivery', NULL, NULL),
(5, 'Usuario', 'c702aebe2d2a9bcea7cc6e72a52206cc', 'Usuario@usuario.com', 'admin', NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_stock_materias_primas`
-- (See below for the actual view)
--
CREATE TABLE `vw_stock_materias_primas` (
`ID` int(11)
,`nombre` varchar(50)
,`unidad_medida` varchar(50)
,`stock_pack` decimal(10,2)
,`unidades_por_pack` int(11)
,`stock_unidades_estimado` decimal(20,2)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_stock_materias_primas`
--
DROP TABLE IF EXISTS `vw_stock_materias_primas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_stock_materias_primas`  AS SELECT `mp`.`ID` AS `ID`, `mp`.`nombre` AS `nombre`, `mp`.`unidad_medida` AS `unidad_medida`, `mp`.`cantidad` AS `stock_pack`, `mp`.`unidades_por_pack` AS `unidades_por_pack`, `mp`.`cantidad`* `mp`.`unidades_por_pack` AS `stock_unidades_estimado` FROM `tbl_materias_primas` AS `mp` ;

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
-- Indexes for table `tbl_compras`
--
ALTER TABLE `tbl_compras`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_compras_detalle`
--
ALTER TABLE `tbl_compras_detalle`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_materias_primas`
--
ALTER TABLE `tbl_materias_primas`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_menu`
--
ALTER TABLE `tbl_menu`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_menu_materias_primas`
--
ALTER TABLE `tbl_menu_materias_primas`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_menu_mp` (`menu_id`,`materia_prima_id`),
  ADD KEY `fk_mmp_mp` (`materia_prima_id`);

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
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_producto` (`producto_id`);

--
-- Indexes for table `tbl_proveedores`
--
ALTER TABLE `tbl_proveedores`
  ADD PRIMARY KEY (`ID`);

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_comentarios`
--
ALTER TABLE `tbl_comentarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_compras`
--
ALTER TABLE `tbl_compras`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_compras_detalle`
--
ALTER TABLE `tbl_compras_detalle`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_materias_primas`
--
ALTER TABLE `tbl_materias_primas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `tbl_menu`
--
ALTER TABLE `tbl_menu`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `tbl_menu_materias_primas`
--
ALTER TABLE `tbl_menu_materias_primas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `tbl_pedidos`
--
ALTER TABLE `tbl_pedidos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `tbl_pedidos_detalle`
--
ALTER TABLE `tbl_pedidos_detalle`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `tbl_proveedores`
--
ALTER TABLE `tbl_proveedores`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tbl_testimonios`
--
ALTER TABLE `tbl_testimonios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_menu_materias_primas`
--
ALTER TABLE `tbl_menu_materias_primas`
  ADD CONSTRAINT `fk_mmp_menu` FOREIGN KEY (`menu_id`) REFERENCES `tbl_menu` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mmp_mp` FOREIGN KEY (`materia_prima_id`) REFERENCES `tbl_materias_primas` (`ID`) ON UPDATE CASCADE;

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
