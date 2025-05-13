<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include our notification utilities
require_once __DIR__ . '/../notifications/notification_utils.php';

// Use connect.php for database connection
$conn = require_once __DIR__ . '/../utils_files/connect.php'; // Database connection

// Determine if this is a JSON response or redirect based on request type
$isJsonResponse = !(isset($_GET['redirect']));

if ($isJsonResponse) {
    header('Content-Type: application/json');
}

// For testing purposes, we'll use a hardcoded customer ID if not staff
$customerId = 1;

// Check if this is a staff member accessing the page
$isStaff = isset($_GET['stylist_id']) || isset($_POST['stylist_id']);
$stylistId = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : (isset($_POST['stylist_id']) ? intval($_POST['stylist_id']) : 0);

// Get appointment ID from GET or POST
$appointmentId = 0;
if (isset($_GET['id'])) {
    $appointmentId = intval($_GET['id']);
} elseif (isset($_POST['appointment_id'])) {
    $appointmentId = intval($_POST['appointment_id']);
} elseif (isset($_POST['id'])) {
    $appointmentId = intval($_POST['id']);
}

// Check for required parameters
if ($appointmentId <= 0) {
    if ($isJsonResponse) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Appointment ID is required'
        ]);
    } else {
        // Redirect back with error
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'calendar';
        header("Location: service_provider_{$redirect}.php?stylist_id={$stylistId}&error=missing_id");
    }
    exit;
}

try {
    // Start transaction
    if ($conn instanceof mysqli) {
        $conn->begin_transaction();
    } else {
        $conn->beginTransaction();
    }
    
    // Get the appointment details with appropriate conditions based on user type
    if ($conn instanceof mysqli) {
        if ($isStaff) {
            // Staff can cancel any appointment
            $sql = "
                SELECT a.*, s.name as service_name, st.name as stylist_name, c.id as customer_id  
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN stylists st ON a.stylist_id = st.id
                LEFT JOIN customers c ON a.customer_id = c.id
                WHERE a.id = ? 
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $appointmentId);
        } else {
            // Regular customers can only cancel their own appointments
            $sql = "
                SELECT a.*, s.name as service_name, st.name as stylist_name  
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN stylists st ON a.stylist_id = st.id
                WHERE a.id = ? 
                AND a.customer_id = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $appointmentId, $customerId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
    } else {
        // PDO version of the same logic
        if ($isStaff) {
            $sql = "
                SELECT a.*, s.name as service_name, st.name as stylist_name, c.id as customer_id
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN stylists st ON a.stylist_id = st.id
                LEFT JOIN customers c ON a.customer_id = c.id
                WHERE a.id = :id
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $appointmentId]);
        } else {
            $sql = "
                SELECT a.*, s.name as service_name, st.name as stylist_name
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN stylists st ON a.stylist_id = st.id
                WHERE a.id = :id 
                AND a.customer_id = :customer_id
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':id' => $appointmentId,
                ':customer_id' => $customerId
            ]);
        }
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$appointment) {
        throw new Exception('Appointment not found or you do not have permission to cancel it');
    }
    
    // Check if appointment is already cancelled or completed
    if ($appointment['status'] === 'cancelled') {
        throw new Exception('This appointment has already been cancelled');
    }
    
    if ($appointment['status'] === 'completed') {
        throw new Exception('Cannot cancel a completed appointment');
    }
    
    // Skip the 24-hour check for staff members
    if (!$isStaff) {
        // Check if appointment is within 24 hours
        $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
        $now = new DateTime();
        $interval = $now->diff($appointmentDateTime);
        $hoursUntilAppointment = $interval->h + ($interval->days * 24);
        
        if ($hoursUntilAppointment < 24 && $appointmentDateTime > $now) {
            throw new Exception('Appointments can only be cancelled at least 24 hours in advance');
        }
    }
    
    // Update appointment status
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = 'cancelled' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = 'cancelled' 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $appointmentId]);
        
        // Create notification using our utility
        $notificationMessage = 'Your appointment for ' . $appointment['service_name'] . ' on ' . 
                              date('F j, Y', strtotime($appointment['appointment_date'])) . ' has been cancelled.';
                              
        $emailSubject = 'Harmony Heaven Spa - Appointment Cancellation';
        $additionalMessage = 'If you did not request this cancellation, please contact us immediately.';
        
        if (isset($appointment['customer_id'])) {
            $notificationResult = sendAppointmentNotification(
                $conn,
                $appointment['customer_id'],
                $appointmentId,
                'appointment_cancellation',
                $notificationMessage,
                $emailSubject,
                $additionalMessage
            );
        }
    }
    
    // Commit transaction
    if ($conn instanceof mysqli) {
        $conn->commit();
    } else {
        $conn->commit();
    }
    
    // Return response based on request type
    if ($isJsonResponse) {
        // Return success response as JSON
        echo json_encode([
            'success' => true,
            'message' => 'Appointment cancelled successfully!'
        ]);
    } else {
        // Redirect back to appropriate page
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'calendar';
        header("Location: service_provider_{$redirect}.php?stylist_id={$stylistId}&success=cancelled");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        if ($conn instanceof mysqli && $conn->begin_transaction) {
            $conn->rollback();
        } elseif ($conn->inTransaction()) {
            $conn->rollBack();
        }
    }
    
    // Return error response based on request type
    if ($isJsonResponse) {
        // Return error response as JSON
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        // Redirect back with error
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'calendar';
        $errorMsg = urlencode($e->getMessage());
        header("Location: service_provider_{$redirect}.php?stylist_id={$stylistId}&error={$errorMsg}");
    }
}
?> 