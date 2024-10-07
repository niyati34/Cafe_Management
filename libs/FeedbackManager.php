<?php
/**
 * Feedback Manager for Food Chef Cafe Management System
 * Handles customer reviews, ratings, and feedback management
 */

class FeedbackManager {
    
    private $db;
    private $logger;
    private $mailer;
    
    public function __construct($db, $logger = null, $mailer = null) {
        $this->db = $db;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
    
    /**
     * Submit customer feedback
     * @param array $feedbackData
     * @return array
     */
    public function submitFeedback($feedbackData) {
        try {
            // Validate required fields
            $required = ['customer_name', 'customer_email', 'rating', 'feedback_type'];
            foreach ($required as $field) {
                if (empty($feedbackData[$field])) {
                    return ['success' => false, 'message' => "Missing required field: {$field}"];
                }
            }
            
            // Validate rating
            if (!is_numeric($feedbackData['rating']) || $feedbackData['rating'] < 1 || $feedbackData['rating'] > 5) {
                return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
            }
            
            // Validate feedback type
            $validTypes = ['general', 'food_quality', 'service', 'ambiance', 'delivery', 'reservation'];
            if (!in_array($feedbackData['feedback_type'], $validTypes)) {
                return ['success' => false, 'message' => 'Invalid feedback type'];
            }
            
            // Insert feedback
            $stmt = $this->db->prepare("
                INSERT INTO customer_feedback (
                    customer_name, customer_email, customer_phone, rating, feedback_type,
                    subject, message, order_id, reservation_id, is_public, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $feedbackData['customer_name'],
                $feedbackData['customer_email'],
                $feedbackData['customer_phone'] ?? '',
                $feedbackData['rating'],
                $feedbackData['feedback_type'],
                $feedbackData['subject'] ?? '',
                $feedbackData['message'] ?? '',
                $feedbackData['order_id'] ?? null,
                $feedbackData['reservation_id'] ?? null,
                $feedbackData['is_public'] ?? 1
            ]);
            
            $feedbackId = $this->db->lastInsertId();
            
            // Log feedback submission
            if ($this->logger) {
                $this->logger->info("Customer feedback submitted", [
                    'feedback_id' => $feedbackId,
                    'customer_name' => $feedbackData['customer_name'],
                    'rating' => $feedbackData['rating'],
                    'type' => $feedbackData['feedback_type']
                ]);
            }
            
            // Send acknowledgment email
            if ($this->mailer) {
                $this->sendFeedbackAcknowledgment($feedbackData);
            }
            
            return [
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedback_id' => $feedbackId
            ];
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error submitting feedback", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Submit food review
     * @param array $reviewData
     * @return array
     */
    public function submitFoodReview($reviewData) {
        try {
            // Validate required fields
            $required = ['food_id', 'customer_name', 'customer_email', 'rating', 'review'];
            foreach ($required as $field) {
                if (empty($reviewData[$field])) {
                    return ['success' => false, 'message' => "Missing required field: {$field}"];
                }
            }
            
            // Validate rating
            if (!is_numeric($reviewData['rating']) || $reviewData['rating'] < 1 || $reviewData['rating'] > 5) {
                return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
            }
            
            // Check if food item exists and is active
            $foodStmt = $this->db->prepare("SELECT id, name FROM food WHERE id = ? AND is_active = 1");
            $foodStmt->execute([$reviewData['food_id']]);
            $food = $foodStmt->fetch();
            
            if (!$food) {
                return ['success' => false, 'message' => 'Food item not found or not available'];
            }
            
            // Insert food review
            $stmt = $this->db->prepare("
                INSERT INTO food_reviews (
                    food_id, customer_name, customer_email, rating, review,
                    is_approved, created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $stmt->execute([
                $reviewData['food_id'],
                $reviewData['customer_name'],
                $reviewData['customer_email'],
                $reviewData['rating'],
                $reviewData['review']
            ]);
            
            $reviewId = $this->db->lastInsertId();
            
            // Update food item average rating
            $this->updateFoodRating($reviewData['food_id']);
            
            // Log review submission
            if ($this->logger) {
                $this->logger->info("Food review submitted", [
                    'review_id' => $reviewId,
                    'food_id' => $reviewData['food_id'],
                    'food_name' => $food['name'],
                    'rating' => $reviewData['rating']
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Review submitted successfully and pending approval',
                'review_id' => $reviewId
            ];
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error submitting food review", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Approve or reject review
     * @param int $reviewId
     * @param bool $approved
     * @param string $adminNotes
     * @return bool
     */
    public function moderateReview($reviewId, $approved, $adminNotes = '') {
        try {
            $status = $approved ? 1 : 0;
            
            $stmt = $this->db->prepare("
                UPDATE food_reviews 
                SET is_approved = ?, admin_notes = ?, moderated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $adminNotes, $reviewId]);
            
            if ($result) {
                // Get review details for logging
                $reviewStmt = $this->db->prepare("SELECT food_id FROM food_reviews WHERE id = ?");
                $reviewStmt->execute([$reviewId]);
                $review = $reviewStmt->fetch();
                
                if ($review) {
                    // Update food rating if approved
                    if ($approved) {
                        $this->updateFoodRating($review['food_id']);
                    }
                }
                
                if ($this->logger) {
                    $this->logger->info("Review moderated", [
                        'review_id' => $reviewId,
                        'approved' => $approved,
                        'admin_notes' => $adminNotes
                    ]);
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error moderating review", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Get approved reviews for a food item
     * @param int $foodId
     * @param int $limit
     * @return array
     */
    public function getFoodReviews($foodId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM food_reviews 
                WHERE food_id = ? AND is_approved = 1 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$foodId, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting food reviews", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Get feedback statistics
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getFeedbackStatistics($startDate = null, $endDate = null) {
        try {
            if (!$startDate) $startDate = date('Y-m-01');
            if (!$endDate) $endDate = date('Y-m-t');
            
            // General feedback stats
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_feedback,
                    AVG(rating) as avg_rating,
                    COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_feedback,
                    COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_feedback,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as neutral_feedback
                FROM customer_feedback 
                WHERE created_at BETWEEN ? AND ?
            ");
            
            $stmt->execute([$startDate, $endDate]);
            $stats = $stmt->fetch();
            
            // Feedback by type
            $typeStmt = $this->db->prepare("
                SELECT feedback_type, COUNT(*) as count, AVG(rating) as avg_rating
                FROM customer_feedback 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY feedback_type
                ORDER BY count DESC
            ");
            
            $typeStmt->execute([$startDate, $endDate]);
            $stats['by_type'] = $typeStmt->fetchAll();
            
            // Food review stats
            $reviewStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_reviews,
                    COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_reviews,
                    AVG(rating) as avg_food_rating
                FROM food_reviews 
                WHERE created_at BETWEEN ? AND ?
            ");
            
            $reviewStmt->execute([$startDate, $endDate]);
            $stats['food_reviews'] = $reviewStmt->fetch();
            
            return $stats;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting feedback statistics", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Get pending reviews for moderation
     * @param int $limit
     * @return array
     */
    public function getPendingReviews($limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT fr.*, f.name as food_name
                FROM food_reviews fr
                JOIN food f ON fr.food_id = f.id
                WHERE fr.is_approved = 0
                ORDER BY fr.created_at ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting pending reviews", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Update food item average rating
     * @param int $foodId
     * @return bool
     */
    private function updateFoodRating($foodId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE food f 
                SET avg_rating = (
                    SELECT AVG(rating) 
                    FROM food_reviews 
                    WHERE food_id = ? AND is_approved = 1
                ),
                total_reviews = (
                    SELECT COUNT(*) 
                    FROM food_reviews 
                    WHERE food_id = ? AND is_approved = 1
                )
                WHERE f.id = ?
            ");
            
            return $stmt->execute([$foodId, $foodId, $foodId]);
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error updating food rating", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Send feedback acknowledgment email
     * @param array $feedbackData
     * @return bool
     */
    private function sendFeedbackAcknowledgment($feedbackData) {
        if (!$this->mailer) {
            return false;
        }
        
        $subject = "Thank you for your feedback - Food Chef Cafe";
        
        $message = "
        <h2>Feedback Received</h2>
        <p>Dear {$feedbackData['customer_name']},</p>
        <p>Thank you for taking the time to share your feedback with us. We appreciate your input and will use it to improve our services.</p>
        
        <h3>Your Feedback Summary:</h3>
        <ul>
            <li><strong>Type:</strong> " . ucfirst(str_replace('_', ' ', $feedbackData['feedback_type'])) . "</li>
            <li><strong>Rating:</strong> {$feedbackData['rating']}/5</li>
        </ul>";
        
        if (!empty($feedbackData['message'])) {
            $message .= "<p><strong>Your Message:</strong><br>{$feedbackData['message']}</p>";
        }
        
        $message .= "
        <p>We will review your feedback and take appropriate action. If you have any urgent concerns, please don't hesitate to contact us directly.</p>
        
        <p>Best regards,<br>Food Chef Team</p>";
        
        return $this->mailer->sendMail($feedbackData['customer_email'], $subject, $message);
    }
    
    /**
     * Get customer feedback history
     * @param string $email
     * @return array
     */
    public function getCustomerFeedbackHistory($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM customer_feedback 
                WHERE customer_email = ? 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$email]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting customer feedback history", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Delete feedback (admin only)
     * @param int $feedbackId
     * @return bool
     */
    public function deleteFeedback($feedbackId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM customer_feedback WHERE id = ?");
            $result = $stmt->execute([$feedbackId]);
            
            if ($result && $this->logger) {
                $this->logger->info("Feedback deleted", ['feedback_id' => $feedbackId]);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error deleting feedback", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
}
?>
