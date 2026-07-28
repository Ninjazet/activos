-- GestActivos - Limpieza controlada y endurecimiento del esquema
-- Fecha: 2026-07-28
--
-- Esta migracion SI corrige:
--   * cargos de prueba duplicados;
--   * textos con codificacion danada;
--   * referencias conocidas a imagenes inexistentes;
--   * indices unicos y una llave foranea duplicada;
--   * tablas heredadas que todavia usan utf8 en lugar de utf8mb4.
--
-- Esta migracion NO elimina equipos, empleados ni correos DEMO.
-- Al final solo presenta un inventario de esos registros para revision manual.

SET NAMES utf8mb4;

-- --------------------------------------------------------------------------
-- 1. Limpieza de datos segura y reversible mediante el respaldo previo
-- --------------------------------------------------------------------------
START TRANSACTION;

-- "ww" era un cargo de prueba y ninguno de sus registros esta referenciado.
DELETE c
FROM cargos c
LEFT JOIN empleados e ON e.idcargo = c.idcargo
WHERE LOWER(TRIM(c.descripcioncargo)) = 'ww'
  AND e.idempleado IS NULL;

-- Se conserva el primer Facturador y se redirige cualquier referencia futura.
UPDATE empleados SET idcargo = 16 WHERE idcargo = 17;
UPDATE cargos SET descripcioncargo = 'Facturador' WHERE idcargo = 16;
DELETE FROM cargos WHERE idcargo = 17;

UPDATE areas SET descripcionarea = 'Datos y Analítica' WHERE idarea = 16;
UPDATE areas SET descripcionarea = 'Auditoría TI' WHERE idarea = 19;

UPDATE cargos SET descripcioncargo = 'Soporte Técnico N2' WHERE idcargo = 26;
UPDATE cargos SET descripcioncargo = 'Líder Técnico' WHERE idcargo = 27;
UPDATE cargos SET descripcioncargo = 'Técnico de Campo' WHERE idcargo = 30;

UPDATE equipo
SET tipo_equipo = 'Teléfono'
WHERE tipo_equipo = 'Tel├®fono';

-- Las fotos de estos registros no existen. Se usan valores que activan
-- correctamente los recursos predeterminados de la aplicacion.
UPDATE equipo SET imagen = '' WHERE idequipo IN (3, 4, 5);
UPDATE empleados
SET imagen = 'public/img/empleados/avatar1.png'
WHERE idempleado IN (1, 39);

-- Un numero de serie ausente se representa con NULL; UNIQUE admite varios NULL.
UPDATE equipo SET numero_serie = NULL WHERE TRIM(COALESCE(numero_serie, '')) = '';

COMMIT;

-- --------------------------------------------------------------------------
-- 2. Esquema: charset y restricciones que el codigo ya presupone
-- --------------------------------------------------------------------------
ALTER DATABASE `gestactivos`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

ALTER TABLE `asignacion` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `empleados`  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `permisos`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `usuarios`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- El respaldo heredado tenia dos FK equivalentes para asignacion.idequipo.
SET @existe_fk_idequipo_duplicada := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'asignacion'
    AND CONSTRAINT_NAME = 'idequipo'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_fk_idequipo := IF(
  @existe_fk_idequipo_duplicada > 0,
  'ALTER TABLE `asignacion` DROP FOREIGN KEY `idequipo`',
  'SELECT "La FK duplicada idequipo ya no existe"'
);
PREPARE stmt_fk_idequipo FROM @sql_fk_idequipo;
EXECUTE stmt_fk_idequipo;
DEALLOCATE PREPARE stmt_fk_idequipo;

-- Un registro de permisos por usuario.
SET @existe_uq_permisos_usuario := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'permisos'
    AND INDEX_NAME = 'uq_permisos_idusuario'
);
SET @sql_uq_permisos := IF(
  @existe_uq_permisos_usuario = 0,
  'ALTER TABLE `permisos` ADD UNIQUE KEY `uq_permisos_idusuario` (`idusuario`)',
  'SELECT "uq_permisos_idusuario ya existe"'
);
PREPARE stmt_uq_permisos FROM @sql_uq_permisos;
EXECUTE stmt_uq_permisos;
DEALLOCATE PREPARE stmt_uq_permisos;

