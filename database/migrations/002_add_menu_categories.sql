-- Migration: Add menu categories and enhance food management
-- Date: 2024-10-04
-- Description: Create menu categories table and enhance food items structure

-- Create menu categories table
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default menu categories
INSERT INTO `menu_categories` (`name`, `description`, `sort_order`) VALUES
('Appetizers', 'Start your meal with our delicious appetizers', 1),
('Main Course', 'Signature dishes prepared with fresh ingredients', 2),
('Desserts', 'Sweet endings to your dining experience', 3),
('Beverages', 'Refreshing drinks and hot beverages', 4),
('Salads', 'Fresh and healthy salad options', 5),
('Soups', 'Warm and comforting soup selections', 6);

-- Add category_id to food table
ALTER TABLE `food` 
ADD COLUMN `category_id` int(11) DEFAULT NULL AFTER `id`,
ADD COLUMN `is_featured` tinyint(1) DEFAULT 0 AFTER `price`,
ADD COLUMN `is_vegetarian` tinyint(1) DEFAULT 0 AFTER `is_featured`,
ADD COLUMN `is_spicy` tinyint(1) DEFAULT 0 AFTER `is_vegetarian`,
ADD COLUMN `preparation_time` int(11) DEFAULT 15 AFTER `is_spicy`,
ADD COLUMN `calories` int(11) DEFAULT NULL AFTER `preparation_time`,
ADD COLUMN `allergens` text AFTER `calories`,
ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `allergens`,
ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add foreign key constraint
ALTER TABLE `food` 
ADD CONSTRAINT `fk_food_category` 
FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Update existing food items with default category (Main Course)
UPDATE `food` SET `category_id` = 2 WHERE `category_id` IS NULL;

-- Create food ratings table
CREATE TABLE IF NOT EXISTS `food_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review` text,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_food_id` (`food_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_approved` (`is_approved`),
  CONSTRAINT `fk_rating_food` FOREIGN KEY (`food_id`) REFERENCES `food`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create food ingredients table
CREATE TABLE IF NOT EXISTS `food_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_id` int(11) NOT NULL,
  `ingredient_name` varchar(100) NOT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `is_optional` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_food_id` (`food_id`),
  CONSTRAINT `fk_ingredient_food` FOREIGN KEY (`food_id`) REFERENCES `food`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add some sample ingredients for existing food items
INSERT INTO `food_ingredients` (`food_id`, `ingredient_name`, `quantity`, `unit`) VALUES
(1, 'Chicken Breast', '200', 'g'),
(1, 'Olive Oil', '2', 'tbsp'),
(1, 'Garlic', '3', 'cloves'),
(1, 'Herbs', '1', 'tsp'),
(2, 'Beef Mince', '250', 'g'),
(2, 'Onion', '1', 'medium'),
(2, 'Tomato Sauce', '100', 'ml'),
(2, 'Cheese', '50', 'g');

-- Create food availability table for daily specials
CREATE TABLE IF NOT EXISTS `food_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL CHECK (day_of_week >= 1 AND day_of_week <= 7),
  `is_available` tinyint(1) DEFAULT 1,
  `special_price` decimal(10,2) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_food_day` (`food_id`, `day_of_week`),
  KEY `idx_day_available` (`day_of_week`, `is_available`),
  CONSTRAINT `fk_availability_food` FOREIGN KEY (`food_id`) REFERENCES `food`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default availability for all food items (available all week)
INSERT INTO `food_availability` (`food_id`, `day_of_week`, `is_available`)
SELECT f.id, d.day_num, 1
FROM food f
CROSS JOIN (
    SELECT 1 as day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
) d;

-- Add indexes for better performance
CREATE INDEX `idx_food_category_active` ON `food` (`category_id`, `is_active`);
CREATE INDEX `idx_food_featured` ON `food` (`is_featured`);
CREATE INDEX `idx_food_vegetarian` ON `food` (`is_vegetarian`);
CREATE INDEX `idx_food_price` ON `food` (`price`);

-- Update food table structure for better organization
ALTER TABLE `food` 
MODIFY COLUMN `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY COLUMN `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
MODIFY COLUMN `price` decimal(10,2) NOT NULL DEFAULT 0.00;

-- Add comments for documentation
ALTER TABLE `food` 
COMMENT = 'Enhanced food items with categories and dietary information';

ALTER TABLE `menu_categories` 
COMMENT = 'Menu categories for organizing food items';

ALTER TABLE `food_ratings` 
COMMENT = 'Customer ratings and reviews for food items';

ALTER TABLE `food_ingredients` 
COMMENT = 'Ingredients list for each food item';

ALTER TABLE `food_availability` 
COMMENT = 'Daily availability and special pricing for food items';
