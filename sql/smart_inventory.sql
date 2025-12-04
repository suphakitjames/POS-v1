-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 11:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'เครื่องใช้ไฟฟ้า', 'อุปกรณ์ไฟฟ้าและอิเล็กทรอนิกส์ต่างๆ'),
(2, 'เสื้อผ้าแฟชั่น', 'เสื้อผ้าบุรุษ สตรี และเด็ก'),
(3, 'ของใช้ในบ้าน', 'เฟอร์นิเจอร์และของตกแต่งบ้าน'),
(4, 'ความงามและสุขภาพ', 'เครื่องสำอางและผลิตภัณฑ์ดูแลสุขภาพ'),
(5, 'อาหารและเครื่องดื่ม', 'อาหารแห้ง เครื่องดื่ม และขนมขบเคี้ยว');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้ที่พยายาม login',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP Address (รองรับทั้ง IPv4 และ IPv6)',
  `is_successful` tinyint(1) DEFAULT 0 COMMENT 'สถานะการ login (0=ล้มเหลว, 1=สำเร็จ)',
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันเวลาที่พยายาม login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บประวัติการพยายาม login';

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้ที่จะรับการแจ้งเตือน',
  `type` enum('low_stock','expiring_soon','out_of_stock','security_alert') NOT NULL COMMENT 'ประเภทการแจ้งเตือน',
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อการแจ้งเตือน',
  `message` text NOT NULL COMMENT 'รายละเอียดการแจ้งเตือน',
  `related_id` int(11) DEFAULT NULL COMMENT 'รหัสที่เกี่ยวข้อง (Product ID หรือ User ID)',
  `is_read` tinyint(1) DEFAULT 0 COMMENT 'สถานะการอ่าน (0=ยังไม่อ่าน, 1=อ่านแล้ว)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้างการแจ้งเตือน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บข้อมูลการแจ้งเตือนทั้งหมด';

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_id`, `is_read`, `created_at`) VALUES
(1, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า หม้อหุงข้าว Sharp 1.8L (SKU: ELEC-002) เหลือเพียง 1 ชิ้น (เกณฑ์: 5)', 2, 1, '2025-11-28 14:28:43'),
(2, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 1, '2025-11-28 14:28:43'),
(3, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า หม้อหุงข้าว Sharp 1.8L (SKU: ELEC-002) เหลือเพียง 1 ชิ้น (เกณฑ์: 5)', 2, 1, '2025-11-28 14:36:43'),
(4, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 1, '2025-11-28 14:36:43'),
(5, 1, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-11-28 14:36:43'),
(6, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า หม้อหุงข้าว Sharp 1.8L (SKU: ELEC-002) เหลือเพียง 1 ชิ้น (เกณฑ์: 5)', 2, 0, '2025-11-28 14:41:32'),
(7, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-11-28 14:41:32'),
(8, 2, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า หม้อหุงข้าว Sharp 1.8L (SKU: ELEC-002) เหลือเพียง 1 ชิ้น (เกณฑ์: 5)', 2, 0, '2025-11-28 22:33:50'),
(9, 2, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-11-28 22:33:50'),
(10, 2, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-11-28 22:33:50'),
(11, 4, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า หม้อหุงข้าว Sharp 1.8L (SKU: ELEC-002) เหลือเพียง 1 ชิ้น (เกณฑ์: 5)', 2, 0, '2025-11-28 22:33:50'),
(12, 4, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-11-28 22:33:50'),
(13, 4, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-11-28 22:33:50'),
(14, 1, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-12-03 13:17:00'),
(15, 1, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-12-03 13:17:00'),
(16, 1, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า ครีมกันแดด Biore UV (SKU: BEAUTY-001) จะหมดอายุใน 28 วัน (วันที่ 31/12/2025)', 11, 0, '2025-12-03 13:17:00'),
(17, 1, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า Power Bank 20000mAh (SKU: ELEC-005) จะหมดอายุใน 29 วัน (วันที่ 01/01/2026)', 18, 0, '2025-12-03 13:17:00'),
(18, 2, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-12-03 13:17:00'),
(19, 2, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-12-03 13:17:00'),
(20, 2, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า ครีมกันแดด Biore UV (SKU: BEAUTY-001) จะหมดอายุใน 28 วัน (วันที่ 31/12/2025)', 11, 0, '2025-12-03 13:17:00'),
(21, 2, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า Power Bank 20000mAh (SKU: ELEC-005) จะหมดอายุใน 29 วัน (วันที่ 01/01/2026)', 18, 0, '2025-12-03 13:17:00'),
(22, 4, 'low_stock', '⚠️ สินค้าต่ำกว่าเกณฑ์', 'สินค้า กางเกงยีนส์ Levi&#039;s (SKU: CLOTH-002) เหลือเพียง 3 ชิ้น (เกณฑ์: 10)', 6, 0, '2025-12-03 13:17:00'),
(23, 4, 'out_of_stock', '📦 สินค้าหมดสต็อก', 'สินค้า โคมไฟตั้งโต๊ะ LED (SKU: HOME-003) หมดสต็อก กรุณาเติมสินค้า', 10, 0, '2025-12-03 13:17:00'),
(24, 4, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า ครีมกันแดด Biore UV (SKU: BEAUTY-001) จะหมดอายุใน 28 วัน (วันที่ 31/12/2025)', 11, 0, '2025-12-03 13:17:00'),
(25, 4, 'expiring_soon', '🕒 สินค้าใกล้หมดอายุ', 'สินค้า Power Bank 20000mAh (SKU: ELEC-005) จะหมดอายุใน 29 วัน (วันที่ 01/01/2026)', 18, 0, '2025-12-03 13:17:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_point` int(11) NOT NULL DEFAULT 10,
  `expire_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `barcode`, `name`, `description`, `image_path`, `category_id`, `supplier_id`, `cost_price`, `selling_price`, `stock_quantity`, `reorder_point`, `expire_date`, `created_at`, `updated_at`) VALUES
(1, 'ELEC-001', '8850001000011', 'พัดลมไอเย็น Masterkool', 'พัดลมไอเย็นขนาด 10 ลิตร ประหยัดไฟ', 'uploads/prod_6930391e6cf332.33456085.jpg', 1, 1, 1500.00, 2990.00, 70, 10, NULL, '2025-11-27 13:00:09', '2025-12-03 13:20:30'),
(2, 'ELEC-002', '8850001000028', 'หม้อหุงข้าว Sharp 1.8L', 'หม้อหุงข้าวอุ่นทิพย์ ขนาด 1.8 ลิตร', 'uploads/prod_6930393b320200.62701097.jpg', 1, 1, 600.00, 890.00, 101, 5, NULL, '2025-11-27 13:00:09', '2025-12-03 13:20:59'),
(3, 'ELEC-003', '8850001000035', 'ไดร์เป่าผม Philips', 'ไดร์เป่าผมพลังแรง 1200W', 'uploads/prod_6930396e865e85.84921310.jpg', 1, 1, 350.00, 590.00, 20, 5, NULL, '2025-11-27 13:00:09', '2025-12-03 13:21:50'),
(4, 'ELEC-004', '8850001000042', 'หูฟัง Bluetooth Sony', 'หูฟังไร้สาย ตัดเสียงรบกวน', 'uploads/prod_6930394382c660.22778269.jpg', 1, 1, 2500.00, 4990.00, 15, 3, NULL, '2025-11-27 13:00:09', '2025-12-03 13:21:07'),
(5, 'CLOTH-001', '8850002000010', 'เสื้อยืด Cotton 100% สีขาว', 'เสื้อยืดคอกลม ผ้าฝ้ายแท้ ใส่สบาย', 'uploads/prod_6930394b2bb020.43341191.jpg', 2, 1, 80.00, 199.00, 100, 20, NULL, '2025-11-27 13:00:09', '2025-12-03 13:21:15'),
(6, 'CLOTH-002', '8850002000027', 'กางเกงยีนส์ Levi\'s', 'กางเกงยีนส์ขากระบอก สีน้ำเงินเข้ม', 'uploads/prod_693038bddbcfe3.37905287.jpg', 2, 1, 1200.00, 2500.00, 3, 10, NULL, '2025-11-27 13:00:09', '2025-12-03 13:18:53'),
(7, 'CLOTH-003', '8850002000034', 'รองเท้าผ้าใบ Nike Air', 'รองเท้าวิ่ง ใส่สบาย น้ำหนักเบา', 'uploads/prod_6930392bb95ec8.57198981.jpg', 2, 1, 1800.00, 3500.00, 15, 5, NULL, '2025-11-27 13:00:09', '2025-12-03 13:20:43'),
(8, 'HOME-001', '8850003000019', 'โซฟาเบด 3 ที่นั่ง', 'โซฟาปรับนอนได้ ผ้ากำมะหยี่ สีเทา', 'uploads/prod_6930395c3f5b94.20096448.jpg', 3, 1, 3500.00, 5900.00, 5, 2, NULL, '2025-11-27 13:00:09', '2025-12-03 13:21:32'),
(9, 'HOME-002', '8850003000026', 'ชุดเครื่องนอน Toto 6 ฟุต', 'ชุดผ้าปูที่นอนพร้อมผ้านวม ลายการ์ตูน', 'uploads/prod_693038e1ad2898.23034712.jpg', 3, 1, 800.00, 1590.00, 15, 5, NULL, '2025-11-27 13:00:09', '2025-12-03 13:19:29'),
(10, 'HOME-003', '8850003000033', 'โคมไฟตั้งโต๊ะ LED', 'โคมไฟอ่านหนังสือ ปรับแสงได้ 3 ระดับ', 'uploads/prod_6930395515c999.11995025.jpg', 3, 1, 250.00, 490.00, 0, 10, NULL, '2025-11-27 13:00:09', '2025-12-03 13:21:25'),
(11, 'BEAUTY-001', '8850004000018', 'ครีมกันแดด Biore UV', 'ครีมกันแดดสูตรน้ำ บางเบา ไม่เหนียว', 'uploads/prod_693038d907fbc4.26768487.jpg', 4, 1, 180.00, 290.00, 80, 15, '2025-12-31', '2025-11-27 13:00:09', '2025-12-03 13:19:21'),
(12, 'BEAUTY-002', '8850004000025', 'ลิปสติก Maybelline', 'ลิปสติกเนื้อแมท ติดทนนาน สีแดงสด', 'uploads/prod_693039338f1b80.76393792.jpg', 4, 1, 150.00, 259.00, 100, 20, '2026-06-30', '2025-11-27 13:00:09', '2025-12-03 13:20:51'),
(13, 'BEAUTY-003', '8850004000032', 'โฟมล้างหน้า Neutrogena', 'โฟมล้างหน้าสูตรอ่อนโยน ลดสิว', 'uploads/prod_69303967e73518.02557465.jpg', 4, 1, 120.00, 199.00, 50, 10, '2025-10-15', '2025-11-27 13:00:09', '2025-12-03 13:21:43'),
(14, 'FOOD-001', '8850005000017', 'ข้าวหอมมะลิ 5 กก.', 'ข้าวหอมมะลิแท้ 100% คัดพิเศษ', 'uploads/prod_693038d10e8799.19900607.jpg', 5, 1, 180.00, 250.00, 200, 30, '2024-12-31', '2025-11-27 13:00:09', '2025-12-03 13:19:13'),
(16, 'FOOD-003', '8850005000031', 'บะหมี่กึ่งสำเร็จรูป (แพ็ค)', 'รสต้มยำกุ้ง แพ็ค 10 ซอง', 'uploads/prod_693039173c3344.79861739.jpg', 5, 1, 45.00, 60.00, 300, 50, '2024-06-01', '2025-11-27 13:00:09', '2025-12-03 13:20:23'),
(17, 'FOOD-004', '8850005000048', 'กาแฟ Nescafe Gold', 'กาแฟสำเร็จรูป รุ่นโกลด์ 500g', 'uploads/prod_693038c770d626.91980928.jpg', 5, 1, 250.00, 390.00, 40, 10, '2025-03-15', '2025-11-27 13:00:09', '2025-12-03 13:19:03'),
(18, 'ELEC-005', '8850001000059', 'Power Bank 20000mAh', 'แบตสำรองชาร์จเร็ว มีหน้าจอ LED', 'uploads/prod_69286069805265.81651060.jpg', 1, 1, 500.00, 550.00, 50, 35, '2026-01-01', '2025-11-27 13:00:09', '2025-11-29 13:24:05'),
(20, 'HOME-004', '8850003000040', 'กล่องเก็บของพลาสติก', 'กล่องเอนกประสงค์ มีล้อเลื่อน 50L', 'uploads/prod_693038b3e9bdc8.59117076.jpg', 3, 1, 150.00, 300.00, 50, 30, NULL, '2025-11-27 13:00:09', '2025-12-03 13:18:43'),
(21, 'DRINK-001', '8850006000010', 'Coke Can 325ml', '', NULL, 5, NULL, 15.00, 20.00, 100, 20, NULL, '2025-12-03 13:31:03', '2025-12-03 13:31:03');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','qr','credit') NOT NULL DEFAULT 'cash',
  `payment_status` enum('paid','pending','cancelled') NOT NULL DEFAULT 'paid',
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('promptpay_id', '0612610592', '2025-12-03 15:32:24');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `start_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `end_cash` decimal(10,2) DEFAULT NULL,
  `expected_cash` decimal(10,2) DEFAULT NULL,
  `diff_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `user_id`, `start_time`, `end_time`, `start_cash`, `end_cash`, `expected_cash`, `diff_amount`, `status`) VALUES
(1, 1, '2025-12-03 20:29:50', NULL, 10001000.00, NULL, NULL, NULL, 'open'),
(2, 2, '2025-12-03 21:02:48', '2025-12-03 21:37:39', 10001000.00, 10000.00, 10001000.00, -9991000.00, 'closed'),
(3, 2, '2025-12-03 21:38:42', '2025-12-03 21:39:39', 1000.00, 0.00, 1000.00, -1000.00, 'closed'),
(4, 2, '2025-12-03 21:45:25', '2025-12-04 04:54:02', 10001000.00, 50000.00, 10001000.00, -9951000.00, 'closed'),
(5, 2, '2025-12-04 04:55:10', NULL, 20000.00, NULL, NULL, NULL, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_info`, `address`) VALUES
(1, 'บริษัท ไทยเทรดดิ้ง จำกัด', '02-123-4567', '123 ถ.สุขุมวิท กทม.'),
(2, 'หจก. สมชายพานิช', '081-987-6544', '456 ถ.เพชรเกษม นครปฐม'),
(4, 'China Import Export', 'hontact@chinaimport.com', 'Guangzhou, China');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `type` enum('in','out','adjust') NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_stock` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `product_id`, `user_id`, `supplier_id`, `type`, `quantity`, `remaining_stock`, `note`, `created_at`) VALUES
(1, 1, 1, 4, 'in', 20, 70, 'ASD0012536645', '2025-11-27 14:41:23'),
(2, 7, 1, NULL, 'out', 10, 15, '[Sale] เหมา', '2025-11-27 14:42:01'),
(3, 2, 1, NULL, 'out', 27, 3, '[Sale] ', '2025-11-28 08:07:57'),
(4, 6, 1, NULL, 'out', 37, 3, '[Usage] ', '2025-11-28 14:22:33'),
(5, 2, 1, NULL, 'out', 2, 1, '[Expired] ', '2025-11-28 14:23:12'),
(6, 10, 1, NULL, 'out', 60, 0, '[Damaged] ', '2025-11-28 14:30:05'),
(7, 2, 1, 2, 'in', 100, 101, '', '2025-11-29 09:09:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$ZYRQNMwJDQN0pJKUD2ugduDYpjOEwGujw7MBQA9RbSGBkzDynnIgC', 'admin', '2022-12-31 17:00:00'),
(2, 'staff01', '$2y$10$ZYRQNMwJDQN0pJKUD2ugduDYpjOEwGujw7MBQA9RbSGBkzDynnIgC', 'staff', '2022-12-31 17:00:00'),
(4, 'teststaff05', '$2y$10$uwsFWsnXYq/ChK6InqZaFe8tpYPW9RWdwcanMChQhc0g0JRH06X6y', 'staff', '2025-11-28 13:47:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_attempted` (`attempted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_supplier_id` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `fk_shifts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_trans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