-- Una cuenta de usuario por empleado.
SET @existe_uq_usuario_empleado := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND INDEX_NAME = 'uq_usuarios_idempleado'
);
SET @sql_uq_usuario_empleado := IF(
  @existe_uq_usuario_empleado = 0,
  'ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_idempleado` (`idempleado`)',
  'SELECT "uq_usuarios_idempleado ya existe"'
);
PREPARE stmt_uq_usuario_empleado FROM @sql_uq_usuario_empleado;
EXECUTE stmt_uq_usuario_empleado;
DEALLOCATE PREPARE stmt_uq_usuario_empleado;

-- El numero de serie identifica al equipo ante el fabricante.
-- Se permite NULL cuando el equipo no tiene serie visible, pero no duplicados.
SET @existe_uq_numero_serie := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'equipo'
    AND INDEX_NAME = 'uq_equipo_numero_serie'
);
SET @sql_uq_numero_serie := IF(
  @existe_uq_numero_serie = 0,
  'ALTER TABLE `equipo` ADD UNIQUE KEY `uq_equipo_numero_serie` (`numero_serie`)',
  'SELECT "uq_equipo_numero_serie ya existe"'
);
PREPARE stmt_uq_numero_serie FROM @sql_uq_numero_serie;
EXECUTE stmt_uq_numero_serie;
DEALLOCATE PREPARE stmt_uq_numero_serie;

-- Retira indices simples que quedaron redundantes despues de los UNIQUE.
SET @existe_idx_permisos_antiguo := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'permisos'
    AND INDEX_NAME = 'idusuario'
);
SET @sql_idx_permisos := IF(
  @existe_idx_permisos_antiguo > 0,
  'ALTER TABLE `permisos` DROP INDEX `idusuario`',
  'SELECT "El indice simple permisos.idusuario ya no existe"'
);
PREPARE stmt_idx_permisos FROM @sql_idx_permisos;
EXECUTE stmt_idx_permisos;
DEALLOCATE PREPARE stmt_idx_permisos;

SET @existe_idx_usuario_empleado_antiguo := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND INDEX_NAME = 'idempleado'
);
SET @sql_idx_usuario_empleado := IF(
  @existe_idx_usuario_empleado_antiguo > 0,
  'ALTER TABLE `usuarios` DROP INDEX `idempleado`',
  'SELECT "El indice simple usuarios.idempleado ya no existe"'
);
PREPARE stmt_idx_usuario_empleado FROM @sql_idx_usuario_empleado;
EXECUTE stmt_idx_usuario_empleado;
DEALLOCATE PREPARE stmt_idx_usuario_empleado;

-- --------------------------------------------------------------------------
-- 3. Inventario DEMO: solo lectura, no elimina ni desactiva registros
-- --------------------------------------------------------------------------
SELECT 'EQUIPOS DEMO (CONSERVADOS)' AS revision,
       eq.idequipo, eq.codigo_activo, eq.numero_serie, eq.factura,
       eq.estado_equipo, eq.activo
FROM equipo eq
WHERE eq.codigo_activo LIKE 'DEMO-%'
   OR eq.numero_serie LIKE 'DEMO-%'
   OR eq.factura LIKE 'DEMO-%'
ORDER BY eq.idequipo;

SELECT 'EMPLEADOS DEMO (CONSERVADOS)' AS revision,
       em.idempleado, em.nombre, em.apellidos, em.correo, em.activo,
       COUNT(DISTINCT CASE WHEN asg.activa = 1 THEN asg.idasignacion END) AS asignaciones_activas,
       COUNT(DISTINCT CASE WHEN us.estado = 1 THEN us.idusuario END) AS usuarios_activos
FROM empleados em
LEFT JOIN asignacion asg ON asg.idempleado = em.idempleado
LEFT JOIN usuarios us ON us.idempleado = em.idempleado
WHERE em.correo LIKE '%@demo.test'
   OR UPPER(CONCAT_WS(' ', em.nombre, em.apellidos)) LIKE '%PRUEBA%'
GROUP BY em.idempleado, em.nombre, em.apellidos, em.correo, em.activo
ORDER BY em.idempleado;

SELECT 'Migración de limpieza aplicada; los datos DEMO fueron conservados.' AS resultado;
