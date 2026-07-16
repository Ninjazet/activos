-- ============================================================
-- GestActivos - Seed DEMO de asignaciones de muestra
-- Ejecutar DESPUÉS de seed_demo_base.sql.
-- Crea solamente 6 asignaciones abiertas y 6 históricas cerradas.
-- Las abiertas quedan sin firma para demostrar el flujo de firma.
-- ============================================================
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS seed_demo_asg_abiertas;
CREATE TEMPORARY TABLE seed_demo_asg_abiertas (
    correo VARCHAR(150), codigo VARCHAR(20), fecha DATETIME
) ENGINE=MEMORY;
INSERT INTO seed_demo_asg_abiertas VALUES
('alan.turingston@demo.test','EQ-1001','2026-06-03 08:30:00'),
('ada.lovelance@demo.test','EQ-1002','2026-06-18 10:15:00'),
('grace.hoppman@demo.test','EQ-1003','2026-07-01 09:00:00'),
('linus.kernelvalds@demo.test','EQ-1004','2026-07-08 14:20:00'),
('esteban.jobsanz@demo.test','EQ-1005','2026-07-12 11:45:00'),
('anabella.borgman@demo.test','EQ-1006','2026-07-15 08:10:00');

INSERT INTO asignacion
(idempleado,idequipo,activa,fecha_asignacion,fecha_devolucion,firma,firma_fecha)
SELECT em.idempleado, eq.idequipo, 1, s.fecha, NULL, NULL, NULL
FROM seed_demo_asg_abiertas s
INNER JOIN empleados em ON em.correo=s.correo AND em.activo=1
INNER JOIN equipo eq ON eq.codigo_activo=s.codigo AND eq.activo=1 AND eq.estado_equipo=1
WHERE NOT EXISTS (
    SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1
);

UPDATE equipo eq
INNER JOIN seed_demo_asg_abiertas s ON s.codigo=eq.codigo_activo
INNER JOIN asignacion a ON a.idequipo=eq.idequipo AND a.activa=1
SET eq.estado_equipo=2;

DROP TEMPORARY TABLE IF EXISTS seed_demo_asg_historial;
CREATE TEMPORARY TABLE seed_demo_asg_historial (
    correo VARCHAR(150), codigo VARCHAR(20), fecha_entrega DATETIME, fecha_devolucion DATETIME
) ENGINE=MEMORY;
INSERT INTO seed_demo_asg_historial VALUES
('guillermo.gatezz@demo.test','EQ-1007','2025-01-15 09:00:00','2025-04-30 16:20:00'),
('marcos.zuckerman@demo.test','EQ-1008','2025-02-03 10:30:00','2025-06-12 11:15:00'),
('timoteo.bernerly@demo.test','EQ-1009','2025-03-10 08:45:00','2025-08-25 15:40:00'),
('dionisio.ritchford@demo.test','EQ-1010','2025-04-07 13:10:00','2025-11-18 09:25:00'),
('kendall.thompsen@demo.test','EQ-1011','2025-05-20 07:55:00','2026-01-09 14:35:00'),
('guido.vanderrosum@demo.test','EQ-1012','2025-07-01 12:00:00','2026-03-22 10:05:00');

INSERT INTO asignacion
(idempleado,idequipo,activa,fecha_asignacion,fecha_devolucion,firma,firma_fecha)
SELECT em.idempleado, eq.idequipo, 0, s.fecha_entrega, s.fecha_devolucion, NULL, NULL
FROM seed_demo_asg_historial s
INNER JOIN empleados em ON em.correo=s.correo
INNER JOIN equipo eq ON eq.codigo_activo=s.codigo
WHERE NOT EXISTS (
    SELECT 1 FROM asignacion a
    WHERE a.idempleado=em.idempleado AND a.idequipo=eq.idequipo
      AND a.fecha_asignacion=s.fecha_entrega
);

DROP TEMPORARY TABLE IF EXISTS seed_demo_asg_abiertas;
DROP TEMPORARY TABLE IF EXISTS seed_demo_asg_historial;
COMMIT;

SELECT 'Asignaciones DEMO aplicadas: 6 abiertas sin firma y 6 históricas cerradas.' AS resultado;