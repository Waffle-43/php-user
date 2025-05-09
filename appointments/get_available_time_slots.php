<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config; // Include the config file to use the existing connection

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check required parameters
if (!isset($_GET['date']) || !isset($_GET['stylist_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Date and stylist ID are required'
    ]);
    exit;
}

$date = $_GET['date'];
$stylist_id = intval($_GET['stylist_id']);
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

// Default duration if service_id is not provided
$duration = 60; // 60 minutes default

// Get service duration if service_id is provided
if ($service_id > 0) {
    $durationStmt = $conn->prepare("SELECT duration FROM services WHERE id = ?");
    $durationStmt->execute([$service_id]);
    $serviceData = $durationStmt->fetch();
    
    if ($serviceData) {
        $duration = intval($serviceData['duration']);
    }
}

try {
    // Get the day of week for the requested date (0 = Sunday, 6 = Saturday)
    $dayOfWeek = date('w', strtotime($date));
    
    // Get stylist's schedule for this day
    $scheduleStmt = $conn->prepare("
        SELECT start_time, end_time, is_available 
        FROM stylist_schedule 
        WHERE stylist_id = ? AND day_of_week = ?
    ");
    $scheduleStmt->execute([$stylist_id, $dayOfWeek]);
    $scheduleData = $scheduleStmt->fetch();
    
    // Check if stylist works on this day
    if (!$scheduleData || $scheduleData['is_available'] == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Stylist is not available on this day',
            'available_slots' => []
        ]);
        exit;
    }
    
    $startTime = $scheduleData['start_time'];
    $endTime = $scheduleData['end_time'];
    
    // Get existing appointments for this stylist on this date
    $appointmentsStmt = $conn->prepare("
        SELECT appointment_time, duration 
        FROM appointments 
        WHERE stylist_id = ? AND appointment_date = ? AND status NOT IN ('cancelled')
        AND (id != ? OR ? = 0)
    ");
    $appointmentsStmt->execute([$stylist_id, $date, $appointment_id, $appointment_id]);
    $existingAppointments = $appointmentsStmt->fetchAll();
    
    // Calculate available time slots
    $availableSlots = [];
    $currentTime = strtotime($startTime);
    $endTimeStamp = strtotime($endTime);
    
    // Create time slots in 30-minute increments
    $slotDuration = 30; // minutes
    
    while ($currentTime + ($duration * 60) <= $endTimeStamp) {
        $slotStart = date('H:i', $currentTime);
        $slotEnd = date('H:i', $currentTime + ($duration * 60));
        $isAvailable = true;
        
        // Check if this slot overlaps with any existing appointment
        foreach ($existingAppointments as $appointment) {
            $apptTime = strtotime($appointment['appointment_time']);
            $apptEnd = $apptTime + ($appointment['duration'] * 60);
            
            // Check for overlap
            if (
                ($currentTime >= $apptTime && $currentTime < $apptEnd) || 
                ($currentTime + ($duration * 60) > $apptTime && $currentTime + ($duration * 60) <= $apptEnd) ||
                ($currentTime <= $apptTime && $currentTime + ($duration * 60) >= $apptEnd)
            ) {
                $isAvailable = false;
                break;
            }
        }
        
        // Add time slot with availability info
        $availableSlots[] = [
            'time' => $slotStart,
            'available' => $isAvailable
        ];
        
        $currentTime += $slotDuration * 60; // Move to next slot
    }
    
    // Return available slots in the correct format
    echo json_encode([
        'success' => true,
        'slots' => $availableSlots,
        'date' => $date,
        'duration' => $duration
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error checking availability: ' . $e->getMessage()
    ]);
}
?> 