-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               9.1.0 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for financial_health
CREATE DATABASE IF NOT EXISTS `financial_health` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `financial_health`;

-- Dumping structure for table financial_health.accounts
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('cash','bank','investment','credit_card','loan','other_asset','other_liability') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank',
  `is_liquid` tinyint(1) NOT NULL DEFAULT '1',
  `is_net_worth` tinyint(1) NOT NULL DEFAULT '1',
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.accounts: 3 rows
DELETE FROM `accounts`;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` (`id`, `name`, `type`, `is_liquid`, `is_net_worth`, `opening_balance`, `created_at`) VALUES
	(1, 'Cash Wallet', 'cash', 1, 1, 100.00, '2025-12-09 12:43:59'),
	(2, 'Main Bank Account', 'bank', 1, 1, 1000.00, '2025-12-09 12:43:59'),
	(3, 'StashAway Simple MYR', 'investment', 1, 1, 200.00, '2025-12-09 12:43:59'),
	(4, 'Alliance CC (4006)', 'credit_card', 0, 0, 50.00, '2025-12-09 15:27:12'),
	(5, 'CIMB CC (7277)', 'credit_card', 0, 0, 500.00, '2025-12-09 15:27:37'),
	(6, 'Maybank CC (4093)', 'credit_card', 1, 1, 200.00, '2025-12-09 15:28:01'),
	(7, 'UOB CC (2151)', 'credit_card', 1, 1, 100.00, '2025-12-09 15:28:16'),
	(8, 'LOSM', 'loan', 1, 1, 0.00, '2025-12-09 16:21:07');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;

-- Dumping structure for table financial_health.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` enum('income','expense') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_debt_payment` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.categories: 9 rows
DELETE FROM `categories`;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`id`, `name`, `kind`, `is_debt_payment`, `created_at`) VALUES
	(1, 'Salary', 'income', 0, '2025-12-09 12:43:59'),
	(2, 'Side Job', 'income', 0, '2025-12-09 12:43:59'),
	(3, 'Food', 'expense', 0, '2025-12-09 12:43:59'),
	(4, 'Transport', 'expense', 0, '2025-12-09 12:43:59'),
	(5, 'Rent', 'expense', 0, '2025-12-09 12:43:59'),
	(6, 'Utilities', 'expense', 0, '2025-12-09 12:43:59'),
	(7, 'Insurance', 'expense', 0, '2025-12-09 12:43:59'),
	(8, 'Loan Installment', 'expense', 1, '2025-12-09 12:43:59'),
	(9, 'Credit Card Payment', 'expense', 1, '2025-12-09 12:43:59');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;

-- Dumping structure for table financial_health.installment_plans
CREATE TABLE IF NOT EXISTS `installment_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_amount` decimal(14,2) NOT NULL,
  `term_months` int NOT NULL,
  `monthly_payment` decimal(14,2) NOT NULL,
  `start_date` date NOT NULL,
  `due_day` tinyint DEFAULT NULL,
  `closed_at` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_installments_card` (`account_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.installment_plans: 0 rows
DELETE FROM `installment_plans`;
/*!40000 ALTER TABLE `installment_plans` DISABLE KEYS */;
INSERT INTO `installment_plans` (`id`, `account_id`, `title`, `merchant`, `original_amount`, `term_months`, `monthly_payment`, `start_date`, `due_day`, `closed_at`, `notes`, `created_at`) VALUES
	(1, 4, 'AZXasdAD', 'asAEQWE', 2222.00, 12, 21.00, '2025-12-09', NULL, NULL, NULL, '2025-12-09 16:20:05');
/*!40000 ALTER TABLE `installment_plans` ENABLE KEYS */;

-- Dumping structure for table financial_health.loans
CREATE TABLE IF NOT EXISTS `loans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lender` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `principal_amount` decimal(14,2) NOT NULL,
  `start_date` date NOT NULL,
  `term_months` int DEFAULT NULL,
  `nominal_rate` decimal(5,2) DEFAULT NULL,
  `monthly_payment` decimal(14,2) DEFAULT NULL,
  `due_day` tinyint DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_loans_account` (`account_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.loans: 0 rows
DELETE FROM `loans`;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` (`id`, `account_id`, `name`, `lender`, `principal_amount`, `start_date`, `term_months`, `nominal_rate`, `monthly_payment`, `due_day`, `notes`, `created_at`) VALUES
	(1, 8, 'DFSDFSDF', 'SDFSDFSDF', 334234.00, '2025-12-09', 32, 5.00, 333.00, 4, NULL, '2025-12-09 16:21:38');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;

-- Dumping structure for table financial_health.monthly_snapshots
CREATE TABLE IF NOT EXISTS `monthly_snapshots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL,
  `income` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expenses` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_assets` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_liabilities` decimal(14,2) NOT NULL DEFAULT '0.00',
  `liquid_assets` decimal(14,2) NOT NULL DEFAULT '0.00',
  `debt_payments` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.monthly_snapshots: 0 rows
DELETE FROM `monthly_snapshots`;
/*!40000 ALTER TABLE `monthly_snapshots` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_snapshots` ENABLE KEYS */;

-- Dumping structure for table financial_health.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booked_at` date NOT NULL,
  `type` enum('income','expense','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `from_account_id` int DEFAULT NULL,
  `to_account_id` int DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `loan_id` int DEFAULT NULL,
  `installment_id` int DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_booked_at` (`booked_at`),
  KEY `idx_type` (`type`),
  KEY `from_account_id` (`from_account_id`),
  KEY `to_account_id` (`to_account_id`),
  KEY `category_id` (`category_id`),
  KEY `fk_tx_loan` (`loan_id`),
  KEY `fk_tx_installment` (`installment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table financial_health.transactions: 0 rows
DELETE FROM `transactions`;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` (`id`, `booked_at`, `type`, `amount`, `from_account_id`, `to_account_id`, `category_id`, `loan_id`, `installment_id`, `description`, `created_at`, `deleted_at`) VALUES
	(1, '2025-12-09', 'expense', 2133.00, 5, 5, 6, NULL, NULL, 'asdasdasdasdas', '2025-12-09 16:19:05', NULL);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
