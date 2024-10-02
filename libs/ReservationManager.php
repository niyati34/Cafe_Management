<?php
/**
 * Reservation Manager for Food Chef Cafe Management System
 * Handles table bookings, availability checks, and reservation processing
 */

class ReservationManager {
    
    private $db;
    private $logger;
    
    public function __construct($db, $logger = null) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    /**
     * Check table availability for a specific date and time
     * @param string $date
     * @param string $time
     * @param int $guests
     * @return array
     */
    public function checkAvailability($date, $time, $guests) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as booked_tables
                FROM reservations 
                WHERE reservation_date = ? 
                AND reservation_time = ? 
                AND status IN ('confirmed', 'pending')
            ");
            
            $stmt->execute([$date, $time]);
            $result = $stmt->fetch();
            
            $totalTables = 20; // Assuming 20 tables in the restaurant
            $availableTables = $totalTables - $result['booked_tables'];
            
            $canAccommodate = $availableTables > 0;
            
            if ($this->logger) {
                $this->logger->info("Availability check for {$date} {$time} - {$guests} guests", [
                    'date' => $date,
                    'time' => $time,
                    'guests' => $guests,
                    'available_tables' => $availableTables,
                    'can_accommodate' => $canAccommodate
                ]);
            }
            
            return [
                'available' => $canAccommodate,
                'available_tables' => $availableTables,
                'total_tables' => $totalTables,
                'booked_tables' => $result['booked_tables']
            ];
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in availability check", ['error' => $e->getMessage()]);
            }
            return ['available' => false, 'error' => 'Database error'];
        }
    }
    
    /**
     * Create a new reservation
     * @param array $reservationData
     * @return array
     */
    public function createReservation($reservationData) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'reservation_date', 'reservation_time', 'guests'];
            foreach ($required as $field) {
                if (empty($reservationData[$field])) {
                    return ['success' => false, 'message' => "Missing required field: {$field}"];
                }
            }
            
            // Check availability
            $availability = $this->checkAvailability(
                $reservationData['reservation_date'],
                $reservationData['reservation_time'],
                $reservationData['guests']
            );
            
            if (!$availability['available']) {
                return ['success' => false, 'message' => 'No tables available for the selected time'];
            }
            
            // Insert reservation
            $stmt = $this->db->prepare("
                INSERT INTO reservations (name, email, phone, reservation_date, reservation_time, guests, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $reservationData['name'],
                $reservationData['email'],
                $reservationData['phone'] ?? '',
                $reservationData['reservation_date'],
                $reservationData['reservation_time'],
                $reservationData['guests'],
                $reservationData['message'] ?? ''
            ]);
            
            $reservationId = $this->db->lastInsertId();
            
            if ($this->logger) {
                $this->logger->logReservation('created', array_merge($reservationData, ['id' => $reservationId]));
            }
            
            return [
                'success' => true,
                'message' => 'Reservation created successfully',
                'reservation_id' => $reservationId
            ];
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error creating reservation", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get reservation by ID
     * @param int $id
     * @return array|false
     */
    public function getReservation($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reservations WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting reservation", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Update reservation status
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status) {
        try {
            $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                UPDATE reservations 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $id]);
            
            if ($result && $this->logger) {
                $this->logger->info("Reservation status updated", [
                    'reservation_id' => $id,
                    'new_status' => $status
                ]);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error updating reservation status", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Get reservations for a specific date
     * @param string $date
     * @return array
     */
    public function getReservationsByDate($date) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reservations 
                WHERE reservation_date = ? 
                ORDER BY reservation_time ASC
            ");
            
            $stmt->execute([$date]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting reservations by date", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Get upcoming reservations
     * @param int $limit
     * @return array
     */
    public function getUpcomingReservations($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reservations 
                WHERE reservation_date >= CURDATE() 
                AND status IN ('confirmed', 'pending')
                ORDER BY reservation_date ASC, reservation_time ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting upcoming reservations", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Cancel reservation
     * @param int $id
     * @param string $reason
     * @return bool
     */
    public function cancelReservation($id, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE reservations 
                SET status = 'cancelled', 
                    message = CONCAT(IFNULL(message, ''), ' Cancelled: ', ?),
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$reason, $id]);
            
            if ($result && $this->logger) {
                $this->logger->logReservation('cancelled', ['id' => $id, 'reason' => $reason]);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error cancelling reservation", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Get reservation statistics
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatistics($startDate = null, $endDate = null) {
        try {
            if (!$startDate) $startDate = date('Y-m-01');
            if (!$endDate) $endDate = date('Y-m-t');
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_reservations,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    AVG(guests) as avg_guests,
                    SUM(guests) as total_guests
                FROM reservations 
                WHERE reservation_date BETWEEN ? AND ?
            ");
            
            $stmt->execute([$startDate, $endDate]);
            $stats = $stmt->fetch();
            
            // Get popular time slots
            $timeStmt = $this->db->prepare("
                SELECT reservation_time, COUNT(*) as count
                FROM reservations 
                WHERE reservation_date BETWEEN ? AND ? 
                AND status IN ('confirmed', 'completed')
                GROUP BY reservation_time 
                ORDER BY count DESC 
                LIMIT 5
            ");
            
            $timeStmt->execute([$startDate, $endDate]);
            $stats['popular_times'] = $timeStmt->fetchAll();
            
            return $stats;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting statistics", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Send reminder notifications
     * @param int $daysAhead
     * @return array
     */
    public function sendReminders($daysAhead = 1) {
        try {
            $reminderDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
            
            $stmt = $this->db->prepare("
                SELECT * FROM reservations 
                WHERE reservation_date = ? 
                AND status = 'confirmed'
                AND reminder_sent = 0
            ");
            
            $stmt->execute([$reminderDate]);
            $reservations = $stmt->fetchAll();
            
            $sentCount = 0;
            foreach ($reservations as $reservation) {
                // Here you would integrate with your email system
                // For now, just mark as reminder sent
                $updateStmt = $this->db->prepare("
                    UPDATE reservations 
                    SET reminder_sent = 1 
                    WHERE id = ?
                ");
                
                if ($updateStmt->execute([$reservation['id']])) {
                    $sentCount++;
                }
            }
            
            if ($this->logger) {
                $this->logger->info("Reminders sent", [
                    'date' => $reminderDate,
                    'sent_count' => $sentCount,
                    'total_reservations' => count($reservations)
                ]);
            }
            
            return ['sent' => $sentCount, 'total' => count($reservations)];
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error sending reminders", ['error' => $e->getMessage()]);
            }
            return ['sent' => 0, 'total' => 0];
        }
    }
}
?>
