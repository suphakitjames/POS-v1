# การวิเคราะห์และออกแบบระบบ POS (Point of Sale) สำหรับร้านค้า

เอกสารนี้ (read9.md) จัดทำขึ้นเพื่อวิเคราะห์และวางแผนการพัฒนาระบบขายหน้าร้าน (POS) ต่อยอดจากระบบจัดการสต็อกสินค้า (Inventory Management) ที่มีอยู่เดิม โดยเน้นความละเอียดในทุกขั้นตอนเพื่อให้สามารถนำไปพัฒนาจริงได้ทันที

---

## 1. ภาพรวมของระบบ (System Overview)

ระบบ POS ที่จะพัฒนาเพิ่มเข้ามานี้ จะทำหน้าที่เป็นจุดขายสินค้าหน้าร้าน โดยพนักงานขาย (Staff) หรือผู้ดูแลระบบ (Admin) สามารถใช้งานได้ ฟังก์ชันหลักคือการตัดสต็อกสินค้าทันทีเมื่อมีการขาย, การคำนวณยอดเงินรวม, การรับชำระเงิน, และการออกใบเสร็จรับเงิน

### เป้าหมายหลัก

1.  **ความรวดเร็ว:** สามารถยิงบาร์โค้ดหรือค้นหาสินค้าเพื่อขายได้อย่างรวดเร็ว
2.  **ความถูกต้อง:** ตัดสต็อกสินค้าทันทีที่ขาย เพื่อให้ข้อมูลสต็อกตรงกับความเป็นจริง
3.  **การตรวจสอบ:** มีระบบบันทึกประวัติการขาย (Sales History) และรายงานยอดขายรายวัน

---

## 2. การออกแบบฐานข้อมูล (Database Design)

จากฐานข้อมูลเดิมที่มีตาราง `users`, `categories`, `suppliers`, `products`, และ `transactions` เราจำเป็นต้องเพิ่มตารางใหม่ 2 ตาราง เพื่อรองรับข้อมูลการขาย ดังนี้:

### 2.1 ตาราง `sales` (บันทึกข้อมูลบิลขาย)

ตารางนี้จะเก็บข้อมูลส่วนหัวของใบเสร็จ (Header) เช่น เลขที่ใบเสร็จ, วันที่ขาย, ยอดรวม, และใครเป็นคนขาย

```sql
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,    -- เลขที่ใบเสร็จ (เช่น INV-20231201-0001)
  `user_id` int(11) NOT NULL,               -- รหัสพนักงานที่ทำรายการ (FK -> users.id)
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00, -- ยอดเงินรวมทั้งบิล
  `payment_method` enum('cash','qr','credit') NOT NULL DEFAULT 'cash', -- วิธีการชำระเงิน
  `payment_status` enum('paid','pending','cancelled') NOT NULL DEFAULT 'paid', -- สถานะการจ่าย
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(), -- วันที่และเวลาที่ขาย
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2.2 ตาราง `sale_items` (บันทึกรายการสินค้าในบิล)

ตารางนี้จะเก็บรายละเอียดว่าใน 1 บิล มีสินค้าอะไรบ้าง จำนวนเท่าไหร่ และราคาตอนที่ขายคือเท่าไหร่ (เพื่อป้องกันปัญหาราคาสินค้าเปลี่ยนในอนาคต)

```sql
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,               -- รหัสบิลขาย (FK -> sales.id)
  `product_id` int(11) NOT NULL,            -- รหัสสินค้า (FK -> products.id)
  `quantity` int(11) NOT NULL,              -- จำนวนที่ขาย
  `price` decimal(10,2) NOT NULL,           -- ราคาต่อหน่วย ณ วันที่ขาย
  `subtotal` decimal(10,2) NOT NULL,        -- ราคารวม (quantity * price)
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 3. โครงสร้างไฟล์และระบบ (File Structure & System Architecture)

เราจะใช้โครงสร้าง MVC (Model-View-Controller) แบบเดิมที่มีอยู่ โดยเพิ่มไฟล์ใหม่ดังนี้:

### 3.1 หน้าจอใช้งาน (Frontend - Views)

