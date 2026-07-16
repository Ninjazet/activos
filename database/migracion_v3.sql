-- ============================================================
-- GestActivos - Migración v3.0
-- Ejecutar UNA SOLA VEZ sobre la base de datos "gestactivos"
-- existente (phpMyAdmin -> pestaña SQL -> pegar y ejecutar).
-- Es seguro volver a correrlo: cada bloque verifica si el
-- cambio ya existe antes de aplicarlo.
-- ============================================================

-- ---------- 1. Eliminación lógica (soft delete) ----------
-- Las tablas de referencia y los activos no se borran físicamente
-- nunca más: se marcan como inactivos para no perder el historial.

ALTER TABLE `areas`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `cargos`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `marca`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `modelo`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `empleados`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `equipo`
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1;

-- La tabla de asignaciones pasa a llevar historial real:
-- en vez de borrar la fila al "devolver" un equipo, se cierra con fecha.
ALTER TABLE `asignacion`
  ADD COLUMN IF NOT EXISTS `activa` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `fecha_asignacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `fecha_devolucion` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `firma` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `firma_fecha` DATETIME NULL;

-- ---------- 2. Restricción UNIQUE en usuarios ----------
-- Evita usuarios duplicados a nivel de base de datos (antes solo
-- se validaba desde el código PHP).
SET @existe_unique := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND index_name = 'uq_usuarios_username'
);
SET @sql_unique := IF(@existe_unique = 0,
  'ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_username` (`username`)',
  'SELECT "uq_usuarios_username ya existe"');
PREPARE stmt FROM @sql_unique;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- La columna 'pass' debe poder guardar un hash bcrypt completo (60 caracteres).
-- Antes era varchar(15)/varchar(20), pensada solo para texto plano corto.
ALTER TABLE `usuarios` MODIFY `pass` VARCHAR(255) NOT NULL;

-- ---------- 3. Bitácora de auditoría ----------
-- Registra logins (éxito/fallo) y cada creación/edición/baja en
-- cualquier módulo: quién, qué, cuándo y desde qué IP.
CREATE TABLE IF NOT EXISTS `bitacora` (
  `idbitacora`    INT(11) NOT NULL AUTO_INCREMENT,
  `idusuario`     INT(11) NULL,
  `usuario_texto` VARCHAR(50)  NULL,
  `accion`        VARCHAR(30)  NOT NULL,
  `modulo`        VARCHAR(50)  NULL,
  `detalle`       VARCHAR(255) NULL,
  `ip`            VARCHAR(45)  NULL,
  `fecha`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idbitacora`),
  KEY `idx_bitacora_usuario` (`idusuario`),
  KEY `idx_bitacora_fecha` (`fecha`),
  KEY `idx_bitacora_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 4. Listo ----------
SELECT 'Migración v3.0 aplicada correctamente.' AS resultado;
