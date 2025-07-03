-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 20, 2025 at 12:12 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_app_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_affected`, `record_id`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1, 1, 'LOGIN', 'users', 1, 'Admin logged in to system', '192.168.1.1', NULL, '2025-06-20 08:33:54'),
(2, 2, 'CREATE', 'orders', 1, 'Created new order ORD-20240101-0001', '192.168.1.2', NULL, '2025-06-20 08:33:54'),
(3, 2, 'UPDATE', 'orders', 1, 'Updated order status to completed', '192.168.1.2', NULL, '2025-06-20 08:33:54'),
(4, 1, 'CREATE', 'products', 13, 'Added new product to inventory', '192.168.1.1', NULL, '2025-06-20 08:33:54'),
(5, 3, 'LOGIN', 'users', 3, 'User logged in to system', '192.168.1.3', NULL, '2025-06-20 08:33:54'),
(6, 1, 'UPDATE', 'inventory', 1, 'Stock adjustment performed', '192.168.1.1', NULL, '2025-06-20 08:33:54'),
(7, 2, 'DELETE', 'orders', 5, 'Cancelled order ORD-20240105-0005', '192.168.1.2', NULL, '2025-06-20 08:33:54'),
(8, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 09:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Electronics', 'Electronic devices and accessories', 1, '2025-06-20 08:33:54'),
(2, 'Clothing', 'Fashion and apparel items', 1, '2025-06-20 08:33:54'),
(3, 'Books', 'Books and educational materials', 1, '2025-06-20 08:33:54'),
(4, 'Home & Garden', 'Home improvement and garden supplies', 1, '2025-06-20 08:33:54'),
(5, 'Sports', 'Sports equipment and accessories', 1, '2025-06-20 08:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `city`, `postal_code`, `is_active`, `created_at`) VALUES
(1, 'Agus Setiawan', 'agus@gmail.com', '081111111111', 'Jl. Merdeka No. 10', 'Jakarta', '10110', 1, '2025-06-20 08:33:54'),
(2, 'Dewi Sartika', 'dewi@gmail.com', '081222222222', 'Jl. Pancasila No. 25', 'Bandung', '40111', 1, '2025-06-20 08:33:54'),
(3, 'Bambang Wijaya', 'bambang@gmail.com', '081333333333', 'Jl. Diponegoro No. 15', 'Surabaya', '60111', 1, '2025-06-20 08:33:54'),
(4, 'Siti Nurhaliza', 'siti@gmail.com', '081444444444', 'Jl. Sudirman No. 88', 'Medan', '20111', 1, '2025-06-20 08:33:54'),
(5, 'Rudi Hartono', 'rudi@gmail.com', '081555555555', 'Jl. Gatot Subroto No. 77', 'Yogyakarta', '55111', 1, '2025-06-20 08:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL DEFAULT 0,
  `stock_after` int(11) NOT NULL DEFAULT 0,
  `reference_type` enum('purchase','sale','adjustment','return') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_id`, `transaction_type`, `quantity`, `stock_before`, `stock_after`, `reference_type`, `reference_id`, `transaction_date`, `notes`, `user_id`) VALUES
