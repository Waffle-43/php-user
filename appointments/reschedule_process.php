<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include notification utilities
require_once 'notification_utils.php';

// Database configuration - direct connection for simplicity
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

header('Content-Type: application/json');

// For testing purposes, we'll use a hardcoded customer ID
$customerId = 1;

// Check for required parameters
if (!isset($_POST['appointment_id']) || !isset($_POST['date']) || !isset($_POST['time'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Get parameters
$appointmentId = (int)$_POST['appointment_id'];
$date = $_POST['date'];
$time = $_POST['time'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get full appointment details with service and stylist info
    $stmt = $conn->prepare("
        SELECT a.*, s.name as service_name, st.name as stylist_name,
               c.name as customer_name, c.email as customer_email 
        FROM appointments a
        LEFT JOIN services s ON a.service_id = s.id
        LEFT JOIN stylists st ON a.stylist_id = st.id
        LEFT JOIN customers c ON a.customer_id = c.id
        WHERE a.id = :appointment_id AND a.customer_id = :customer_id
    ");
    
    $stmt->execute([
        ':appointment_id' => $appointmentId,
        ':customer_id' => $customerId
    ]);
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        throw new Exception('Appointment not found or does not belong to you');
    }
    
    // Check if the appointment is already cancelled or completed
    if ($appointment['status'] === 'cancelled' || $appointment['status'] === 'completed') {
        throw new Exception('Cannot reschedule a cancelled or completed appointment');
    }
    
    // Check if the time slot is available
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE stylist_id = :stylist_id 
        AND appointment_date = :date 
        AND appointment_time = :time 
        AND status != 'cancelled'
        AND id != :appointment_id
    ");
    
    $stmt->execute([
        ':stylist_id' => $appointment['stylist_id'],
        ':date' => $date,
        ':time' => $time,
        ':appointment_id' => $appointmentId
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('This time slot has already been booked');
    }
    
    // Update appointment
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET appointment_date = :date, 
            appointment_time = :time, 
            status = 'rescheduled'
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':date' => $date,
        ':time' => $time,
        ':id' => $appointmentId
    ]);
    
    // Format date and time for notification
    $formattedDate = date('l, F j, Y', strtotime($date));
    $formattedTime = date('g:i A', strtotime($time));
    
    // Create notification using our utility
    $notificationMessage = "Your appointment for " . $appointment['service_name'] . " has been rescheduled to {$formattedDate} at {$formattedTime}.";
    $emailSubject = 'Harmony Heaven Spa - Appointment Rescheduled';
    $additionalMessage = 'Your appointment details have been updated. If you did not request this change, please contact us immediately.';
    
    // Update appointment with new date and time for email template
    $appointment['appointment_date'] = $date;
    $appointment['appointment_time'] = $time;
    $appointment['status'] = 'rescheduled';
    
    // Send notification
    $notificationResult = sendAppointmentNotification(
        $conn,
        $customerId,
        $appointmentId,
        'appointment_rescheduled',
        $notificationMessage,
        $emailSubject,
        $additionalMessage
    );
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Appointment rescheduled successfully!',
        'appointment_id' => $appointmentId,
        'notification_sent' => $notificationResult['database_notification'],
        'email_sent' => $notificationResult['email_sent']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 