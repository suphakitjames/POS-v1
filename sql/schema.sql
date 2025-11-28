-- Database Schema for Smart Inventory System
-- Version: 1.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
-- Password is 'admin123'
-- Hash generated using PASSWORD_DEFAULT (BCRYPT)
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'admin', '2023-01-01 00:00:00'),
(2, 'staff01', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'staff', '2023-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
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
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_info`, `address`) VALUES
(1, 'บริษัท ไทยเทรดดิ้ง จำกัด', '02-123-4567', '123 ถ.สุขุมวิท กทม.'),
(2, 'หจก. สมชายพานิช', '081-987-6543', '456 ถ.เพชรเกษม นครปฐม'),
(3, 'บจก. สยามฟู้ดส์', '02-999-8888', '789 นิคมอุตสาหกรรมบางปู สมุทรปราการ'),
(4, 'China Import Export', 'contact@chinaimport.com', 'Guangzhou, China');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_point` int(11) NOT NULL DEFAULT 10,
  `expire_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `barcode`, `name`, `description`, `image_path`, `category_id`, `cost_price`, `selling_price`, `stock_quantity`, `reorder_point`, `expire_date`) VALUES
(1, 'ELEC-001', '8850001000011', 'พัดลมไอเย็น Masterkool', 'พัดลมไอเย็นขนาด 10 ลิตร ประหยัดไฟ', 'uploads/fan-masterkool.jpg', 1, 1500.00, 2990.00, 50, 10, NULL),
(2, 'ELEC-002', '8850001000028', 'หม้อหุงข้าว Sharp 1.8L', 'หม้อหุงข้าวอุ่นทิพย์ ขนาด 1.8 ลิตร', 'uploads/rice-cooker-sharp.jpg', 1, 600.00, 890.00, 30, 5, NULL),
(3, 'ELEC-003', '8850001000035', 'ไดร์เป่าผม Philips', 'ไดร์เป่าผมพลังแรง 1200W', 'uploads/hair-dryer.jpg', 1, 350.00, 590.00, 20, 5, NULL),
(4, 'ELEC-004', '8850001000042', 'หูฟัง Bluetooth Sony', 'หูฟังไร้สาย ตัดเสียงรบกวน', 'uploads/sony-headphone.jpg', 1, 2500.00, 4990.00, 15, 3, NULL),
(5, 'CLOTH-001', '8850002000010', 'เสื้อยืด Cotton 100% สีขาว', 'เสื้อยืดคอกลม ผ้าฝ้ายแท้ ใส่สบาย', 'uploads/tshirt-white.jpg', 2, 80.00, 199.00, 100, 20, NULL),
(6, 'CLOTH-002', '8850002000027', 'กางเกงยีนส์ Levi\'s', 'กางเกงยีนส์ขากระบอก สีน้ำเงินเข้ม', 'uploads/jeans-levis.jpg', 2, 1200.00, 2500.00, 40, 10, NULL),
(7, 'CLOTH-003', '8850002000034', 'รองเท้าผ้าใบ Nike Air', 'รองเท้าวิ่ง ใส่สบาย น้ำหนักเบา', 'uploads/nike-shoes.jpg', 2, 1800.00, 3500.00, 25, 5, NULL),
(8, 'HOME-001', '8850003000019', 'โซฟาเบด 3 ที่นั่ง', 'โซฟาปรับนอนได้ ผ้ากำมะหยี่ สีเทา', 'uploads/sofa-bed.jpg', 3, 3500.00, 5900.00, 5, 2, NULL),
(9, 'HOME-002', '8850003000026', 'ชุดเครื่องนอน Toto 6 ฟุต', 'ชุดผ้าปูที่นอนพร้อมผ้านวม ลายการ์ตูน', 'uploads/bed-sheet.jpg', 3, 800.00, 1590.00, 15, 5, NULL),
(10, 'HOME-003', '8850003000033', 'โคมไฟตั้งโต๊ะ LED', 'โคมไฟอ่านหนังสือ ปรับแสงได้ 3 ระดับ', 'uploads/lamp-led.jpg', 3, 250.00, 490.00, 60, 10, NULL),
(11, 'BEAUTY-001', '8850004000018', 'ครีมกันแดด Biore UV', 'ครีมกันแดดสูตรน้ำ บางเบา ไม่เหนียว', 'uploads/sunscreen.jpg', 4, 180.00, 290.00, 80, 15, '2025-12-31'),
(12, 'BEAUTY-002', '8850004000025', 'ลิปสติก Maybelline', 'ลิปสติกเนื้อแมท ติดทนนาน สีแดงสด', 'uploads/lipstick.jpg', 4, 150.00, 259.00, 100, 20, '2026-06-30'),
(13, 'BEAUTY-003', '8850004000032', 'โฟมล้างหน้า Neutrogena', 'โฟมล้างหน้าสูตรอ่อนโยน ลดสิว', 'uploads/facial-foam.jpg', 4, 120.00, 199.00, 50, 10, '2025-10-15'),
(14, 'FOOD-001', '8850005000017', 'ข้าวหอมมะลิ 5 กก.', 'ข้าวหอมมะลิแท้ 100% คัดพิเศษ', 'uploads/rice-5kg.jpg', 5, 180.00, 250.00, 200, 30, '2024-12-31'),
(15, 'FOOD-002', '8850005000024', 'น้ำมันพืช 1 ลิตร', 'น้ำมันปาล์มสำหรับทอด', 'uploads/oil-1l.jpg', 5, 35.00, 45.00, 150, 20, '2024-08-20'),
(16, 'FOOD-003', '8850005000031', 'บะหมี่กึ่งสำเร็จรูป (แพ็ค)', 'รสต้มยำกุ้ง แพ็ค 10 ซอง', 'uploads/noodle-pack.jpg', 5, 45.00, 60.00, 300, 50, '2024-06-01'),
(17, 'FOOD-004', '8850005000048', 'กาแฟ Nescafe Gold', 'กาแฟสำเร็จรูป รุ่นโกลด์ 200g', 'uploads/coffee-gold.jpg', 5, 250.00, 390.00, 40, 10, '2025-03-15'),
(18, 'ELEC-005', '8850001000059', 'Power Bank 20000mAh', 'แบตสำรองชาร์จเร็ว มีหน้าจอ LED', 'uploads/powerbank.jpg', 1, 400.00, 790.00, 80, 15, NULL),
(19, 'CLOTH-004', '8850002000041', 'กระเป๋าเป้ Adidas', 'กระเป๋าเป้สะพายหลัง สีดำ จุของได้เยอะ', 'uploads/bag-adidas.jpg', 2, 900.00, 1800.00, 20, 5, NULL),
(20, 'HOME-004', '8850003000040', 'กล่องเก็บของพลาสติก', 'กล่องเอนกประสงค์ มีล้อเลื่อน 50L', 'uploads/box-plastic.jpg', 3, 150.00, 299.00, 120, 20, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `type` enum('in','out','adjust') NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_stock` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `fk_trans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_trans_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
