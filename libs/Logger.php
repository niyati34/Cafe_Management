<?php
/**
 * Logging Library for Food Chef Cafe Management System
 * Handles application logging, error tracking, and activity monitoring
 */

class Logger {
    
    private $logDir;
    private $logLevel;
    
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    
    public function __construct($logDir = 'logs', $logLevel = self::LEVEL_INFO) {
        $this->logDir = rtrim($logDir, '/');
        $this->logLevel = $logLevel;
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Log debug message
     * @param string $message
     * @param array $context
     */
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, 'DEBUG', $message, $context);
    }
    
    /**
     * Log info message
     * @param string $message
     * @param array $context
     */
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, 'INFO', $message, $context);
    }
    
    /**
     * Log warning message
     * @param string $message
     * @param array $context
     */
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, 'WARNING', $message, $context);
    }
    
    /**
     * Log error message
     * @param string $message
     * @param array $context
     */
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, 'ERROR', $message, $context);
    }
    
    /**
     * Log critical message
     * @param string $message
     * @param array $context
     */
    public function critical($message, $context = []) {
        $this->log(self::LEVEL_CRITICAL, 'CRITICAL', $message, $context);
    }
    
    /**
     * Log user activity
     * @param string $action
     * @param array $data
     */
    public function logActivity($action, $data = []) {
        $context = [
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'username' => $_SESSION['username'] ?? 'guest',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'action' => $action,
            'data' => $data
        ];
        
        $this->info("User Activity: {$action}", $context);
    }
    
    /**
     * Log database operations
     * @param string $operation
     * @param string $table
     * @param array $data
     */
    public function logDatabase($operation, $table, $data = []) {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
            'user_id' => $_SESSION['user_id'] ?? 'system'
        ];
        
        $this->info("Database {$operation} on table {$table}", $context);
    }
    
    /**
     * Log security events
     * @param string $event
     * @param array $details
     */
    public function logSecurity($event, $details = []) {
        $context = [
            'event' => $event,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $this->warning("Security Event: {$event}", $context);
    }
    
    /**
     * Log payment transactions
     * @param string $transactionId
     * @param string $status
     * @param array $data
     */
    public function logPayment($transactionId, $status, $data = []) {
        $context = [
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $data['amount'] ?? 0,
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'data' => $data
        ];
        
        $this->info("Payment Transaction: {$transactionId} - {$status}", $context);
    }
    
    /**
     * Log reservation events
     * @param string $action
     * @param array $reservation
     */
    public function logReservation($action, $reservation) {
        $context = [
            'action' => $action,
            'reservation_id' => $reservation['id'] ?? 'new',
            'customer_name' => $reservation['name'] ?? 'unknown',
            'customer_email' => $reservation['email'] ?? 'unknown',
            'date' => $reservation['reservation_date'] ?? 'unknown',
            'time' => $reservation['reservation_time'] ?? 'unknown',
            'guests' => $reservation['guests'] ?? 1
        ];
        
        $this->info("Reservation {$action}", $context);
    }
    
    /**
     * Write log entry
     * @param int $level
     * @param string $levelName
     * @param string $message
     * @param array $context
     */
    private function log($level, $levelName, $message, $context = []) {
        if ($level < $this->logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = $this->logDir . '/app_' . date('Y-m-d') . '.log';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'message' => $message,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get log entries for a specific date
     * @param string $date
     * @param int $level
     * @return array
     */
    public function getLogs($date = null, $level = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $logFile = $this->logDir . '/app_' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $logEntry = json_decode($line, true);
            
            if ($logEntry && ($level === null || $this->getLevelValue($logEntry['level']) >= $level)) {
                $logs[] = $logEntry;
            }
        }
        
        return $logs;
    }
    
    /**
     * Get level value
     * @param string $levelName
     * @return int
     */
    private function getLevelValue($levelName) {
        switch (strtoupper($levelName)) {
            case 'DEBUG':
                return self::LEVEL_DEBUG;
            case 'INFO':
                return self::LEVEL_INFO;
            case 'WARNING':
                return self::LEVEL_WARNING;
            case 'ERROR':
                return self::LEVEL_ERROR;
            case 'CRITICAL':
                return self::LEVEL_CRITICAL;
            default:
                return self::LEVEL_INFO;
        }
    }
    
    /**
     * Clean old log files
     * @param int $daysToKeep
     */
    public function cleanOldLogs($daysToKeep = 30) {
        $files = glob($this->logDir . '/app_*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log statistics
     * @param string $date
     * @return array
     */
    public function getLogStats($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $logs = $this->getLogs($date);
        $stats = [
            'total' => count($logs),
            'debug' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'critical' => 0
        ];
        
        foreach ($logs as $log) {
            $level = strtolower($log['level']);
            if (isset($stats[$level])) {
                $stats[$level]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Export logs to CSV
     * @param string $date
     * @param string $filename
     * @return bool
     */
    public function exportToCSV($date = null, $filename = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        if ($filename === null) {
            $filename = "logs_export_{$date}.csv";
        }
        
        $logs = $this->getLogs($date);
        
        if (empty($logs)) {
            return false;
        }
        
        $csv = fopen($filename, 'w');
        
        // Write header
        fputcsv($csv, ['Timestamp', 'Level', 'Message', 'Context']);
        
        // Write data
        foreach ($logs as $log) {
            fputcsv($csv, [
                $log['timestamp'],
                $log['level'],
                $log['message'],
                json_encode($log['context'])
            ]);
        }
        
        fclose($csv);
        return true;
    }
}
?>
