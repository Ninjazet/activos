-- ============================================================
-- GestActivos - Seed DEMO base (idempotente)
-- Crea catálogos, 30 empleados y 30 equipos SIN asignaciones.
-- No usa IDs fijos y puede ejecutarse nuevamente sin duplicar datos.
-- ============================================================
START TRANSACTION;

-- Áreas adicionales
INSERT INTO areas (descripcionarea, activo)
SELECT s.nombre, 1 FROM (
    SELECT 'Desarrollo de Software' nombre UNION ALL
    SELECT 'Infraestructura y Redes' UNION ALL
    SELECT 'Ciberseguridad' UNION ALL
    SELECT 'Calidad de Software' UNION ALL
    SELECT 'Datos y Analítica' UNION ALL
    SELECT 'Operaciones TI' UNION ALL
    SELECT 'Compras y Proveedores' UNION ALL
    SELECT 'Auditoría TI'
) s
WHERE NOT EXISTS (SELECT 1 FROM areas a WHERE a.descripcionarea = s.nombre);

-- Cargos adicionales
INSERT INTO cargos (descripcioncargo, activo)
SELECT s.nombre, 1 FROM (
    SELECT 'Desarrollador de Software' nombre UNION ALL
    SELECT 'Analista de Sistemas' UNION ALL
    SELECT 'Administrador de Redes' UNION ALL
    SELECT 'Especialista en Ciberseguridad' UNION ALL
    SELECT 'Arquitecto de Soluciones' UNION ALL
    SELECT 'Administrador de Base de Datos' UNION ALL
    SELECT 'Ingeniero de Datos' UNION ALL
    SELECT 'Soporte Técnico N2' UNION ALL
    SELECT 'Líder Técnico' UNION ALL
    SELECT 'Especialista en QA' UNION ALL
    SELECT 'Coordinador ITAM' UNION ALL
    SELECT 'Técnico de Campo'
) s
WHERE NOT EXISTS (SELECT 1 FROM cargos c WHERE c.descripcioncargo = s.nombre);

-- Marcas adicionales
INSERT INTO marca (nombreMarca, activo)
SELECT s.nombre, 1 FROM (
    SELECT 'Apple' nombre UNION ALL SELECT 'ASUS' UNION ALL SELECT 'Samsung' UNION ALL
    SELECT 'Microsoft' UNION ALL SELECT 'Acer' UNION ALL SELECT 'Epson' UNION ALL
    SELECT 'Cisco' UNION ALL SELECT 'Ubiquiti' UNION ALL SELECT 'HPE'
) s
WHERE NOT EXISTS (SELECT 1 FROM marca m WHERE m.nombreMarca = s.nombre);

-- Modelos adicionales
INSERT INTO modelo (nombreModelo, activo)
SELECT s.nombre, 1 FROM (
    SELECT 'ThinkPad X1 Carbon Gen 12' nombre UNION ALL
    SELECT 'HP EliteBook 845 G11' UNION ALL
    SELECT 'Dell Latitude 9450' UNION ALL
    SELECT 'MacBook Pro 16 M4' UNION ALL
    SELECT 'ASUS ZenBook Duo' UNION ALL
    SELECT 'Samsung Galaxy Book4 Ultra' UNION ALL
    SELECT 'Microsoft Surface Laptop 6' UNION ALL
    SELECT 'Acer TravelMate P4' UNION ALL
    SELECT 'Dell UltraSharp U2723QE' UNION ALL
    SELECT 'Samsung ViewFinity S7' UNION ALL
    SELECT 'HP LaserJet Pro 4003dw' UNION ALL
    SELECT 'Epson EcoTank L5590' UNION ALL
    SELECT 'Samsung Galaxy S24' UNION ALL
    SELECT 'Apple iPhone 15' UNION ALL
    SELECT 'Cisco Catalyst 9200' UNION ALL
    SELECT 'Ubiquiti UniFi U6 Pro' UNION ALL
    SELECT 'HPE ProLiant DL380 Gen11' UNION ALL
    SELECT 'Dell OptiPlex 7010' UNION ALL
    SELECT 'Lenovo ThinkCentre M90q' UNION ALL
    SELECT 'ASUS ProArt PA279CRV'
) s
WHERE NOT EXISTS (SELECT 1 FROM modelo m WHERE m.nombreModelo = s.nombre);

