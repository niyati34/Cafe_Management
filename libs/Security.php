<?php
/**
 * Security Library for Food Chef Cafe Management System
 * Provides security functions for input validation, sanitization, and protection
 */

class Security {
    
    /**
     * Sanitize user input
     * @param string $input
     * @return string
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Hash password securely
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Prevent SQL injection by using prepared statements
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public static function prepareStatement($pdo, $sql, $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Validate file upload
     * @param array $file
     * @param array $allowedTypes
     * @param int $maxSize
     * @return array
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds limit';
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        return $errors;
    }
    
    /**
     * Generate secure random string
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Log security events
     * @param string $event
     * @param string $details
     */
    public static function logSecurityEvent($event, $details = '') {
        $logEntry = date('Y-m-d H:i:s') . " - $event - $details\n";
        file_put_contents('logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if request is AJAX
     * @return bool
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Rate limiting check
     * @param string $identifier
     * @param int $maxAttempts
     * @param int $timeWindow
     * @return bool
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $cacheFile = "cache/rate_limit_$identifier.txt";
        $currentTime = time();
        
        if (file_exists($cacheFile)) {
            $attempts = json_decode(file_get_contents($cacheFile), true);
            $attempts = array_filter($attempts, function($time) use ($currentTime, $timeWindow) {
                return $time > ($currentTime - $timeWindow);
            });
        } else {
            $attempts = [];
        }
        
        if (count($attempts) >= $maxAttempts) {
            return false; // Rate limit exceeded
        }
        
        $attempts[] = $currentTime;
        file_put_contents($cacheFile, json_encode($attempts));
        
        return true; // Within rate limit
    }
}
?>
