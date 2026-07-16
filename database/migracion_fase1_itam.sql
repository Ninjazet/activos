-- ============================================================
-- GestActivos - Fase 1 ITAM: datos de compra y estado del equipo
-- Puede ejecutarse más de una vez sobre la base gestactivos.
-- Estados: 1 Disponible, 2 Asignado, 3 En mantenimiento,
--          4 Perdido o robado, 5 Dado de baja.
-- ============================================================

ALTER TABLE `equipo`
  ADD COLUMN IF NOT EXISTS `fecha_compra` DATE NULL,
  ADD COLUMN IF NOT EXISTS `costo` DECIMAL(12,2) NULL,
  ADD COLUMN IF NOT EXISTS `factura` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `vencimiento_garantia` DATE NULL,
  ADD COLUMN IF NOT EXISTS `estado_equipo` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `numero_serie` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `codigo_activo` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `tipo_equipo` VARCHAR(50) NOT NULL DEFAULT 'Otro';

ALTER TABLE `empleados`
  ADD COLUMN IF NOT EXISTS `correo` VARCHAR(150) NULL;

-- Genera códigos para los registros que ya existían.
UPDATE `equipo`
SET `codigo_activo` = CONCAT('EQ-', LPAD(`idequipo`, 4, '0'))
WHERE `codigo_activo` IS NULL OR `codigo_activo` = '';

-- Garantiza que ninguna etiqueta de activo se repita.
SET @existe_codigo_unico := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'equipo'
    AND index_name = 'uq_equipo_codigo_activo'
);
SET @sql_codigo_unico := IF(@existe_codigo_unico = 0,
  'ALTER TABLE `equipo` ADD UNIQUE KEY `uq_equipo_codigo_activo` (`codigo_activo`)',
  'SELECT "uq_equipo_codigo_activo ya existe"');
PREPARE stmt_codigo FROM @sql_codigo_unico;
EXECUTE stmt_codigo;
DEALLOCATE PREPARE stmt_codigo;
-- Conserva la realidad actual del inventario al aplicar la migración.
UPDATE `equipo` eq
SET eq.`estado_equipo` = 1
WHERE eq.`activo` = 1
  AND eq.`estado_equipo` = 2
  AND NOT EXISTS (
    SELECT 1 FROM `asignacion` asg
    WHERE asg.`idequipo` = eq.`idequipo` AND asg.`activa` = 1
  );

UPDATE `equipo`
SET `estado_equipo` = 5
WHERE `activo` = 0;
UPDATE `equipo` eq
SET eq.`estado_equipo` = 2
WHERE EXISTS (
  SELECT 1 FROM `asignacion` asg
  WHERE asg.`idequipo` = eq.`idequipo` AND asg.`activa` = 1
);

SELECT 'Fase 1 ITAM aplicada correctamente.' AS resultado;