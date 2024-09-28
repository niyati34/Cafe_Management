<?php
/**
 * Food Chef Cafe Management System - API Endpoints
 * RESTful API for mobile app integration and external services
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/config.php';
require_once '../libs/Db.php';
require_once '../libs/Security.php';

$db = new Db();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/', '', $path);

// API Authentication
function authenticateRequest() {
    $headers = getallheaders();
    $apiKey = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'API key required']);
        exit;
    }
    
    // In a real application, validate against database
    $validKeys = ['food_chef_api_2024', 'mobile_app_key', 'admin_api_key'];
    if (!in_array($apiKey, $validKeys)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    return true;
}

// Handle different API endpoints
switch ($path) {
    case 'menu':
        authenticateRequest();
        handleMenuAPI($method);
        break;
        
    case 'reservations':
        authenticateRequest();
        handleReservationsAPI($method);
        break;
        
    case 'contact':
        handleContactAPI($method);
        break;
        
    case 'about':
        authenticateRequest();
        handleAboutAPI($method);
        break;
        
    case 'team':
        authenticateRequest();
        handleTeamAPI($method);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function handleMenuAPI($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $sql = "SELECT * FROM food WHERE status = 1 ORDER BY category, name";
            $result = $db->select($sql);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result,
                    'count' => count($result)
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'data' => [],
                    'count' => 0
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleReservationsAPI($method) {
    global $db;
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }
            
            $name = Security::sanitizeInput($input['name'] ?? '');
            $email = Security::sanitizeInput($input['email'] ?? '');
            $phone = Security::sanitizeInput($input['phone'] ?? '');
            $date = Security::sanitizeInput($input['date'] ?? '');
            $time = Security::sanitizeInput($input['time'] ?? '');
            $guests = (int)($input['guests'] ?? 1);
            $message = Security::sanitizeInput($input['message'] ?? '');
            
            if (!$name || !$email || !$date || !$time) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            if (!Security::validateEmail($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                return;
            }
            
            $sql = "INSERT INTO reservations (name, email, phone, reservation_date, reservation_time, guests, message, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [$name, $email, $phone, $date, $time, $guests, $message];
            
            if ($db->insert($sql, $params)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Reservation created successfully',
                    'reservation_id' => $db->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create reservation']);
            }
            break;
            
        case 'GET':
            $sql = "SELECT * FROM reservations ORDER BY created_at DESC LIMIT 50";
            $result = $db->select($sql);
            
            echo json_encode([
                'status' => 'success',
                'data' => $result ?: [],
                'count' => $result ? count($result) : 0
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleContactAPI($method) {
    global $db;
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }
            
            $name = Security::sanitizeInput($input['name'] ?? '');
            $email = Security::sanitizeInput($input['email'] ?? '');
            $subject = Security::sanitizeInput($input['subject'] ?? '');
            $message = Security::sanitizeInput($input['message'] ?? '');
            
            if (!$name || !$email || !$message) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            if (!Security::validateEmail($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                return;
            }
            
            $sql = "INSERT INTO contact_messages (name, email, subject, message, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $params = [$name, $email, $subject, $message];
            
            if ($db->insert($sql, $params)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Message sent successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to send message']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleAboutAPI($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $sql = "SELECT * FROM about WHERE status = 1 LIMIT 1";
            $result = $db->select($sql);
            
            if ($result && count($result) > 0) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result[0]
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'data' => null
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleTeamAPI($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $sql = "SELECT * FROM team WHERE status = 1 ORDER BY position_order";
            $result = $db->select($sql);
            
            echo json_encode([
                'status' => 'success',
                'data' => $result ?: [],
                'count' => $result ? count($result) : 0
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
?>
