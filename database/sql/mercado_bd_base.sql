-- =============================================================================
-- SISTEMA DE GESTION DE MERCADOS - MERCADO LAS ESTRELLAS
-- Base de Datos: mercado_bd (Esquema Limpio + Datos Semilla / Base)
-- Generado para despliegue y desarrollo limpio.
-- Nota: No utiliza procedimientos almacenados; la logica reside en Laravel.
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: mercado_bd
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banco`
--

DROP TABLE IF EXISTS `banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banco` (
  `id_banco` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `siglas` varchar(20) NOT NULL,
  `estado` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  PRIMARY KEY (`id_banco`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco`
--

LOCK TABLES `banco` WRITE;
/*!40000 ALTER TABLE `banco` DISABLE KEYS */;
INSERT INTO `banco` (`id_banco`, `nombre`, `siglas`, `estado`) VALUES (1,'Banco de Crédito del Perú','BCP','1'),
(2,'Banco Continental BBVA','BBVA','1'),
(3,'Interbank','IBK','1'),
(4,'Banco Nacion','BN','1'),
(5,'Scotiabank','SC','1');

/*!40000 ALTER TABLE `banco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banco_cuenta`
--

DROP TABLE IF EXISTS `banco_cuenta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banco_cuenta` (
  `id_bancocuenta` bigint(20) unsigned NOT NULL,
  `numero_cuenta` varchar(100) NOT NULL,
  `id_banco` bigint(20) unsigned NOT NULL,
  `estado` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco_cuenta`
--

LOCK TABLES `banco_cuenta` WRITE;
/*!40000 ALTER TABLE `banco_cuenta` DISABLE KEYS */;
INSERT INTO `banco_cuenta` (`id_bancocuenta`, `numero_cuenta`, `id_banco`, `estado`) VALUES (1,'0-800-00-222',2,'1'),
(2,'0-801-00-271',2,'1'),
(3,'0-800-00-222\r\n',1,'1'),
(4,'0-800-00-222\r\n',1,'1');

/*!40000 ALTER TABLE `banco_cuenta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocks` (
  `id_block` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_block`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` (`id_block`, `nombre`) VALUES (1,'Ninguno'),
(3,'1 Piso'),
(4,'2 Piso');

/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuota_servicios`
--

DROP TABLE IF EXISTS `cuota_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuota_servicios` (
  `id_cuota_servicio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_cuota` bigint(20) unsigned NOT NULL,
  `id_servicio` bigint(20) unsigned NOT NULL,
  `importe` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_cuota_servicio`),
  KEY `cuota_servicios_id_cuota_foreign` (`id_cuota`),
  KEY `cuota_servicios_id_servicio_foreign` (`id_servicio`),
  CONSTRAINT `cuota_servicios_id_cuota_foreign` FOREIGN KEY (`id_cuota`) REFERENCES `cuotas` (`id_cuota`),
  CONSTRAINT `cuota_servicios_id_servicio_foreign` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=973 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuota_servicios`
--

LOCK TABLES `cuota_servicios` WRITE;
/*!40000 ALTER TABLE `cuota_servicios` DISABLE KEYS */;
-- Tabla `cuota_servicios`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `cuota_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuotas`
--

DROP TABLE IF EXISTS `cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuotas` (
  `id_cuota` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha_emision` datetime NOT NULL,
  `fecha_vencimiento` datetime NOT NULL,
  `importe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `global` varchar(1) NOT NULL COMMENT '1 = es para todos los puestos ; 0 =  es para 1 puesto',
  `id_anio` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_cuota`)
) ENGINE=InnoDB AUTO_INCREMENT=974 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuotas`
--

LOCK TABLES `cuotas` WRITE;
/*!40000 ALTER TABLE `cuotas` DISABLE KEYS */;
-- Tabla `cuotas`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pagos`
--

DROP TABLE IF EXISTS `detalle_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pagos` (
  `id_detallepago` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pago` bigint(20) unsigned NOT NULL,
  `id_cuota` bigint(20) unsigned DEFAULT NULL,
  `id_deuda` bigint(20) unsigned NOT NULL,
  `id_puesto` bigint(20) unsigned DEFAULT NULL,
  `importe` decimal(10,2) NOT NULL,
  `id_servicio` bigint(20) DEFAULT NULL,
  `id_deuda_cuota` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id_detallepago`),
  KEY `detalle_pagos_id_pago_foreign` (`id_pago`),
  KEY `detalle_pagos_id_cuota_foreign` (`id_cuota`),
  KEY `detalle_pagos_id_deuda_foreign` (`id_deuda`),
  KEY `detalle_pagos_id_puesto_foreign` (`id_puesto`),
  CONSTRAINT `detalle_pagos_id_cuota_foreign` FOREIGN KEY (`id_cuota`) REFERENCES `cuotas` (`id_cuota`),
  CONSTRAINT `detalle_pagos_id_deuda_foreign` FOREIGN KEY (`id_deuda`) REFERENCES `deudas` (`id_deuda`),
  CONSTRAINT `detalle_pagos_id_pago_foreign` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`),
  CONSTRAINT `detalle_pagos_id_puesto_foreign` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`)
) ENGINE=InnoDB AUTO_INCREMENT=339 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pagos`
--

LOCK TABLES `detalle_pagos` WRITE;
/*!40000 ALTER TABLE `detalle_pagos` DISABLE KEYS */;
-- Tabla `detalle_pagos`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `detalle_pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deuda_cuotas`
--

