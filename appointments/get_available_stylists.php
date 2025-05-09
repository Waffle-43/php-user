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

// Check required parameters
if (!isset($_GET['date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Date parameter is required']);
    exit;
}

$date = $_GET['date'];
$time = isset($_GET['time']) ? $_GET['time'] : null;
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;

try {
    // Connect to database directly
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get stylists from database
    // In a real application, you would check the stylists availability
    // based on their schedules and existing appointments
    $stmt = $conn->query("SELECT * FROM stylists WHERE is_active = 1 ORDER BY name");
    $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Simulate some unavailable stylists (in a real app, you'd check appointments table)
    // Make some stylists randomly unavailable
    foreach ($stylists as $key => $stylist) {
        // 20% chance a stylist is unavailable
        if (rand(1, 5) === 1) {
            unset($stylists[$key]);
        }
    }
    
    // Re-index array
    $stylists = array_values($stylists);
    
    echo json_encode($stylists);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load available stylists',
        'message' => $e->getMessage()
    ]);
}
?> 