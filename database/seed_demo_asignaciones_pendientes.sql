-- ============================================================
-- GestActivos - Escenarios DEMO pendientes (DESACTIVADOS)
-- Este archivo NO crea asignaciones con su configuración normal.
-- Para aplicarlas manualmente cambia @APLICAR_PENDIENTES de 0 a 1.
-- ============================================================
SET @APLICAR_PENDIENTES := 0;
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS seed_demo_pendientes;
CREATE TEMPORARY TABLE seed_demo_pendientes (
    correo VARCHAR(150), codigo VARCHAR(20), fecha DATETIME, escenario VARCHAR(100)
) ENGINE=MEMORY;
INSERT INTO seed_demo_pendientes VALUES
('margarita.hamilton@demo.test','EQ-1013','2026-07-16 09:00:00','Teléfono pendiente de firma'),
('donaldo.knuthe@demo.test','EQ-1014','2026-07-16 09:20:00','Teléfono para entrega ejecutiva'),
('ricardo.stallmont@demo.test','EQ-1015','2026-07-16 10:00:00','Equipo de red para laboratorio'),
('vicente.cerfino@demo.test','EQ-1016','2026-07-16 10:30:00','Punto de acceso para infraestructura'),
('francisca.allenton@demo.test','EQ-1017','2026-07-16 11:00:00','Servidor para ambiente QA'),
('lorenzo.pageson@demo.test','EQ-1018','2026-07-16 11:30:00','Estación para pruebas automatizadas');

INSERT INTO asignacion
(idempleado,idequipo,activa,fecha_asignacion,fecha_devolucion,firma,firma_fecha)
SELECT em.idempleado, eq.idequipo, 1, s.fecha, NULL, NULL, NULL
FROM seed_demo_pendientes s
INNER JOIN empleados em ON em.correo=s.correo AND em.activo=1
INNER JOIN equipo eq ON eq.codigo_activo=s.codigo AND eq.activo=1 AND eq.estado_equipo=1
WHERE @APLICAR_PENDIENTES=1
  AND NOT EXISTS (SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1);

UPDATE equipo eq
INNER JOIN seed_demo_pendientes s ON s.codigo=eq.codigo_activo
INNER JOIN asignacion a ON a.idequipo=eq.idequipo AND a.activa=1
SET eq.estado_equipo=2
WHERE @APLICAR_PENDIENTES=1;

DROP TEMPORARY TABLE IF EXISTS seed_demo_pendientes;
COMMIT;

SELECT IF(@APLICAR_PENDIENTES=1,
  '6 asignaciones pendientes aplicadas.',
  'Sin cambios: escenarios pendientes preparados pero desactivados.') AS resultado;