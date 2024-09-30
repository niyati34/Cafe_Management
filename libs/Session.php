<?php
/**
 * Session Management Library for Food Chef Cafe Management System
 * Handles user sessions, authentication, and security
 */

class Session {
    
    /**
     * Initialize session with security settings
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1);
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Set session variable
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     * @param string $key
     */
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destroy entire session
     */
    public static function destroy() {
        session_destroy();
        session_unset();
    }
    
    /**
     * Set flash message
     * @param string $key
     * @param string $message
     */
    public static function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }
    
    /**
     * Get flash message
     * @param string $key
     * @return string|null
     */
    public static function getFlash($key) {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
    
    /**
     * Check if flash message exists
     * @param string $key
     * @return bool
     */
    public static function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
    
    /**
     * Set user authentication
     * @param array $userData
     */
    public static function setAuth($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['role'] = $userData['role'] ?? 'user';
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }
    
    /**
     * Get current user ID
     * @return int|null
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     * @return string|null
     */
    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Get current user role
     * @return string|null
     */
    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public static function hasRole($role) {
        return self::getUserRole() === $role;
    }
    
    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['authenticated']);
        unset($_SESSION['login_time']);
    }
    
    /**
     * Set CSRF token
     * @return string
     */
    public static function setCSRFToken() {
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
     * Get session data for debugging
     * @return array
     */
    public static function debug() {
        return $_SESSION;
    }
    
    /**
     * Clean old session data
     */
    public static function cleanup() {
        $currentTime = time();
        
        // Remove old flash messages
        if (isset($_SESSION['flash'])) {
            foreach ($_SESSION['flash'] as $key => $value) {
                if (isset($value['timestamp']) && $currentTime - $value['timestamp'] > 3600) {
                    unset($_SESSION['flash'][$key]);
                }
            }
        }
        
        // Check session timeout (30 minutes)
        if (self::isAuthenticated() && isset($_SESSION['login_time'])) {
            if ($currentTime - $_SESSION['login_time'] > 1800) {
                self::logout();
                self::setFlash('error', 'Session expired. Please login again.');
            }
        }
    }
}
?>
