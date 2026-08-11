-- GestActivos: Licencias de software - Parte 3
-- Cupos numerados, asignacion/devolucion e integridad por destino.
-- Aplicar despues de migracion_licencias_parte1_20260731.sql.

SET NAMES utf8mb4;

ALTER TABLE `licencia_asignaciones`
  ADD COLUMN IF NOT EXISTS `motivo_devolucion` varchar(500) DEFAULT NULL AFTER `fecha_devolucion`,
  ADD COLUMN IF NOT EXISTS `empleado_asignado_activo` int(11)
    GENERATED ALWAYS AS (CASE WHEN `activa` = 1 THEN `idempleado` ELSE NULL END) STORED,
  ADD COLUMN IF NOT EXISTS `equipo_asignado_activo` int(11)
    GENERATED ALWAYS AS (CASE WHEN `activa` = 1 THEN `idequipo` ELSE NULL END) STORED;

SET @existe_uq_licencia_empleado_activo := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='licencia_asignaciones'
    AND INDEX_NAME='uq_licencia_empleado_activo'
);
SET @sql_uq_licencia_empleado_activo := IF(
  @existe_uq_licencia_empleado_activo=0,
  'ALTER TABLE `licencia_asignaciones` ADD UNIQUE KEY `uq_licencia_empleado_activo` (`idlicencia`,`empleado_asignado_activo`)',
  'SELECT "uq_licencia_empleado_activo ya existe"'
);
PREPARE stmt_uq_licencia_empleado FROM @sql_uq_licencia_empleado_activo;
EXECUTE stmt_uq_licencia_empleado;
DEALLOCATE PREPARE stmt_uq_licencia_empleado;

SET @existe_uq_licencia_equipo_activo := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='licencia_asignaciones'
    AND INDEX_NAME='uq_licencia_equipo_activo'
);
SET @sql_uq_licencia_equipo_activo := IF(
  @existe_uq_licencia_equipo_activo=0,
  'ALTER TABLE `licencia_asignaciones` ADD UNIQUE KEY `uq_licencia_equipo_activo` (`idlicencia`,`equipo_asignado_activo`)',
  'SELECT "uq_licencia_equipo_activo ya existe"'
);
PREPARE stmt_uq_licencia_equipo FROM @sql_uq_licencia_equipo_activo;
EXECUTE stmt_uq_licencia_equipo;
DEALLOCATE PREPARE stmt_uq_licencia_equipo;

-- Genera los cupos que correspondan a las licencias finitas existentes.
-- INSERT IGNORE hace que la migracion sea repetible y nunca revive un cupo retirado.
DROP PROCEDURE IF EXISTS `gest_generar_cupos_licencias`;
DELIMITER $$
CREATE PROCEDURE `gest_generar_cupos_licencias`()
BEGIN
  DECLARE terminado tinyint DEFAULT 0;
  DECLARE licencia_id int;
  DECLARE cantidad int;
  DECLARE numero int;
  DECLARE cursor_licencias CURSOR FOR
    SELECT idlicencia, cantidad_total
    FROM licencias
    WHERE cantidad_total IS NOT NULL
    ORDER BY idlicencia;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET terminado=1;

  OPEN cursor_licencias;
  ciclo: LOOP
    FETCH cursor_licencias INTO licencia_id, cantidad;
    IF terminado=1 THEN
      LEAVE ciclo;
    END IF;
    SET numero=1;
    WHILE numero<=cantidad DO
      INSERT IGNORE INTO licencia_cupos (idlicencia,numero_cupo)
      VALUES (licencia_id,numero);
      SET numero=numero+1;
    END WHILE;
  END LOOP;
  CLOSE cursor_licencias;
END$$
DELIMITER ;

CALL `gest_generar_cupos_licencias`();
DROP PROCEDURE IF EXISTS `gest_generar_cupos_licencias`;

SELECT 'Licencias Parte 3 aplicada correctamente.' AS resultado;