(1, 1, 'out', 1, 25, 24, 'sale', 1, '2025-06-20 08:33:54', NULL, NULL),
(2, 3, 'out', 1, 50, 49, 'sale', 1, '2025-06-20 08:33:54', NULL, NULL),
(3, 4, 'out', 1, 100, 99, 'sale', 2, '2025-06-20 08:33:54', NULL, NULL),
(4, 8, 'out', 1, 60, 59, 'sale', 2, '2025-06-20 08:33:54', NULL, NULL),
(5, 11, 'out', 1, 15, 14, 'sale', 2, '2025-06-20 08:33:54', NULL, NULL),
(6, 2, 'out', 1, 10, 9, 'sale', 3, '2025-06-20 08:33:54', NULL, NULL),
(7, 6, 'out', 1, 40, 39, 'sale', 4, '2025-06-20 08:33:54', NULL, NULL),
(8, 12, 'out', 1, 45, 44, 'sale', 4, '2025-06-20 08:33:54', NULL, NULL),
(9, 5, 'out', 1, 75, 74, 'sale', 5, '2025-06-20 08:33:54', NULL, NULL),
(10, 7, 'out', 1, 30, 29, 'sale', 5, '2025-06-20 08:33:54', NULL, NULL),
(11, 1, 'adjustment', 5, 20, 25, 'adjustment', NULL, '2025-06-20 08:33:54', 'Stock correction after physical count', 1),
(12, 4, 'adjustment', -2, 102, 100, 'adjustment', NULL, '2025-06-20 08:33:54', 'Damaged items removed', 1),
(13, 7, 'in', 10, 20, 30, 'purchase', NULL, '2025-06-20 08:33:54', 'New stock from supplier', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','partial') DEFAULT 'unpaid',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `user_id`, `order_number`, `total_amount`, `status`, `payment_status`, `order_date`, `notes`) VALUES
(1, 1, 2, 'ORD-20240101-0001', 4000000.00, 'completed', 'paid', '2025-06-20 08:33:54', 'First order from regular customer'),
(2, 2, 2, 'ORD-20240102-0002', 275000.00, 'processing', 'paid', '2025-06-20 08:33:54', 'Rush order - express delivery'),
(3, 3, 3, 'ORD-20240103-0003', 1250000.00, 'pending', 'unpaid', '2025-06-20 08:33:54', 'Waiting for payment confirmation'),
(4, 4, 2, 'ORD-20240104-0004', 650000.00, 'completed', 'paid', '2025-06-20 08:33:54', 'Bulk order discount applied'),
(5, 1, 3, 'ORD-20240105-0005', 835000.00, 'cancelled', 'unpaid', '2025-06-20 08:33:54', 'Customer requested cancellation');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `tr_generate_order_number` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(LAST_INSERT_ID() + 1, 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 1, 1, 3500000.00, 3500000.00),
(2, 1, 3, 1, 500000.00, 500000.00),
(3, 2, 4, 1, 150000.00, 150000.00),
(4, 2, 8, 1, 85000.00, 85000.00),
(5, 2, 11, 1, 175000.00, 175000.00),
(6, 3, 2, 1, 15000000.00, 15000000.00),
(7, 4, 6, 1, 750000.00, 750000.00),
(8, 4, 12, 1, 125000.00, 125000.00),
(9, 5, 5, 1, 350000.00, 350000.00),
(10, 5, 7, 1, 125000.00, 125000.00);

--
-- Triggers `order_details`
--
DELIMITER $$
CREATE TRIGGER `tr_update_stock_after_order` AFTER INSERT ON `order_details` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','transfer','credit_card','debit_card','e_wallet') DEFAULT 'cash',
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `payment_date`, `status`, `reference_number`, `notes`) VALUES
(1, 1, 'transfer', 4000000.00, '2025-06-20 08:33:54', 'completed', 'TRF-20240101-001', 'Bank transfer via BCA'),
(2, 2, 'cash', 460000.00, '2025-06-20 08:33:54', 'completed', 'CSH-20240102-001', 'Cash payment on delivery'),
(3, 4, 'e_wallet', 875000.00, '2025-06-20 08:33:54', 'completed', 'EWL-20240104-001', 'Payment via OVO');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 0,
  `sku` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `supplier_id`, `name`, `description`, `price`, `stock`, `min_stock`, `sku`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Smartphone Android', 'Latest Android smartphone with 128GB storage', 3500000.00, 24, 5, 'ELC-001', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(2, 1, 1, 'Laptop Gaming', 'High performance gaming laptop', 15000000.00, 9, 2, 'ELC-002', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(3, 1, 1, 'Wireless Headphones', 'Bluetooth wireless headphones', 500000.00, 49, 10, 'ELC-003', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(4, 2, 2, 'T-Shirt Cotton', 'Premium cotton t-shirt', 150000.00, 99, 20, 'CLT-001', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(5, 2, 2, 'Jeans Denim', 'Classic blue denim jeans', 350000.00, 74, 15, 'CLT-002', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(6, 2, 2, 'Running Shoes', 'Comfortable running shoes', 750000.00, 39, 8, 'CLT-003', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(7, 3, 3, 'Programming Guide', 'Complete programming tutorial book', 125000.00, 29, 5, 'BOK-001', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(8, 3, 3, 'Novel Fiction', 'Bestseller fiction novel', 85000.00, 59, 10, 'BOK-002', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(9, 3, 3, 'Business Strategy', 'Business management and strategy', 200000.00, 20, 5, 'BOK-003', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(10, 4, 4, 'Table Lamp', 'Modern LED table lamp', 250000.00, 35, 7, 'HOM-001', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(11, 4, 4, 'Garden Tools Set', 'Complete gardening tools set', 450000.00, 14, 3, 'HOM-002', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(12, 5, 5, 'Basketball', 'Official size basketball', 175000.00, 44, 10, 'SPT-001', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(13, 5, 5, 'Yoga Mat', 'Premium yoga exercise mat', 125000.00, 55, 12, 'SPT-002', NULL, 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`) VALUES
