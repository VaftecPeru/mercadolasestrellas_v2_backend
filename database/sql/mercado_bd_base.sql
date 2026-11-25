-- =============================================================================
-- SISTEMA DE GESTION DE MERCADOS - MERCADO LAS ESTRELLAS
-- Base de Datos: mercado_bd (Esquema Limpio + Datos Semilla / Base)
-- Generado para despliegue y desarrollo limpio.
-- Nota:
--   1. No utiliza procedimientos almacenados; la logica reside en Laravel.
--   2. No incluye credenciales predeterminadas (admin/password) ni datos
--      personales heredados. El acceso inicial se aprovisiona de forma segura
--      mediante: php artisan admin:inicial.
--   3. Todos los contadores AUTO_INCREMENT y correlativos de documentos estan
--      reiniciados para una instalacion limpia.
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

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
CREATE TABLE `banco` (
  `id_banco` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `siglas` varchar(20) NOT NULL,
  `estado` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  PRIMARY KEY (`id_banco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banco`
--

INSERT INTO `banco` (`id_banco`, `nombre`, `siglas`, `estado`) VALUES
(1,'Banco de Crédito del Perú','BCP','1'),
(2,'Banco Continental BBVA','BBVA','1'),
(3,'Interbank','IBK','1'),
(4,'Banco Nacion','BN','1'),
(5,'Scotiabank','SC','1');

--
-- Table structure for table `banco_cuenta`
--

DROP TABLE IF EXISTS `banco_cuenta`;
CREATE TABLE `banco_cuenta` (
  `id_bancocuenta` bigint(20) unsigned NOT NULL,
  `numero_cuenta` varchar(100) NOT NULL,
  `id_banco` bigint(20) unsigned NOT NULL,
  `estado` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  PRIMARY KEY (`id_bancocuenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banco_cuenta`
--

INSERT INTO `banco_cuenta` (`id_bancocuenta`, `numero_cuenta`, `id_banco`, `estado`) VALUES
(1,'0-800-00-222',2,'1'),
(2,'0-801-00-271',2,'1'),
(3,'0-800-00-222',1,'1');

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id_block` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_block`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`id_block`, `nombre`) VALUES
(1,'Ninguno'),
(2,'1 Piso'),
(3,'2 Piso');

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cuota_servicios`
--

DROP TABLE IF EXISTS `cuota_servicios`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `cuota_servicios`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `cuotas`
--

DROP TABLE IF EXISTS `cuotas`;
CREATE TABLE `cuotas` (
  `id_cuota` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha_emision` datetime NOT NULL,
  `fecha_vencimiento` datetime NOT NULL,
  `importe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `global` varchar(1) NOT NULL COMMENT '1 = es para todos los puestos ; 0 =  es para 1 puesto',
  `id_anio` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_cuota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `cuotas`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `detalle_pagos`
--

DROP TABLE IF EXISTS `detalle_pagos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `detalle_pagos`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `deuda_cuotas`
--

DROP TABLE IF EXISTS `deuda_cuotas`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `deuda_cuotas`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `deudas`
--

DROP TABLE IF EXISTS `deudas`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `deudas`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
CREATE TABLE `documentos` (
  `id_documento` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_documento` bigint(20) unsigned NOT NULL DEFAULT 0,
  `serie` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_documento`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documentos` (correlativo numero_documento reiniciado en 0)
--

INSERT INTO `documentos` (`id_documento`, `numero_documento`, `serie`, `estado`, `fecha_registro`) VALUES
(1,0,'0000','1','2025-10-02 09:30:54');

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
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

--
-- Table structure for table `giro_negocios`
--

DROP TABLE IF EXISTS `giro_negocios`;
CREATE TABLE `giro_negocios` (
  `id_gironegocio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_gironegocio`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giro_negocios`
--

INSERT INTO `giro_negocios` (`id_gironegocio`, `nombre`) VALUES
(1,'Varios');

--
-- Table structure for table `inquilinos`
--

DROP TABLE IF EXISTS `inquilinos`;
CREATE TABLE `inquilinos` (
  `id_inquilino` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  PRIMARY KEY (`id_inquilino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `inquilinos`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
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

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
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

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1,'0001_01_01_000000_create_users_table',1),
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
(19,'2024_08_22_160112_create_cuota_servicios_table',1),
(20,'2025_09_04_063421_add_importe_to_cuota_servicios_table',1),
(21,'2026_09_14_000000_preparar_cuentas_socio_y_roles',1),
(22,'2026_09_14_000001_agregar_modulo_usuarios_roles',1),
(23,'2026_09_14_000002_agregar_id_persona_a_usuarios',1),
(24,'2026_10_05_000000_habilitar_reporte_pagos_socio',1),
(25,'2026_10_29_000000_crear_tablas_base_faltantes',1),
(26,'2026_10_29_000001_sincronizar_columnas_faltantes',1);

--
-- Table structure for table `modulo`
--

DROP TABLE IF EXISTS `modulo`;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modulo`
--

INSERT INTO `modulo` (`id_modulo`, `nombre`, `url`, `url_foco`, `url_activa`, `icon`, `estado`, `id_modulo_parent`, `orden`) VALUES
(1,'Panel de Control',NULL,NULL,'0','DashboardIcon','1',NULL,1000),
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
(12,'Reporte de resumen','/home/reporte-resumen','reporte-resumen','1','Description','1',2,2005),
(13,'Usuarios y Roles','/home/usuarios','usuarios','1','ManageAccounts','1',1,1006);

--
-- Table structure for table `pago_banco`
--

DROP TABLE IF EXISTS `pago_banco`;
CREATE TABLE `pago_banco` (
  `id_pagobanco` bigint(20) NOT NULL,
  `id_banco` bigint(20) unsigned NOT NULL,
  `id_bancocuenta` bigint(20) unsigned NOT NULL,
  `numero_operacion` varchar(50) NOT NULL,
  `fecha_operacion` date NOT NULL,
  PRIMARY KEY (`id_pagobanco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `pago_banco`: inicializada sin datos operativos.

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `pagos`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `personal_access_tokens`: inicializada sin tokens heredados (AUTO_INCREMENT=1).

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
CREATE TABLE `personas` (
  `id_persona` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT '1' COMMENT '1 = activo ; 0 = desactivo',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `nombre_completo` varchar(450) DEFAULT NULL,
  `direccion` varchar(500) DEFAULT NULL,
  `sexo` varchar(50) NOT NULL DEFAULT '1' COMMENT '1 = Masculino ; 2 = Femenino',
  `tipo_persona` varchar(50) DEFAULT '1' COMMENT '1 = natural ; 2 = juridica',
  PRIMARY KEY (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `personas`: inicializada sin registros heredados ni datos personales predeterminados (AUTO_INCREMENT=1).

--
-- Table structure for table `puesto_cuotas`
--

DROP TABLE IF EXISTS `puesto_cuotas`;
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

--
-- Table structure for table `puestos`
--

DROP TABLE IF EXISTS `puestos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `puestos`: inicializada sin datos operativos (AUTO_INCREMENT=1).

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
CREATE TABLE `rol` (
  `id_rol` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(100) DEFAULT NULL,
  `es_administrador` varchar(1) DEFAULT '0' COMMENT '1 = si ; 0 = no',
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre`, `codigo`, `es_administrador`) VALUES
(1,'Administrador','ADM','1'),
(2,'Socio','SOC','0'),
(3,'Cajero','CAJ','0');

--
-- Table structure for table `rol_modulo`
--

DROP TABLE IF EXISTS `rol_modulo`;
CREATE TABLE `rol_modulo` (
  `id_rol` bigint(20) unsigned NOT NULL,
  `id_modulo` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id_rol`,`id_modulo`),
  KEY `rolmodulo_modulo_fk` (`id_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rol_modulo`
--

INSERT INTO `rol_modulo` (`id_rol`, `id_modulo`) VALUES
(1,1),
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
(1,12),
(1,13),
(2,2),
(2,8),
(2,9),
(3,1),
(3,2),
(3,3),
(3,7),
(3,8),
(3,9),
(3,10),
(3,11),
(3,12);

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
CREATE TABLE `servicios` (
  `id_servicio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `tipo_servicio` int(11) NOT NULL COMMENT '1 = Ordinario (Pagos Fijos) ; 2 = Extraordinario (Pagos Extras) ; 3 = Metrado (Pago por área) ; 4 = Cuota Extraordinaria',
  `activo` varchar(1) NOT NULL DEFAULT '1' COMMENT '1 = esta activo ( se mostrara) ; 0 = esta desactivo( no se mostrara)',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `codigo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `servicios` (Catalogo base limpio sin registros historicos de años anteriores)
--

INSERT INTO `servicios` (`id_servicio`, `nombre`, `costo_unitario`, `tipo_servicio`, `activo`, `fecha_registro`, `codigo`) VALUES
(1,'Gastos Administrativos',0.00,1,'1','2026-01-01 00:00:00','1'),
(2,'Impuesto Predial y Arbitrios',0.00,1,'1','2026-01-01 00:00:00','2'),
(3,'Servicio Contable',0.00,1,'1','2026-01-01 00:00:00','3'),
(4,'Cuotas extraordinarias',0.00,4,'1','2026-01-01 00:00:00','4'),
(5,'Multa por inasistencia',0.00,2,'1','2026-01-01 00:00:00','5'),
(6,'Consumo de Agua',0.00,1,'1','2026-01-01 00:00:00','6'),
(7,'Vigilancia y Seguridad',0.00,1,'1','2026-01-01 00:00:00','7'),
(8,'Limpieza y Mantenimiento',0.00,1,'1','2026-01-01 00:00:00','8');

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
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

-- Tabla `sessions`: inicializada sin sesiones activas.

--
-- Table structure for table `setup_anio`
--

DROP TABLE IF EXISTS `setup_anio`;
CREATE TABLE `setup_anio` (
  `id_anio` bigint(20) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setup_anio`
--

INSERT INTO `setup_anio` (`id_anio`, `nombre`) VALUES
(2012,'2012'),
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
(2025,'2025'),
(2026,'2026');

--
-- Table structure for table `setup_mes`
--

DROP TABLE IF EXISTS `setup_mes`;
CREATE TABLE `setup_mes` (
  `id_mes` bigint(20) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setup_mes`
--

INSERT INTO `setup_mes` (`id_mes`, `nombre`) VALUES
(1,'Enero'),
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

--
-- Table structure for table `socios`
--

DROP TABLE IF EXISTS `socios`;
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

-- Tabla `socios`: inicializada sin datos operativos.

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
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

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_persona` bigint(20) unsigned DEFAULT NULL,
  `nombre_usuario` varchar(255) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `estado` varchar(255) DEFAULT '1',
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 1,
  `bloqueado` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_acceso` datetime DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_rol` int(20) unsigned NOT NULL,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla `usuarios`: inicializada sin credenciales predeterminadas (admin/password eliminados por seguridad).
-- Para crear el administrador inicial de forma segura, ejecute: php artisan admin:inicial

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

COMMIT;
SET FOREIGN_KEY_CHECKS=1;
