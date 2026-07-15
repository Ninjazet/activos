-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-07-2026 a las 19:47:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestactivos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `idarea` int(11) NOT NULL,
  `descripcionarea` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`idarea`, `descripcionarea`, `activo`) VALUES
(1, 'Gerencia General', 1),
(2, 'Administracion', 1),
(3, 'Informatica', 1),
(4, 'Mantenimiento', 1),
(5, 'Soporte IT', 1),
(6, 'Recursos Humanos', 1),
(7, 'Contabilidad', 1),
(8, 'Recepcion', 1),
(9, 'facturacion', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion`
--

CREATE TABLE `asignacion` (
  `idasignacion` int(11) NOT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idequipo` int(11) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `fecha_devolucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `asignacion`
--

INSERT INTO `asignacion` (`idasignacion`, `idempleado`, `idequipo`, `activa`, `fecha_asignacion`, `fecha_devolucion`) VALUES
(4, 2, 2, 1, '2026-07-01 11:06:00', NULL),
(5, 8, 6, 1, '2026-07-01 11:06:00', NULL),
(6, 9, 5, 1, '2026-07-01 11:14:50', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `idbitacora` int(11) NOT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `usuario_texto` varchar(50) DEFAULT NULL,
  `accion` varchar(30) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`idbitacora`, `idusuario`, `usuario_texto`, `accion`, `modulo`, `detalle`, `ip`, `fecha`) VALUES
(1, 1, 'emartinez', 'login_exitoso', NULL, NULL, '::1', '2026-07-01 11:09:14'),
(2, NULL, 'emartinez', 'login_fallido', NULL, 'Intento fallido desde ::1', '::1', '2026-07-01 11:12:14'),
(3, NULL, 'emartinez', 'login_fallido', NULL, 'Intento fallido desde ::1', '::1', '2026-07-01 11:12:19'),
(4, 1, 'emartinez', 'login_exitoso', NULL, NULL, '::1', '2026-07-01 11:12:23'),
(5, 1, 'emartinez', 'crear', 'asignacion', 'emp=9 equipo=5', '::1', '2026-07-01 11:14:50'),
(6, 1, 'emartinez', 'crear', 'marca', 'facturacion', '::1', '2026-07-01 11:15:39'),
(7, 1, 'emartinez', 'crear', 'usuarios', 'www', '::1', '2026-07-02 11:44:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargos`
--

CREATE TABLE `cargos` (
  `idcargo` int(11) NOT NULL,
  `descripcioncargo` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cargos`
--

INSERT INTO `cargos` (`idcargo`, `descripcioncargo`, `activo`) VALUES
(1, 'Administrador General', 1),
(2, 'Jefe de Contabilidad', 1),
(3, 'Auxiliar Contable', 1),
(4, 'Gerente Administrativo', 1),
(5, 'Recepcionista', 1),
(6, 'Auxiliar de Soporte IT', 1),
(7, 'Asistente de Recursos Humanos', 1),
(8, 'Gerente Comercial', 1),
(11, 'ww', 1),
(12, 'ww', 1),
(13, 'ww', 1),
(14, 'ww', 1),
(15, 'ww', 1),
(16, 'facturador', 1),
(17, 'facturador', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `idempleado` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `telefono` int(11) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `imagen` varchar(500) NOT NULL,
  `idcargo` int(11) NOT NULL,
  `idarea` int(11) NOT NULL,
  `idsexo` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`idempleado`, `nombre`, `apellidos`, `edad`, `telefono`, `direccion`, `imagen`, `idcargo`, `idarea`, `idsexo`, `activo`) VALUES
(1, 'Edwin Enrique', 'Martinez Diaz', 32, 32650620, 'Santa Marta', 'imagenes/empleados/edwin.jpg', 1, 1, 1, 1),
(2, 'Denia Maricela', 'Nuñez Velasquez', 28, 33003400, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 2, 2, 2, 1),
(4, 'Eduardo', 'Papadopolo', 25, 89982210, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 3, 3, 1, 1),
(5, 'Karla Patricia', 'Nuñez Garcia', 30, 33456790, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 4, 4, 2, 1),
(6, 'Henry ', 'Cavill', 45, 99807765, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 5, 5, 1, 1),
(7, 'Jennifer Esthefania', 'Carbajal Ponce', 40, 88778866, 'San Barbara', 'imagenes/empleados/avatar2.png', 2, 2, 2, 1),
(8, 'Juan Ramon', 'Ledezma Velasquez', 36, 99807766, 'Santa Barbara', 'imagenes/empleados/avatar2.png', 3, 2, 1, 1),
(9, 'Adelina', 'Flores Moreno', 29, 88973320, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 4, 5, 2, 1),
(10, 'Diana Emilia', 'Medina Ordoñez', 30, 33456787, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 6, 7, 2, 1),
(11, 'wilfredo', 'castellanos', 21, 98679665, 'santa barbara santa barbara', 'imagenes/empleados/avatar2.png', 1, 1, 1, 1),
(39, 'Wilfredo', 'Castellanos', 24, 94934008, 'San Pedro sula', 'public/img/empleados/emp_1782434728.png', 2, 7, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipo`
--

CREATE TABLE `equipo` (
  `idequipo` int(11) NOT NULL,
  `idmarca_equipo` int(11) NOT NULL,
  `idmodelo_equipo` int(11) NOT NULL,
  `imagen` varchar(500) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `equipo`
--

INSERT INTO `equipo` (`idequipo`, `idmarca_equipo`, `idmodelo_equipo`, `imagen`, `activo`) VALUES
(1, 2, 4, '', 1),
(2, 1, 2, '', 1),
(3, 1, 1, 'imagenes/equipos/', 1),
(4, 1, 1, 'imagenes/equipos/', 1),
(5, 1, 3, 'public/img/equipos/equipo_1782434930.png', 1),
(6, 4, 6, '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marca`
--

CREATE TABLE `marca` (
  `idmarca` int(11) NOT NULL,
  `nombreMarca` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marca`
--

INSERT INTO `marca` (`idmarca`, `nombreMarca`, `activo`) VALUES
(1, 'Lenovo', 1),
(2, 'HP', 1),
(3, 'dell', 1),
(4, 'dell2', 1),
(5, 'facturacion', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelo`
--

CREATE TABLE `modelo` (
  `idmodelo` int(11) NOT NULL,
  `nombreModelo` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelo`
--

INSERT INTO `modelo` (`idmodelo`, `nombreModelo`, `activo`) VALUES
(1, 'Legion Pro 7i', 1),
(2, 'Legion Pro 5', 1),
(3, 'HP Spectre', 1),
(4, 'HP Pavilion', 1),
(5, 'Legion 5', 1),
(6, 'dell', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `idpermiso` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `datosmaestros` tinyint(1) NOT NULL,
  `transacciones` tinyint(1) NOT NULL,
  `consultas` tinyint(1) NOT NULL,
  `reportes` tinyint(1) NOT NULL,
  `seguridad` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`idpermiso`, `idusuario`, `datosmaestros`, `transacciones`, `consultas`, `reportes`, `seguridad`) VALUES
(1, 1, 1, 1, 1, 1, 1),
(2, 2, 1, 0, 1, 0, 0),
(3, 3, 1, 0, 1, 0, 0),
(4, 4, 0, 0, 0, 1, 0),
(5, 5, 1, 1, 1, 1, 1),
(6, 6, 0, 1, 1, 1, 0),
(7, 7, 1, 1, 1, 1, 1),
(8, 8, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sexo`
--

CREATE TABLE `sexo` (
  `idsexo` int(11) NOT NULL,
  `descripcionsexo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sexo`
--

INSERT INTO `sexo` (`idsexo`, `descripcionsexo`) VALUES
(1, 'Masculino'),
(2, 'Femenino');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idusuario` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `idempleado` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idusuario`, `username`, `pass`, `idempleado`, `estado`) VALUES
(1, 'emartinez', '$2y$10$gcpigWkliTOaPrrPmPdVs.OTxLvLxDM4dahBCGMO0lJK5kyU67oru', 1, 1),
(2, 'cpavon', '123', 2, 0),
(3, 'jgarcia', '12345', 3, 1),
(4, 'ariana', '123', 36, 1),
(5, 'tecwil', '1234', 11, 1),
(6, 'wcastes', '12345', 7, 1),
(7, 'wcaste', '12345', 5, 1),
(8, 'www', '$2y$10$ar4zHU3GT.v8SNsIK49SEeEcKJxH9z0G863BRPbeoAD0ZM2iFeoXa', 4, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`idarea`);

--
-- Indices de la tabla `asignacion`
--
ALTER TABLE `asignacion`
  ADD PRIMARY KEY (`idasignacion`),
  ADD KEY `idequipo_idx` (`idequipo`),
  ADD KEY `idempleado_idx` (`idempleado`);

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`idbitacora`),
  ADD KEY `idx_bitacora_usuario` (`idusuario`),
  ADD KEY `idx_bitacora_fecha` (`fecha`),
  ADD KEY `idx_bitacora_accion` (`accion`);

--
-- Indices de la tabla `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`idcargo`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`idempleado`),
  ADD KEY `idcargo_idx` (`idcargo`),
  ADD KEY `idarea_idx` (`idarea`),
  ADD KEY `empleados_FK_2` (`idsexo`);

--
-- Indices de la tabla `equipo`
--
ALTER TABLE `equipo`
  ADD PRIMARY KEY (`idequipo`),
  ADD KEY `idmarca_equipo` (`idmarca_equipo`),
  ADD KEY `idmodelo_equipo` (`idmodelo_equipo`);

--
-- Indices de la tabla `marca`
--
ALTER TABLE `marca`
  ADD PRIMARY KEY (`idmarca`),
  ADD KEY `nombreMarca` (`nombreMarca`);

--
-- Indices de la tabla `modelo`
--
ALTER TABLE `modelo`
  ADD PRIMARY KEY (`idmodelo`),
  ADD KEY `nombreModelo` (`nombreModelo`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`idpermiso`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indices de la tabla `sexo`
--
ALTER TABLE `sexo`
  ADD PRIMARY KEY (`idsexo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idusuario`),
  ADD UNIQUE KEY `uq_usuarios_username` (`username`),
  ADD KEY `idempleado` (`idempleado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `idarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `asignacion`
--
ALTER TABLE `asignacion`
  MODIFY `idasignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `idbitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `idcargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `equipo`
--
ALTER TABLE `equipo`
  MODIFY `idequipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `marca`
--
ALTER TABLE `marca`
  MODIFY `idmarca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `modelo`
--
ALTER TABLE `modelo`
  MODIFY `idmodelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `sexo`
--
ALTER TABLE `sexo`
  MODIFY `idsexo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignacion`
--
ALTER TABLE `asignacion`
  ADD CONSTRAINT `asignacion_ibfk_1` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`),
  ADD CONSTRAINT `idempleado` FOREIGN KEY (`idempleado`) REFERENCES `empleados` (`idempleado`),
  ADD CONSTRAINT `idequipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_FK` FOREIGN KEY (`idcargo`) REFERENCES `cargos` (`idcargo`),
  ADD CONSTRAINT `empleados_FK_1` FOREIGN KEY (`idarea`) REFERENCES `areas` (`idarea`),
  ADD CONSTRAINT `empleados_FK_2` FOREIGN KEY (`idsexo`) REFERENCES `sexo` (`idsexo`);

--
-- Filtros para la tabla `equipo`
--
ALTER TABLE `equipo`
  ADD CONSTRAINT `equipo_ibfk_1` FOREIGN KEY (`idmodelo_equipo`) REFERENCES `modelo` (`idmodelo`),
  ADD CONSTRAINT `equipo_ibfk_2` FOREIGN KEY (`idmarca_equipo`) REFERENCES `marca` (`idmarca`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
