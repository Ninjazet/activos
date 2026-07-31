-- GestActivos: proveedores y mantenimientos
-- Fecha: 2026-07-31
-- Aplicar una sola vez sobre una base de Fase 1 o posterior.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `proveedores` (
  `idproveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `rtn` varchar(30) DEFAULT NULL,
  `contacto` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idproveedor`),
  UNIQUE KEY `uq_proveedores_nombre` (`nombre`),
  UNIQUE KEY `uq_proveedores_rtn` (`rtn`),
  KEY `idx_proveedores_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `equipo`
  ADD COLUMN IF NOT EXISTS `idproveedor` int(11) DEFAULT NULL AFTER `idmodelo_equipo`;

SET @existe_indice_equipo_proveedor := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='equipo'
    AND INDEX_NAME='idx_equipo_proveedor'
);
SET @sql_indice_equipo_proveedor := IF(
  @existe_indice_equipo_proveedor=0,
  'ALTER TABLE `equipo` ADD KEY `idx_equipo_proveedor` (`idproveedor`)',
  'SELECT "idx_equipo_proveedor ya existe"'
);
PREPARE stmt FROM @sql_indice_equipo_proveedor;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @existe_fk_equipo_proveedor := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='equipo'
    AND CONSTRAINT_NAME='fk_equipo_proveedor'
);
SET @sql_fk_equipo_proveedor := IF(
  @existe_fk_equipo_proveedor=0,
  'ALTER TABLE `equipo` ADD CONSTRAINT `fk_equipo_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "fk_equipo_proveedor ya existe"'
);
PREPARE stmt FROM @sql_fk_equipo_proveedor;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `mantenimientos` (
  `idmantenimiento` int(11) NOT NULL AUTO_INCREMENT,
  `idequipo` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `idasignacion_origen` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Abierto',
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `descripcion_problema` varchar(1000) NOT NULL,
  `diagnostico` varchar(1000) DEFAULT NULL,
  `trabajo_realizado` varchar(1000) DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `resultado` varchar(30) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `estado_anterior_equipo` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `origen` varchar(20) NOT NULL DEFAULT 'Manual',
  `idusuario_apertura` int(11) DEFAULT NULL,
  `idusuario_cierre` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idmantenimiento`),
  UNIQUE KEY `uq_mantenimiento_asignacion_origen` (`idasignacion_origen`),
  KEY `idx_mantenimiento_equipo_estado` (`idequipo`,`estado`),
  KEY `idx_mantenimiento_estado_fecha` (`estado`,`fecha_ingreso`),
  KEY `idx_mantenimiento_proveedor` (`idproveedor`),
  KEY `idx_mantenimiento_usuario_apertura` (`idusuario_apertura`),
  KEY `idx_mantenimiento_usuario_cierre` (`idusuario_cierre`),
  CONSTRAINT `fk_mantenimiento_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_asignacion` FOREIGN KEY (`idasignacion_origen`) REFERENCES `asignacion` (`idasignacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_usuario_apertura` FOREIGN KEY (`idusuario_apertura`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_usuario_cierre` FOREIGN KEY (`idusuario_cierre`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET @existia_permiso_mantenimientos := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permisos'
    AND COLUMN_NAME='mantenimientos'
);

ALTER TABLE `permisos`
  ADD COLUMN IF NOT EXISTS `mantenimientos` tinyint(1) NOT NULL DEFAULT 0 AFTER `transacciones`;

SET @sql_permisos_iniciales := IF(
  @existia_permiso_mantenimientos=0,
  'UPDATE `permisos` SET `mantenimientos`=`transacciones`',
  'SELECT "El permiso mantenimientos ya estaba configurado"'
);
PREPARE stmt FROM @sql_permisos_iniciales;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Todo equipo que ya estaba en mantenimiento recibe un historial inicial.
INSERT INTO `mantenimientos` (
  `idequipo`, `tipo`, `estado`, `descripcion_problema`,
  `estado_anterior_equipo`, `origen`
)
SELECT
  eq.`idequipo`, 'Correctivo', 'Abierto',
  'Registro inicial generado al habilitar el módulo de mantenimientos.',
  1, 'Migración'
FROM `equipo` eq
WHERE eq.`activo`=1
  AND eq.`estado_equipo`=3
  AND NOT EXISTS (
    SELECT 1 FROM `mantenimientos` m
    WHERE m.`idequipo`=eq.`idequipo`
      AND m.`estado` IN ('Abierto','En proceso')
  );

SELECT 'Migración de proveedores y mantenimientos aplicada correctamente.' AS resultado;
