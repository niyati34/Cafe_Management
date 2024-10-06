-- Migration: Create orders system tables
-- Date: 2024-10-06
-- Description: Create orders and order_items tables for food ordering system

-- Create orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in',
  `delivery_address` text,
  `special_instructions` text,
  `status` enum('pending','confirmed','preparing','ready','delivered','cancelled','completed') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_order_type` (`order_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `special_requests` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_food_id` (`food_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_food` FOREIGN KEY (`food_id`) REFERENCES `food`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table for customer accounts
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `role`) VALUES
('admin', 'admin@foodchef.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Create customer addresses table
CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_address_type` (`address_type`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payment transactions table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `transaction_id` varchar(100) UNIQUE,
  `payment_method` enum('cash','credit_card','debit_card','online','mobile') DEFAULT 'cash',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `gateway_response` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transaction_id` (`transaction_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create delivery zones table
CREATE TABLE IF NOT EXISTS `delivery_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(100) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_order` decimal(10,2) DEFAULT 0.00,
  `estimated_time` int(11) DEFAULT 30 COMMENT 'Estimated delivery time in minutes',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default delivery zones
INSERT INTO `delivery_zones` (`zone_name`, `delivery_fee`, `minimum_order`, `estimated_time`) VALUES
('Downtown', 2.99, 15.00, 25),
('Midtown', 3.99, 20.00, 35),
('Suburbs', 4.99, 25.00, 45),
('Airport Area', 5.99, 30.00, 60);

-- Create order notifications table
CREATE TABLE IF NOT EXISTS `order_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `notification_type` enum('email','sms','push') DEFAULT 'email',
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`notification_type`),
  CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX `idx_orders_customer_status` ON `orders` (`customer_email`, `status`);
CREATE INDEX `idx_orders_date_status` ON `orders` (`created_at`, `status`);
CREATE INDEX `idx_order_items_price` ON `order_items` (`unit_price`, `total_price`);
CREATE INDEX `idx_users_email_role` ON `users` (`email`, `role`);

-- Add table comments for documentation
ALTER TABLE `orders` 
COMMENT = 'Customer food orders with delivery and status tracking';

ALTER TABLE `order_items` 
COMMENT = 'Individual food items in each order';

ALTER TABLE `users` 
COMMENT = 'Customer and staff user accounts';

ALTER TABLE `customer_addresses` 
COMMENT = 'Customer delivery addresses';

ALTER TABLE `payment_transactions` 
COMMENT = 'Payment processing and transaction history';

ALTER TABLE `delivery_zones` 
COMMENT = 'Delivery zones with pricing and timing';

ALTER TABLE `order_notifications` 
COMMENT = 'Order status notification tracking';

-- Create view for order summary
CREATE OR REPLACE VIEW `order_summary` AS
SELECT 
    o.id,
    o.customer_name,
    o.customer_email,
    o.total_amount,
    o.order_type,
    o.status,
    o.created_at,
    COUNT(oi.id) as items_count,
    GROUP_CONCAT(CONCAT(oi.food_name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id
ORDER BY o.created_at DESC;
