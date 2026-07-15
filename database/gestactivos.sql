-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2023 at 12:29 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `asignacion`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `idarea` int(11) NOT NULL,
  `descripcionarea` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`idarea`, `descripcionarea`) VALUES
(1, 'Gerencia General'),
(2, 'Administracion'),
(3, 'Informatica'),
(4, 'Mantenimiento'),
(5, 'Soporte IT'),
(6, 'Recursos Humanos'),
(7, 'Contabilidad'),
(8, 'Recepcion');

-- --------------------------------------------------------

--
-- Table structure for table `asignacion`
--

CREATE TABLE `asignacion` (
  `idasignacion` int(11) NOT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idequipo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `asignacion`
--

INSERT INTO `asignacion` (`idasignacion`, `idempleado`, `idequipo`) VALUES
(1, 10, 2);

-- --------------------------------------------------------

--
-- Table structure for table `cargos`
--

CREATE TABLE `cargos` (
  `idcargo` int(11) NOT NULL,
  `descripcioncargo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cargos`
--

INSERT INTO `cargos` (`idcargo`, `descripcioncargo`) VALUES
(1, 'Administrador General'),
(2, 'Jefe de Contabilidad'),
(3, 'Auxiliar Contable'),
(4, 'Gerente Administrativo'),
(5, 'Recepcionista'),
(6, 'Auxiliar de Soporte IT'),
(7, 'Asistente de Recursos Humanos'),
(8, 'Gerente Comercial'),
(9, ''),
(10, ''),
(11, 'ww'),
(12, 'ww'),
(13, 'ww'),
(14, 'ww'),
(15, 'ww');

-- --------------------------------------------------------

--
-- Table structure for table `empleados`
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
  `idsexo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `empleados`
--

INSERT INTO `empleados` (`idempleado`, `nombre`, `apellidos`, `edad`, `telefono`, `direccion`, `imagen`, `idcargo`, `idarea`, `idsexo`) VALUES
(1, 'Edwin Enrique', 'Martinez Diaz', 32, 32650620, 'Santa Marta', 'imagenes/empleados/edwin.jpg', 1, 1, 1),
(2, 'Denia Maricela', 'Nuñez Velasquez', 28, 33003400, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 2, 2, 2),
(4, 'Eduardo', 'Papadopolo', 25, 89982210, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 3, 3, 1),
(5, 'Karla Patricia', 'Nuñez Garcia', 30, 33456790, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 4, 4, 2),
(6, 'Henry ', 'Cavill', 45, 99807765, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 5, 5, 1),
(7, 'Jennifer Esthefania', 'Carbajal Ponce', 40, 88778866, 'San Barbara', 'imagenes/empleados/avatar2.png', 2, 2, 2),
(8, 'Juan Ramon', 'Ledezma Velasquez', 36, 99807766, 'Santa Barbara', 'imagenes/empleados/avatar2.png', 3, 2, 1),
(9, 'Adelina', 'Flores Moreno', 29, 88973320, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 4, 5, 2),
(10, 'Diana Emilia', 'Medina Ordoñez', 30, 33456787, 'Tegucigalpa', 'imagenes/empleados/avatar2.png', 6, 7, 2),
(11, 'wilfredo', 'castellanos', 21, 98679665, 'santa barbara santa barbara', 'imagenes/empleados/avatar2.png', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `equipo`
--

CREATE TABLE `equipo` (
  `idequipo` int(11) NOT NULL,
  `idmarca_equipo` int(11) NOT NULL,
  `idmodelo_equipo` int(11) NOT NULL,
  `imagen` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `equipo`
--

INSERT INTO `equipo` (`idequipo`, `idmarca_equipo`, `idmodelo_equipo`, `imagen`) VALUES
(1, 2, 4, ''),
(2, 1, 2, ''),
(3, 1, 1, 'imagenes/equipos/'),
(4, 1, 1, 'imagenes/equipos/');

-- --------------------------------------------------------

--
-- Table structure for table `marca`
--

CREATE TABLE `marca` (
  `idmarca` int(11) NOT NULL,
  `nombreMarca` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `marca`
--

INSERT INTO `marca` (`idmarca`, `nombreMarca`) VALUES
(2, 'HP'),
(1, 'Lenovo');

-- --------------------------------------------------------

--
-- Table structure for table `modelo`
--

CREATE TABLE `modelo` (
  `idmodelo` int(11) NOT NULL,
  `nombreModelo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `modelo`
--

INSERT INTO `modelo` (`idmodelo`, `nombreModelo`) VALUES
(4, 'HP Pavilion'),
(3, 'HP Spectre'),
(5, 'Legion 5'),
(2, 'Legion Pro 5'),
(1, 'Legion Pro 7i');

-- --------------------------------------------------------

--
-- Table structure for table `permisos`
--

CREATE TABLE `permisos` (
  `idpermiso` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `datosmaestros` tinyint(1) NOT NULL,
  `transacciones` tinyint(1) NOT NULL,
  `consultas` tinyint(1) NOT NULL,
  `reportes` tinyint(1) NOT NULL,
  `seguridad` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `permisos`
--

INSERT INTO `permisos` (`idpermiso`, `idusuario`, `datosmaestros`, `transacciones`, `consultas`, `reportes`, `seguridad`) VALUES
(1, 1, 1, 1, 1, 1, 1),
(2, 2, 1, 0, 1, 0, 0),
(3, 3, 1, 0, 1, 0, 0),
(4, 4, 0, 0, 0, 1, 0),
(5, 5, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sexo`
--

CREATE TABLE `sexo` (
  `idsexo` int(11) NOT NULL,
  `descripcionsexo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sexo`
--

INSERT INTO `sexo` (`idsexo`, `descripcionsexo`) VALUES
(1, 'Masculino'),
(2, 'Femenino');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `idusuario` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `pass` varchar(15) NOT NULL,
  `idempleado` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`idusuario`, `username`, `pass`, `idempleado`, `estado`) VALUES
(1, 'emartinez', '12345', 1, 1),
(2, 'cpavon', '123', 2, 0),
(3, 'jgarcia', '12345', 3, 1),
(4, 'ariana', '123', 36, 1),
(5, 'tecwil', '1234', 11, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`idarea`);

--
-- Indexes for table `asignacion`
--
ALTER TABLE `asignacion`
  ADD PRIMARY KEY (`idasignacion`),
  ADD KEY `idequipo_idx` (`idequipo`),
  ADD KEY `idempleado_idx` (`idempleado`);

--
-- Indexes for table `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`idcargo`);

--
-- Indexes for table `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`idempleado`),
  ADD KEY `idcargo_idx` (`idcargo`),
  ADD KEY `idarea_idx` (`idarea`),
  ADD KEY `empleados_FK_2` (`idsexo`);

--
-- Indexes for table `equipo`
--
ALTER TABLE `equipo`
  ADD PRIMARY KEY (`idequipo`),
  ADD KEY `idmarca_equipo` (`idmarca_equipo`),
  ADD KEY `idmodelo_equipo` (`idmodelo_equipo`);

--
-- Indexes for table `marca`
--
ALTER TABLE `marca`
  ADD PRIMARY KEY (`idmarca`),
  ADD KEY `nombreMarca` (`nombreMarca`);

--
-- Indexes for table `modelo`
--
ALTER TABLE `modelo`
  ADD PRIMARY KEY (`idmodelo`),
  ADD KEY `nombreModelo` (`nombreModelo`);

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`idpermiso`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indexes for table `sexo`
--
ALTER TABLE `sexo`
  ADD PRIMARY KEY (`idsexo`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idusuario`),
  ADD KEY `idempleado` (`idempleado`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `idarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `asignacion`
--
ALTER TABLE `asignacion`
  MODIFY `idasignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cargos`
--
ALTER TABLE `cargos`
  MODIFY `idcargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `empleados`
--
ALTER TABLE `empleados`
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `equipo`
--
ALTER TABLE `equipo`
  MODIFY `idequipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `marca`
--
ALTER TABLE `marca`
  MODIFY `idmarca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `modelo`
--
ALTER TABLE `modelo`
  MODIFY `idmodelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sexo`
--
ALTER TABLE `sexo`
  MODIFY `idsexo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asignacion`
--
ALTER TABLE `asignacion`
  ADD CONSTRAINT `asignacion_ibfk_1` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`),
  ADD CONSTRAINT `idempleado` FOREIGN KEY (`idempleado`) REFERENCES `empleados` (`idempleado`),
  ADD CONSTRAINT `idequipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`);

--
-- Constraints for table `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_FK` FOREIGN KEY (`idcargo`) REFERENCES `cargos` (`idcargo`),
  ADD CONSTRAINT `empleados_FK_1` FOREIGN KEY (`idarea`) REFERENCES `areas` (`idarea`),
  ADD CONSTRAINT `empleados_FK_2` FOREIGN KEY (`idsexo`) REFERENCES `sexo` (`idsexo`);

--
-- Constraints for table `equipo`
--
ALTER TABLE `equipo`
  ADD CONSTRAINT `equipo_ibfk_1` FOREIGN KEY (`idmodelo_equipo`) REFERENCES `modelo` (`idmodelo`),
  ADD CONSTRAINT `equipo_ibfk_2` FOREIGN KEY (`idmarca_equipo`) REFERENCES `marca` (`idmarca`);

--
-- Constraints for table `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
