-- ===============================================
-- SAMPLE DATA FOR TESTING
-- ===============================================

USE web_app_db;

-- ===============================================
-- 1. SAMPLE USERS
-- ===============================================
INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', '081234567890'),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'John Doe', '081234567891'),
('user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Jane Smith', '081234567892');
-- Password untuk semua user: "password"

-- ===============================================
-- 2. SAMPLE CATEGORIES
-- ===============================================
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Clothing', 'Fashion and apparel items'),
('Books', 'Books and educational materials'),
('Home & Garden', 'Home improvement and garden supplies'),
('Sports', 'Sports equipment and accessories');

-- ===============================================
-- 3. SAMPLE SUPPLIERS
-- ===============================================
INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES
('PT Electronics Indonesia', 'Budi Santoso', 'budi@electronics.co.id', '021-12345678', 'Jl. Sudirman No. 123, Jakarta'),
('CV Fashion Store', 'Sari Dewi', 'sari@fashion.co.id', '021-87654321', 'Jl. Thamrin No. 456, Jakarta'),
('Toko Buku Cerdas', 'Ahmad Rahman', 'ahmad@bukucerdas.co.id', '021-11223344', 'Jl. Kebon Jeruk No. 789, Jakarta'),
('Home Center', 'Linda Kusuma', 'linda@homecenter.co.id', '021-55667788', 'Jl. Kemang No. 321, Jakarta'),
('Sports World', 'Doni Pratama', 'doni@sportsworld.co.id', '021-99887766', 'Jl. Senayan No. 654, Jakarta');

-- ===============================================
-- 4. SAMPLE PRODUCTS
-- ===============================================
INSERT INTO products (category_id, supplier_id, name, description, price, stock, min_stock, sku) VALUES
-- Electronics
(1, 1, 'Smartphone Android', 'Latest Android smartphone with 128GB storage', 3500000.00, 25, 5, 'ELC-001'),
(1, 1, 'Laptop Gaming', 'High performance gaming laptop', 15000000.00, 10, 2, 'ELC-002'),
(1, 1, 'Wireless Headphones', 'Bluetooth wireless headphones', 500000.00, 50, 10, 'ELC-003'),

-- Clothing
(2, 2, 'T-Shirt Cotton', 'Premium cotton t-shirt', 150000.00, 100, 20, 'CLT-001'),
(2, 2, 'Jeans Denim', 'Classic blue denim jeans', 350000.00, 75, 15, 'CLT-002'),
(2, 2, 'Running Shoes', 'Comfortable running shoes', 750000.00, 40, 8, 'CLT-003'),

-- Books
(3, 3, 'Programming Guide', 'Complete programming tutorial book', 125000.00, 30, 5, 'BOK-001'),
(3, 3, 'Novel Fiction', 'Bestseller fiction novel', 85000.00, 60, 10, 'BOK-002'),
(3, 3, 'Business Strategy', 'Business management and strategy', 200000.00, 20, 5, 'BOK-003'),

-- Home & Garden
(4, 4, 'Table Lamp', 'Modern LED table lamp', 250000.00, 35, 7, 'HOM-001'),
(4, 4, 'Garden Tools Set', 'Complete gardening tools set', 450000.00, 15, 3, 'HOM-002'),

-- Sports
(5, 5, 'Basketball', 'Official size basketball', 175000.00, 45, 10, 'SPT-001'),
(5, 5, 'Yoga Mat', 'Premium yoga exercise mat', 125000.00, 55, 12, 'SPT-002');

-- ===============================================
-- 5. SAMPLE CUSTOMERS
-- ===============================================
INSERT INTO customers (name, email, phone, address, city, postal_code) VALUES
('Agus Setiawan', 'agus@gmail.com', '081111111111', 'Jl. Merdeka No. 10', 'Jakarta', '10110'),
('Dewi Sartika', 'dewi@gmail.com', '081222222222', 'Jl. Pancasila No. 25', 'Bandung', '40111'),
('Bambang Wijaya', 'bambang@gmail.com', '081333333333', 'Jl. Diponegoro No. 15', 'Surabaya', '60111'),
('Siti Nurhaliza', 'siti@gmail.com', '081444444444', 'Jl. Sudirman No. 88', 'Medan', '20111'),
('Rudi Hartono', 'rudi@gmail.com', '081555555555', 'Jl. Gatot Subroto No. 77', 'Yogyakarta', '55111');