DROP TEMPORARY TABLE IF EXISTS seed_demo_empleados;
CREATE TEMPORARY TABLE seed_demo_empleados (
    nombre VARCHAR(50), apellidos VARCHAR(50), edad INT, telefono INT,
    correo VARCHAR(150), direccion VARCHAR(100), cargo VARCHAR(100),
    area VARCHAR(100), sexo TINYINT, activo TINYINT
) ENGINE=MEMORY;

INSERT INTO seed_demo_empleados VALUES
('Alan','Turingston',41,98741230,'alan.turingston@demo.test','Tegucigalpa','Especialista en Ciberseguridad','Ciberseguridad',1,1),
('Ada','Lovelance',33,88452201,'ada.lovelance@demo.test','San Pedro Sula','Desarrollador de Software','Desarrollo de Software',2,1),
('Grace','Hoppman',47,99562310,'grace.hoppman@demo.test','Comayagua','Líder Técnico','Desarrollo de Software',2,1),
('Linus','Kernelvalds',29,32456871,'linus.kernelvalds@demo.test','Tegucigalpa','Administrador de Redes','Infraestructura y Redes',1,1),
('Esteban','Jobsanz',38,88907654,'esteban.jobsanz@demo.test','San Pedro Sula','Arquitecto de Soluciones','Desarrollo de Software',1,1),
('Anabella','Borgman',31,99123456,'anabella.borgman@demo.test','La Ceiba','Ingeniero de Datos','Datos y Analítica',2,1),
('Guillermo','Gatezz',44,32678901,'guillermo.gatezz@demo.test','Tegucigalpa','Analista de Sistemas','Operaciones TI',1,1),
('Marcos','Zuckerman',27,88345678,'marcos.zuckerman@demo.test','San Pedro Sula','Desarrollador de Software','Desarrollo de Software',1,1),
('Timoteo','Bernerly',39,99456123,'timoteo.bernerly@demo.test','Tegucigalpa','Arquitecto de Soluciones','Infraestructura y Redes',1,1),
('Dionisio','Ritchford',42,32567890,'dionisio.ritchford@demo.test','Choloma','Desarrollador de Software','Desarrollo de Software',1,1),
('Kendall','Thompsen',36,88678123,'kendall.thompsen@demo.test','Tegucigalpa','Soporte Técnico N2','Soporte IT',1,1),
('Guido','Vanderrosum',34,99789456,'guido.vanderrosum@demo.test','San Pedro Sula','Desarrollador de Software','Desarrollo de Software',1,1),
('Margarita','Hamilton',46,32890567,'margarita.hamilton@demo.test','Tegucigalpa','Líder Técnico','Calidad de Software',2,1),
('Donaldo','Knuthe',50,88901234,'donaldo.knuthe@demo.test','Comayagua','Analista de Sistemas','Operaciones TI',1,1),
('Ricardo','Stallmont',43,99012345,'ricardo.stallmont@demo.test','Tegucigalpa','Desarrollador de Software','Desarrollo de Software',1,1),
('Vicente','Cerfino',37,32123098,'vicente.cerfino@demo.test','San Pedro Sula','Administrador de Redes','Infraestructura y Redes',1,1),
('Francisca','Allenton',40,88234109,'francisca.allenton@demo.test','Tegucigalpa','Desarrollador de Software','Calidad de Software',2,1),
('Lorenzo','Pageson',26,99345210,'lorenzo.pageson@demo.test','La Ceiba','Especialista en QA','Calidad de Software',1,1),
('Sergio','Brinsky',28,32456321,'sergio.brinsky@demo.test','Tegucigalpa','Analista de Sistemas','Operaciones TI',1,1),
('Satoshi','Nakamura',35,88567432,'satoshi.nakamura@demo.test','San Pedro Sula','Especialista en Ciberseguridad','Ciberseguridad',1,1),
('Isabel','Feinlerman',45,99678543,'isabel.feinlerman@demo.test','Tegucigalpa','Administrador de Base de Datos','Datos y Analítica',2,1),
('Catalina','Johanson',32,32789654,'catalina.johanson@demo.test','Comayagua','Ingeniero de Datos','Datos y Analítica',2,1),
('Rosalinda','Perlman',41,88890765,'rosalinda.perlman@demo.test','Tegucigalpa','Administrador de Redes','Infraestructura y Redes',2,1),
('Jaime','Goslinger',39,99901876,'jaime.goslinger@demo.test','San Pedro Sula','Desarrollador de Software','Desarrollo de Software',1,1),
('Yukiro','Matsuda',30,32012987,'yukiro.matsuda@demo.test','Tegucigalpa','Desarrollador de Software','Desarrollo de Software',1,1),
('Barbara','Liskovna',48,88123098,'barbara.liskovna@demo.test','Choloma','Arquitecto de Soluciones','Auditoría TI',2,1),
('Karina','Sparckson',33,99234109,'karina.sparckson@demo.test','Tegucigalpa','Analista de Sistemas','Operaciones TI',2,1),
('Brian','Kernighanz',44,32345210,'brian.kernighanz@demo.test','San Pedro Sula','Especialista en QA','Calidad de Software',1,1),
('Jefferson','Deane',37,88456321,'jefferson.deane@demo.test','Tegucigalpa','Coordinador ITAM','Compras y Proveedores',1,1),
('Marisol','Mayerson',29,99567432,'marisol.mayerson@demo.test','La Ceiba','Técnico de Campo','Soporte IT',2,0);

