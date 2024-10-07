<?php
/**
 * Database Setup Script for Food Chef Cafe Management System
 * This script will create the database and all necessary tables
 */

// Database connection parameters (without database name)
$host = 'localhost';
$username = 'root';
$password = '';

echo "<h2>Food Chef Cafe - Database Setup</h2>";

try {
    // Connect to MySQL server (without selecting a database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connected to MySQL server successfully</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `foodchef` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ Database 'foodchef' created/verified successfully</p>";
    
    // Select the database
    $pdo->exec("USE `foodchef`");
    echo "<p style='color: green;'>✓ Database 'foodchef' selected successfully</p>";
    
    // Create tables
    $tables = [
        // Users table
        "CREATE TABLE IF NOT EXISTS `users` (
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
            UNIQUE KEY `uk_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Menu categories table
        "CREATE TABLE IF NOT EXISTS `menu_categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `image` varchar(255) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Food table
        "CREATE TABLE IF NOT EXISTS `food` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_id` int(11) DEFAULT NULL,
            `name` varchar(255) NOT NULL,
            `description` text,
            `price` decimal(10,2) NOT NULL DEFAULT 0.00,
            `image` varchar(255) DEFAULT NULL,
            `is_featured` tinyint(1) DEFAULT 0,
            `is_vegetarian` tinyint(1) DEFAULT 0,
            `is_spicy` tinyint(1) DEFAULT 0,
            `preparation_time` int(11) DEFAULT 15,
            `calories` int(11) DEFAULT NULL,
            `allergens` text,
            `is_active` tinyint(1) DEFAULT 1,
            `avg_rating` decimal(3,2) DEFAULT NULL,
            `total_reviews` int(11) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_category` (`category_id`),
            KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Reservations table
        "CREATE TABLE IF NOT EXISTS `reservations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `reservation_date` date NOT NULL,
            `reservation_time` time NOT NULL,
            `guests` int(11) DEFAULT 1,
            `message` text,
            `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
            `reminder_sent` tinyint(1) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_email` (`email`),
            KEY `idx_date` (`reservation_date`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Orders table
        "CREATE TABLE IF NOT EXISTS `orders` (
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
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Order items table
        "CREATE TABLE IF NOT EXISTS `order_items` (
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
            KEY `idx_food_id` (`food_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Food reviews table
        "CREATE TABLE IF NOT EXISTS `food_reviews` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `food_id` int(11) NOT NULL,
            `customer_name` varchar(255) NOT NULL,
            `customer_email` varchar(255) NOT NULL,
            `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
            `review` text,
            `is_approved` tinyint(1) DEFAULT 0,
            `admin_notes` text,
            `moderated_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_food_id` (`food_id`),
            KEY `idx_rating` (`rating`),
            KEY `idx_approved` (`is_approved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Customer feedback table
        "CREATE TABLE IF NOT EXISTS `customer_feedback` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_name` varchar(255) NOT NULL,
            `customer_email` varchar(255) NOT NULL,
            `customer_phone` varchar(20) DEFAULT NULL,
            `rating` tinyint(1) NOT NULL,
            `feedback_type` enum('general','food_quality','service','ambiance','delivery','reservation') NOT NULL,
            `subject` varchar(255) DEFAULT NULL,
            `message` text,
            `order_id` int(11) DEFAULT NULL,
            `reservation_id` int(11) DEFAULT NULL,
            `is_public` tinyint(1) DEFAULT 1,
            `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_customer_email` (`customer_email`),
            KEY `idx_feedback_type` (`feedback_type`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    // Execute table creation
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    echo "<p style='color: green;'>✓ All tables created successfully</p>";
    
    // Insert default data
    $defaultData = [
        // Insert default admin user (password: admin123)
        "INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `role`) VALUES 
        ('admin', 'admin@foodchef.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin')",
        
        // Insert default menu categories
        "INSERT IGNORE INTO `menu_categories` (`name`, `description`, `sort_order`) VALUES
        ('Appetizers', 'Start your meal with our delicious appetizers', 1),
        ('Main Course', 'Signature dishes prepared with fresh ingredients', 2),
        ('Desserts', 'Sweet endings to your dining experience', 3),
        ('Beverages', 'Refreshing drinks and hot beverages', 4),
        ('Salads', 'Fresh and healthy salad options', 5),
        ('Soups', 'Warm and comforting soup selections', 6)",
        
        // Insert sample food items
        "INSERT IGNORE INTO `food` (`category_id`, `name`, `description`, `price`, `is_featured`, `is_vegetarian`, `preparation_time`) VALUES
        (2, 'Grilled Chicken Breast', 'Juicy grilled chicken breast with herbs and spices', 18.99, 1, 0, 20),
        (2, 'Beef Burger', 'Classic beef burger with fresh vegetables', 15.99, 1, 0, 15),
        (1, 'Bruschetta', 'Toasted bread topped with tomatoes and herbs', 8.99, 0, 1, 10),
        (3, 'Chocolate Cake', 'Rich chocolate cake with vanilla ice cream', 12.99, 0, 1, 5),
        (4, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 4.99, 0, 1, 2)"
    ];
    
    foreach ($defaultData as $sql) {
        $pdo->exec($sql);
    }
    echo "<p style='color: green;'>✓ Default data inserted successfully</p>";
    
    // Update configuration file
    $configContent = "<?php 
// Database Configuration
define('BASEURL','http://localhost/hotel/');
define('HOSTNAME','localhost');
define('USERNAME','root');
define('PASSWORD','');
define('DB','foodchef');

// Environment Configuration
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', true);
define('TIMEZONE', 'UTC');

// Security Configuration
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', 'uploads/');

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@foodchef.com');
define('FROM_NAME', 'Food Chef Cafe');

// Logging Configuration
define('LOG_LEVEL', 'INFO');
define('LOG_RETENTION_DAYS', 30);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600);

// API Configuration
define('API_RATE_LIMIT', 100);
define('API_KEY_REQUIRED', true);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database connection function
function getDbConnection() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . HOSTNAME . \";dbname=\" . DB . \";charset=utf8mb4\",
            USERNAME,
            PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
            ]
        );
        return \$pdo;
    } catch (PDOException \$e) {
        if (DEBUG_MODE) {
            die(\"Connection failed: \" . \$e->getMessage());
        } else {
            die(\"Database connection failed. Please try again later.\");
        }
    }
}
?>";
    
    file_put_contents('config/config.php', $configContent);
    echo "<p style='color: green;'>✓ Configuration file updated successfully</p>";
    
    echo "<h3 style='color: green;'>🎉 Database setup completed successfully!</h3>";
    echo "<p><strong>Database Name:</strong> foodchef</p>";
    echo "<p><strong>Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Click here to go to the main page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Username and password are correct</li>";
    echo "<li>You have permission to create databases</li>";
    echo "</ul>";
}
?>
