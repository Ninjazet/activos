-- GestActivos: datos demostrativos para Proveedores y Mantenimientos
-- Requiere: migracion_proveedores_mantenimientos_20260731.sql
-- Es idempotente: puede volver a ejecutarse sin duplicar estos registros.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `proveedores`
  (`nombre`, `rtn`, `contacto`, `telefono`, `correo`, `direccion`, `observaciones`, `activo`)
VALUES
  ('DEMO - Tecno Suministros HN', 'DEMO-RTN-001', 'Andrea Mejía',  '+504 2201-1001', 'ventas01@proveedores.demo.test', 'Tegucigalpa, Francisco Morazán', 'Proveedor de prueba para computadoras y accesorios.', 1),
  ('DEMO - Soluciones Digitales CA', 'DEMO-RTN-002', 'Carlos Pineda', '+504 2201-1002', 'ventas02@proveedores.demo.test', 'San Pedro Sula, Cortés', 'Proveedor de prueba para licenciamiento y hardware.', 1),
  ('DEMO - Infraestructura IT', 'DEMO-RTN-003', 'María Lagos', '+504 2201-1003', 'ventas03@proveedores.demo.test', 'Tegucigalpa, Francisco Morazán', 'Proveedor de prueba para servidores y almacenamiento.', 1),
  ('DEMO - Redes y Comunicaciones', 'DEMO-RTN-004', 'José Rivera', '+504 2201-1004', 'ventas04@proveedores.demo.test', 'San Pedro Sula, Cortés', 'Proveedor de prueba para redes y telecomunicaciones.', 1),
  ('DEMO - Servicios Técnicos Integrales', 'DEMO-RTN-005', 'Diana Flores', '+504 2201-1005', 'ventas05@proveedores.demo.test', 'Comayagua, Comayagua', 'Taller demostrativo de diagnóstico y reparación.', 1),
  ('DEMO - Impresión Empresarial', 'DEMO-RTN-006', 'Luis Romero', '+504 2201-1006', 'ventas06@proveedores.demo.test', 'Tegucigalpa, Francisco Morazán', 'Proveedor de prueba para impresoras y consumibles.', 1),
  ('DEMO - Seguridad Electrónica', 'DEMO-RTN-007', 'Sofía Cruz', '+504 2201-1007', 'ventas07@proveedores.demo.test', 'La Ceiba, Atlántida', 'Proveedor demostrativo de seguridad y monitoreo.', 1),
  ('DEMO - Equipos Móviles', 'DEMO-RTN-008', 'Daniel Núñez', '+504 2201-1008', 'ventas08@proveedores.demo.test', 'San Pedro Sula, Cortés', 'Proveedor de prueba para teléfonos y tabletas.', 1),
  ('DEMO - Data Center Honduras', 'DEMO-RTN-009', 'Paola Reyes', '+504 2201-1009', 'ventas09@proveedores.demo.test', 'Tegucigalpa, Francisco Morazán', 'Proveedor demostrativo de infraestructura crítica.', 1),
  ('DEMO - Soporte Corporativo', 'DEMO-RTN-010', 'Miguel Zelaya', '+504 2201-1010', 'ventas10@proveedores.demo.test', 'Choloma, Cortés', 'Proveedor de prueba para soporte técnico externo.', 1)
ON DUPLICATE KEY UPDATE
  `rtn` = VALUES(`rtn`),
  `contacto` = VALUES(`contacto`),
  `telefono` = VALUES(`telefono`),
  `correo` = VALUES(`correo`),
  `direccion` = VALUES(`direccion`),
  `observaciones` = VALUES(`observaciones`),
  `activo` = 1;

DROP TEMPORARY TABLE IF EXISTS `tmp_equipos_demo_mantenimiento`;
CREATE TEMPORARY TABLE `tmp_equipos_demo_mantenimiento` (
  `orden` int(11) NOT NULL AUTO_INCREMENT,
  `idequipo` int(11) NOT NULL,
  PRIMARY KEY (`orden`),
  UNIQUE KEY `uq_tmp_equipo` (`idequipo`)
) ENGINE=Memory;

