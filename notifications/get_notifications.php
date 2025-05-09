<?php
// Start session
session_start();

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

// Set content type to JSON
header('Content-Type: application/json');

// For development purposes, use a hardcoded customer ID if session is not set
$customerId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if notifications table exists and its structure
    $checkTable = $conn->prepare("SHOW TABLES LIKE 'notifications'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() === 0) {
        // Table doesn't exist, create it
        $conn->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                recipient_type ENUM('customer', 'stylist') NOT NULL DEFAULT 'customer',
                recipient_id INT NOT NULL,
                appointment_id INT,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (recipient_type, recipient_id, is_read)
            )
        ");
    }
    
    // Check structure of notifications table
    $checkColumns = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'notifications'
    ");
    $checkColumns->execute();
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    
    // Try to get notifications using the appropriate structure with appointment details
    if (in_array('recipient_type', $columns) && in_array('recipient_id', $columns)) {
        // New structure with recipient_type and recipient_id
        $stmt = $conn->prepare("
            SELECT n.*, 
                   a.appointment_date, a.appointment_time, a.duration, a.status,
                   s.name as service_name, 
                   st.name as stylist_name
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN stylists st ON a.stylist_id = st.id
            WHERE n.recipient_type = 'customer' AND n.recipient_id = :recipient_id
            ORDER BY n.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':recipient_id' => $customerId]);
    }
    else if (in_array('user_id', $columns)) {
        // Old structure with user_id
        $stmt = $conn->prepare("
            SELECT n.*, 
                   a.appointment_date, a.appointment_time, a.duration, a.status,
                   s.name as service_name, 
                   st.name as stylist_name
            FROM notifications n
            LEFT JOIN appointments a ON n.related_id = a.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN stylists st ON a.stylist_id = st.id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':user_id' => $customerId]);
    }
    else {
        // Fall back to a simplistic approach if structure is unknown
        throw new Exception("Notifications table structure is not compatible");
    }
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process notifications to add formatted dates and times if available
    foreach ($notifications as &$notification) {
        if (!empty($notification['appointment_date'])) {
            $notification['formatted_date'] = date('l, F j, Y', strtotime($notification['appointment_date']));
        }
        
        if (!empty($notification['appointment_time'])) {
            $notification['formatted_time'] = date('g:i A', strtotime($notification['appointment_time']));
        }
        
        // Add a preview field for easy display in dropdown
        $notification['preview'] = !empty($notification['service_name']) ? 
            $notification['service_name'] : 
            'Appointment';
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 