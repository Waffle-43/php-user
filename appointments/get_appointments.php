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

// For testing purposes, we'll use a hardcoded customer ID
$customerId = 1;

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get appointments with service and stylist details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            st.name as stylist_name
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            stylists st ON a.stylist_id = st.id
        WHERE 
            a.customer_id = :customer_id
        ORDER BY 
            a.appointment_date DESC, 
            a.appointment_time DESC
    ");
    
    $stmt->execute([':customer_id' => $customerId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($appointments);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load appointments',
        'message' => $e->getMessage()
    ]);
}
?> 