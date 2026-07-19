-- ============================================================
-- GestActivos - Fase 3: entrega, devolución y trazabilidad
-- Ejecutar sobre la base existente `gestactivos` después de
-- migracion_fase1_itam.sql y migracion_v3.sql.
-- Es idempotente: puede ejecutarse nuevamente sin duplicar campos.
-- ============================================================

ALTER TABLE `asignacion`
  ADD COLUMN IF NOT EXISTS `condicion_entrega` VARCHAR(30) NOT NULL DEFAULT 'Bueno' AFTER `fecha_asignacion`,
  ADD COLUMN IF NOT EXISTS `entrega_cargador` TINYINT(1) NOT NULL DEFAULT 0 AFTER `condicion_entrega`,
  ADD COLUMN IF NOT EXISTS `entrega_maletin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `entrega_cargador`,
  ADD COLUMN IF NOT EXISTS `entrega_otros` VARCHAR(255) NULL AFTER `entrega_maletin`,
  ADD COLUMN IF NOT EXISTS `observaciones_entrega` VARCHAR(500) NULL AFTER `entrega_otros`,
  ADD COLUMN IF NOT EXISTS `condicion_devolucion` VARCHAR(30) NULL AFTER `fecha_devolucion`,
  ADD COLUMN IF NOT EXISTS `devolucion_cargador` TINYINT(1) NULL AFTER `condicion_devolucion`,
  ADD COLUMN IF NOT EXISTS `devolucion_maletin` TINYINT(1) NULL AFTER `devolucion_cargador`,
  ADD COLUMN IF NOT EXISTS `devolucion_otros` VARCHAR(255) NULL AFTER `devolucion_maletin`,
  ADD COLUMN IF NOT EXISTS `observaciones_devolucion` VARCHAR(500) NULL AFTER `devolucion_otros`,
  ADD COLUMN IF NOT EXISTS `estado_equipo_devolucion` TINYINT UNSIGNED NULL AFTER `observaciones_devolucion`,
  ADD COLUMN IF NOT EXISTS `firma_devolucion` VARCHAR(255) NULL AFTER `firma_fecha`,
  ADD COLUMN IF NOT EXISTS `firma_devolucion_fecha` DATETIME NULL AFTER `firma_devolucion`,
  ADD COLUMN IF NOT EXISTS `idusuario_devolucion` INT(11) NULL AFTER `firma_devolucion_fecha`;

-- Normaliza asignaciones anteriores sin alterar su historial.
UPDATE `asignacion`
SET `condicion_entrega` = 'Bueno'
WHERE `condicion_entrega` IS NULL OR TRIM(`condicion_entrega`) = '';

-- Índices para las validaciones de asignaciones abiertas y auditoría.
SET @existe_idx_equipo_activa := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'asignacion'
    AND index_name = 'idx_asignacion_equipo_activa'
);
SET @sql_idx_equipo_activa := IF(@existe_idx_equipo_activa = 0,
  'ALTER TABLE `asignacion` ADD KEY `idx_asignacion_equipo_activa` (`idequipo`, `activa`)',
  'SELECT "idx_asignacion_equipo_activa ya existe"');
PREPARE stmt_idx_equipo FROM @sql_idx_equipo_activa;
EXECUTE stmt_idx_equipo;
DEALLOCATE PREPARE stmt_idx_equipo;

SET @existe_idx_empleado_activa := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'asignacion'
    AND index_name = 'idx_asignacion_empleado_activa'
);
SET @sql_idx_empleado_activa := IF(@existe_idx_empleado_activa = 0,
  'ALTER TABLE `asignacion` ADD KEY `idx_asignacion_empleado_activa` (`idempleado`, `activa`)',
  'SELECT "idx_asignacion_empleado_activa ya existe"');
PREPARE stmt_idx_empleado FROM @sql_idx_empleado_activa;
EXECUTE stmt_idx_empleado;
DEALLOCATE PREPARE stmt_idx_empleado;

SET @existe_idx_usuario_devolucion := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'asignacion'
    AND index_name = 'idx_asignacion_usuario_devolucion'
);
SET @sql_idx_usuario_devolucion := IF(@existe_idx_usuario_devolucion = 0,
  'ALTER TABLE `asignacion` ADD KEY `idx_asignacion_usuario_devolucion` (`idusuario_devolucion`)',
  'SELECT "idx_asignacion_usuario_devolucion ya existe"');
PREPARE stmt_idx_usuario FROM @sql_idx_usuario_devolucion;
EXECUTE stmt_idx_usuario;
DEALLOCATE PREPARE stmt_idx_usuario;

-- Relaciona la devolución con el usuario de IT que la recibió.
SET @existe_fk_usuario_devolucion := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE constraint_schema = DATABASE()
    AND table_name = 'asignacion'
    AND constraint_name = 'fk_asignacion_usuario_devolucion'
);
SET @sql_fk_usuario_devolucion := IF(@existe_fk_usuario_devolucion = 0,
  'ALTER TABLE `asignacion` ADD CONSTRAINT `fk_asignacion_usuario_devolucion` FOREIGN KEY (`idusuario_devolucion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "fk_asignacion_usuario_devolucion ya existe"');
PREPARE stmt_fk_usuario FROM @sql_fk_usuario_devolucion;
EXECUTE stmt_fk_usuario;
DEALLOCATE PREPARE stmt_fk_usuario;

SELECT 'Fase 3 de asignaciones aplicada correctamente.' AS resultado;
