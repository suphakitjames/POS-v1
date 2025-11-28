-- ========================================
-- Notification System - Database Schema
-- ========================================
-- สร้างตารางสำหรับระบบแจ้งเตือน
-- Version: 1.0
-- Date: 2025-11-28
-- ========================================

-- Table: notifications
-- เก็บข้อมูลการแจ้งเตือนทั้งหมด
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'รหัสผู้ใช้ที่จะรับการแจ้งเตือน',
    type ENUM('low_stock', 'expiring_soon', 'out_of_stock', 'security_alert') NOT NULL COMMENT 'ประเภทการแจ้งเตือน',
    title VARCHAR(255) NOT NULL COMMENT 'หัวข้อการแจ้งเตือน',
    message TEXT NOT NULL COMMENT 'รายละเอียดการแจ้งเตือน',
    related_id INT NULL COMMENT 'รหัสที่เกี่ยวข้อง (Product ID หรือ User ID)',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'สถานะการอ่าน (0=ยังไม่อ่าน, 1=อ่านแล้ว)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้างการแจ้งเตือน',
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes สำหรับเพิ่มประสิทธิภาพในการค้นหา
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ตารางเก็บข้อมูลการแจ้งเตือนทั้งหมด';

-- ========================================

-- Table: login_attempts
-- เก็บประวัติการพยายาม login เพื่อตรวจจับกิจกรรมที่น่าสงสัย
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT 'ชื่อผู้ใช้ที่พยายาม login',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP Address (รองรับทั้ง IPv4 และ IPv6)',
    is_successful BOOLEAN DEFAULT FALSE COMMENT 'สถานะการ login (0=ล้มเหลว, 1=สำเร็จ)',
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่พยายาม login',
    
    -- Indexes
    INDEX idx_username (username),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ตารางเก็บประวัติการพยายาม login';

-- ========================================
-- Sample Data (Optional - สำหรับทดสอบ)
-- ========================================

-- ตัวอย่างการสร้าง notification (ไม่บังคับ - ใช้สำหรับทดสอบ)
-- INSERT INTO notifications (user_id, type, title, message, related_id) VALUES
-- (1, 'low_stock', 'สินค้าต่ำกว่าเกณฑ์', 'สินค้า SKU-001 เหลือเพียง 2 ชิ้น', 1),
-- (1, 'expiring_soon', 'สินค้าใกล้หมดอายุ', 'สินค้า ABC จะหมดอายุใน 7 วัน', 2);

-- ตัวอย่างการบันทึก login attempt ที่ล้มเหลว
-- INSERT INTO login_attempts (username, ip_address, is_successful) VALUES
-- ('testuser', '192.168.1.100', FALSE),
-- ('testuser', '192.168.1.100', FALSE),
-- ('testuser', '192.168.1.100', FALSE);

-- ========================================
-- Maintenance Queries (สำหรับบำรุงรักษา)
-- ========================================

-- ลบ notifications ที่อ่านแล้วและเก่ากว่า 30 วัน
-- DELETE FROM notifications WHERE is_read = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ลบ login_attempts เก่ากว่า 90 วัน
-- DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ========================================
-- Useful Queries (คำสั่งที่มีประโยชน์)
-- ========================================

-- ดูการแจ้งเตือนที่ยังไม่อ่านทั้งหมด
-- SELECT * FROM notifications WHERE is_read = FALSE ORDER BY created_at DESC;

-- นับจำนวนการแจ้งเตือนที่ยังไม่อ่านแยกตาม user
-- SELECT user_id, COUNT(*) as unread_count FROM notifications WHERE is_read = FALSE GROUP BY user_id;

-- ดู login attempts ที่ล้มเหลวล่าสุด 10 รายการ
-- SELECT * FROM login_attempts WHERE is_successful = FALSE ORDER BY attempted_at DESC LIMIT 10;

-- ตรวจสอบ IP ที่มีการ login ผิดพลาดมากกว่า 3 ครั้งใน 1 ชั่วโมงที่ผ่านมา
-- SELECT ip_address, COUNT(*) as fail_count FROM login_attempts 
-- WHERE is_successful = FALSE AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
-- GROUP BY ip_address HAVING fail_count >= 3;