-- ===============================================
-- 6. SAMPLE ORDERS
-- ===============================================
INSERT INTO orders (customer_id, user_id, order_number, total_amount, status, payment_status, notes) VALUES
(1, 2, 'ORD-20240101-0001', 4000000.00, 'completed', 'paid', 'First order from regular customer'),
(2, 2, 'ORD-20240102-0002', 275000.00, 'processing', 'paid', 'Rush order - express delivery'),
(3, 3, 'ORD-20240103-0003', 1250000.00, 'pending', 'unpaid', 'Waiting for payment confirmation'),
(4, 2, 'ORD-20240104-0004', 650000.00, 'completed', 'paid', 'Bulk order discount applied'),
(1, 3, 'ORD-20240105-0005', 835000.00, 'cancelled', 'unpaid', 'Customer requested cancellation');

-- ===============================================
-- 7. SAMPLE ORDER DETAILS
-- ===============================================
INSERT INTO order_details (order_id, product_id, quantity, price, subtotal) VALUES
-- Order 1: Smartphone + Headphones
(1, 1, 1, 3500000.00, 3500000.00),
(1, 3, 1, 500000.00, 500000.00),

-- Order 2: T-Shirt + Novel
(2, 4, 1, 150000.00, 150000.00),
(2, 8, 1, 85000.00, 85000.00),
(2, 11, 1, 175000.00, 175000.00),

-- Order 3: Laptop
(3, 2, 1, 15000000.00, 15000000.00),

-- Order 4: Running Shoes + Yoga Mat
(4, 6, 1, 750000.00, 750000.00),
(4, 12, 1, 125000.00, 125000.00),

-- Order 5: Jeans + Programming Book (Cancelled)
(5, 5, 1, 350000.00, 350000.00),
(5, 7, 1, 125000.00, 125000.00);

-- Note: Stock akan otomatis berkurang karena trigger

-- ===============================================
-- 8. SAMPLE PAYMENTS
-- ===============================================
INSERT INTO payments (order_id, payment_method, amount, status, reference_number, notes) VALUES
(1, 'transfer', 4000000.00, 'completed', 'TRF-20240101-001', 'Bank transfer via BCA'),
(2, 'cash', 460000.00, 'completed', 'CSH-20240102-001', 'Cash payment on delivery'),
(4, 'e_wallet', 875000.00, 'completed', 'EWL-20240104-001', 'Payment via OVO');

-- ===============================================
-- 9. SAMPLE INVENTORY RECORDS (Manual Adjustments)
-- ===============================================
INSERT INTO inventory (product_id, transaction_type, quantity, stock_before, stock_after, reference_type, notes, user_id) VALUES
(1, 'adjustment', 5, 20, 25, 'adjustment', 'Stock correction after physical count', 1),
(4, 'adjustment', -2, 102, 100, 'adjustment', 'Damaged items removed', 1),
(7, 'in', 10, 20, 30, 'purchase', 'New stock from supplier', 1);

-- ===============================================
-- 10. SAMPLE ACTIVITY LOGS
-- ===============================================
INSERT INTO activity_logs (user_id, action, table_affected, record_id, description, ip_address) VALUES
(1, 'LOGIN', 'users', 1, 'Admin logged in to system', '192.168.1.1'),
(2, 'CREATE', 'orders', 1, 'Created new order ORD-20240101-0001', '192.168.1.2'),
(2, 'UPDATE', 'orders', 1, 'Updated order status to completed', '192.168.1.2'),
(1, 'CREATE', 'products', 13, 'Added new product to inventory', '192.168.1.1'),
(3, 'LOGIN', 'users', 3, 'User logged in to system', '192.168.1.3'),
(1, 'UPDATE', 'inventory', 1, 'Stock adjustment performed', '192.168.1.1'),
(2, 'DELETE', 'orders', 5, 'Cancelled order ORD-20240105-0005', '192.168.1.2');

-- ===============================================
-- VERIFICATION QUERIES
-- ===============================================

-- Check total records in each table
SELECT 'USERS' as table_name, COUNT(*) as total_records FROM users
UNION ALL
SELECT 'CATEGORIES', COUNT(*) FROM categories
UNION ALL
SELECT 'SUPPLIERS', COUNT(*) FROM suppliers
UNION ALL
SELECT 'PRODUCTS', COUNT(*) FROM products
UNION ALL
SELECT 'CUSTOMERS', COUNT(*) FROM customers
UNION ALL
SELECT 'ORDERS', COUNT(*) FROM orders
UNION ALL
SELECT 'ORDER_DETAILS', COUNT(*) FROM order_details
UNION ALL
SELECT 'PAYMENTS', COUNT(*) FROM payments
UNION ALL
SELECT 'INVENTORY', COUNT(*) FROM inventory
UNION ALL
SELECT 'ACTIVITY_LOGS', COUNT(*) FROM activity_logs;

-- Test the views
SELECT 'SAMPLE PRODUCTS VIEW:' as info;
SELECT * FROM v_products LIMIT 5;

SELECT 'SAMPLE ORDER SUMMARY VIEW:' as info;
SELECT * FROM v_order_summary LIMIT 3;

SELECT 'Sample data inserted successfully!' as status;