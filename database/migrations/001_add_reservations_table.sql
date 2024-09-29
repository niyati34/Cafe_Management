-- Migration: Add reservations table
-- Date: 2024-09-29
-- Description: Create reservations table for booking management

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `guests` int(11) DEFAULT 1,
  `message` text,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_date` (`reservation_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add some sample data
INSERT INTO `reservations` (`name`, `email`, `phone`, `reservation_date`, `reservation_time`, `guests`, `message`, `status`) VALUES
('John Doe', 'john@example.com', '+1234567890', '2024-10-15', '19:00:00', 2, 'Window seat preferred', 'confirmed'),
('Jane Smith', 'jane@example.com', '+1234567891', '2024-10-16', '20:00:00', 4, 'Birthday celebration', 'pending'),
('Mike Johnson', 'mike@example.com', '+1234567892', '2024-10-17', '18:30:00', 2, NULL, 'confirmed');