INSERT INTO empleados
(nombre, apellidos, edad, telefono, correo, direccion, imagen, idcargo, idarea, idsexo, activo)
SELECT s.nombre, s.apellidos, s.edad, s.telefono, s.correo, s.direccion,
       CASE WHEN s.sexo=2 THEN 'public/img/empleados/avatar2.png' ELSE 'public/img/empleados/avatar1.png' END,
       c.idcargo, a.idarea, s.sexo, s.activo
FROM seed_demo_empleados s
INNER JOIN cargos c ON c.descripcioncargo=s.cargo
INNER JOIN areas a ON a.descripcionarea=s.area
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE e.correo=s.correo);

DROP TEMPORARY TABLE IF EXISTS seed_demo_equipos;
CREATE TEMPORARY TABLE seed_demo_equipos (
    codigo VARCHAR(20), serie VARCHAR(100), tipo VARCHAR(50), marca VARCHAR(50), modelo VARCHAR(50),
    fecha DATE, costo DECIMAL(12,2), factura VARCHAR(100), garantia DATE,
    estado TINYINT, activo TINYINT
) ENGINE=MEMORY;

INSERT INTO seed_demo_equipos VALUES
('EQ-1001','DEMO-SN-1001','Laptop','Lenovo','ThinkPad X1 Carbon Gen 12','2025-01-15',48900,'DEMO-FAC-1001','2028-01-15',1,1),
('EQ-1002','DEMO-SN-1002','Laptop','HP','HP EliteBook 845 G11','2025-02-10',42500,'DEMO-FAC-1002','2028-02-10',1,1),
('EQ-1003','DEMO-SN-1003','Laptop','Dell','Dell Latitude 9450','2025-03-08',46500,'DEMO-FAC-1003','2028-03-08',1,1),
('EQ-1004','DEMO-SN-1004','Laptop','Apple','MacBook Pro 16 M4','2025-04-22',78500,'DEMO-FAC-1004','2028-04-22',1,1),
('EQ-1005','DEMO-SN-1005','Laptop','ASUS','ASUS ZenBook Duo','2025-05-16',53900,'DEMO-FAC-1005','2028-05-16',1,1),
('EQ-1006','DEMO-SN-1006','Laptop','Samsung','Samsung Galaxy Book4 Ultra','2025-06-05',51200,'DEMO-FAC-1006','2028-06-05',1,1),
('EQ-1007','DEMO-SN-1007','Laptop','Microsoft','Microsoft Surface Laptop 6','2025-06-20',44750,'DEMO-FAC-1007','2028-06-20',1,1),
('EQ-1008','DEMO-SN-1008','Laptop','Acer','Acer TravelMate P4','2025-07-11',31800,'DEMO-FAC-1008','2028-07-11',1,1),
('EQ-1009','DEMO-SN-1009','Monitor','Dell','Dell UltraSharp U2723QE','2024-08-15',16200,'DEMO-FAC-1009','2027-08-15',1,1),
('EQ-1010','DEMO-SN-1010','Monitor','Samsung','Samsung ViewFinity S7','2024-09-12',11800,'DEMO-FAC-1010','2027-09-12',1,1),
('EQ-1011','DEMO-SN-1011','Impresora','HP','HP LaserJet Pro 4003dw','2024-10-03',14600,'DEMO-FAC-1011','2027-10-03',1,1),
('EQ-1012','DEMO-SN-1012','Impresora','Epson','Epson EcoTank L5590','2024-10-28',12900,'DEMO-FAC-1012','2027-10-28',1,1),
('EQ-1013','DEMO-SN-1013','Teléfono','Samsung','Samsung Galaxy S24','2025-01-09',22900,'DEMO-FAC-1013','2027-01-09',1,1),
('EQ-1014','DEMO-SN-1014','Teléfono','Apple','Apple iPhone 15','2025-01-19',26900,'DEMO-FAC-1014','2027-01-19',1,1),
('EQ-1015','DEMO-SN-1015','Equipo de red','Cisco','Cisco Catalyst 9200','2024-11-07',68500,'DEMO-FAC-1015','2029-11-07',1,1),
('EQ-1016','DEMO-SN-1016','Equipo de red','Ubiquiti','Ubiquiti UniFi U6 Pro','2024-12-14',7200,'DEMO-FAC-1016','2027-12-14',1,1),
('EQ-1017','DEMO-SN-1017','Servidor','HPE','HPE ProLiant DL380 Gen11','2025-02-26',245000,'DEMO-FAC-1017','2030-02-26',1,1),
('EQ-1018','DEMO-SN-1018','Computadora de escritorio','Dell','Dell OptiPlex 7010','2025-03-13',34200,'DEMO-FAC-1018','2028-03-13',1,1),
('EQ-1019','DEMO-SN-1019','Computadora de escritorio','Lenovo','Lenovo ThinkCentre M90q','2023-04-12',28700,'DEMO-FAC-1019','2026-04-12',3,1),
('EQ-1020','DEMO-SN-1020','Monitor','ASUS','ASUS ProArt PA279CRV','2023-05-18',17300,'DEMO-FAC-1020','2026-05-18',3,1),
('EQ-1021','DEMO-SN-1021','Laptop','HP','HP EliteBook 845 G11','2023-06-21',36500,'DEMO-FAC-1021','2026-06-21',3,1),
('EQ-1022','DEMO-SN-1022','Impresora','Epson','Epson EcoTank L5590','2023-07-07',10800,'DEMO-FAC-1022','2026-07-07',3,1),
('EQ-1023','DEMO-SN-1023','Laptop','Dell','Dell Latitude 9450','2023-08-16',39800,'DEMO-FAC-1023','2026-08-16',4,1),
('EQ-1024','DEMO-SN-1024','Teléfono','Samsung','Samsung Galaxy S24','2024-01-25',21600,'DEMO-FAC-1024','2026-01-25',4,1),
('EQ-1025','DEMO-SN-1025','Laptop','Acer','Acer TravelMate P4','2021-02-11',24800,'DEMO-FAC-1025','2024-02-11',5,0),
('EQ-1026','DEMO-SN-1026','Monitor','Samsung','Samsung ViewFinity S7','2020-09-30',8900,'DEMO-FAC-1026','2023-09-30',5,0),
('EQ-1027','DEMO-SN-1027','Laptop','Lenovo','ThinkPad X1 Carbon Gen 12','2026-01-12',49800,'DEMO-FAC-1027','2029-01-12',1,1),
('EQ-1028','DEMO-SN-1028','Laptop','Apple','MacBook Pro 16 M4','2026-02-09',79900,'DEMO-FAC-1028','2029-02-09',1,1),
('EQ-1029','DEMO-SN-1029','Monitor','Dell','Dell UltraSharp U2723QE','2026-03-17',16900,'DEMO-FAC-1029','2029-03-17',1,1),
('EQ-1030','DEMO-SN-1030','Equipo de red','Ubiquiti','Ubiquiti UniFi U6 Pro','2026-04-06',7600,'DEMO-FAC-1030','2029-04-06',1,1);

INSERT INTO equipo
(idmarca_equipo,idmodelo_equipo,imagen,activo,fecha_compra,costo,factura,vencimiento_garantia,
 estado_equipo,numero_serie,codigo_activo,tipo_equipo)
SELECT ma.idmarca, mo.idmodelo, '', s.activo, s.fecha, s.costo, s.factura, s.garantia,
       s.estado, s.serie, s.codigo, s.tipo
FROM seed_demo_equipos s
INNER JOIN marca ma ON ma.nombreMarca=s.marca
INNER JOIN modelo mo ON mo.nombreModelo=s.modelo
WHERE NOT EXISTS (SELECT 1 FROM equipo e WHERE e.codigo_activo=s.codigo);

DROP TEMPORARY TABLE IF EXISTS seed_demo_empleados;
DROP TEMPORARY TABLE IF EXISTS seed_demo_equipos;
COMMIT;

SELECT 'Seed base DEMO aplicado: catálogos, empleados y equipos; sin asignaciones.' AS resultado;