(1, 'PT Electronics Indonesia', 'Budi Santoso', 'budi@electronics.co.id', '021-12345678', 'Jl. Sudirman No. 123, Jakarta', 1, '2025-06-20 08:33:54'),
(2, 'CV Fashion Store', 'Sari Dewi', 'sari@fashion.co.id', '021-87654321', 'Jl. Thamrin No. 456, Jakarta', 1, '2025-06-20 08:33:54'),
(3, 'Toko Buku Cerdas', 'Ahmad Rahman', 'ahmad@bukucerdas.co.id', '021-11223344', 'Jl. Kebon Jeruk No. 789, Jakarta', 1, '2025-06-20 08:33:54'),
(4, 'Home Center', 'Linda Kusuma', 'linda@homecenter.co.id', '021-55667788', 'Jl. Kemang No. 321, Jakarta', 1, '2025-06-20 08:33:54'),
(5, 'Sports World', 'Doni Pratama', 'doni@sportsworld.co.id', '021-99887766', 'Jl. Senayan No. 654, Jakarta', 1, '2025-06-20 08:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', '081234567890', 1, '2025-06-20 08:33:54', '2025-06-20 09:22:17'),
(2, 'user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'John Doe', '081234567891', 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54'),
(3, 'user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Jane Smith', '081234567892', 1, '2025-06-20 08:33:54', '2025-06-20 08:33:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_order_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_order_summary` (
`id` int(11)
,`order_number` varchar(50)
,`customer_name` varchar(100)
,`processed_by` varchar(100)
,`total_amount` decimal(10,2)
,`status` enum('pending','processing','completed','cancelled')
,`payment_status` enum('unpaid','paid','partial')
,`order_date` timestamp
,`total_items` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_products`
-- (See below for the actual view)
--
CREATE TABLE `v_products` (
`id` int(11)
,`name` varchar(200)
,`description` text
,`price` decimal(10,2)
,`stock` int(11)
,`min_stock` int(11)
,`sku` varchar(50)
,`category_name` varchar(100)
,`supplier_name` varchar(100)
,`is_active` tinyint(1)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_order_summary`
--
DROP TABLE IF EXISTS `v_order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`order_number` AS `order_number`, `c`.`name` AS `customer_name`, `u`.`full_name` AS `processed_by`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`payment_status` AS `payment_status`, `o`.`order_date` AS `order_date`, count(`od`.`id`) AS `total_items` FROM (((`orders` `o` join `customers` `c` on(`o`.`customer_id` = `c`.`id`)) join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `order_details` `od` on(`o`.`id` = `od`.`order_id`)) GROUP BY `o`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_products`
--
DROP TABLE IF EXISTS `v_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`description` AS `description`, `p`.`price` AS `price`, `p`.`stock` AS `stock`, `p`.`min_stock` AS `min_stock`, `p`.`sku` AS `sku`, `c`.`name` AS `category_name`, `s`.`name` AS `supplier_name`, `p`.`is_active` AS `is_active`, `p`.`created_at` AS `created_at` FROM ((`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) left join `suppliers` `s` on(`p`.`supplier_id` = `s`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_timestamp` (`timestamp`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_inventory_product` (`product_id`),
  ADD KEY `idx_inventory_date` (`transaction_date`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_date` (`order_date`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_details_order` (`order_id`),
  ADD KEY `idx_order_details_product` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_supplier` (`supplier_id`),
  ADD KEY `idx_products_sku` (`sku`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
