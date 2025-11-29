-- Add supplier_id column to products table
-- This migration adds support for tracking which supplier each product comes from

-- Step 1: Add supplier_id column (nullable at first)
ALTER TABLE `products` 
ADD COLUMN `supplier_id` INT(11) NULL AFTER `category_id`;

-- Step 2: Set default supplier for existing products
-- Assign all existing products to supplier ID 1 (บริษัท ไทยเทรดดิ้ง จำกัด)
-- You can change this to match your business needs
UPDATE `products` 
SET `supplier_id` = 1 
WHERE `supplier_id` IS NULL;

-- Step 3: Add foreign key constraint
ALTER TABLE `products`
ADD CONSTRAINT `fk_product_supplier` 
FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Step 4: Add index for better query performance
ALTER TABLE `products`
ADD INDEX `idx_supplier_id` (`supplier_id`);

-- Verification: Check the updated table structure
-- DESCRIBE `products`;

-- Optional: View products with their suppliers
-- SELECT p.*, s.name as supplier_name 
-- FROM products p 
-- LEFT JOIN suppliers s ON p.supplier_id = s.id 
-- LIMIT 5;
