-- GestActivos: Licencias de software - Parte 1
-- Fundamento de datos, historial y permiso independiente.
-- Aplicar sobre una base que ya tenga proveedores y mantenimientos.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `software` (
  `idsoftware` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `fabricante` varchar(120) NOT NULL,
  `version` varchar(60) NOT NULL DEFAULT '',
  `edicion` varchar(100) NOT NULL DEFAULT '',
  `categoria` varchar(80) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idsoftware`),
  UNIQUE KEY `uq_software_producto` (`nombre`,`fabricante`,`version`,`edicion`),
  KEY `idx_software_activo_nombre` (`activo`,`nombre`),
  CONSTRAINT `chk_software_activo` CHECK (`activo` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `licencias` (
  `idlicencia` int(11) NOT NULL AUTO_INCREMENT,
  `idsoftware` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `codigo_licencia` varchar(24) DEFAULT NULL,
  `modalidad` varchar(20) NOT NULL,
  `metrica` varchar(30) NOT NULL,
  `cantidad_total` int(10) unsigned DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `renovacion_automatica` tinyint(1) NOT NULL DEFAULT 0,
  `reutilizable` tinyint(1) NOT NULL DEFAULT 1,
  `costo_total` decimal(12,2) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'HNL',
  `factura` varchar(100) DEFAULT NULL,
  `orden_compra` varchar(100) DEFAULT NULL,
  `numero_contrato` varchar(100) DEFAULT NULL,
  `licenciado_a_nombre` varchar(150) DEFAULT NULL,
  `licenciado_a_correo` varchar(150) DEFAULT NULL,
  `clave_cifrada` text DEFAULT NULL,
  `clave_mascara` varchar(40) DEFAULT NULL,
  `clave_huella` char(64) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idlicencia`),
  UNIQUE KEY `uq_licencias_codigo` (`codigo_licencia`),
  KEY `idx_licencias_software` (`idsoftware`),
  KEY `idx_licencias_proveedor` (`idproveedor`),
  KEY `idx_licencias_vencimiento` (`activo`,`fecha_vencimiento`),
  KEY `idx_licencias_modalidad_metrica` (`modalidad`,`metrica`),
  CONSTRAINT `fk_licencias_software` FOREIGN KEY (`idsoftware`) REFERENCES `software` (`idsoftware`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencias_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencias_modalidad` CHECK (`modalidad` IN ('Perpetua','Suscripción','Prueba')),
  CONSTRAINT `chk_licencias_metrica` CHECK (`metrica` IN ('Usuario','Dispositivo','Concurrente','Corporativa','Servidor/Procesador')),
  CONSTRAINT `chk_licencias_cantidad` CHECK (`cantidad_total` IS NULL OR `cantidad_total` > 0),
  CONSTRAINT `chk_licencias_costo` CHECK (`costo_total` IS NULL OR `costo_total` >= 0),
  CONSTRAINT `chk_licencias_fechas` CHECK (`fecha_inicio` IS NULL OR `fecha_vencimiento` IS NULL OR `fecha_vencimiento` >= `fecha_inicio`),
  CONSTRAINT `chk_licencias_banderas` CHECK (`renovacion_automatica` IN (0,1) AND `reutilizable` IN (0,1) AND `activo` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `licencia_cupos` (
  `idcupo` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `numero_cupo` int(10) unsigned NOT NULL,
  `etiqueta` varchar(100) DEFAULT NULL,
  `clave_cifrada` text DEFAULT NULL,
  `clave_mascara` varchar(40) DEFAULT NULL,
  `clave_huella` char(64) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_retiro` datetime DEFAULT NULL,
  `motivo_retiro` varchar(500) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcupo`),
  UNIQUE KEY `uq_licencia_cupo_numero` (`idlicencia`,`numero_cupo`),
  UNIQUE KEY `uq_licencia_cupo_compuesto` (`idlicencia`,`idcupo`),
  KEY `idx_licencia_cupos_activos` (`idlicencia`,`activo`),
  CONSTRAINT `fk_licencia_cupos_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_cupos_numero` CHECK (`numero_cupo` > 0),
  CONSTRAINT `chk_licencia_cupos_activo` CHECK (`activo` IN (0,1)),
  CONSTRAINT `chk_licencia_cupos_retiro` CHECK ((`activo` = 1 AND `fecha_retiro` IS NULL) OR (`activo` = 0 AND `fecha_retiro` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `licencia_asignaciones` (
  `idasignacion_licencia` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idcupo` int(11) DEFAULT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idequipo` int(11) DEFAULT NULL,
  `correo_cuenta` varchar(150) DEFAULT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` datetime DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario_asignacion` int(11) DEFAULT NULL,
  `idusuario_devolucion` int(11) DEFAULT NULL,
  `cupo_asignado_activo` int(11) GENERATED ALWAYS AS (CASE WHEN `activa` = 1 THEN `idcupo` ELSE NULL END) STORED,
  PRIMARY KEY (`idasignacion_licencia`),
  UNIQUE KEY `uq_licencia_asignacion_compuesta` (`idlicencia`,`idasignacion_licencia`),
  UNIQUE KEY `uq_licencia_cupo_asignado_activo` (`cupo_asignado_activo`),
  KEY `idx_licencia_asignaciones_licencia` (`idlicencia`,`activa`),
  KEY `idx_licencia_asignaciones_empleado` (`idempleado`,`activa`),
  KEY `idx_licencia_asignaciones_equipo` (`idequipo`,`activa`),
  KEY `idx_licencia_asignaciones_usuario_asigna` (`idusuario_asignacion`),
  KEY `idx_licencia_asignaciones_usuario_devuelve` (`idusuario_devolucion`),
  CONSTRAINT `fk_licencia_asignaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_cupo` FOREIGN KEY (`idlicencia`,`idcupo`) REFERENCES `licencia_cupos` (`idlicencia`,`idcupo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_empleado` FOREIGN KEY (`idempleado`) REFERENCES `empleados` (`idempleado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_usuario_asigna` FOREIGN KEY (`idusuario_asignacion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_usuario_devuelve` FOREIGN KEY (`idusuario_devolucion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_asignacion_destino` CHECK (((`idempleado` IS NOT NULL) + (`idequipo` IS NOT NULL)) = 1),
  CONSTRAINT `chk_licencia_asignacion_estado` CHECK ((`activa` = 1 AND `fecha_devolucion` IS NULL) OR (`activa` = 0 AND `fecha_devolucion` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `licencia_instalaciones` (
  `idinstalacion` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idasignacion_licencia` int(11) DEFAULT NULL,
  `idequipo` int(11) NOT NULL,
  `version_instalada` varchar(80) DEFAULT NULL,
  `fecha_instalacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_desinstalacion` datetime DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario_registro` int(11) DEFAULT NULL,
  `idusuario_retiro` int(11) DEFAULT NULL,
  `licencia_instalada_activa` int(11) GENERATED ALWAYS AS (CASE WHEN `activa` = 1 THEN `idlicencia` ELSE NULL END) STORED,
  `equipo_instalacion_activa` int(11) GENERATED ALWAYS AS (CASE WHEN `activa` = 1 THEN `idequipo` ELSE NULL END) STORED,
  PRIMARY KEY (`idinstalacion`),
  UNIQUE KEY `uq_licencia_instalacion_activa` (`licencia_instalada_activa`,`equipo_instalacion_activa`),
  KEY `idx_licencia_instalaciones_licencia` (`idlicencia`,`activa`),
  KEY `idx_licencia_instalaciones_equipo` (`idequipo`,`activa`),
  KEY `idx_licencia_instalaciones_asignacion` (`idlicencia`,`idasignacion_licencia`),
  KEY `idx_licencia_instalaciones_usuario_registro` (`idusuario_registro`),
  KEY `idx_licencia_instalaciones_usuario_retiro` (`idusuario_retiro`),
  CONSTRAINT `fk_licencia_instalaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_asignacion` FOREIGN KEY (`idlicencia`,`idasignacion_licencia`) REFERENCES `licencia_asignaciones` (`idlicencia`,`idasignacion_licencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_usuario_registro` FOREIGN KEY (`idusuario_registro`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_usuario_retiro` FOREIGN KEY (`idusuario_retiro`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_instalacion_estado` CHECK ((`activa` = 1 AND `fecha_desinstalacion` IS NULL) OR (`activa` = 0 AND `fecha_desinstalacion` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `licencia_renovaciones` (
  `idrenovacion` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `fecha_renovacion` date NOT NULL,
  `fecha_vencimiento_anterior` date DEFAULT NULL,
  `fecha_vencimiento_nueva` date DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'HNL',
  `factura` varchar(100) DEFAULT NULL,
  `orden_compra` varchar(100) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idrenovacion`),
  KEY `idx_licencia_renovaciones_licencia` (`idlicencia`,`fecha_renovacion`),
  KEY `idx_licencia_renovaciones_proveedor` (`idproveedor`),
  KEY `idx_licencia_renovaciones_usuario` (`idusuario`),
  CONSTRAINT `fk_licencia_renovaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_renovaciones_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_renovaciones_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_renovaciones_costo` CHECK (`costo` IS NULL OR `costo` >= 0),
  CONSTRAINT `chk_licencia_renovaciones_fechas` CHECK (`fecha_vencimiento_anterior` IS NULL OR `fecha_vencimiento_nueva` IS NULL OR `fecha_vencimiento_nueva` >= `fecha_vencimiento_anterior`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET @existia_permiso_licencias := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permisos'
    AND COLUMN_NAME='licencias'
);

ALTER TABLE `permisos`
  ADD COLUMN IF NOT EXISTS `licencias` tinyint(1) NOT NULL DEFAULT 0 AFTER `mantenimientos`;

SET @sql_permiso_licencias := IF(
  @existia_permiso_licencias=0,
  'UPDATE `permisos` SET `licencias`=`datosmaestros`',
  'SELECT "El permiso licencias ya estaba configurado"'
);
PREPARE stmt_permiso_licencias FROM @sql_permiso_licencias;
EXECUTE stmt_permiso_licencias;
DEALLOCATE PREPARE stmt_permiso_licencias;

SELECT 'Licencias Parte 1 aplicada correctamente.' AS resultado;
