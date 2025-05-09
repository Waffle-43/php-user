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

// Get service and stylist IDs if provided
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
$stylistId = isset($_GET['stylist_id']) ? (int)$_GET['stylist_id'] : null;

try {
    // Connect to database directly
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the current date and format it as YYYY-MM-DD
    $currentDate = date('Y-m-d');
    
    // Get the last day of next month
    $lastDay = date('Y-m-d', strtotime('last day of next month'));
    
    // Get service duration if provided
    $serviceDuration = 60; // Default to 60 minutes
    if ($serviceId) {
        $stmt = $conn->prepare("SELECT duration FROM services WHERE id = :id");
        $stmt->execute([':id' => $serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service) {
            $serviceDuration = (int)$service['duration'];
        }
    }
    
    // Get stylist's schedule if provided
    $stylistUnavailableDates = [];
    if ($stylistId) {
        // Get all appointments for this stylist
        $stmt = $conn->prepare("
            SELECT appointment_date, COUNT(*) as appointment_count
            FROM appointments
            WHERE stylist_id = :stylist_id
            AND status != 'cancelled'
            AND appointment_date BETWEEN :start_date AND :end_date
            GROUP BY appointment_date
        ");
        
        $stmt->execute([
            ':stylist_id' => $stylistId,
            ':start_date' => $currentDate,
            ':end_date' => $lastDay
        ]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // If there are already 8 or more appointments on this day, consider it fully booked
            if ($row['appointment_count'] >= 8) {
                $stylistUnavailableDates[] = $row['appointment_date'];
            }
        }
    }
    
    // For the initial version, we'll generate dates for the next month
    $availableDates = [];
    $startDate = new DateTime($currentDate);
    $endDate = new DateTime($lastDay);
    $interval = new DateInterval('P1D'); // 1 day interval
    $dateRange = new DatePeriod($startDate, $interval, $endDate);
    
    foreach ($dateRange as $date) {
        $dayOfWeek = $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $dateStr = $date->format('Y-m-d');
        
        // Skip past dates (shouldn't happen but just in case)
        if ($date < new DateTime($currentDate)) {
            continue;
        }
        
        // Skip dates where stylist is fully booked
        if (in_array($dateStr, $stylistUnavailableDates)) {
            continue;
        }
        
        // Check if it's a day off (can be configured per salon)
        // For example, if salon is closed on Sundays or specific holidays
        $isClosed = ($dayOfWeek == 7); // Closed on Sundays
        
        if ($isClosed) {
            continue;
        }
        
        // Assume weekends (Saturday = 6) have limited availability
        $isWeekend = ($dayOfWeek == 6);
        
        // Calculate available slots based on business hours and slot duration
        $businessStartHour = $isWeekend ? 10 : 9; // 10 AM on weekends, 9 AM on weekdays
        $businessEndHour = $isWeekend ? 16 : 18;  // 4 PM on weekends, 6 PM on weekdays
        
        // Calculate number of available slots
        $totalMinutes = ($businessEndHour - $businessStartHour) * 60;
        $totalPossibleSlots = floor($totalMinutes / 30); // 30-minute intervals
        
        // Get booked slots for this date and stylist (if specified)
        $bookedCount = 0;
        if ($stylistId) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE appointment_date = :date 
                AND stylist_id = :stylist_id
                AND status != 'cancelled'
            ");
            
            $stmt->execute([
                ':date' => $dateStr,
                ':stylist_id' => $stylistId
            ]);
            
            $bookedCount = (int)$stmt->fetchColumn();
        }
        
        // Calculate available slots (approximate calculation)
        $availableSlots = $totalPossibleSlots - $bookedCount;
        
        // Only include dates with available slots
        if ($availableSlots > 0) {
            $availableDates[] = [
                'date' => $dateStr,
                'formatted' => $date->format('l, F j, Y'),
                'day_of_week' => $date->format('l'),
                'is_weekend' => $isWeekend,
                'available_slots' => $availableSlots
            ];
        }
    }
    
    echo json_encode($availableDates);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load available dates',
        'message' => $e->getMessage()
    ]);
}
?> 