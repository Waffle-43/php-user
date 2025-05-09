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

// Get stylist_id from POST request if available (for service provider dashboard)
$stylistId = isset($_POST['stylist_id']) ? (int)$_POST['stylist_id'] : 0;

// For development purposes, use a hardcoded customer ID if session is not set
$customerId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Get action from request - default to mark_all if mark_all parameter is set
if (isset($_POST['mark_all'])) {
    $action = 'mark_all_read';
} else {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
}

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($action === 'mark_read') {
        // Get notification ID
        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        
        if ($notificationId <= 0) {
            throw new Exception("Invalid notification ID");
        }
        
        // Mark single notification as read
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $stylistId > 0 ? $stylistId : $customerId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
    else if ($action === 'mark_all_read') {
        // Mark all notifications as read
        if ($stylistId > 0) {
            // Mark all notifications for a stylist
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $stylistId]);
        } else {
            // Mark all notifications for a customer
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $customerId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
    else {
        throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 