-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: talha_pos
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'staff',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_store_role` (`store_id`,`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Hamad Yasin','Hamad Yasin','yasinhamad730@gmail.com','$2y$10$AI2G0HBPgyjlNNWCVixjSOyKYAEUx6pwtlFPvhcSuOYWxE1E3W97e','super_admin','active',NULL,0,NULL,NULL,'2026-10-27 06:15:31'),(2,1,'Store Admin','store@devaltum.pos','store@devaltum.pos','$2y$10$7y5s4XDJXnSrqvzuCOCHp.ZjVPpagukCuBdPBTexCgv7mOZU4Zi3m','store_owner','active','',0,NULL,NULL,'2026-10-27 06:17:28');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('bluetooth_scanner_enabled','1'),('company_address',''),('company_name','DevAltum POS'),('company_tagline','Official point of sale system'),('credit_whatsapp_template','Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}'),('currency_code','PKR'),('currency_symbol','Rs'),('devaltum_brand_color','#0f172a'),('devaltum_brand_name','DevAltum'),('devaltum_brand_prefix','Software By'),('devaltum_credit_alignment','left'),('devaltum_credit_font_family','Oswald'),('devaltum_credit_font_size','12.5'),('devaltum_credit_font_style','normal'),('devaltum_credit_font_weight','800'),('devaltum_credit_logo_size','53'),('devaltum_credit_show_logo','1'),('devaltum_custom_logo',''),('devaltum_phone_text','+92 3101648600'),('hardware_scanner_enabled','1'),('last_auto_backup_date','2026-10-29'),('last_daily_automation_date','2026-10-29'),('last_schema_sync_at','2026-10-27 11:15:15'),('mobile_scanner_enabled','0'),('printer_default_mode','document'),('printer_margin_mm','4'),('printer_paper_size','auto'),('printer_status_tracking','1'),('project_slug','devaltum-pos'),('receipt_footer','Thank you for choosing us. For support, please contact our store team.'),('receipt_logo',''),('receipt_template','classic'),('scanner_beep_enabled','1'),('scanner_poll_interval_ms','900'),('schema_checksum','730775262076788b89a680999c89c87b'),('schema_version','2.6.0'),('show_product_images','1'),('system_installed','1'),('system_license_key','WPqphbU85bQd3+cTIAQFbhzjAPRn8OT3GG6i3GsKWyhrcWuHF/gPpLtX1AtTcpVDgpU93CJzREbShBPXOoLIEOj3SuuNJDTWkV+ZhOCU9Mw24kLHuaPL7KSPCz5bEasv3FmPpoei8arPLMYb6QJohljmXVrr3pVxpq2Hh7vPbNK7fJ46JATBNZiy07B/WHuw9oitvb89slYYzXz0xCZRig=='),('tax_enabled','0'),('tax_label','Sales Tax'),('tax_rate','18'),('website_logo',''),('whatsapp_message','Thank you for shopping with us. Please contact our store for support or order assistance.'),('whatsapp_number','+923101648600'),('whatsapp_provider','none');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lifetime',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `current_plan_id` int DEFAULT NULL,
  `parent_store_id` int DEFAULT NULL,
  `branch_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scanner_pair_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_started_at` datetime DEFAULT NULL,
  `subscription_expires_at` datetime DEFAULT NULL,
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `next_monthly_charge_date` date DEFAULT NULL,
  `monthly_charge_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_uid` (`store_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `stores` WRITE;
/*!40000 ALTER TABLE `stores` DISABLE KEYS */;
INSERT INTO `stores` VALUES (1,'store-pos-main-001','DevAltum Store','Store Owner','store@devaltum.pos','+923101648600','Main Market','lifetime','active',NULL,NULL,NULL,'123456',NOW(),NULL,'paid',NULL,0.00,NOW(),NOW());
/*!40000 ALTER TABLE `stores` ENABLE KEYS */;
UNLOCK TABLES;

-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `method_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `account_number` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `account_holder` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method_logo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29 10:13:29