INSERT INTO `tmp_equipos_demo_mantenimiento` (`idequipo`)
SELECT eq.`idequipo`
FROM `equipo` eq
WHERE eq.`activo` = 1
  AND eq.`estado_equipo` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `asignacion` a
    WHERE a.`idequipo` = eq.`idequipo` AND a.`activa` = 1
  )
  AND NOT EXISTS (
    SELECT 1 FROM `mantenimientos` m
    WHERE m.`idequipo` = eq.`idequipo`
      AND m.`estado` IN ('Abierto', 'En proceso')
  )
ORDER BY eq.`idequipo` DESC
LIMIT 10;

-- Relaciona hasta diez equipos disponibles, uno con cada proveedor demo.
UPDATE `equipo` eq
INNER JOIN `tmp_equipos_demo_mantenimiento` t ON t.`idequipo` = eq.`idequipo`
INNER JOIN `proveedores` p ON p.`nombre` = ELT(
  t.`orden`,
  'DEMO - Tecno Suministros HN',
  'DEMO - Soluciones Digitales CA',
  'DEMO - Infraestructura IT',
  'DEMO - Redes y Comunicaciones',
  'DEMO - Servicios Técnicos Integrales',
  'DEMO - Impresión Empresarial',
  'DEMO - Seguridad Electrónica',
  'DEMO - Equipos Móviles',
  'DEMO - Data Center Honduras',
  'DEMO - Soporte Corporativo'
)
SET eq.`idproveedor` = p.`idproveedor`
WHERE eq.`idproveedor` IS NULL;

SET @usuario_demo := (
  SELECT MIN(u.`idusuario`) FROM `usuarios` u WHERE u.`estado` = 1
);

-- Agrega hasta diez mantenimientos cerrados para probar filtros, métricas y PDF.
INSERT INTO `mantenimientos` (
  `idequipo`, `idproveedor`, `tipo`, `estado`, `fecha_ingreso`, `fecha_cierre`,
  `descripcion_problema`, `diagnostico`, `trabajo_realizado`, `costo`, `resultado`,
  `observaciones`, `estado_anterior_equipo`, `origen`,
  `idusuario_apertura`, `idusuario_cierre`
)
SELECT
  t.`idequipo`,
  eq.`idproveedor`,
  IF(MOD(t.`orden`, 2) = 0, 'Correctivo', 'Preventivo'),
  'Completado',
  DATE_SUB(NOW(), INTERVAL (t.`orden` * 6) DAY),
  DATE_ADD(DATE_SUB(NOW(), INTERVAL (t.`orden` * 6) DAY), INTERVAL 1 DAY),
  CONCAT('Mantenimiento demostrativo #', LPAD(t.`orden`, 2, '0'), ' para validar el historial técnico.'),
  IF(MOD(t.`orden`, 2) = 0, 'Se detectó desgaste normal de componentes.', 'Equipo operativo; mantenimiento preventivo programado.'),
  IF(MOD(t.`orden`, 2) = 0, 'Ajuste, limpieza y sustitución de componente de prueba.', 'Limpieza, actualización y comprobación general.'),
  175.00 + (t.`orden` * 85.50),
  'Reparado',
  'Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',
  1,
  'Seed demo',
  @usuario_demo,
  @usuario_demo
FROM `tmp_equipos_demo_mantenimiento` t
INNER JOIN `equipo` eq ON eq.`idequipo` = t.`idequipo`
WHERE NOT EXISTS (
  SELECT 1 FROM `mantenimientos` m
  WHERE m.`idequipo` = t.`idequipo`
    AND m.`origen` = 'Seed demo'
);

DROP TEMPORARY TABLE `tmp_equipos_demo_mantenimiento`;

COMMIT;

SELECT COUNT(*) AS proveedores_demo
FROM `proveedores`
WHERE `nombre` LIKE 'DEMO - %';

SELECT COUNT(*) AS mantenimientos_demo
FROM `mantenimientos`
WHERE `origen` = 'Seed demo';
