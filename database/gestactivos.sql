-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gestactivos
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `idarea` int(11) NOT NULL AUTO_INCREMENT,
  `descripcionarea` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idarea`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` VALUES (1,'Gerencia General',1),(2,'Administracion',1),(3,'Informatica',1),(4,'Mantenimiento',1),(5,'Soporte IT',1),(6,'Recursos Humanos',1),(7,'Contabilidad',1),(8,'Recepcion',1),(9,'facturacion',1),(11,'Área QA Sistemas',1),(12,'Desarrollo de Software',0),(13,'Infraestructura y Redes',1),(14,'Ciberseguridad',1),(15,'Calidad de Software',1),(16,'Datos y Analítica',1),(17,'Operaciones TI',1),(18,'Compras y Proveedores',1),(19,'Auditoría TI',1);
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignacion`
--

DROP TABLE IF EXISTS `asignacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asignacion` (
  `idasignacion` int(11) NOT NULL AUTO_INCREMENT,
  `idempleado` int(11) DEFAULT NULL,
  `idequipo` int(11) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `condicion_entrega` varchar(30) NOT NULL DEFAULT 'Bueno',
  `entrega_cargador` tinyint(1) NOT NULL DEFAULT 0,
  `entrega_maletin` tinyint(1) NOT NULL DEFAULT 0,
  `entrega_otros` varchar(255) DEFAULT NULL,
  `observaciones_entrega` varchar(500) DEFAULT NULL,
  `requiere_firma_entrega` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_devolucion` datetime DEFAULT NULL,
  `condicion_devolucion` varchar(30) DEFAULT NULL,
  `devolucion_cargador` tinyint(1) DEFAULT NULL,
  `devolucion_maletin` tinyint(1) DEFAULT NULL,
  `devolucion_otros` varchar(255) DEFAULT NULL,
  `observaciones_devolucion` varchar(500) DEFAULT NULL,
  `estado_equipo_devolucion` tinyint(3) unsigned DEFAULT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `firma_fecha` datetime DEFAULT NULL,
  `firma_devolucion` varchar(255) DEFAULT NULL,
  `firma_devolucion_fecha` datetime DEFAULT NULL,
  `idusuario_devolucion` int(11) DEFAULT NULL,
  PRIMARY KEY (`idasignacion`),
  KEY `idequipo_idx` (`idequipo`),
  KEY `idempleado_idx` (`idempleado`),
  KEY `idx_asignacion_equipo_activa` (`idequipo`,`activa`),
  KEY `idx_asignacion_empleado_activa` (`idempleado`,`activa`),
  KEY `idx_asignacion_usuario_devolucion` (`idusuario_devolucion`),
  CONSTRAINT `asignacion_ibfk_1` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`),
  CONSTRAINT `fk_asignacion_usuario_devolucion` FOREIGN KEY (`idusuario_devolucion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `idempleado` FOREIGN KEY (`idempleado`) REFERENCES `empleados` (`idempleado`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignacion`
--

LOCK TABLES `asignacion` WRITE;
/*!40000 ALTER TABLE `asignacion` DISABLE KEYS */;
INSERT INTO `asignacion` VALUES (4,2,2,1,'2026-07-01 11:06:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_4_1784223425.jpg','2026-07-16 11:37:05',NULL,NULL,NULL),(5,8,6,1,'2026-07-01 11:06:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_5_1784221127.jpg','2026-07-16 10:58:47',NULL,NULL,NULL),(6,9,5,1,'2026-07-01 11:14:50','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_6_1784221107.jpg','2026-07-16 10:58:27',NULL,NULL,NULL),(7,7,1,1,'2026-07-06 18:49:39','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_7_1784220820.png','2026-07-16 10:53:40',NULL,NULL,NULL),(8,4,3,1,'2026-07-06 18:50:02','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_8_1784221094.jpg','2026-07-16 10:58:14',NULL,NULL,NULL),(9,2,4,1,'2026-07-16 10:54:24','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_9_1784221032.jpg','2026-07-16 10:57:12',NULL,NULL,NULL),(10,7,7,1,'2026-07-16 11:56:03','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_10_1784224577.jpg','2026-07-16 11:56:17',NULL,NULL,NULL),(11,41,8,0,'2026-07-16 12:30:46','Bueno',0,0,NULL,NULL,0,'2026-07-16 12:35:24',NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_11_1784226773.jpg','2026-07-16 12:32:53',NULL,NULL,NULL),(12,43,9,1,'2026-06-03 08:30:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,44,10,1,'2026-06-18 10:15:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,45,11,1,'2026-07-01 09:00:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,46,12,1,'2026-07-08 14:20:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,47,13,1,'2026-07-12 11:45:00','Bueno',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,48,14,0,'2026-07-15 08:10:00','Bueno',0,0,NULL,NULL,0,'2026-07-19 13:45:13','Con daño',1,0,NULL,NULL,3,NULL,NULL,'public/img/firmas/firma_devolucion_17_20260719_134513_7a8c9b5d.jpg','2026-07-19 13:45:13',11),(19,49,15,0,'2025-01-15 09:00:00','Bueno',0,0,NULL,NULL,0,'2025-04-30 16:20:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,50,16,0,'2025-02-03 10:30:00','Bueno',0,0,NULL,NULL,0,'2025-06-12 11:15:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,51,17,0,'2025-03-10 08:45:00','Bueno',0,0,NULL,NULL,0,'2025-08-25 15:40:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,52,18,0,'2025-04-07 13:10:00','Bueno',0,0,NULL,NULL,0,'2025-11-18 09:25:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,53,19,0,'2025-05-20 07:55:00','Bueno',0,0,NULL,NULL,0,'2026-01-09 14:35:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,54,20,0,'2025-07-01 12:00:00','Bueno',0,0,NULL,NULL,0,'2026-03-22 10:05:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,43,21,0,'2026-07-19 13:43:57','Nuevo',1,0,NULL,NULL,0,'2026-07-19 13:44:37','Bueno',1,0,NULL,NULL,1,'public/img/firmas/firma_entrega_27_20260719_134406_78b14a7c.jpg','2026-07-19 13:44:06','public/img/firmas/firma_devolucion_27_20260719_134437_677b0a63.jpg','2026-07-19 13:44:37',11),(28,52,22,0,'2026-07-20 18:41:19','Nuevo',1,0,'audifonos',NULL,1,'2026-07-20 18:43:11','Bueno',1,0,'audifonos',NULL,1,'public/img/firmas/firma_entrega_28_20260720_184207_905d51dc.jpg','2026-07-20 18:42:07','public/img/firmas/firma_devolucion_28_20260720_184311_f05c4806.jpg','2026-07-20 18:43:11',11),(29,4,25,1,'2026-07-27 10:37:24','Nuevo',1,1,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public/img/firmas/firma_entrega_29_20260727_103747_931a67ac.jpg','2026-07-27 10:37:47',NULL,NULL,NULL);
/*!40000 ALTER TABLE `asignacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bitacora` (
  `idbitacora` int(11) NOT NULL AUTO_INCREMENT,
  `idusuario` int(11) DEFAULT NULL,
  `usuario_texto` varchar(50) DEFAULT NULL,
  `accion` varchar(30) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idbitacora`),
  KEY `idx_bitacora_usuario` (`idusuario`),
  KEY `idx_bitacora_fecha` (`fecha`),
  KEY `idx_bitacora_accion` (`accion`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora`
--

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
INSERT INTO `bitacora` VALUES (1,1,'emartinez','login_exitoso',NULL,NULL,'::1','2026-07-01 11:09:14'),(2,NULL,'emartinez','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-01 11:12:14'),(3,NULL,'emartinez','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-01 11:12:19'),(4,1,'emartinez','login_exitoso',NULL,NULL,'::1','2026-07-01 11:12:23'),(5,1,'emartinez','crear','asignacion','emp=9 equipo=5','::1','2026-07-01 11:14:50'),(6,1,'emartinez','crear','marca','facturacion','::1','2026-07-01 11:15:39'),(7,1,'emartinez','crear','usuarios','www','::1','2026-07-02 11:44:51'),(8,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-06 18:45:53'),(9,7,'wcaste','crear','asignacion','emp=7 equipo=1','::1','2026-07-06 18:49:39'),(10,7,'wcaste','crear','asignacion','emp=4 equipo=3','::1','2026-07-06 18:50:02'),(11,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 11:31:20'),(12,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 11:33:55'),(13,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:04:00'),(14,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:04:04'),(15,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:31:29'),(16,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:34:14'),(17,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:41:17'),(18,7,'wcaste','login_exitoso',NULL,NULL,'::1','2026-07-15 12:45:07'),(19,7,'wcaste','crear','empleados','Wilfredos Castellanoss','::1','2026-07-15 12:46:48'),(20,7,'wcaste','crear','usuarios','wcastess','::1','2026-07-15 12:47:07'),(21,9,'wcastess','login_exitoso',NULL,NULL,'::1','2026-07-15 12:47:14'),(22,9,'wcastess','login_exitoso',NULL,NULL,'::1','2026-07-15 13:24:47'),(23,8,'www','login_exitoso',NULL,NULL,'::1','2026-07-16 10:34:41'),(24,9,'wcastess','crear','acta_firma','asignacion #7','::1','2026-07-16 10:53:40'),(25,9,'wcastess','crear','asignacion','emp=2 equipo=4','::1','2026-07-16 10:54:24'),(26,9,'wcastess','crear','acta_firma','asignacion #9','::1','2026-07-16 10:54:33'),(27,9,'wcastess','crear','acta_firma','asignacion #9','::1','2026-07-16 10:57:12'),(28,9,'wcastess','crear','acta_firma','asignacion #8','::1','2026-07-16 10:57:38'),(29,9,'wcastess','crear','acta_firma','asignacion #8','::1','2026-07-16 10:58:14'),(30,9,'wcastess','crear','acta_firma','asignacion #6','::1','2026-07-16 10:58:27'),(31,9,'wcastess','crear','acta_firma','asignacion #5','::1','2026-07-16 10:58:47'),(32,9,'wcastess','login_exitoso',NULL,NULL,'127.0.0.1','2026-07-16 11:24:44'),(33,9,'wcastess','crear','acta_firma','asignacion #4','::1','2026-07-16 11:37:05'),(34,9,'wcastess','login_exitoso',NULL,NULL,'::1','2026-07-16 11:49:31'),(35,9,'wcastess','crear','equipos','marca=5 modelo=6','::1','2026-07-16 11:55:43'),(36,9,'wcastess','crear','asignacion','emp=7 equipo=7','::1','2026-07-16 11:56:03'),(37,9,'wcastess','crear','acta_firma','asignacion #10','::1','2026-07-16 11:56:17'),(38,9,'wcastess','login_exitoso',NULL,NULL,'::1','2026-07-16 12:11:41'),(39,9,'wcastess','crear','areas','Área QA Sistemas','::1','2026-07-16 12:20:44'),(40,9,'wcastess','crear','cargos','Analista QA IT','::1','2026-07-16 12:22:13'),(41,9,'wcastess','crear','marca','Lenovo QA','::1','2026-07-16 12:22:39'),(42,9,'wcastess','crear','modelo','ThinkPad QA T14','::1','2026-07-16 12:23:01'),(43,9,'wcastess','crear','empleados','ALMA PRUEBA','::1','2026-07-16 12:24:13'),(44,9,'wcastess','editar','empleados','ALMA PRUEBA (#41)','::1','2026-07-16 12:25:16'),(45,9,'wcastess','crear','usuarios','ALMA','::1','2026-07-16 12:26:48'),(46,10,'ALMA','login_exitoso',NULL,NULL,'::1','2026-07-16 12:27:27'),(47,9,'wcastess','login_exitoso',NULL,NULL,'::1','2026-07-16 12:28:33'),(48,9,'wcastess','crear','equipos','#8 marca=6 modelo=7','::1','2026-07-16 12:29:39'),(49,9,'wcastess','crear','asignacion','emp=41 equipo=8','::1','2026-07-16 12:30:46'),(50,9,'wcastess','crear','acta_firma','asignacion #11','::1','2026-07-16 12:32:54'),(51,9,'wcastess','devolucion','asignacion','#11 equipo=8','::1','2026-07-16 12:35:24'),(52,9,'wcastess','editar','equipos','#8','::1','2026-07-16 12:36:48'),(53,9,'wcastess','editar','equipos','#8','::1','2026-07-16 12:37:14'),(54,9,'wcastess','crear','empleados','ADMIN ADMIN','::1','2026-07-16 12:41:18'),(55,9,'wcastess','crear','usuarios','ADMIN','::1','2026-07-16 12:41:42'),(56,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-16 12:41:49'),(59,1,'emartinez','devolver','asignaciones','asignación #25; condición: Bueno; estado equipo: 1','127.0.0.1','2026-07-19 12:28:41'),(60,1,'emartinez','devolver','asignaciones','asignación #26; condición: Con daño; estado equipo: 3','127.0.0.1','2026-07-19 12:29:02'),(61,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 13:42:39'),(62,11,'ADMIN','crear','asignacion','#27 emp=43 equipo=21','::1','2026-07-19 13:43:57'),(63,11,'ADMIN','crear','acta_entrega','asignacion #27','::1','2026-07-19 13:44:06'),(64,11,'ADMIN','devolver','asignaciones','asignación #27; condición: Bueno; estado equipo: 1','::1','2026-07-19 13:44:37'),(65,11,'ADMIN','devolver','asignaciones','asignación #17; condición: Con daño; estado equipo: 3','::1','2026-07-19 13:45:13'),(66,11,'ADMIN','editar','usuarios','ALMA (#10)','::1','2026-07-19 13:46:21'),(67,11,'ADMIN','eliminar','usuarios','#1','::1','2026-07-19 13:46:31'),(68,11,'ADMIN','reactivar','usuarios','#1','::1','2026-07-19 13:46:36'),(69,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 14:19:31'),(70,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 14:31:57'),(71,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 14:50:46'),(72,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 16:39:46'),(73,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 20:03:16'),(74,11,'ADMIN','editar','asignacion','#16 emp=47 equipo=13 anterior=13','::1','2026-07-19 20:10:45'),(75,NULL,'ADMIN','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:12:50'),(76,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 20:12:50'),(77,NULL,'ADMINyuiktu6t','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:13:01'),(78,NULL,'ADMINyuiktu6t','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:13:08'),(79,NULL,'ADMINyuiktu6t','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:13:10'),(80,NULL,'ADMINyuiktu6t','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:13:11'),(81,NULL,'ADMINyuiktu6t','login_fallido',NULL,'Intento fallido desde ::1','::1','2026-07-19 20:13:13'),(82,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-19 20:13:23'),(83,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-20 18:13:40'),(84,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-20 18:34:06'),(85,11,'ADMIN','crear','marca','psa (alta contextual)','::1','2026-07-20 18:35:44'),(86,11,'ADMIN','crear','equipos','#39 marca=17 modelo=19','::1','2026-07-20 18:37:20'),(87,11,'ADMIN','eliminar','areas','#12','::1','2026-07-20 18:39:32'),(88,11,'ADMIN','eliminar','empleados','#67','::1','2026-07-20 18:39:44'),(89,11,'ADMIN','crear','asignacion','#28 emp=52 equipo=22','::1','2026-07-20 18:41:19'),(90,11,'ADMIN','crear','acta_entrega','asignacion #28','::1','2026-07-20 18:42:07'),(91,11,'ADMIN','devolver','asignaciones','asignación #28; condición: Bueno; estado equipo: 1','::1','2026-07-20 18:43:11'),(92,11,'ADMIN','crear','usuarios','ADMINn','::1','2026-07-20 18:46:00'),(93,11,'ADMIN','crear','empleados','Wilfredo Castellanos','::1','2026-07-21 18:20:03'),(94,12,'ADMINn','login_exitoso',NULL,NULL,'::1','2026-07-27 09:58:39'),(95,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-27 10:04:58'),(96,11,'ADMIN','crear','asignacion','#29 emp=4 equipo=25','::1','2026-07-27 10:37:24'),(97,11,'ADMIN','crear','acta_entrega','asignacion #29','::1','2026-07-27 10:37:47'),(98,11,'ADMIN','crear','equipos','#40 marca=11 modelo=15','::1','2026-07-27 11:46:20'),(99,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-28 11:14:50'),(100,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-28 11:40:16'),(101,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-28 12:01:43'),(102,11,'ADMIN','eliminar','equipos','#40','::1','2026-07-28 12:01:59'),(103,11,'ADMIN','reactivar','equipos','#40','::1','2026-07-28 12:02:07'),(104,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-28 12:08:49'),(105,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-31 11:58:03'),(106,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-31 11:58:13'),(107,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-31 15:14:34'),(108,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-07-31 15:14:50'),(109,11,'ADMIN','crear','software','#1 AAAA AAA','::1','2026-07-31 15:16:29'),(110,11,'ADMIN','cerrar','mantenimientos','mantenimiento #1 equipo=14 resultado=Reparado','::1','2026-07-31 15:19:02'),(111,11,'ADMIN','revelar_clave','licencias','LIC-0005 (#5)','::1','2026-07-31 15:39:02'),(112,11,'ADMIN','eliminar','licencias','LIC-0002 (#2)','::1','2026-07-31 15:43:57'),(113,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-08-11 10:43:27'),(114,11,'ADMIN','login_exitoso',NULL,NULL,'::1','2026-08-11 10:43:27'),(115,11,'ADMIN','cerrar','mantenimientos','mantenimiento #2 equipo=27 resultado=Reparado','::1','2026-08-11 11:21:23'),(116,2,'cpavon','crear','mantenimientos','Mantenimiento preventivo abierto para EQ-0040','localhost','2026-08-11 11:44:19');
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cargos`
--

DROP TABLE IF EXISTS `cargos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargos` (
  `idcargo` int(11) NOT NULL AUTO_INCREMENT,
  `descripcioncargo` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idcargo`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos`
--

LOCK TABLES `cargos` WRITE;
/*!40000 ALTER TABLE `cargos` DISABLE KEYS */;
INSERT INTO `cargos` VALUES (1,'Administrador General',1),(2,'Jefe de Contabilidad',1),(3,'Auxiliar Contable',1),(4,'Gerente Administrativo',1),(5,'Recepcionista',1),(6,'Auxiliar de Soporte IT',1),(7,'Asistente de Recursos Humanos',1),(8,'Gerente Comercial',1),(16,'Facturador',1),(18,'Analista QA IT',1),(19,'Desarrollador de Software',1),(20,'Analista de Sistemas',1),(21,'Administrador de Redes',1),(22,'Especialista en Ciberseguridad',1),(23,'Arquitecto de Soluciones',1),(24,'Administrador de Base de Datos',1),(25,'Ingeniero de Datos',1),(26,'Soporte Técnico N2',1),(27,'Líder Técnico',1),(28,'Especialista en QA',1),(29,'Coordinador ITAM',1),(30,'Técnico de Campo',1);
/*!40000 ALTER TABLE `cargos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `idempleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `telefono` int(11) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `imagen` varchar(500) NOT NULL,
  `idcargo` int(11) NOT NULL,
  `idarea` int(11) NOT NULL,
  `idsexo` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `correo` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`idempleado`),
  KEY `idcargo_idx` (`idcargo`),
  KEY `idarea_idx` (`idarea`),
  KEY `empleados_FK_2` (`idsexo`),
  CONSTRAINT `empleados_FK` FOREIGN KEY (`idcargo`) REFERENCES `cargos` (`idcargo`),
  CONSTRAINT `empleados_FK_1` FOREIGN KEY (`idarea`) REFERENCES `areas` (`idarea`),
  CONSTRAINT `empleados_FK_2` FOREIGN KEY (`idsexo`) REFERENCES `sexo` (`idsexo`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (1,'Edwin Enrique','Martinez Diaz',32,32650620,'Santa Marta','public/img/empleados/avatar1.png',1,1,1,1,NULL),(2,'Denia Maricela','Nuñez Velasquez',28,33003400,'Tegucigalpa','imagenes/empleados/avatar2.png',2,2,2,1,NULL),(4,'Eduardo','Papadopolo',25,89982210,'Tegucigalpa','imagenes/empleados/avatar2.png',3,3,1,1,NULL),(5,'Karla Patricia','Nuñez Garcia',30,33456790,'Tegucigalpa','imagenes/empleados/avatar2.png',4,4,2,1,NULL),(6,'Henry ','Cavill',45,99807765,'Tegucigalpa','imagenes/empleados/avatar2.png',5,5,1,1,NULL),(7,'Jennifer Esthefania','Carbajal Ponce',40,88778866,'San Barbara','imagenes/empleados/avatar2.png',2,2,2,1,NULL),(8,'Juan Ramon','Ledezma Velasquez',36,99807766,'Santa Barbara','imagenes/empleados/avatar2.png',3,2,1,1,NULL),(9,'Adelina','Flores Moreno',29,88973320,'Tegucigalpa','imagenes/empleados/avatar2.png',4,5,2,1,NULL),(10,'Diana Emilia','Medina Ordoñez',30,33456787,'Tegucigalpa','imagenes/empleados/avatar2.png',6,7,2,1,NULL),(11,'wilfredo','castellanos',21,98679665,'santa barbara santa barbara','imagenes/empleados/avatar2.png',1,1,1,1,NULL),(39,'Wilfredo','Castellanos',24,94934008,'San Pedro sula','public/img/empleados/avatar1.png',2,7,1,1,NULL),(40,'Wilfredos','Castellanoss',24,94934008,'San Pedro sula','',1,2,1,1,NULL),(41,'ALMA','PRUEBA',28,NULL,'Laboratori','public/img/empleados/emp_8f336b3299a7fb28.jpg',18,11,2,1,'curiosos131@gmail.com'),(42,'ADMIN','ADMIN',54,94934008,'San Pedro sula','public/img/empleados/emp_7dd821c7866879fa.jpg',18,11,1,1,'curiosoos131@gmail.com'),(43,'Alan','Turingston',41,98741230,'Tegucigalpa','public/img/empleados/avatar1.png',22,14,1,1,'alan.turingston@demo.test'),(44,'Ada','Lovelance',33,88452201,'San Pedro Sula','public/img/empleados/avatar2.png',19,12,2,1,'ada.lovelance@demo.test'),(45,'Grace','Hoppman',47,99562310,'Comayagua','public/img/empleados/avatar2.png',27,12,2,1,'grace.hoppman@demo.test'),(46,'Linus','Kernelvalds',29,32456871,'Tegucigalpa','public/img/empleados/avatar1.png',21,13,1,1,'linus.kernelvalds@demo.test'),(47,'Esteban','Jobsanz',38,88907654,'San Pedro Sula','public/img/empleados/avatar1.png',23,12,1,1,'esteban.jobsanz@demo.test'),(48,'Anabella','Borgman',31,99123456,'La Ceiba','public/img/empleados/avatar2.png',25,16,2,1,'anabella.borgman@demo.test'),(49,'Guillermo','Gatezz',44,32678901,'Tegucigalpa','public/img/empleados/avatar1.png',20,17,1,1,'guillermo.gatezz@demo.test'),(50,'Marcos','Zuckerman',27,88345678,'San Pedro Sula','public/img/empleados/avatar1.png',19,12,1,1,'marcos.zuckerman@demo.test'),(51,'Timoteo','Bernerly',39,99456123,'Tegucigalpa','public/img/empleados/avatar1.png',23,13,1,1,'timoteo.bernerly@demo.test'),(52,'Dionisio','Ritchford',42,32567890,'Choloma','public/img/empleados/avatar1.png',19,12,1,1,'dionisio.ritchford@demo.test'),(53,'Kendall','Thompsen',36,88678123,'Tegucigalpa','public/img/empleados/avatar1.png',26,5,1,1,'kendall.thompsen@demo.test'),(54,'Guido','Vanderrosum',34,99789456,'San Pedro Sula','public/img/empleados/avatar1.png',19,12,1,1,'guido.vanderrosum@demo.test'),(55,'Margarita','Hamilton',46,32890567,'Tegucigalpa','public/img/empleados/avatar2.png',27,15,2,1,'margarita.hamilton@demo.test'),(56,'Donaldo','Knuthe',50,88901234,'Comayagua','public/img/empleados/avatar1.png',20,17,1,1,'donaldo.knuthe@demo.test'),(57,'Ricardo','Stallmont',43,99012345,'Tegucigalpa','public/img/empleados/avatar1.png',19,12,1,1,'ricardo.stallmont@demo.test'),(58,'Vicente','Cerfino',37,32123098,'San Pedro Sula','public/img/empleados/avatar1.png',21,13,1,1,'vicente.cerfino@demo.test'),(59,'Francisca','Allenton',40,88234109,'Tegucigalpa','public/img/empleados/avatar2.png',19,15,2,1,'francisca.allenton@demo.test'),(60,'Lorenzo','Pageson',26,99345210,'La Ceiba','public/img/empleados/avatar1.png',28,15,1,1,'lorenzo.pageson@demo.test'),(61,'Sergio','Brinsky',28,32456321,'Tegucigalpa','public/img/empleados/avatar1.png',20,17,1,1,'sergio.brinsky@demo.test'),(62,'Satoshi','Nakamura',35,88567432,'San Pedro Sula','public/img/empleados/avatar1.png',22,14,1,1,'satoshi.nakamura@demo.test'),(63,'Isabel','Feinlerman',45,99678543,'Tegucigalpa','public/img/empleados/avatar2.png',24,16,2,1,'isabel.feinlerman@demo.test'),(64,'Catalina','Johanson',32,32789654,'Comayagua','public/img/empleados/avatar2.png',25,16,2,1,'catalina.johanson@demo.test'),(65,'Rosalinda','Perlman',41,88890765,'Tegucigalpa','public/img/empleados/avatar2.png',21,13,2,1,'rosalinda.perlman@demo.test'),(66,'Jaime','Goslinger',39,99901876,'San Pedro Sula','public/img/empleados/avatar1.png',19,12,1,1,'jaime.goslinger@demo.test'),(67,'Yukiro','Matsuda',30,32012987,'Tegucigalpa','public/img/empleados/avatar1.png',19,12,1,0,'yukiro.matsuda@demo.test'),(68,'Barbara','Liskovna',48,88123098,'Choloma','public/img/empleados/avatar2.png',23,19,2,1,'barbara.liskovna@demo.test'),(69,'Karina','Sparckson',33,99234109,'Tegucigalpa','public/img/empleados/avatar2.png',20,17,2,1,'karina.sparckson@demo.test'),(70,'Brian','Kernighanz',44,32345210,'San Pedro Sula','public/img/empleados/avatar1.png',28,15,1,1,'brian.kernighanz@demo.test'),(71,'Jefferson','Deane',37,88456321,'Tegucigalpa','public/img/empleados/avatar1.png',29,18,1,1,'jefferson.deane@demo.test'),(72,'Marisol','Mayerson',29,99567432,'La Ceiba','public/img/empleados/avatar2.png',30,5,2,0,'marisol.mayerson@demo.test'),(73,'Wilfredo','Castellanos',67,94934008,'San Pedro sula','public/img/empleados/emp_79adbb5fae3bbf31.jpg',24,2,1,1,'curiosiu7juos131@gmail.com');
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipo`
--

DROP TABLE IF EXISTS `equipo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipo` (
  `idequipo` int(11) NOT NULL AUTO_INCREMENT,
  `idmarca_equipo` int(11) NOT NULL,
  `idmodelo_equipo` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `imagen` varchar(500) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_compra` date DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `factura` varchar(100) DEFAULT NULL,
  `vencimiento_garantia` date DEFAULT NULL,
  `estado_equipo` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `numero_serie` varchar(100) DEFAULT NULL,
  `codigo_activo` varchar(20) DEFAULT NULL,
  `tipo_equipo` varchar(50) NOT NULL DEFAULT 'Otro',
  PRIMARY KEY (`idequipo`),
  UNIQUE KEY `uq_equipo_codigo_activo` (`codigo_activo`),
  UNIQUE KEY `uq_equipo_numero_serie` (`numero_serie`),
  KEY `idmarca_equipo` (`idmarca_equipo`),
  KEY `idmodelo_equipo` (`idmodelo_equipo`),
  KEY `idx_equipo_proveedor` (`idproveedor`),
  CONSTRAINT `equipo_ibfk_1` FOREIGN KEY (`idmodelo_equipo`) REFERENCES `modelo` (`idmodelo`),
  CONSTRAINT `equipo_ibfk_2` FOREIGN KEY (`idmarca_equipo`) REFERENCES `marca` (`idmarca`),
  CONSTRAINT `fk_equipo_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipo`
--

LOCK TABLES `equipo` WRITE;
/*!40000 ALTER TABLE `equipo` DISABLE KEYS */;
INSERT INTO `equipo` VALUES (1,2,4,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0001','Otro'),(2,1,2,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0002','Otro'),(3,1,1,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0003','Otro'),(4,1,1,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0004','Otro'),(5,1,3,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0005','Otro'),(6,4,6,NULL,'',1,NULL,NULL,NULL,NULL,2,NULL,'EQ-0006','Otro'),(7,5,6,NULL,'',1,'2026-07-08',22.00,'1111','2029-06-06',2,NULL,'EQ-0007','Otro'),(8,6,7,NULL,'',1,'2026-07-01',25000.00,'0001','2026-10-28',1,'NS10','EQ-0008','Laptop'),(9,1,8,NULL,'',1,'2025-01-15',48900.00,'DEMO-FAC-1001','2028-01-15',2,'DEMO-SN-1001','EQ-1001','Laptop'),(10,2,9,NULL,'',1,'2025-02-10',42500.00,'DEMO-FAC-1002','2028-02-10',2,'DEMO-SN-1002','EQ-1002','Laptop'),(11,3,10,NULL,'',1,'2025-03-08',46500.00,'DEMO-FAC-1003','2028-03-08',2,'DEMO-SN-1003','EQ-1003','Laptop'),(12,7,11,NULL,'',1,'2025-04-22',78500.00,'DEMO-FAC-1004','2028-04-22',2,'DEMO-SN-1004','EQ-1004','Laptop'),(13,8,12,NULL,'',1,'2025-05-16',53900.00,'DEMO-FAC-1005','2028-05-16',2,'DEMO-SN-1005','EQ-1005','Laptop'),(14,9,13,NULL,'',1,'2025-06-05',51200.00,'DEMO-FAC-1006','2028-06-05',1,'DEMO-SN-1006','EQ-1006','Laptop'),(15,10,14,NULL,'',1,'2025-06-20',44750.00,'DEMO-FAC-1007','2028-06-20',1,'DEMO-SN-1007','EQ-1007','Laptop'),(16,11,15,NULL,'',1,'2025-07-11',31800.00,'DEMO-FAC-1008','2028-07-11',1,'DEMO-SN-1008','EQ-1008','Laptop'),(17,3,16,NULL,'',1,'2024-08-15',16200.00,'DEMO-FAC-1009','2027-08-15',1,'DEMO-SN-1009','EQ-1009','Monitor'),(18,9,17,NULL,'',1,'2024-09-12',11800.00,'DEMO-FAC-1010','2027-09-12',1,'DEMO-SN-1010','EQ-1010','Monitor'),(19,2,18,NULL,'',1,'2024-10-03',14600.00,'DEMO-FAC-1011','2027-10-03',1,'DEMO-SN-1011','EQ-1011','Impresora'),(20,12,19,NULL,'',1,'2024-10-28',12900.00,'DEMO-FAC-1012','2027-10-28',1,'DEMO-SN-1012','EQ-1012','Impresora'),(21,9,20,NULL,'',1,'2025-01-09',22900.00,'DEMO-FAC-1013','2027-01-09',1,'DEMO-SN-1013','EQ-1013','Teléfono'),(22,7,21,10,'',1,'2025-01-19',26900.00,'DEMO-FAC-1014','2027-01-19',1,'DEMO-SN-1014','EQ-1014','Teléfono'),(23,13,22,9,'',1,'2024-11-07',68500.00,'DEMO-FAC-1015','2029-11-07',1,'DEMO-SN-1015','EQ-1015','Equipo de red'),(24,14,23,8,'',1,'2024-12-14',7200.00,'DEMO-FAC-1016','2027-12-14',1,'DEMO-SN-1016','EQ-1016','Equipo de red'),(25,15,24,NULL,'',1,'2025-02-26',245000.00,'DEMO-FAC-1017','2030-02-26',2,'DEMO-SN-1017','EQ-1017','Servidor'),(26,3,25,7,'',1,'2025-03-13',34200.00,'DEMO-FAC-1018','2028-03-13',1,'DEMO-SN-1018','EQ-1018','Computadora de escritorio'),(27,1,26,NULL,'',1,'2023-04-12',28700.00,'DEMO-FAC-1019','2026-04-12',1,'DEMO-SN-1019','EQ-1019','Computadora de escritorio'),(28,8,27,NULL,'',1,'2023-05-18',17300.00,'DEMO-FAC-1020','2026-05-18',3,'DEMO-SN-1020','EQ-1020','Monitor'),(29,2,9,NULL,'',1,'2023-06-21',36500.00,'DEMO-FAC-1021','2026-06-21',3,'DEMO-SN-1021','EQ-1021','Laptop'),(30,12,19,NULL,'',1,'2023-07-07',10800.00,'DEMO-FAC-1022','2026-07-07',3,'DEMO-SN-1022','EQ-1022','Impresora'),(31,3,10,NULL,'',1,'2023-08-16',39800.00,'DEMO-FAC-1023','2026-08-16',4,'DEMO-SN-1023','EQ-1023','Laptop'),(32,9,20,NULL,'',1,'2024-01-25',21600.00,'DEMO-FAC-1024','2026-01-25',4,'DEMO-SN-1024','EQ-1024','Teléfono'),(33,11,15,NULL,'',0,'2021-02-11',24800.00,'DEMO-FAC-1025','2024-02-11',5,'DEMO-SN-1025','EQ-1025','Laptop'),(34,9,17,NULL,'',0,'2020-09-30',8900.00,'DEMO-FAC-1026','2023-09-30',5,'DEMO-SN-1026','EQ-1026','Monitor'),(35,1,8,6,'',1,'2026-01-12',49800.00,'DEMO-FAC-1027','2029-01-12',1,'DEMO-SN-1027','EQ-1027','Laptop'),(36,7,11,5,'',1,'2026-02-09',79900.00,'DEMO-FAC-1028','2026-09-08',1,'DEMO-SN-1028','EQ-1028','Laptop'),(37,3,16,4,'',1,'2026-03-17',16900.00,'DEMO-FAC-1029','2026-09-02',1,'DEMO-SN-1029','EQ-1029','Monitor'),(38,14,23,3,'',1,'2026-04-06',7600.00,'DEMO-FAC-1030','2026-08-24',1,'DEMO-SN-1030','EQ-1030','Equipo de red'),(39,17,19,2,'',1,'2026-07-20',50000.00,'00014545','2026-07-26',1,'NS10000','EQ-0039','Impresora'),(40,11,15,1,'',1,NULL,NULL,NULL,'2026-08-19',3,NULL,'EQ-0040','Laptop');
/*!40000 ALTER TABLE `equipo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia_asignaciones`
--

DROP TABLE IF EXISTS `licencia_asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencia_asignaciones` (
  `idasignacion_licencia` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idcupo` int(11) DEFAULT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idequipo` int(11) DEFAULT NULL,
  `correo_cuenta` varchar(150) DEFAULT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` datetime DEFAULT NULL,
  `motivo_devolucion` varchar(500) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario_asignacion` int(11) DEFAULT NULL,
  `idusuario_devolucion` int(11) DEFAULT NULL,
  `cupo_asignado_activo` int(11) GENERATED ALWAYS AS (case when `activa` = 1 then `idcupo` else NULL end) STORED,
  `empleado_asignado_activo` int(11) GENERATED ALWAYS AS (case when `activa` = 1 then `idempleado` else NULL end) STORED,
  `equipo_asignado_activo` int(11) GENERATED ALWAYS AS (case when `activa` = 1 then `idequipo` else NULL end) STORED,
  PRIMARY KEY (`idasignacion_licencia`),
  UNIQUE KEY `uq_licencia_asignacion_compuesta` (`idlicencia`,`idasignacion_licencia`),
  UNIQUE KEY `uq_licencia_cupo_asignado_activo` (`cupo_asignado_activo`),
  UNIQUE KEY `uq_licencia_empleado_activo` (`idlicencia`,`empleado_asignado_activo`),
  UNIQUE KEY `uq_licencia_equipo_activo` (`idlicencia`,`equipo_asignado_activo`),
  KEY `idx_licencia_asignaciones_licencia` (`idlicencia`,`activa`),
  KEY `idx_licencia_asignaciones_empleado` (`idempleado`,`activa`),
  KEY `idx_licencia_asignaciones_equipo` (`idequipo`,`activa`),
  KEY `idx_licencia_asignaciones_usuario_asigna` (`idusuario_asignacion`),
  KEY `idx_licencia_asignaciones_usuario_devuelve` (`idusuario_devolucion`),
  KEY `fk_licencia_asignaciones_cupo` (`idlicencia`,`idcupo`),
  CONSTRAINT `fk_licencia_asignaciones_cupo` FOREIGN KEY (`idlicencia`, `idcupo`) REFERENCES `licencia_cupos` (`idlicencia`, `idcupo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_empleado` FOREIGN KEY (`idempleado`) REFERENCES `empleados` (`idempleado`),
  CONSTRAINT `fk_licencia_asignaciones_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`),
  CONSTRAINT `fk_licencia_asignaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_usuario_asigna` FOREIGN KEY (`idusuario_asignacion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_asignaciones_usuario_devuelve` FOREIGN KEY (`idusuario_devolucion`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_asignacion_destino` CHECK ((`idempleado` is not null) + (`idequipo` is not null) = 1),
  CONSTRAINT `chk_licencia_asignacion_estado` CHECK (`activa` = 1 and `fecha_devolucion` is null or `activa` = 0 and `fecha_devolucion` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_asignaciones`
--

LOCK TABLES `licencia_asignaciones` WRITE;
/*!40000 ALTER TABLE `licencia_asignaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `licencia_asignaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia_cupos`
--

DROP TABLE IF EXISTS `licencia_cupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencia_cupos` (
  `idcupo` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `numero_cupo` int(10) unsigned NOT NULL,
  `etiqueta` varchar(100) DEFAULT NULL,
  `clave_cifrada` text DEFAULT NULL,
  `clave_mascara` varchar(40) DEFAULT NULL,
  `clave_huella` char(64) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_retiro` datetime DEFAULT NULL,
  `motivo_retiro` varchar(500) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcupo`),
  UNIQUE KEY `uq_licencia_cupo_numero` (`idlicencia`,`numero_cupo`),
  UNIQUE KEY `uq_licencia_cupo_compuesto` (`idlicencia`,`idcupo`),
  KEY `idx_licencia_cupos_activos` (`idlicencia`,`activo`),
  CONSTRAINT `fk_licencia_cupos_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_cupos_numero` CHECK (`numero_cupo` > 0),
  CONSTRAINT `chk_licencia_cupos_activo` CHECK (`activo` in (0,1)),
  CONSTRAINT `chk_licencia_cupos_retiro` CHECK (`activo` = 1 and `fecha_retiro` is null or `activo` = 0 and `fecha_retiro` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_cupos`
--

LOCK TABLES `licencia_cupos` WRITE;
/*!40000 ALTER TABLE `licencia_cupos` DISABLE KEYS */;
INSERT INTO `licencia_cupos` VALUES (1,1,1,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(2,1,2,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(3,1,3,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(4,1,4,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(5,1,5,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(6,1,6,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(7,1,7,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(8,1,8,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(9,1,9,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(10,1,10,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(11,1,11,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(12,1,12,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(13,1,13,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(14,1,14,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(15,1,15,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(16,1,16,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(17,1,17,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(18,1,18,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(19,1,19,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(20,1,20,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(21,1,21,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(22,1,22,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(23,1,23,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(24,1,24,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(25,1,25,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(26,2,1,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(27,2,2,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(28,2,3,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(29,2,4,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(30,2,5,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(31,2,6,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(32,2,7,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(33,2,8,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(34,2,9,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(35,2,10,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(36,3,1,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(37,3,2,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(38,3,3,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(39,3,4,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(40,3,5,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(41,3,6,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(42,3,7,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(43,3,8,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(44,3,9,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(45,3,10,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(46,3,11,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(47,3,12,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(48,3,13,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(49,3,14,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(50,3,15,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(51,3,16,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(52,3,17,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(53,3,18,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(54,3,19,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(55,3,20,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(56,3,21,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(57,3,22,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(58,3,23,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(59,3,24,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(60,3,25,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(61,3,26,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(62,3,27,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(63,3,28,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(64,3,29,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(65,3,30,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(66,3,31,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(67,3,32,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(68,3,33,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(69,3,34,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(70,3,35,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(71,3,36,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(72,3,37,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(73,3,38,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(74,3,39,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(75,3,40,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(76,4,1,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(77,4,2,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(78,4,3,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(79,4,4,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(80,4,5,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(81,4,6,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(82,4,7,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(83,4,8,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(84,4,9,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(85,4,10,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(86,4,11,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(87,4,12,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(88,4,13,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(89,4,14,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(90,4,15,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(91,5,1,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(92,5,2,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(93,5,3,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(94,5,4,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34'),(95,5,5,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-07-31 15:42:34');
/*!40000 ALTER TABLE `licencia_cupos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia_instalaciones`
--

DROP TABLE IF EXISTS `licencia_instalaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencia_instalaciones` (
  `idinstalacion` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idasignacion_licencia` int(11) DEFAULT NULL,
  `idequipo` int(11) NOT NULL,
  `version_instalada` varchar(80) DEFAULT NULL,
  `fecha_instalacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_desinstalacion` datetime DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario_registro` int(11) DEFAULT NULL,
  `idusuario_retiro` int(11) DEFAULT NULL,
  `licencia_instalada_activa` int(11) GENERATED ALWAYS AS (case when `activa` = 1 then `idlicencia` else NULL end) STORED,
  `equipo_instalacion_activa` int(11) GENERATED ALWAYS AS (case when `activa` = 1 then `idequipo` else NULL end) STORED,
  PRIMARY KEY (`idinstalacion`),
  UNIQUE KEY `uq_licencia_instalacion_activa` (`licencia_instalada_activa`,`equipo_instalacion_activa`),
  KEY `idx_licencia_instalaciones_licencia` (`idlicencia`,`activa`),
  KEY `idx_licencia_instalaciones_equipo` (`idequipo`,`activa`),
  KEY `idx_licencia_instalaciones_asignacion` (`idlicencia`,`idasignacion_licencia`),
  KEY `idx_licencia_instalaciones_usuario_registro` (`idusuario_registro`),
  KEY `idx_licencia_instalaciones_usuario_retiro` (`idusuario_retiro`),
  CONSTRAINT `fk_licencia_instalaciones_asignacion` FOREIGN KEY (`idlicencia`, `idasignacion_licencia`) REFERENCES `licencia_asignaciones` (`idlicencia`, `idasignacion_licencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_usuario_registro` FOREIGN KEY (`idusuario_registro`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_instalaciones_usuario_retiro` FOREIGN KEY (`idusuario_retiro`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_instalacion_estado` CHECK (`activa` = 1 and `fecha_desinstalacion` is null or `activa` = 0 and `fecha_desinstalacion` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_instalaciones`
--

LOCK TABLES `licencia_instalaciones` WRITE;
/*!40000 ALTER TABLE `licencia_instalaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `licencia_instalaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia_renovaciones`
--

DROP TABLE IF EXISTS `licencia_renovaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencia_renovaciones` (
  `idrenovacion` int(11) NOT NULL AUTO_INCREMENT,
  `idlicencia` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `fecha_renovacion` date NOT NULL,
  `fecha_vencimiento_anterior` date DEFAULT NULL,
  `fecha_vencimiento_nueva` date DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'HNL',
  `factura` varchar(100) DEFAULT NULL,
  `orden_compra` varchar(100) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idrenovacion`),
  KEY `idx_licencia_renovaciones_licencia` (`idlicencia`,`fecha_renovacion`),
  KEY `idx_licencia_renovaciones_proveedor` (`idproveedor`),
  KEY `idx_licencia_renovaciones_usuario` (`idusuario`),
  CONSTRAINT `fk_licencia_renovaciones_licencia` FOREIGN KEY (`idlicencia`) REFERENCES `licencias` (`idlicencia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_renovaciones_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_renovaciones_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_licencia_renovaciones_costo` CHECK (`costo` is null or `costo` >= 0),
  CONSTRAINT `chk_licencia_renovaciones_fechas` CHECK (`fecha_vencimiento_anterior` is null or `fecha_vencimiento_nueva` is null or `fecha_vencimiento_nueva` >= `fecha_vencimiento_anterior`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_renovaciones`
--

LOCK TABLES `licencia_renovaciones` WRITE;
/*!40000 ALTER TABLE `licencia_renovaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `licencia_renovaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencias`
--

DROP TABLE IF EXISTS `licencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencias` (
  `idlicencia` int(11) NOT NULL AUTO_INCREMENT,
  `idsoftware` int(11) NOT NULL,
  `idproveedor` int(11) DEFAULT NULL,
  `codigo_licencia` varchar(24) DEFAULT NULL,
  `modalidad` varchar(20) NOT NULL,
  `metrica` varchar(30) NOT NULL,
  `cantidad_total` int(10) unsigned DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `renovacion_automatica` tinyint(1) NOT NULL DEFAULT 0,
  `reutilizable` tinyint(1) NOT NULL DEFAULT 1,
  `costo_total` decimal(12,2) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'HNL',
  `factura` varchar(100) DEFAULT NULL,
  `orden_compra` varchar(100) DEFAULT NULL,
  `numero_contrato` varchar(100) DEFAULT NULL,
  `licenciado_a_nombre` varchar(150) DEFAULT NULL,
  `licenciado_a_correo` varchar(150) DEFAULT NULL,
  `clave_cifrada` text DEFAULT NULL,
  `clave_mascara` varchar(40) DEFAULT NULL,
  `clave_huella` char(64) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idlicencia`),
  UNIQUE KEY `uq_licencias_codigo` (`codigo_licencia`),
  KEY `idx_licencias_software` (`idsoftware`),
  KEY `idx_licencias_proveedor` (`idproveedor`),
  KEY `idx_licencias_vencimiento` (`activo`,`fecha_vencimiento`),
  KEY `idx_licencias_modalidad_metrica` (`modalidad`,`metrica`),
  CONSTRAINT `fk_licencias_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_licencias_software` FOREIGN KEY (`idsoftware`) REFERENCES `software` (`idsoftware`) ON UPDATE CASCADE,
  CONSTRAINT `chk_licencias_modalidad` CHECK (`modalidad` in ('Perpetua','Suscripción','Prueba')),
  CONSTRAINT `chk_licencias_metrica` CHECK (`metrica` in ('Usuario','Dispositivo','Concurrente','Corporativa','Servidor/Procesador')),
  CONSTRAINT `chk_licencias_cantidad` CHECK (`cantidad_total` is null or `cantidad_total` > 0),
  CONSTRAINT `chk_licencias_costo` CHECK (`costo_total` is null or `costo_total` >= 0),
  CONSTRAINT `chk_licencias_fechas` CHECK (`fecha_inicio` is null or `fecha_vencimiento` is null or `fecha_vencimiento` >= `fecha_inicio`),
  CONSTRAINT `chk_licencias_banderas` CHECK (`renovacion_automatica` in (0,1) and `reutilizable` in (0,1) and `activo` in (0,1))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencias`
--

LOCK TABLES `licencias` WRITE;
/*!40000 ALTER TABLE `licencias` DISABLE KEYS */;
INSERT INTO `licencias` VALUES (1,2,1,'LIC-0001','Suscripción','Usuario',25,'2026-07-01','2026-07-01','2027-06-30',1,1,1250.00,'USD','DEMO-LIC-FAC-001','DEMO-LIC-OC-001','DEMO-LIC-CONTRATO-001','GestActivos - Datos demostrativos','licencias001@demo.test','v1:duiVAflK3p6bHP014XNkDeaY6PPVvZuWLhEEVZtbHnXwiasnTYvxajp/qSivw8GxnN1YCA==','••••-••••-0001','d9eb0cffc09aed873ddd30d6b4da8cf2437aad9bd9671e8207f2efbb6b2ba4e6','Registro ficticio generado por database/seed_demo_licencias.php.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(2,3,2,'LIC-0002','Suscripción','Usuario',10,'2026-01-01','2026-01-01','2026-08-15',1,1,780.00,'USD','DEMO-LIC-FAC-002','DEMO-LIC-OC-002','DEMO-LIC-CONTRATO-002','GestActivos - Datos demostrativos','licencias002@demo.test','v1:RYaYX8dEz/oGoZAK4LTkQvsM3+Ys+QhKYVlvNKP/vK/e5aq9aPF05pb+/u9umKqCWrGOCw==','••••-••••-0002','a8d875c63fab8cf6676ac8585e14392323abbc1520f5a9cdadde6a9a521395b1','Registro ficticio generado por database/seed_demo_licencias.php.',0,'2026-07-31 15:20:14','2026-07-31 15:43:57'),(3,4,3,'LIC-0003','Suscripción','Dispositivo',40,'2025-07-01','2025-07-01','2026-06-30',1,1,980.00,'USD','DEMO-LIC-FAC-003','DEMO-LIC-OC-003','DEMO-LIC-CONTRATO-003','GestActivos - Datos demostrativos','licencias003@demo.test','v1:Gcg7hhljKqMRsJpgz+YoV3S4/7yJZdPvxkrPuTOrV4DkUNX/j16zZM9C55Wn3Sij0CYu','••••-••••-0003','586f7f0b460bcbcb0fcbcf00f23d96a74036653177963b647a695736a322aeb4','Registro ficticio generado por database/seed_demo_licencias.php.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(4,5,4,'LIC-0004','Perpetua','Dispositivo',15,'2026-02-10',NULL,NULL,0,1,2100.00,'USD','DEMO-LIC-FAC-004','DEMO-LIC-OC-004','DEMO-LIC-CONTRATO-004','GestActivos - Datos demostrativos','licencias004@demo.test','v1:Fg+5vvitduIyJjAHXXcvNE6F4gm5Q2oSxE9ILUfG16OweZlYl4N00+swNi5FdPJ+X8kH','••••-••••-0004','331a91977da6d4e5177b517c8825c585100172f099670c25578011fe08aed037','Registro ficticio generado por database/seed_demo_licencias.php.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(5,6,5,'LIC-0005','Suscripción','Concurrente',5,'2026-07-15','2026-07-15','2027-07-14',1,1,650.00,'USD','DEMO-LIC-FAC-005','DEMO-LIC-OC-005','DEMO-LIC-CONTRATO-005','GestActivos - Datos demostrativos','licencias005@demo.test','v1:MindJH9s0DxIlUELtBKOOhkUNkpcuJHNVcdJc68SE4t8cVZNB08ZN/TujzuGUphbpw==','••••-••••-0005','b58dab53b320f4616a32b6059976f07b009ef47648ce6697e112fb897e8be5fc','Registro ficticio generado por database/seed_demo_licencias.php.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(6,7,6,'LIC-0006','Suscripción','Corporativa',NULL,'2026-07-31','2026-07-31','2027-07-30',1,1,1800.00,'USD','DEMO-LIC-FAC-006','DEMO-LIC-OC-006','DEMO-LIC-CONTRATO-006','GestActivos - Datos demostrativos','licencias006@demo.test','v1:lpfDQ5sXVjZXIl6tPIHZspEt7X/K6obdOcPv4zil98hqE1MPAHd1eRMqXYM6PlNB','••••-••••-0006','8e90045a4c175574a5290cc46af6a05ac40f5aab2344446a2f7367a4001f3cae','Registro ficticio generado por database/seed_demo_licencias.php.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14');
/*!40000 ALTER TABLE `licencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimientos`
--

DROP TABLE IF EXISTS `mantenimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantenimientos` (
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
  CONSTRAINT `fk_mantenimiento_asignacion` FOREIGN KEY (`idasignacion_origen`) REFERENCES `asignacion` (`idasignacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_equipo` FOREIGN KEY (`idequipo`) REFERENCES `equipo` (`idequipo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedores` (`idproveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_usuario_apertura` FOREIGN KEY (`idusuario_apertura`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_usuario_cierre` FOREIGN KEY (`idusuario_cierre`) REFERENCES `usuarios` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantenimientos`
--

LOCK TABLES `mantenimientos` WRITE;
/*!40000 ALTER TABLE `mantenimientos` DISABLE KEYS */;
INSERT INTO `mantenimientos` VALUES (1,14,NULL,NULL,'Correctivo','Completado','2026-07-31 12:16:35','2026-07-31 15:19:02','Registro inicial generado al habilitar el módulo de mantenimientos.','A','A',2.00,'Reparado',NULL,1,'Migración',NULL,11,'2026-07-31 12:16:35','2026-07-31 15:19:02'),(2,27,NULL,NULL,'Correctivo','Completado','2026-07-31 12:16:35','2026-08-11 11:21:23','Registro inicial generado al habilitar el módulo de mantenimientos.','a','a',1.00,'Reparado','a',1,'Migración',NULL,11,'2026-07-31 12:16:35','2026-08-11 11:21:23'),(3,28,NULL,NULL,'Correctivo','Abierto','2026-07-31 12:16:35',NULL,'Registro inicial generado al habilitar el módulo de mantenimientos.',NULL,NULL,NULL,NULL,NULL,1,'Migración',NULL,NULL,'2026-07-31 12:16:35','2026-07-31 12:16:35'),(4,29,NULL,NULL,'Correctivo','Abierto','2026-07-31 12:16:35',NULL,'Registro inicial generado al habilitar el módulo de mantenimientos.',NULL,NULL,NULL,NULL,NULL,1,'Migración',NULL,NULL,'2026-07-31 12:16:35','2026-07-31 12:16:35'),(5,30,NULL,NULL,'Correctivo','Abierto','2026-07-31 12:16:35',NULL,'Registro inicial generado al habilitar el módulo de mantenimientos.',NULL,NULL,NULL,NULL,NULL,1,'Migración',NULL,NULL,'2026-07-31 12:16:35','2026-07-31 12:16:35'),(8,40,1,NULL,'Preventivo','Completado','2026-07-25 14:08:42','2026-07-26 14:08:42','Mantenimiento demostrativo #01 para validar el historial técnico.','Equipo operativo; mantenimiento preventivo programado.','Limpieza, actualización y comprobación general.',260.50,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(9,39,2,NULL,'Correctivo','Completado','2026-07-19 14:08:42','2026-07-20 14:08:42','Mantenimiento demostrativo #02 para validar el historial técnico.','Se detectó desgaste normal de componentes.','Ajuste, limpieza y sustitución de componente de prueba.',346.00,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(10,38,3,NULL,'Preventivo','Completado','2026-07-13 14:08:42','2026-07-14 14:08:42','Mantenimiento demostrativo #03 para validar el historial técnico.','Equipo operativo; mantenimiento preventivo programado.','Limpieza, actualización y comprobación general.',431.50,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(11,37,4,NULL,'Correctivo','Completado','2026-07-07 14:08:42','2026-07-08 14:08:42','Mantenimiento demostrativo #04 para validar el historial técnico.','Se detectó desgaste normal de componentes.','Ajuste, limpieza y sustitución de componente de prueba.',517.00,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(12,36,5,NULL,'Preventivo','Completado','2026-07-01 14:08:42','2026-07-02 14:08:42','Mantenimiento demostrativo #05 para validar el historial técnico.','Equipo operativo; mantenimiento preventivo programado.','Limpieza, actualización y comprobación general.',602.50,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(13,35,6,NULL,'Correctivo','Completado','2026-06-25 14:08:42','2026-06-26 14:08:42','Mantenimiento demostrativo #06 para validar el historial técnico.','Se detectó desgaste normal de componentes.','Ajuste, limpieza y sustitución de componente de prueba.',688.00,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(14,26,7,NULL,'Preventivo','Completado','2026-06-19 14:08:42','2026-06-20 14:08:42','Mantenimiento demostrativo #07 para validar el historial técnico.','Equipo operativo; mantenimiento preventivo programado.','Limpieza, actualización y comprobación general.',773.50,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(15,24,8,NULL,'Correctivo','Completado','2026-06-13 14:08:42','2026-06-14 14:08:42','Mantenimiento demostrativo #08 para validar el historial técnico.','Se detectó desgaste normal de componentes.','Ajuste, limpieza y sustitución de componente de prueba.',859.00,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(16,23,9,NULL,'Preventivo','Completado','2026-06-07 14:08:42','2026-06-08 14:08:42','Mantenimiento demostrativo #09 para validar el historial técnico.','Equipo operativo; mantenimiento preventivo programado.','Limpieza, actualización y comprobación general.',944.50,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(17,22,10,NULL,'Correctivo','Completado','2026-06-01 14:08:42','2026-06-02 14:08:42','Mantenimiento demostrativo #10 para validar el historial técnico.','Se detectó desgaste normal de componentes.','Ajuste, limpieza y sustitución de componente de prueba.',1030.00,'Reparado','Registro generado por database/seed_demo_proveedores_mantenimientos.sql.',1,'Seed demo',1,1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(18,40,NULL,NULL,'Preventivo','Abierto','2026-08-11 11:44:19',NULL,'Revision general preventiva y limpieza interna.','Pendiente de evaluacion tecnica.',NULL,NULL,NULL,'Mantenimiento de control agregado para seguimiento operativo.',1,'Manual',2,NULL,'2026-08-11 11:44:19','2026-08-11 11:45:08');
/*!40000 ALTER TABLE `mantenimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marca`
--

DROP TABLE IF EXISTS `marca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marca` (
  `idmarca` int(11) NOT NULL AUTO_INCREMENT,
  `nombreMarca` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idmarca`),
  KEY `nombreMarca` (`nombreMarca`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marca`
--

LOCK TABLES `marca` WRITE;
/*!40000 ALTER TABLE `marca` DISABLE KEYS */;
INSERT INTO `marca` VALUES (1,'Lenovo',1),(2,'HP',1),(3,'dell',1),(4,'dell2',1),(5,'facturacion',1),(6,'Lenovo QA',1),(7,'Apple',1),(8,'ASUS',1),(9,'Samsung',1),(10,'Microsoft',1),(11,'Acer',1),(12,'Epson',1),(13,'Cisco',1),(14,'Ubiquiti',1),(15,'HPE',1),(17,'psa',1);
/*!40000 ALTER TABLE `marca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modelo`
--

DROP TABLE IF EXISTS `modelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modelo` (
  `idmodelo` int(11) NOT NULL AUTO_INCREMENT,
  `nombreModelo` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idmodelo`),
  KEY `nombreModelo` (`nombreModelo`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelo`
--

LOCK TABLES `modelo` WRITE;
/*!40000 ALTER TABLE `modelo` DISABLE KEYS */;
INSERT INTO `modelo` VALUES (1,'Legion Pro 7i',1),(2,'Legion Pro 5',1),(3,'HP Spectre',1),(4,'HP Pavilion',1),(5,'Legion 5',1),(6,'dell',1),(7,'ThinkPad QA T14',1),(8,'ThinkPad X1 Carbon Gen 12',1),(9,'HP EliteBook 845 G11',1),(10,'Dell Latitude 9450',1),(11,'MacBook Pro 16 M4',1),(12,'ASUS ZenBook Duo',1),(13,'Samsung Galaxy Book4 Ultra',1),(14,'Microsoft Surface Laptop 6',1),(15,'Acer TravelMate P4',1),(16,'Dell UltraSharp U2723QE',1),(17,'Samsung ViewFinity S7',1),(18,'HP LaserJet Pro 4003dw',1),(19,'Epson EcoTank L5590',1),(20,'Samsung Galaxy S24',1),(21,'Apple iPhone 15',1),(22,'Cisco Catalyst 9200',1),(23,'Ubiquiti UniFi U6 Pro',1),(24,'HPE ProLiant DL380 Gen11',1),(25,'Dell OptiPlex 7010',1),(26,'Lenovo ThinkCentre M90q',1),(27,'ASUS ProArt PA279CRV',1);
/*!40000 ALTER TABLE `modelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `idpermiso` int(11) NOT NULL AUTO_INCREMENT,
  `idusuario` int(11) NOT NULL,
  `datosmaestros` tinyint(1) NOT NULL,
  `transacciones` tinyint(1) NOT NULL,
  `mantenimientos` tinyint(1) NOT NULL DEFAULT 0,
  `licencias` tinyint(1) NOT NULL DEFAULT 0,
  `consultas` tinyint(1) NOT NULL,
  `reportes` tinyint(1) NOT NULL,
  `actas` tinyint(1) NOT NULL DEFAULT 0,
  `seguridad` tinyint(1) NOT NULL,
  PRIMARY KEY (`idpermiso`),
  UNIQUE KEY `uq_permisos_idusuario` (`idusuario`),
  CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (1,1,1,1,1,1,1,1,1,1),(2,2,1,0,0,1,1,0,0,0),(3,3,1,0,0,1,1,0,0,0),(4,4,0,0,0,0,0,1,1,0),(5,5,1,1,1,1,1,1,1,1),(6,6,0,1,1,0,1,1,1,0),(7,7,1,1,1,1,1,1,1,1),(8,8,1,1,1,1,1,1,1,1),(9,9,1,1,1,1,1,1,1,1),(10,10,1,1,1,1,1,1,1,1),(11,11,1,1,1,1,1,1,1,1),(12,12,0,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'DEMO - Tecno Suministros HN','DEMO-RTN-001','Andrea Mejía','+504 2201-1001','ventas01@proveedores.demo.test','Tegucigalpa, Francisco Morazán','Proveedor de prueba para computadoras y accesorios.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(2,'DEMO - Soluciones Digitales CA','DEMO-RTN-002','Carlos Pineda','+504 2201-1002','ventas02@proveedores.demo.test','San Pedro Sula, Cortés','Proveedor de prueba para licenciamiento y hardware.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(3,'DEMO - Infraestructura IT','DEMO-RTN-003','María Lagos','+504 2201-1003','ventas03@proveedores.demo.test','Tegucigalpa, Francisco Morazán','Proveedor de prueba para servidores y almacenamiento.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(4,'DEMO - Redes y Comunicaciones','DEMO-RTN-004','José Rivera','+504 2201-1004','ventas04@proveedores.demo.test','San Pedro Sula, Cortés','Proveedor de prueba para redes y telecomunicaciones.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(5,'DEMO - Servicios Técnicos Integrales','DEMO-RTN-005','Diana Flores','+504 2201-1005','ventas05@proveedores.demo.test','Comayagua, Comayagua','Taller demostrativo de diagnóstico y reparación.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(6,'DEMO - Impresión Empresarial','DEMO-RTN-006','Luis Romero','+504 2201-1006','ventas06@proveedores.demo.test','Tegucigalpa, Francisco Morazán','Proveedor de prueba para impresoras y consumibles.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(7,'DEMO - Seguridad Electrónica','DEMO-RTN-007','Sofía Cruz','+504 2201-1007','ventas07@proveedores.demo.test','La Ceiba, Atlántida','Proveedor demostrativo de seguridad y monitoreo.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(8,'DEMO - Equipos Móviles','DEMO-RTN-008','Daniel Núñez','+504 2201-1008','ventas08@proveedores.demo.test','San Pedro Sula, Cortés','Proveedor de prueba para teléfonos y tabletas.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(9,'DEMO - Data Center Honduras','DEMO-RTN-009','Paola Reyes','+504 2201-1009','ventas09@proveedores.demo.test','Tegucigalpa, Francisco Morazán','Proveedor demostrativo de infraestructura crítica.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42'),(10,'DEMO - Soporte Corporativo','DEMO-RTN-010','Miguel Zelaya','+504 2201-1010','ventas10@proveedores.demo.test','Choloma, Cortés','Proveedor de prueba para soporte técnico externo.',1,'2026-07-31 14:08:42','2026-07-31 14:08:42');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sexo`
--

DROP TABLE IF EXISTS `sexo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sexo` (
  `idsexo` int(11) NOT NULL AUTO_INCREMENT,
  `descripcionsexo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idsexo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sexo`
--

LOCK TABLES `sexo` WRITE;
/*!40000 ALTER TABLE `sexo` DISABLE KEYS */;
INSERT INTO `sexo` VALUES (1,'Masculino'),(2,'Femenino');
/*!40000 ALTER TABLE `sexo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `software`
--

DROP TABLE IF EXISTS `software`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `software` (
  `idsoftware` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `fabricante` varchar(120) NOT NULL,
  `version` varchar(60) NOT NULL DEFAULT '',
  `edicion` varchar(100) NOT NULL DEFAULT '',
  `categoria` varchar(80) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idsoftware`),
  UNIQUE KEY `uq_software_producto` (`nombre`,`fabricante`,`version`,`edicion`),
  KEY `idx_software_activo_nombre` (`activo`,`nombre`),
  CONSTRAINT `chk_software_activo` CHECK (`activo` in (0,1))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `software`
--

LOCK TABLES `software` WRITE;
/*!40000 ALTER TABLE `software` DISABLE KEYS */;
INSERT INTO `software` VALUES (1,'AAA','AAAA','AAA','AAA','AAA',NULL,1,'2026-07-31 15:16:29','2026-07-31 15:16:29'),(2,'DEMO - Microsoft 365','Microsoft','2026','Business Standard','Ofimática','Suite de productividad demostrativa.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(3,'DEMO - Adobe Acrobat','Adobe','2026','Pro','PDF y documentos','Edición y firma de documentos PDF de prueba.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(4,'DEMO - ESET Protect','ESET','Cloud','Advanced','Seguridad','Protección de dispositivos para pruebas de vencimiento.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(5,'DEMO - Windows 11','Microsoft','11','Pro','Sistema operativo','Licencia perpetua demostrativa por dispositivo.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(6,'DEMO - AnyDesk','AnyDesk Software','Cloud','Advanced','Acceso remoto','Acceso remoto concurrente para pruebas.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14'),(7,'DEMO - Notion','Notion Labs','Cloud','Enterprise','Colaboración','Licenciamiento corporativo demostrativo sin límite de cupos.',1,'2026-07-31 15:20:14','2026-07-31 15:20:14');
/*!40000 ALTER TABLE `software` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `idusuario` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `idempleado` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL,
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `uq_usuarios_username` (`username`),
  UNIQUE KEY `uq_usuarios_idempleado` (`idempleado`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'emartinez','$2y$10$gcpigWkliTOaPrrPmPdVs.OTxLvLxDM4dahBCGMO0lJK5kyU67oru',1,1),(2,'cpavon','$2y$10$h/l5mDwf7VaVGns6O7jF2e6l799cw5B.AX/u9KetXFSNTBCEMZmJa',2,0),(3,'jgarcia','$2y$10$AM7k44ehUW8aUSg54jGJTOw5ZEwxnweSPpSXGlCMm1PzJt80TNeuW',3,1),(4,'ariana','$2y$10$VpLZBKTLDfTwRX80lTNPsurVycFsZ6LOaZLf75K80iRezZdIWS6.W',36,1),(5,'tecwil','$2y$10$xGl52f.1J97GVdhS4cpIK.5OJWFnD1sY4ofUEpTcPfy968nVS0B/.',11,1),(6,'wcastes','$2y$10$vimeN.4XsvljhOgtJP9XKujt4iBZP8DAX5EUKRNbbmDWoHq29/.HG',7,1),(7,'wcaste','$2y$10$DB5fWv5gwZvOtvw/M00D2.P8HD7H7zg9b9dRc8Zo3FprL67TQIUpC',5,1),(8,'www','$2y$10$ar4zHU3GT.v8SNsIK49SEeEcKJxH9z0G863BRPbeoAD0ZM2iFeoXa',4,1),(9,'wcastess','$2y$10$KZjAHcXRpWyU67rle9jnou6HmBW2AWzccqK/EuJgNZy47glrypcSC',40,1),(10,'ALMA','$2y$10$WcWI5V6yzfQvbbEXXwvAQucEsVaykugcAXDoiSMWliftF4fHbZjN.',41,1),(11,'ADMIN','$2y$10$7HtbM2rm6vIRhFqJ2rDIhOqTnKNvCtyZ5Q57BL8PpjCfzB08PRnfK',42,1),(12,'ADMINn','$2y$10$KRZyJEzFeEAFV/JKFhm9teEmIRntIQ2aAFvdfYYFTIdvIVV.Zb8wu',45,1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'gestactivos'
--

--
-- Dumping routines for database 'gestactivos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-11 13:39:45
