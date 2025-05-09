<?php
// Include database configuration
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Set headers for JSON response
header('Content-Type: application/json');

// Get appointment ID
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$appointment_id) {
    echo json_encode(['error' => 'Appointment ID is required']);
    exit;
}

try {
    // Get appointment details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            s.price,
            s.description as service_description,
            st.name as stylist_name,
            st.id as stylist_id,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            stylists st ON a.stylist_id = st.id
        JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.id = ?
    ");
    
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        echo json_encode(['error' => 'Appointment not found']);
        exit;
    }
    
    // Format date and time for display
    $appointment['formatted_date'] = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $appointment['formatted_time'] = date('g:i A', strtotime($appointment['appointment_time']));
    $appointment['end_time'] = date('g:i A', strtotime($appointment['appointment_time']) + $appointment['duration'] * 60);
    
    echo json_encode(['appointment' => $appointment]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 