DROP TABLE IF EXISTS `deuda_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deuda_cuotas` (
  `id_deuda_cuota` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_deuda` bigint(20) unsigned NOT NULL,
  `id_cuota_servicio` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL COMMENT 'importe',
  `a_cuenta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_deuda_cuota`),
  KEY `deuda_cuotas_id_deuda_foreign` (`id_deuda`),
  KEY `deuda_cuotas_cuota_servicios_fk` (`id_cuota_servicio`),
  CONSTRAINT `deuda_cuotas_cuota_servicios_fk` FOREIGN KEY (`id_cuota_servicio`) REFERENCES `cuota_servicios` (`id_cuota_servicio`),
  CONSTRAINT `deuda_cuotas_id_deuda_foreign` FOREIGN KEY (`id_deuda`) REFERENCES `deudas` (`id_deuda`)
) ENGINE=InnoDB AUTO_INCREMENT=677 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deuda_cuotas`
--

LOCK TABLES `deuda_cuotas` WRITE;
/*!40000 ALTER TABLE `deuda_cuotas` DISABLE KEYS */;
-- Tabla `deuda_cuotas`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `deuda_cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deudas`
--

DROP TABLE IF EXISTS `deudas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deudas` (
  `id_deuda` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_socio` bigint(20) unsigned DEFAULT NULL,
  `id_puesto` bigint(20) unsigned DEFAULT NULL,
  `total_deuda` decimal(10,2) NOT NULL COMMENT 'importe',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_cuota` bigint(20) NOT NULL,
  PRIMARY KEY (`id_deuda`),
  KEY `deudas_id_socio_foreign` (`id_socio`),
  KEY `deudas_id_puesto_foreign` (`id_puesto`),
  CONSTRAINT `deudas_id_puesto_foreign` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`),
  CONSTRAINT `deudas_id_socio_foreign` FOREIGN KEY (`id_socio`) REFERENCES `socios` (`id_socio`)
) ENGINE=InnoDB AUTO_INCREMENT=667 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deudas`
--

LOCK TABLES `deudas` WRITE;
/*!40000 ALTER TABLE `deudas` DISABLE KEYS */;
-- Tabla `deudas`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `deudas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id_documento` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_documento` bigint(20) unsigned NOT NULL,
  `serie` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id_documento`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` (`id_documento`, `numero_documento`, `serie`, `estado`, `fecha_registro`) VALUES (1,47,'0000','1','2025-10-02 09:30:54');

/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `giro_negocios`
--

DROP TABLE IF EXISTS `giro_negocios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `giro_negocios` (
  `id_gironegocio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_gironegocio`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `giro_negocios`
--

LOCK TABLES `giro_negocios` WRITE;
/*!40000 ALTER TABLE `giro_negocios` DISABLE KEYS */;
INSERT INTO `giro_negocios` (`id_gironegocio`, `nombre`) VALUES (1,'Varios');

/*!40000 ALTER TABLE `giro_negocios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquilinos`
--

DROP TABLE IF EXISTS `inquilinos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquilinos` (
  `id_inquilino` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  PRIMARY KEY (`id_inquilino`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquilinos`
--

LOCK TABLES `inquilinos` WRITE;
/*!40000 ALTER TABLE `inquilinos` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquilinos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_08_08_153711_create_blocks_table',1),
(5,'2024_08_09_153699_create_giro_negocios_table',1),
(6,'2024_08_09_153703_create_usuarios_table',1),
(7,'2024_08_09_153705_create_socios_table',1),
(8,'2024_08_09_182245_create_inquilinos_table',1),
(9,'2024_08_09_182246_create_puestos_table',1),
(10,'2024_08_14_162704_create_personal_access_tokens_table',1),
(11,'2024_08_16_144712_create_documentos_table',1),
(12,'2024_08_16_144724_create_pagos_table',1),
(13,'2024_08_16_144739_create_servicios_table',1),
(14,'2024_08_16_144758_create_cuotas_table',1),
(15,'2024_08_16_144760_create_deudas_table',1),
(16,'2024_08_22_155610_create_puesto_cuotas_table',1),
(17,'2024_08_22_155839_create_deuda_cuotas_table',1),
(18,'2024_08_22_155865_create_detalle_pagos_table',1),
(19,'2024_08_22_160112_create_cuota_servicios_table',1);

/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulo`
--

DROP TABLE IF EXISTS `modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo` (
  `id_modulo` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `url_foco` varchar(255) DEFAULT NULL,
  `url_activa` varchar(1) DEFAULT NULL COMMENT '1 = activo ; 0 = desactivo',
  `icon` varchar(50) DEFAULT NULL,
  `estado` varchar(1) DEFAULT '0' COMMENT '1 = activo ; 0 = desactivo',
  `id_modulo_parent` bigint(20) unsigned DEFAULT NULL COMMENT 'parent',
  `orden` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulo`
--

LOCK TABLES `modulo` WRITE;
/*!40000 ALTER TABLE `modulo` DISABLE KEYS */;
INSERT INTO `modulo` (`id_modulo`, `nombre`, `url`, `url_foco`, `url_activa`, `icon`, `estado`, `id_modulo_parent`, `orden`) VALUES (1,'Panel de Control',NULL,NULL,'0','DashboardIcon','1',NULL,1000),
(2,'Reportes',NULL,NULL,'0','DashboardIcon','1',NULL,2000),
(3,'Socios','/home/socios','socios','1','Groups','1',1,1001),
(4,'Puestos','/home/puestos','puestos','1','Storefront','1',1,1002),
(5,'Servicios','/home/servicios','servicios','1','ShoppingBasket','1',1,1003),
(6,'Generar Cuota','/home/cuotas','cuotas','1','Article','1',1,1004),
(7,'Pagos','/home/pagos','pagos','1','MonetizationOn','1',1,1005),
(8,'Reporte Pagos','/home/reporte-pagos','reporte-pagos','1','Description','1',2,2001),
(9,'Reporte Deudas','/home/reporte-deudas','reporte-deudas','1','Description','1',2,2002),
(10,'Reporte de cuotas por metrado','/home/reporte-cuotas-metrado','reporte-cuotas-metrado','1','Description','1',2,2003),
(11,'Reporte de cuotas por puestos','/home/reporte-cuotas-puesto','reporte-cuotas-puesto','1','Description','1',2,2004),
(12,'Reporte de resumen','/home/reporte-resumen','reporte-resumen','1','Description','1',2,2005);

/*!40000 ALTER TABLE `modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_banco`
--

DROP TABLE IF EXISTS `pago_banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago_banco` (
  `id_pagobanco` bigint(20) NOT NULL,
  `id_banco` bigint(20) unsigned NOT NULL,
  `id_bancocuenta` bigint(20) unsigned NOT NULL,
  `numero_operacion` varchar(50) NOT NULL,
  `fecha_operacion` date NOT NULL,
  PRIMARY KEY (`id_pagobanco`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_banco`
--

LOCK TABLES `pago_banco` WRITE;
/*!40000 ALTER TABLE `pago_banco` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_banco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id_pago` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_socio` bigint(20) unsigned DEFAULT NULL,
  `id_documento` bigint(20) unsigned NOT NULL,
  `numero_pago` varchar(50) NOT NULL,
  `serie` varchar(50) NOT NULL,
  `total_pago` decimal(10,2) NOT NULL COMMENT 'importe',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `anulado` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `pagos_id_socio_foreign` (`id_socio`),
  KEY `pagos_id_documento_foreign` (`id_documento`),
  CONSTRAINT `pagos_id_documento_foreign` FOREIGN KEY (`id_documento`) REFERENCES `documentos` (`id_documento`),
  CONSTRAINT `pagos_id_socio_foreign` FOREIGN KEY (`id_socio`) REFERENCES `socios` (`id_socio`)
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
-- Tabla `pagos`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
-- Tabla `personal_access_tokens`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personas` (
  `id_persona` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  `fecha_registro` datetime NOT NULL,
  `nombre_completo` varchar(450) DEFAULT NULL,
  `direccion` varchar(500) DEFAULT NULL,
  `sexo` varchar(50) NOT NULL DEFAULT '1' COMMENT '1 = Masculino ; 2 = Femenino',
  `tipo_persona` varchar(50) DEFAULT '1' COMMENT '1 = natural ; 2 = juridica',
  PRIMARY KEY (`id_persona`)
) ENGINE=InnoDB AUTO_INCREMENT=288 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personas`
--

LOCK TABLES `personas` WRITE;
/*!40000 ALTER TABLE `personas` DISABLE KEYS */;
-- Usuario Administrador Base (Persona)
INSERT INTO `personas` (`id_persona`, `nombre`, `apellido_paterno`, `apellido_materno`, `dni`, `correo`, `telefono`, `estado`, `fecha_registro`, `nombre_completo`, `direccion`, `sexo`, `tipo_persona`) VALUES (1,'admin','admin','admin','12346578','hectorvasquez@vaftecperu.com','900112844','1','2024-12-04 11:47:26','admin',NULL,'1','natural');

/*!40000 ALTER TABLE `personas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `puesto_cuotas`
--

DROP TABLE IF EXISTS `puesto_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `puesto_cuotas` (
  `id_puesto_cuota` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_puesto` bigint(20) unsigned NOT NULL,
  `id_cuota` bigint(20) unsigned NOT NULL,
  `estado` varchar(255) NOT NULL,
  PRIMARY KEY (`id_puesto_cuota`),
  KEY `puesto_cuotas_id_puesto_foreign` (`id_puesto`),
  KEY `puesto_cuotas_id_cuota_foreign` (`id_cuota`),
  CONSTRAINT `puesto_cuotas_id_cuota_foreign` FOREIGN KEY (`id_cuota`) REFERENCES `cuotas` (`id_cuota`),
  CONSTRAINT `puesto_cuotas_id_puesto_foreign` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `puesto_cuotas`
--

LOCK TABLES `puesto_cuotas` WRITE;
/*!40000 ALTER TABLE `puesto_cuotas` DISABLE KEYS */;
/*!40000 ALTER TABLE `puesto_cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `puestos`
--

DROP TABLE IF EXISTS `puestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `puestos` (
  `id_puesto` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_socio` bigint(20) unsigned DEFAULT NULL,
  `id_block` bigint(20) unsigned DEFAULT NULL,
  `id_gironegocio` bigint(20) unsigned DEFAULT NULL,
  `numero_puesto` varchar(30) NOT NULL,
  `area` varchar(50) NOT NULL,
  `id_inquilino` bigint(20) unsigned DEFAULT NULL,
  `estado` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = puesto activo y libre ; 2 = puesto activo y ocupado',
  `activo` varchar(1) NOT NULL DEFAULT '1',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_puesto`),
  KEY `puestos_id_socio_foreign` (`id_socio`),
  KEY `puestos_id_block_foreign` (`id_block`),
  KEY `puestos_id_gironegocio_foreign` (`id_gironegocio`),
  KEY `puestos_id_inquilino_foreign` (`id_inquilino`),
  CONSTRAINT `puestos_id_block_foreign` FOREIGN KEY (`id_block`) REFERENCES `blocks` (`id_block`),
  CONSTRAINT `puestos_id_gironegocio_foreign` FOREIGN KEY (`id_gironegocio`) REFERENCES `giro_negocios` (`id_gironegocio`),
  CONSTRAINT `puestos_id_inquilino_foreign` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id_inquilino`),
  CONSTRAINT `puestos_id_socio_foreign` FOREIGN KEY (`id_socio`) REFERENCES `socios` (`id_socio`)
) ENGINE=InnoDB AUTO_INCREMENT=307 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `puestos`
--

LOCK TABLES `puestos` WRITE;
/*!40000 ALTER TABLE `puestos` DISABLE KEYS */;
-- Tabla `puestos`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `puestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(100) DEFAULT NULL,
  `es_administrador` varchar(1) DEFAULT '0' COMMENT '1 = si ; 0 = no',
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` (`id_rol`, `nombre`, `codigo`, `es_administrador`) VALUES (1,'Administrador','ADM','1'),
(2,'Socio','SOC','0');

/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_modulo`
--

DROP TABLE IF EXISTS `rol_modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_modulo` (
  `id_rol` bigint(20) unsigned NOT NULL,
  `id_modulo` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id_rol`,`id_modulo`),
  KEY `rolmodulo_modulo_fk` (`id_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_modulo`
--

LOCK TABLES `rol_modulo` WRITE;
/*!40000 ALTER TABLE `rol_modulo` DISABLE KEYS */;
INSERT INTO `rol_modulo` (`id_rol`, `id_modulo`) VALUES (1,1),
(1,2),
(1,3),
(1,4),
(1,5),
(1,6),
(1,7),
(1,8),
(1,9),
(1,10),
(1,11),
(1,12);

/*!40000 ALTER TABLE `rol_modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicios` (
  `id_servicio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `tipo_servicio` int(11) NOT NULL COMMENT '1 = Ordinario (Pagos Fijos) ; 2 = Extraordinario (Pagos Extras) ; 3 = Metrado (Pago por área) ; 4 = Cuota Extraordinaria',
  `activo` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = esta activo ( se mostrara) ; 0 = esta desactivo( no se mostrara)',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `codigo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=1328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` (`id_servicio`, `nombre`, `costo_unitario`, `tipo_servicio`, `activo`, `fecha_registro`, `codigo`) VALUES (1151,'Accidente de Trabajo en obra, aporte x socio ',0.00,1,'1','2026-03-28 19:36:00','1'),
(1152,'Adeudos de Alumbrado nocturno',0.00,1,'1','2026-03-28 19:36:00','2'),
(1153,'Adeudos Diversos de Colaboraciones',0.00,1,'1','2026-03-28 19:36:00','3'),
(1154,'Anteproyecto y Proyecto del Mercado de Abasto',0.00,1,'1','2026-03-28 19:36:00','4'),
(1155,'Aporte de Adicional de Puesto o Tienda/ cancelado/ x cancelar',0.00,1,'1','2026-03-28 19:36:00','5'),
(1156,'Aporte X Inasistencias a Asambleas Generales ',0.00,1,'1','2026-03-28 19:36:00','6'),
(1157,'Aportes x Construcción de Puesto o Tienda y/o saldo.',0.00,1,'1','2026-03-28 19:36:00','7'),
(1158,'Asambleas Generales Socios - Enero',0.00,1,'1','2026-03-28 19:36:00','8'),
(1159,'Asambleas Generales Socios - Febrero',0.00,1,'1','2026-03-28 19:36:00','9'),
(1160,'Asambleas Generales Socios - Marzo',0.00,1,'1','2026-03-28 19:36:00','10'),
(1161,'Asambleas Generales Socios  - Abril ',0.00,1,'1','2026-03-28 19:36:00','11'),
(1162,'Asambleas Generales Socios -  Mayo ',0.00,1,'1','2026-03-28 19:36:00','12'),
(1163,'Asambleas Generales Socios - Junio',0.00,1,'1','2026-03-28 19:36:00','13'),
(1164,'Asambleas Generales Socios - Julio',0.00,1,'1','2026-03-28 19:36:00','14'),
(1165,'Asambleas Generales Socios - Agosto ',0.00,1,'1','2026-03-28 19:36:00','15'),
(1166,'Asambleas Generales Socios - Setiembre',0.00,1,'1','2026-03-28 19:36:00','16'),
(1167,'Asambleas Generales Socios - Octubre',0.00,1,'1','2026-03-28 19:36:00','17'),
(1168,'Asambleas Generales Socios - Noviembre',0.00,1,'1','2026-03-28 19:36:00','18'),
(1169,'Asambleas Generales Socios Diciembre',0.00,1,'1','2026-03-28 19:36:00','19'),
(1170,'Asambleas Generales E - Socios Enero',0.00,1,'1','2026-03-28 19:36:00','20'),
(1171,'Asambleas Generales E - Socios Febrero',0.00,1,'1','2026-03-28 19:36:00','21'),
(1172,'Asambleas Generales E - Socios Marzo ',0.00,1,'1','2026-03-28 19:36:00','22'),
(1173,'Asambleas Generales E - Socios Abril ',0.00,1,'1','2026-03-28 19:36:00','23'),
(1174,'Asambleas Generales E - Socios Mayo ',0.00,1,'1','2026-03-28 19:36:00','24'),
(1175,'Asambleas Generales E - Socios Junio ',0.00,1,'1','2026-03-28 19:36:00','25'),
(1176,'Asambleas Generales E - Socios Julio ',0.00,1,'1','2026-03-28 19:36:00','26'),
(1177,'Asambleas Generales E - Socios Agosto',0.00,1,'1','2026-03-28 19:36:00','27'),
(1178,'Asambleas Generales E - Socios Setiembre',0.00,1,'1','2026-03-28 19:36:00','28'),
(1179,'Asambleas Generales E - Socios Octubre',0.00,1,'1','2026-03-28 19:36:00','29'),
(1180,'Asambleas Generales E - Socios Noviembre',0.00,1,'1','2026-03-28 19:36:00','30'),
(1181,'Asambleas Generales E - Socios Diciembre',0.00,1,'1','2026-03-28 19:36:00','31'),
(1182,'Asamblea General -  Enero',0.00,1,'1','2026-03-28 19:36:00','32'),
(1183,'Asamblea General -  Febrero',0.00,1,'1','2026-03-28 19:36:00','33'),
(1184,'Asamblea General -  Marzo',0.00,1,'1','2026-03-28 19:36:00','34'),
(1185,'Asamblea General -  Abril',0.00,1,'1','2026-03-28 19:36:00','35'),
(1186,'Asamblea General -  Mayo',0.00,1,'1','2026-03-28 19:36:00','36'),
(1187,'Asamblea General -  Junio',0.00,1,'1','2026-03-28 19:36:00','37'),
(1188,'Asamblea General -  Julio',0.00,1,'1','2026-03-28 19:36:00','38'),
(1189,'Asamblea General -  Agosto',0.00,1,'1','2026-03-28 19:36:00','39'),
(1190,'Asamblea General -  Setiembre',0.00,1,'1','2026-03-28 19:36:00','40'),
(1191,'Asamblea General -  Octubre',0.00,1,'1','2026-03-28 19:36:00','41'),
(1192,'Asamblea General -  Noviembre',0.00,1,'1','2026-03-28 19:36:00','42'),
(1193,'Asamblea General -  Diciembre',0.00,1,'1','2026-03-28 19:36:00','43'),
(1194,'Asamblea Interna - Enero',0.00,1,'1','2026-03-28 19:36:00','44'),
(1195,'Asamblea Interna - Febrero',0.00,1,'1','2026-03-28 19:36:00','45'),
(1196,'Asamblea Interna - Marzo ',0.00,1,'1','2026-03-28 19:36:00','46'),
(1197,'Asamblea Interna - Abril',0.00,1,'1','2026-03-28 19:36:00','47'),
(1198,'Asamblea Interna - Mayo',0.00,1,'1','2026-03-28 19:36:00','48'),
(1199,'Asamblea Interna - Junio',0.00,1,'1','2026-03-28 19:36:00','49'),
(1200,'Asamblea Interna - Julio',0.00,1,'1','2026-03-28 19:36:00','50'),
(1201,'Asamblea Interna - Agosto',0.00,1,'1','2026-03-28 19:36:00','51'),
(1202,'Asamblea Interna - Setiembre',0.00,1,'1','2026-03-28 19:36:00','52'),
(1203,'Asamblea Interna - Octubre',0.00,1,'1','2026-03-28 19:36:00','53'),
(1204,'Asamblea Interna - Noviembre',0.00,1,'1','2026-03-28 19:36:00','54'),
(1205,'Asamblea Interna - Diciembre',0.00,1,'1','2026-03-28 19:36:00','55'),
(1206,'Calicata-Estudio de suelos',0.00,1,'1','2026-03-28 19:36:00','56'),
(1207,'Construcción Adicional',0.00,1,'1','2026-03-28 19:36:00','57'),
(1208,'Construcción de Cisterna x m2=66.67 soles ',0.00,1,'1','2026-03-28 19:36:00','58'),
(1209,'Construcción de Trampa de Grasa',0.00,1,'1','2026-03-28 19:36:00','59'),
(1210,'Construcción Extraordinaria',0.00,1,'1','2026-03-28 19:36:00','60'),
(1211,'Consumo de Agua',0.00,1,'1','2026-03-28 19:36:00','61'),
(1212,'Cuotas extraordinarias',0.00,4,'1','2026-03-28 19:36:00','62'),
(1213,'Defensa Civil - Constancia de Seguridad',0.00,1,'1','2026-03-28 19:36:00','63'),
(1214,'Desinfección - 2023',0.00,1,'1','2026-03-28 19:36:00','64'),
(1215,'Desinsectación - 2023',0.00,1,'1','2026-03-28 19:36:00','65'),
(1216,'Desratización - 2023',0.00,1,'1','2026-03-28 19:36:00','66'),
(1217,'Distribución de Puestos y Tiendas/Enero - 2023',0.00,1,'1','2026-03-28 19:36:00','67'),
(1218,'Electrificación 1 / Grupal=(60/ Indep. =140/mora 10%(176) - 2023',0.00,1,'1','2026-03-28 19:36:00','68'),
(1219,'Electrificación 2 / Mora 10% (181.50 soles)> lAlelo 02.0X - 2023',0.00,1,'1','2026-03-28 19:36:00','69'),
(1220,'Eliminación de Desmonte de Calle 10-Frente a Area de Descarga - 2023',0.00,1,'1','2026-03-28 19:36:00','70'),
(1221,'Eliminación de Desmonte frontis de Ca. 9 - 2023',0.00,1,'1','2026-03-28 19:36:00','71'),
(1222,'Equipamiento del Sistema Contra Insendio Aporte - 2023',0.00,1,'1','2026-03-28 19:36:00','72'),
(1223,'Excavación para instalacion de Desague - 2023',0.00,1,'1','2026-03-28 19:36:00','73'),
(1224,'Faena General - Enero 2023',0.00,1,'1','2026-03-28 19:36:00','74'),
(1225,'Faena General - Febrero 2023',0.00,1,'1','2026-03-28 19:36:00','75'),
(1226,'Faena General - Marzo 2023',0.00,1,'1','2026-03-28 19:36:00','76'),
(1227,'Faena General - Abril 2023',0.00,1,'1','2026-03-28 19:36:00','77'),
(1228,'Faena General - MayoEnero 2023',0.00,1,'1','2026-03-28 19:36:00','78'),
(1229,'Faena General - Junio 2023',0.00,1,'1','2026-03-28 19:36:00','79'),
(1230,'Faena General - Julio 2023',0.00,1,'1','2026-03-28 19:36:00','80'),
(1231,'Faena General - Agosto 2023',0.00,1,'1','2026-03-28 19:36:00','81'),
(1232,'Faena General - Setiembre 2023',0.00,1,'1','2026-03-28 19:36:00','82'),
(1233,'Faena General - Octubre 2023',0.00,1,'1','2026-03-28 19:36:00','83'),
(1234,'Faena General - Noviembre 2023',0.00,1,'1','2026-03-28 19:36:00','84'),
(1235,'Faena General - diciembre 2023',0.00,1,'1','2026-03-28 19:36:00','85'),
(1236,'Faena interna -  Enero 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1237,'Faena interna -  Febrero 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1238,'Faena interna -  Marzo 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1239,'Faena interna -  Abril 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1240,'Faena interna -  Mayo 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1241,'Faena interna -  Junio 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1242,'Faena interna -  Julio 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1243,'Faena interna -  Agosto 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1244,'Faena interna -  Setiembre 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1245,'Faena interna -  Octubre 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1246,'Faena interna -  Noviembre 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1247,'Faena interna -  Diciembre 2023',0.00,1,'1','2026-03-28 19:36:00','86'),
(1248,'Fanea de Recuperación - Enero 2023',0.00,1,'1','2026-03-28 19:36:00','87'),
(1249,'Fanea de Recuperación - Febrero 2023',0.00,1,'1','2026-03-28 19:36:00','88'),
(1250,'Fanea de Recuperación - Marzo 2023',0.00,1,'1','2026-03-28 19:36:00','89'),
(1251,'Fanea de Recuperación - Abril 2023',0.00,1,'1','2026-03-28 19:36:00','90'),
(1252,'Fanea de Recuperación -  Mayo 2023',0.00,1,'1','2026-03-28 19:36:00','91'),
(1253,'Fanea de Recuperación - Junio 2023',0.00,1,'1','2026-03-28 19:36:00','92'),
(1254,'Fanea de Recuperación - Julio 2023',0.00,1,'1','2026-03-28 19:36:00','93'),
(1255,'Fanea de Recuperación - Agosto 2023',0.00,1,'1','2026-03-28 19:36:00','94'),
(1256,'Fanea de Recuperación - Setiembre 2023',0.00,1,'1','2026-03-28 19:36:00','95'),
(1257,'Fanea de Recuperación - Octubre 2023',0.00,1,'1','2026-03-28 19:36:00','96'),
(1258,'Fanea de Recuperación - Noviembre 2023',0.00,1,'1','2026-03-28 19:36:00','97'),
(1259,'Fanea de Recuperación - Diciembre 2023',0.00,1,'1','2026-03-28 19:36:00','98'),
(1260,'Faena Adelantada - Enero 2023',0.00,1,'1','2026-03-28 19:36:00','99'),
(1261,'Faena Adelantada - Febrero 2023',0.00,1,'1','2026-03-28 19:36:00','100'),
(1262,'Faena Adelantada - Marzo 2023',0.00,1,'1','2026-03-28 19:36:00','101'),
(1263,'Faena Adelantada - Abril 2023',0.00,1,'1','2026-03-28 19:36:00','102'),
(1264,'Faena Adelantada - Mayo 2023',0.00,1,'1','2026-03-28 19:36:00','103'),
(1265,'Faena Adelantada - Junio 2023',0.00,1,'1','2026-03-28 19:36:00','104'),
(1266,'Faena Adelantada - Julio 2023',0.00,1,'1','2026-03-28 19:36:00','105'),
(1267,'Faena Adelantada - Agosto 2023',0.00,1,'1','2026-03-28 19:36:00','106'),
(1268,'Faena Adelantada - Setiembre 2023',0.00,1,'1','2026-03-28 19:36:00','107'),
(1269,'Faena Adelantada - Octubre 2023',0.00,1,'1','2026-03-28 19:36:00','108'),
(1270,'Faena Adelantada - Noviembre 2023',0.00,1,'1','2026-03-28 19:36:00','109'),
(1271,'Faena Adelantada - Diciembre 2023',0.00,1,'1','2026-03-28 19:36:00','110'),
(1272,'Fumigación - 2023',0.00,1,'1','2026-03-28 19:36:00','111'),
(1273,'Fumigación x Puesto y Tienda/6-15m2/15/ 15-24m2/20soles - 2023',0.00,1,'1','2026-03-28 19:36:00','112'),
(1274,'Gastos Administrativos',0.00,1,'1','2026-03-28 19:36:00','113'),
(1275,'Impuesto Predial y Arbitrios ( S/ 86,81) - 2023',0.00,1,'1','2026-03-28 19:36:00','114'),
(1276,'Inauguración de obra/Reapertura 10 de Mayo - 2023',0.00,1,'1','2026-03-28 19:36:00','115'),
(1277,'INDECI - Constacia de Seguridad - 2023',0.00,1,'1','2026-03-28 19:36:00','116'),
(1278,'Licencencia.de Construcción con Resolucion x m2=10.84 solesc=2/2:8q2 2010/78 - 2023',0.00,1,'1','2026-03-28 19:36:00','117'),
(1279,'Licencia de Construccion /Cercado Provisional - 2023',0.00,1,'1','2026-03-28 19:36:00','118'),
(1280,'Licencia de Funcionamiento - 2023',0.00,1,'1','2026-03-28 19:36:00','119'),
(1281,'Limpieza de taque septico - 2023',0.00,1,'1','2026-03-28 19:36:00','120'),
(1282,'Limpieza y desinfección de sisternas o reservorios de agua - 2023',0.00,1,'1','2026-03-28 19:36:00','121'),
(1283,'Limpieza y Mantenimiento de Trampa de Grasa - 2023',0.00,1,'1','2026-03-28 19:36:00','122'),
(1284,'Mano de Obra x Instalacion de Tapa Galvanizada - 2023',0.00,1,'1','2026-03-28 19:36:00','123'),
(1285,'Mano de Obra x Pintado de puertas Enrrollables y Rejas - 2023',0.00,1,'1','2026-03-28 19:36:00','124'),
(1286,'Mant.de puertas princ., rejas, Servicio Contable, señaliz. (183.70) - 2023',0.00,1,'1','2026-03-28 19:36:00','125'),
(1287,'Mantenimiento Pozo Tierra y Fumigación - 2023',0.00,1,'1','2026-03-28 19:36:00','126'),
(1288,'Materiales xa Pintado de puertas Enrrollables - 2023',0.00,1,'1','2026-03-28 19:36:00','127'),
(1289,'Medidor de Luz (60) y Llave termomágnetica (35) /Puesto-Tienda -2023',0.00,1,'1','2026-03-28 19:36:00','128'),
(1290,'Medidor de Luz del Sur x Blocks - 2023',0.00,1,'1','2026-03-28 19:36:00','129'),
(1291,'Movimiento de tierra - Tienda=150 / x Puesto = 20 soles - 2023',0.00,1,'1','2026-03-28 19:36:00','130'),
(1292,'Multa x Carné de Sanidad - 2023',0.00,1,'1','2026-03-28 19:36:00','131'),
(1293,'Nivelación de terreno C 202 - 2023',0.00,1,'1','2026-03-28 19:36:00','132'),
(1294,'Otros Adeudos no considerados - 2023',0.00,1,'1','2026-03-28 19:36:00','133'),
(1295,'Pavimentacion de pista/perimetro del Mercado 60%/m2=15 soles - 2023',0.00,1,'1','2026-03-28 19:36:00','134'),
(1296,'Portamedidor de Agua (22) y Tapa Galvanizado (23) - 2023',0.00,1,'1','2026-03-28 19:36:00','135'),
(1297,'Reunión Interna  - Enero 2023',0.00,1,'1','2026-03-28 19:36:00','136'),
(1298,'Reunión Interna  - Febrero',0.00,1,'1','2026-03-28 19:36:00','137'),
(1299,'Reunión Interna  - Marzo',0.00,1,'1','2026-03-28 19:36:00','138'),
(1300,'Reunión Interna  - Abril',0.00,1,'1','2026-03-28 19:36:00','139'),
(1301,'Reunión Interna  - Mayo',0.00,1,'1','2026-03-28 19:36:00','140'),
(1302,'Reunión Interna  - Junio',0.00,1,'1','2026-03-28 19:36:00','141'),
(1303,'Reunión Interna  - Julio',0.00,1,'1','2026-03-28 19:36:00','142'),
(1304,'Reunión Interna  - Agosto',0.00,1,'1','2026-03-28 19:36:00','143'),
(1305,'Reunión Interna  - Setiembre',0.00,1,'1','2026-03-28 19:36:00','144'),
(1306,'Reunión Interna  -  Octubre',0.00,1,'1','2026-03-28 19:36:00','145'),
(1307,'Reunión Interna  - Noviembre',0.00,1,'1','2026-03-28 19:36:00','146'),
(1308,'Reunión Interna  - Diciembre',0.00,1,'1','2026-03-28 19:36:00','147'),
(1309,'Remodelación de SS.HH.',0.00,1,'1','2026-03-28 19:36:00','148'),
(1310,'Remodelacion de Trampa Grasa / 10% mora (132)',0.00,1,'1','2026-03-28 19:36:00','149'),
(1311,'Retiro de Desmonte Calle 9',0.00,1,'1','2026-03-28 19:36:00','150'),
(1312,'Retiro de Tapa Galvanizada y Reinstalacion',0.00,1,'1','2026-03-28 19:36:00','151'),
(1313,'Servicio Contable',0.00,1,'1','2026-03-28 19:36:00','152'),
(1314,'Servicio de vigilancia de 2da quinc. Febr. Aporte Obligatorio',0.00,1,'1','2026-03-28 19:36:00','153'),
(1315,'Suministro x Instalacion de Agua-Desague 1 - Sedapal ',0.00,1,'1','2026-03-28 19:36:00','154'),
(1316,'Vigilancia  y Seguridad (servicio) -  Enero',0.00,1,'1','2026-03-28 19:36:00','155'),
(1317,'Vigilancia  y Seguridad (servicio) -  Febrero',0.00,1,'1','2026-03-28 19:36:00','156'),
(1318,'Vigilancia  y Seguridad (servicio) -  Marzo',0.00,1,'1','2026-03-28 19:36:00','157'),
(1319,'Vigilancia  y Seguridad (servicio) -  Abril',0.00,1,'1','2026-03-28 19:36:00','158'),
(1320,'Vigilancia  y Seguridad (servicio) -  Mayo',0.00,1,'1','2026-03-28 19:36:00','159'),
(1321,'Vigilancia  y Seguridad (servicio) -  Junio',0.00,1,'1','2026-03-28 19:36:00','160'),
(1322,'Vigilancia  y Seguridad (servicio) -  Julio\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n',0.00,1,'1','2026-03-28 19:36:00','161'),
(1323,'Vigilancia  y Seguridad (servicio) -  Agosto',0.00,1,'1','2026-03-28 19:36:00','162'),
(1324,'Vigilancia  y Seguridad (servicio) -  Setiembre',0.00,1,'1','2026-03-28 19:36:00','163'),
(1325,'Vigilancia  y Seguridad (servicio) -  Octubre',0.00,1,'1','2026-03-28 19:36:00','164'),
(1326,'Vigilancia  y Seguridad (servicio) -  Noviembre',0.00,1,'1','2026-03-28 19:36:00','165'),
(1327,'Vigilancia  y Seguridad (servicio) -  Diciembre',0.00,1,'1','2026-03-28 19:36:00','166');

/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
-- Tabla `sessions`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setup_anio`
--

DROP TABLE IF EXISTS `setup_anio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `setup_anio` (
  `id_anio` bigint(20) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_anio`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setup_anio`
--

LOCK TABLES `setup_anio` WRITE;
/*!40000 ALTER TABLE `setup_anio` DISABLE KEYS */;
INSERT INTO `setup_anio` (`id_anio`, `nombre`) VALUES (2012,'2012'),
(2013,'2013'),
(2014,'2014'),
(2015,'2015'),
(2016,'2016'),
(2017,'2017'),
(2018,'2018'),
(2019,'2019'),
(2020,'2020'),
(2021,'2021'),
(2022,'2022'),
(2023,'2023'),
(2024,'2024'),
(2025,'2025');

/*!40000 ALTER TABLE `setup_anio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setup_mes`
--

DROP TABLE IF EXISTS `setup_mes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `setup_mes` (
  `id_mes` bigint(20) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_mes`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setup_mes`
--

LOCK TABLES `setup_mes` WRITE;
/*!40000 ALTER TABLE `setup_mes` DISABLE KEYS */;
INSERT INTO `setup_mes` (`id_mes`, `nombre`) VALUES (1,'Enero'),
(2,'Febrero'),
(3,'Marzo'),
(4,'Abril'),
(5,'Mayo'),
(6,'Junio'),
(7,'Julio'),
(8,'Agosto'),
(9,'Setiembre'),
(10,'Octubre'),
(11,'Noviembre'),
(12,'Diciembre');

/*!40000 ALTER TABLE `setup_mes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `socios`
--

DROP TABLE IF EXISTS `socios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `socios` (
  `id_socio` bigint(20) unsigned NOT NULL,
  `id_usuario` bigint(20) unsigned DEFAULT NULL,
  `nombres` varchar(150) DEFAULT NULL,
  `apellido_paterno` varchar(100) DEFAULT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `dni` varchar(8) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `sexo` varchar(10) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(1) DEFAULT '1' COMMENT '1 = socio activo ; 0 = socio desactivo (retirado)',
  PRIMARY KEY (`id_socio`),
  KEY `socios_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `socios_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `socios`
--

LOCK TABLES `socios` WRITE;
/*!40000 ALTER TABLE `socios` DISABLE KEYS */;
-- Tabla `socios`: inicializada sin datos operativos.

/*!40000 ALTER TABLE `socios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` bigint(20) unsigned NOT NULL,
  `rol` varchar(255) NOT NULL,
  `nombre_usuario` varchar(255) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `estado` varchar(255) DEFAULT '1',
  `token` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_rol` int(20) unsigned NOT NULL,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
-- Usuario Administrador Base (Credencial de Acceso: admin / password)
INSERT INTO `usuarios` (`id_usuario`, `rol`, `nombre_usuario`, `contrasenia`, `estado`, `token`, `fecha_registro`, `id_rol`) VALUES (1,'Administrador','admin','$2y$12$HNJvX5UnK8a7mUvZxKs3zeo0leHVp2fg3.iTNMWSUbPmsvFdxb.T.','1',NULL,'0000-00-00 00:00:00',1);

/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'mercado_bd'
--

--
-- Dumping routines for database 'mercado_bd'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-03  8:06:25

COMMIT;
SET FOREIGN_KEY_CHECKS=1;
