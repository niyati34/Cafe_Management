<?php 
// Database Configuration
define('BASEURL','http://localhost/project/');
define('HOSTNAME','localhost');
define('USERNAME','root');
define('PASSWORD','');
define('DB','project');

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, staging, production
define('DEBUG_MODE', true);
define('TIMEZONE', 'UTC');

// Security Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
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
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR, CRITICAL
define('LOG_RETENTION_DAYS', 30);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// API Configuration
define('API_RATE_LIMIT', 100); // requests per hour
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
        $pdo = new PDO(
            "mysql:host=" . HOSTNAME . ";dbname=" . DB . ";charset=utf8mb4",
            USERNAME,
            PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Connection failed: " . $e->getMessage());
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
}
?>
