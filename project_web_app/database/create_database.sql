-- ===============================================
-- DATABASE STRUCTURE FOR WEB APPLICATION
-- Total: 10 Tables with Relationships
-- ===============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS web_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_app_db;

-- ===============================================
-- 1. USERS TABLE - User Management
-- ===============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================================
-- 2. CATEGORIES TABLE - Product Categories
-- ===============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================================
-- 3. SUPPLIERS TABLE - Supplier Information
-- ===============================================
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================================
-- 4. PRODUCTS TABLE - Main Products
-- ===============================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    supplier_id INT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    sku VARCHAR(50) UNIQUE,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- ===============================================
-- 5. CUSTOMERS TABLE - Customer Data
-- ===============================================
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================================
-- 6. ORDERS TABLE - Order Transactions
-- ===============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'partial') DEFAULT 'unpaid',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- ===============================================
-- 7. ORDER_DETAILS TABLE - Order Items
-- ===============================================
CREATE TABLE order_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- ===============================================
-- 8. PAYMENTS TABLE - Payment Records
-- ===============================================
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method ENUM('cash', 'transfer', 'credit_card', 'debit_card', 'e_wallet') DEFAULT 'cash',
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    reference_number VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
);

-- ===============================================
-- 9. INVENTORY TABLE - Stock Management
-- ===============================================
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    stock_before INT NOT NULL DEFAULT 0,
    stock_after INT NOT NULL DEFAULT 0,
    reference_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
    reference_id INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    user_id INT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ===============================================
-- 10. ACTIVITY_LOGS TABLE - System Activity
-- ===============================================
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_affected VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ===============================================
-- INDEXES FOR PERFORMANCE
-- ===============================================
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_supplier ON products(supplier_id);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_date ON orders(order_date);
CREATE INDEX idx_order_details_order ON order_details(order_id);
CREATE INDEX idx_order_details_product ON order_details(product_id);
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_inventory_product ON inventory(product_id);
CREATE INDEX idx_inventory_date ON inventory(transaction_date);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_timestamp ON activity_logs(timestamp);

-- ===============================================
-- CREATE VIEWS FOR COMMON QUERIES
-- ===============================================

-- View: Product with Category and Supplier info
CREATE VIEW v_products AS
SELECT 
    p.id,
    p.name,
    p.description,
    p.price,
    p.stock,
    p.min_stock,
    p.sku,
    c.name as category_name,
    s.name as supplier_name,
    p.is_active,
    p.created_at
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN suppliers s ON p.supplier_id = s.id;

-- View: Order Summary
CREATE VIEW v_order_summary AS
SELECT 
    o.id,
    o.order_number,
    c.name as customer_name,
    u.full_name as processed_by,
    o.total_amount,
    o.status,
    o.payment_status,
    o.order_date,
    COUNT(od.id) as total_items
FROM orders o
JOIN customers c ON o.customer_id = c.id
JOIN users u ON o.user_id = u.id
LEFT JOIN order_details od ON o.id = od.order_id
GROUP BY o.id;

-- ===============================================
-- TRIGGERS FOR AUTOMATIC OPERATIONS
-- ===============================================

-- Trigger: Auto generate order number
DELIMITER //
CREATE TRIGGER tr_generate_order_number 
BEFORE INSERT ON orders 
FOR EACH ROW 
BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(LAST_INSERT_ID() + 1, 4, '0'));
    END IF;
END//
DELIMITER ;

-- Trigger: Update product stock after order detail insert
DELIMITER //
CREATE TRIGGER tr_update_stock_after_order 
AFTER INSERT ON order_details 
FOR EACH ROW 
BEGIN
    UPDATE products 
    SET stock = stock - NEW.quantity 
    WHERE id = NEW.product_id;
    
    -- Insert inventory record
    INSERT INTO inventory (product_id, transaction_type, quantity, stock_before, stock_after, reference_type, reference_id)
    SELECT 
        NEW.product_id,
        'out',
        NEW.quantity,
        stock + NEW.quantity,
        stock,
        'sale',
        NEW.order_id
    FROM products WHERE id = NEW.product_id;
END//
DELIMITER ;

-- ===============================================
-- SUCCESS MESSAGE
-- ===============================================
SELECT 'Database structure created successfully!' as status;