- **`public/pos.php`**: หน้าจอหลักสำหรับขายสินค้า (POS Terminal)
  - **ส่วนค้นหา:** ช่อง Input สำหรับยิงบาร์โค้ด หรือพิมพ์ชื่อสินค้า (ใช้ AJAX Search)
  - **ส่วนแสดงรายการ (Cart):** ตารางแสดงสินค้าที่กำลังจะขาย, จำนวน, ราคา, และปุ่มลบรายการ
  - **ส่วนสรุปยอด:** แสดงยอดรวม (Total), ช่องรับเงิน (Cash Received), และเงินทอน (Change)
  - **ปุ่มดำเนินการ:** ปุ่ม "ชำระเงิน" (Checkout), ปุ่ม "ยกเลิก" (Cancel)
- **`public/sales_history.php`**: หน้าจอแสดงประวัติการขาย (สำหรับ Admin/Staff)
- **`templates/receipt.php`** (หรือไฟล์ PDF): รูปแบบใบเสร็จสำหรับพิมพ์

### 3.2 ระบบหลังบ้าน (Backend - API/Logic)

- **`public/api/pos_search.php`**: API สำหรับค้นหาสินค้า (รับ Keyword -> ส่งคืน JSON รายการสินค้า)
- **`public/api/pos_checkout.php`**: API สำหรับบันทึกการขาย
  - รับข้อมูล JSON (รายการสินค้า, ยอดเงิน)
  - Start Transaction (Database)
  - Insert ลงตาราง `sales`
  - Loop Insert ลงตาราง `sale_items`
  - Update ตัดสต็อกในตาราง `products`
  - Insert ลงตาราง `transactions` (Type: 'out') เพื่อเก็บ Log การเคลื่อนไหวสินค้า
  - Commit Transaction
  - ส่งคืนผลลัพธ์ (Success/Fail) และ `sale_id` เพื่อไปพิมพ์ใบเสร็จ

---

## 4. ขั้นตอนการทำงานอย่างละเอียด (Detailed Workflow)

### ขั้นตอนที่ 1: การเตรียมขาย (Pre-Sale)

1.  พนักงานเข้าสู่ระบบ และไปที่เมนู "POS" หรือ "ขายหน้าร้าน"
2.  ระบบโหลดหน้า `pos.php` ซึ่งเคอร์เซอร์จะโฟกัสที่ช่อง "ค้นหา/บาร์โค้ด" พร้อมขายทันที

### ขั้นตอนที่ 2: การเพิ่มสินค้าลงตะกร้า (Add to Cart)

1.  **กรณีใช้เครื่องยิงบาร์โค้ด:** พนักงานยิงบาร์โค้ดที่ตัวสินค้า -> ระบบค้นหา `sku` หรือ `barcode` -> ถ้าเจอสินค้า ระบบจะเพิ่มลงในตารางรายการขายด้านขวาทันที (ถ้ามีอยู่แล้ว ให้เพิ่มจำนวน +1)
2.  **กรณีค้นหาด้วยชื่อ:** พนักงานพิมพ์ชื่อสินค้า -> ระบบแสดง Dropdown รายการสินค้าที่ใกล้เคียง -> พนักงานเลือกสินค้า -> เพิ่มลงตาราง
3.  **การปรับจำนวน:** พนักงานสามารถกดปุ่ม +/- หรือพิมพ์ตัวเลขแก้จำนวนสินค้าในตารางได้
4.  **การลบรายการ:** มีปุ่มกากบาท (X) ท้ายแถวเพื่อลบสินค้าที่ไม่อาว

### ขั้นตอนที่ 3: การชำระเงิน (Checkout)

1.  ระบบคำนวณ "ยอดรวมสุทธิ" (Grand Total) ตลอดเวลาที่มีการเปลี่ยนแปลงรายการ
2.  พนักงานกดปุ่ม "ชำระเงิน" (หรือกด Hotkey เช่น F2)
3.  Modal เด้งขึ้นมาให้เลือกวิธีชำระเงิน (เงินสด/โอน) และใส่จำนวนเงินที่รับมา
4.  ระบบคำนวณ "เงินทอน" ให้เห็นทันที
5.  พนักงานกดยืนยัน "บันทึกรายการ"

### ขั้นตอนที่ 4: การบันทึกและพิมพ์ใบเสร็จ (Save & Print)

