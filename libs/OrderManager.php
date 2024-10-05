<?php
/**
 * Order Manager for Food Chef Cafe Management System
 * Handles food orders, order tracking, and order processing
 */

class OrderManager {
    
    private $db;
    private $logger;
    private $mailer;
    
    public function __construct($db, $logger = null, $mailer = null) {
        $this->db = $db;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
    
    /**
     * Create a new food order
     * @param array $orderData
     * @return array
     */
    public function createOrder($orderData) {
        try {
            // Validate required fields
            $required = ['customer_name', 'customer_email', 'customer_phone', 'items'];
            foreach ($required as $field) {
                if (empty($orderData[$field])) {
                    return ['success' => false, 'message' => "Missing required field: {$field}"];
                }
            }
            
            // Validate items
            if (!is_array($orderData['items']) || empty($orderData['items'])) {
                return ['success' => false, 'message' => 'Order must contain at least one item'];
            }
            
            // Calculate total and validate items
            $total = 0;
            $orderItems = [];
            
            foreach ($orderData['items'] as $item) {
                if (empty($item['food_id']) || empty($item['quantity'])) {
                    return ['success' => false, 'message' => 'Invalid item data'];
                }
                
                // Get food item details
                $foodStmt = $this->db->prepare("SELECT id, name, price, is_active FROM food WHERE id = ?");
                $foodStmt->execute([$item['food_id']]);
                $food = $foodStmt->fetch();
                
                if (!$food || !$food['is_active']) {
                    return ['success' => false, 'message' => "Food item not available: {$item['food_id']}"];
                }
                
                $itemTotal = $food['price'] * $item['quantity'];
                $total += $itemTotal;
                
                $orderItems[] = [
                    'food_id' => $item['food_id'],
                    'food_name' => $food['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $food['price'],
                    'total_price' => $itemTotal
                ];
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Create order
            $orderStmt = $this->db->prepare("
                INSERT INTO orders (customer_name, customer_email, customer_phone, total_amount, 
                                  order_type, delivery_address, special_instructions, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $orderStmt->execute([
                $orderData['customer_name'],
                $orderData['customer_email'],
                $orderData['customer_phone'],
                $total,
                $orderData['order_type'] ?? 'dine_in',
                $orderData['delivery_address'] ?? '',
                $orderData['special_instructions'] ?? ''
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Create order items
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, food_id, food_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($orderItems as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['food_id'],
                    $item['food_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                ]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log order creation
            if ($this->logger) {
                $this->logger->info("Order created", [
                    'order_id' => $orderId,
                    'customer_name' => $orderData['customer_name'],
                    'total_amount' => $total,
                    'items_count' => count($orderItems)
                ]);
            }
            
            // Send confirmation email
            if ($this->mailer) {
                $this->sendOrderConfirmation($orderId, $orderData, $orderItems, $total);
            }
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'total_amount' => $total
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            
            if ($this->logger) {
                $this->logger->error("Database error creating order", ['error' => $e->getMessage()]);
            }
            
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get order by ID
     * @param int $id
     * @return array|false
     */
    public function getOrder($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, GROUP_CONCAT(oi.food_name, ' x', oi.quantity) as items_summary
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.id = ?
                GROUP BY o.id
            ");
            
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Get order items
                $itemsStmt = $this->db->prepare("
                    SELECT * FROM order_items WHERE order_id = ?
                ");
                $itemsStmt->execute([$id]);
                $order['items'] = $itemsStmt->fetchAll();
            }
            
            return $order;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting order", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Update order status
     * @param int $id
     * @param string $status
     * @param string $notes
     * @return bool
     */
    public function updateOrderStatus($id, $status, $notes = '') {
        try {
            $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = ?, notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $notes, $id]);
            
            if ($result && $this->logger) {
                $this->logger->info("Order status updated", [
                    'order_id' => $id,
                    'new_status' => $status,
                    'notes' => $notes
                ]);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error updating order status", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Get orders by status
     * @param string $status
     * @param int $limit
     * @return array
     */
    public function getOrdersByStatus($status, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, GROUP_CONCAT(oi.food_name, ' x', oi.quantity) as items_summary
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.status = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$status, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting orders by status", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Get pending orders
     * @return array
     */
    public function getPendingOrders() {
        return $this->getOrdersByStatus('pending');
    }
    
    /**
     * Get orders for today
     * @return array
     */
    public function getTodayOrders() {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, GROUP_CONCAT(oi.food_name, ' x', oi.quantity) as items_summary
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE DATE(o.created_at) = CURDATE()
                GROUP BY o.id
                ORDER BY o.created_at DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting today's orders", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Get order statistics
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOrderStatistics($startDate = null, $endDate = null) {
        try {
            if (!$startDate) $startDate = date('Y-m-01');
            if (!$endDate) $endDate = date('Y-m-t');
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(DISTINCT customer_email) as unique_customers
                FROM orders 
                WHERE created_at BETWEEN ? AND ?
            ");
            
            $stmt->execute([$startDate, $endDate]);
            $stats = $stmt->fetch();
            
            // Get popular food items
            $popularStmt = $this->db->prepare("
                SELECT oi.food_name, SUM(oi.quantity) as total_ordered
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ? AND o.status != 'cancelled'
                GROUP BY oi.food_id, oi.food_name
                ORDER BY total_ordered DESC
                LIMIT 10
            ");
            
            $popularStmt->execute([$startDate, $endDate]);
            $stats['popular_items'] = $popularStmt->fetchAll();
            
            return $stats;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error getting order statistics", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    /**
     * Cancel order
     * @param int $id
     * @param string $reason
     * @return bool
     */
    public function cancelOrder($id, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    notes = CONCAT(IFNULL(notes, ''), ' Cancelled: ', ?),
                    updated_at = NOW() 
                WHERE id = ? AND status IN ('pending', 'confirmed')
            ");
            
            $result = $stmt->execute([$reason, $id]);
            
            if ($result && $this->logger) {
                $this->logger->info("Order cancelled", [
                    'order_id' => $id,
                    'reason' => $reason
                ]);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error cancelling order", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    /**
     * Send order confirmation email
     * @param int $orderId
     * @param array $orderData
     * @param array $orderItems
     * @param float $total
     * @return bool
     */
    private function sendOrderConfirmation($orderId, $orderData, $orderItems, $total) {
        if (!$this->mailer) {
            return false;
        }
        
        $subject = "Order Confirmation #{$orderId} - Food Chef Cafe";
        
        $message = "
        <h2>Order Confirmation</h2>
        <p>Dear {$orderData['customer_name']},</p>
        <p>Thank you for your order! Here are your order details:</p>
        
        <h3>Order #{$orderId}</h3>
        <table border='1' style='border-collapse: collapse; width: 100%;'>
            <tr style='background: #f0f0f0;'>
                <th style='padding: 8px;'>Item</th>
                <th style='padding: 8px;'>Quantity</th>
                <th style='padding: 8px;'>Price</th>
                <th style='padding: 8px;'>Total</th>
            </tr>";
        
        foreach ($orderItems as $item) {
            $message .= "
            <tr>
                <td style='padding: 8px;'>{$item['food_name']}</td>
                <td style='padding: 8px;'>{$item['quantity']}</td>
                <td style='padding: 8px;'>$" . number_format($item['unit_price'], 2) . "</td>
                <td style='padding: 8px;'>$" . number_format($item['total_price'], 2) . "</td>
            </tr>";
        }
        
        $message .= "
        </table>
        
        <p><strong>Total Amount: $" . number_format($total, 2) . "</strong></p>
        
        <p>We'll notify you when your order is ready!</p>
        
        <p>Best regards,<br>Food Chef Team</p>";
        
        return $this->mailer->sendMail($orderData['customer_email'], $subject, $message);
    }
}
?>
