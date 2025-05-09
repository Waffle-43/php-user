<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - direct connection for simplicity
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

header('Content-Type: application/json');

// Check if stylist ID is provided
if (!isset($_GET['stylist_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Stylist ID is required'
    ]);
    exit;
}

$stylistId = (int)$_GET['stylist_id'];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get stylist details
    $stmt = $conn->prepare("SELECT * FROM stylists WHERE id = :stylist_id");
    $stmt->execute([':stylist_id' => $stylistId]);
    $stylist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stylist) {
        throw new Exception('Stylist not found');
    }
    
    // Return stylist data
    echo json_encode($stylist);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 