1.  Frontend ส่งข้อมูลไปที่ `api/pos_checkout.php`
2.  Backend ทำการตัดสต็อกและบันทึกข้อมูลลงฐานข้อมูล (ตามที่ออกแบบในข้อ 2)
3.  เมื่อบันทึกสำเร็จ ระบบจะส่ง `sale_id` กลับมา
4.  Frontend เปิดหน้าต่างใหม่ (Popup) เพื่อแสดงใบเสร็จ (`templates/receipt.php?id=...`) และสั่งพิมพ์อัตโนมัติ (`window.print()`)
5.  หน้าจอ POS เคลียร์รายการทั้งหมด เพื่อเตรียมพร้อมสำหรับการขายบิลถัดไป

---

## 5. ส่วนเพิ่มเติม: ระบบกะและใบเสร็จ (Shift & Receipt System)

### 5.1 ระบบกะ (Shift Management)

ระบบต้องรองรับการทำงานเป็นกะ เพื่อควบคุมเงินสดในลิ้นชัก (Cash Drawer)

- **ตาราง `shifts`**:
  - `id`, `user_id`, `start_time`, `end_time`
  - `start_cash` (เงินทอนเริ่มกะ)
  - `end_cash` (เงินสดที่นับได้ตอนปิดกะ)
  - `expected_cash` (เงินสดที่ควรจะมี = start_cash + cash_sales)
  - `diff_amount` (ส่วนต่าง ขาด/เกิน)
  - `status` (open, closed)

**Flow การทำงาน:**

1. **เปิดกะ (Open Shift):** ก่อนเข้าหน้า POS พนักงานต้องระบุ "เงินทอนเริ่มต้น"
2. **ระหว่างกะ:** ขายของตามปกติ
3. **ปิดกะ (Close Shift):** เมื่อเลิกงาน กดปิดกะ -> ระบบสรุปยอดขาย (เงินสด, QR) -> พนักงานนับเงินสดจริงใส่ระบบ -> บันทึกยอด

### 5.2 ระบบใบเสร็จ (Thermal Receipt)

- รองรับเครื่องพิมพ์ความร้อนขนาด **58mm** และ **80mm**
- ใช้ CSS `@media print` จัดรูปแบบให้พอดีกับกระดาษม้วน
- ข้อมูลในใบเสร็จ: ชื่อร้าน, เลขที่ใบเสร็จ, วันที่, รายการสินค้า, ยอดรวม, QR Code (ท้ายใบเสร็จ ถ้ามี)

### 5.3 ระบบ PromptPay QR

- ใช้ Library `promptpay-qr` ในการเจน QR Code จากยอดเงินสุทธิ
- แสดง QR Code บนหน้าจอ POS เมื่อเลือกชำระด้วย "QR PromptPay"
- ระบบต้องตรวจสอบได้ว่าจ่ายแล้ว (ในเฟสแรกอาจจะเป็นการตรวจสอบด้วยตาเปล่า หรือแนบสลิป)

---

## 6. แผนการพัฒนา (Implementation Plan)

### Phase 1: เตรียมฐานข้อมูล (Database Setup)

- [ ] สร้างตาราง `sales`, `sale_items` (ตามข้อ 2)
- [ ] สร้างตาราง `shifts` สำหรับระบบกะ

### Phase 2: พัฒนา Backend API

- [ ] สร้าง `api/pos_search.php`
- [ ] สร้าง `api/pos_checkout.php`
- [ ] สร้าง `api/shift_management.php` (Open/Close/Check)
- [ ] ติดตั้ง/เขียน Script สำหรับ `promptpay-qr`

### Phase 3: พัฒนา Frontend UI

- [ ] หน้า `pos.php` (รองรับ Responsive)
- [ ] Modal เปิด/ปิดกะ
- [ ] Modal จ่ายเงิน + แสดง PromptPay QR
- [ ] หน้า `receipt.php` (จัด CSS สำหรับ Thermal Printer)

### Phase 4: ทดสอบและปรับปรุง

- [ ] ทดสอบ Flow เปิดกะ -> ขาย -> ปิดกะ
- [ ] ทดสอบพิมพ์ใบเสร็จจริง

---

## 7. ข้อควรระวัง (Important Notes)

- **Security:** ตรวจสอบสิทธิ์ทุกครั้งที่เรียก API (Session Check)
- **Concurrency:** ล็อคสต็อกขณะตัดยอด
- **Validation:** ห้ามขายถ้าสต็อกไม่พอ, ห้ามปิดกะถ้ายอดไม่ตรง (หรือให้ใส่เหตุผล)
