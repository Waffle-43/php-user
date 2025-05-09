<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file for debugging
$logFile = 'appointment_debug.log';
file_put_contents($logFile, "\n".date('Y-m-d H:i:s') . " ========== New Request ==========\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n", FILE_APPEND);

// Log all incoming data
file_put_contents($logFile, date('Y-m-d H:i:s') . " - GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST params: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Read raw input
$rawInput = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw input length: " . strlen($rawInput) . "\n", FILE_APPEND);
if (strlen($rawInput) > 0) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n", FILE_APPEND);
}

// Database configuration - direct connection for simplicity
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

header('Content-Type: application/json');

// Detect action from multiple sources - we're going to be VERY thorough about this
$action = '';

// Check in the query string (highest priority)
if (isset($_GET['action']) && !empty($_GET['action'])) {
    $action = $_GET['action'];
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Action from GET: $action\n", FILE_APPEND);
}
// Check in POST data
else if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Action from POST: $action\n", FILE_APPEND);
}
// Check in the URL itself
else if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, 'action=create_appointment') !== false) {
        $action = 'create_appointment';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Action from REQUEST_URI: $action\n", FILE_APPEND);
    }
}
// Check in JSON input
else {
    // Try to get action from JSON input
    $jsonInput = file_get_contents('php://input');
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw input detected: " . substr($jsonInput, 0, 500) . "\n", FILE_APPEND);
    
    if (!empty($jsonInput)) {
        $data = json_decode($jsonInput, true);
        if ($data !== null) {
            if (isset($data['action']) && !empty($data['action'])) {
                $action = $data['action'];
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Action from JSON: $action\n", FILE_APPEND);
            }
            
            // If it's a booking submission with appointment_date, assume it's create_appointment
            if (isset($data['appointment_date']) && isset($data['start_time'])) {
                $action = 'create_appointment';
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Action set to create_appointment based on JSON appointment data\n", FILE_APPEND);
            }
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Failed to parse JSON: " . json_last_error_msg() . "\n", FILE_APPEND);
        }
    }
}

// Special case for appointment creation with POST method
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // If we're making a POST request, assume it's for appointment creation
    // This is a fallback but can help in some browser configurations
    $action = 'create_appointment';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Assuming action=create_appointment based on POST method\n", FILE_APPEND);
}

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Final action determined: '$action'\n", FILE_APPEND);

// Include notification utilities
require_once 'notification_utils.php';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Connected to database\n", FILE_APPEND);
    
    // Include our utility functions
    if (file_exists('service_utils.php')) {
        require_once 'service_utils.php';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Loaded service_utils.php\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: service_utils.php not found\n", FILE_APPEND);
        throw new Exception('Required utility file service_utils.php not found');
    }
    
    // Handle different actions
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Processing action: '$action'\n", FILE_APPEND);
    
    switch ($action) {
        case 'get_stylists':
            handleGetStylists($conn);
            break;
            
        case 'get_time_slots':
            handleGetTimeSlots($conn);
            break;
            
        case 'create_appointment':
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Calling handleCreateAppointment()\n", FILE_APPEND);
            handleCreateAppointment($conn);
            break;
            
        default:
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Invalid action: '$action'\n", FILE_APPEND);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ]);
    }
} catch (Exception $e) {
    // Log the error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Function to handle getting stylists
function handleGetStylists($conn) {
    // Check for required parameters
    if (!isset($_GET['date'])) {
        throw new Exception('Missing date parameter');
    }
    
    $date = $_GET['date'];
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
    
    try {
        // First check if we should get specialists for a specific service category
        $serviceCategory = null;
        if ($serviceId) {
            // Get service category from the service ID
            $service = getServiceById($conn, $serviceId);
            if ($service && isset($service['category'])) {
                $serviceCategory = $service['category'];
            }
        }
        
        // Build a query to get available stylists
        // We'll try using the stylists table first as defined in setup_service_provider_module.php
        $query = "SELECT s.id, s.name, s.specialization, s.bio, s.profile_image, s.rating ";
        
        // Check if staff assignments table exists (integration with Service Management Module)
        $staffAssignmentExists = false;
        
        try {
            $checkTable = $conn->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_name = 'staff_service_assignments'
            ");
            $checkTable->execute();
            $staffAssignmentExists = $checkTable->fetchColumn() ? true : false;
        } catch (Exception $e) {
            // If there's an error, assume the table doesn't exist
            $staffAssignmentExists = false;
        }
        
        if ($staffAssignmentExists && $serviceCategory) {
            // Join with staff_service_assignments to get stylists assigned to this service category
            $query .= ", ssa.service_id, ssa.specialty_level
                FROM stylists s
                JOIN staff_service_assignments ssa ON s.id = ssa.staff_id
                JOIN services svc ON ssa.service_id = svc.id
                WHERE s.is_active = 1
                AND svc.category = :category
                ORDER BY ssa.specialty_level DESC, s.rating DESC, s.name";
                
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':category', $serviceCategory);
        } else {
            // Simple query without service specialization
            $query .= "FROM stylists s
                WHERE s.is_active = 1
                ORDER BY s.rating DESC, s.name";
            
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we didn't find any stylists, try querying with available column instead of is_active
        if (empty($stylists)) {
            $query = "SELECT id, name, specialization as specialization, 
                     bio, image as profile_image, rating as rating
                     FROM stylists WHERE available = 1
                     ORDER BY name";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get availability information for each stylist
        foreach ($stylists as &$stylist) {
            // Check stylist's availability on the selected date
            $availability = getStylistAvailability($conn, $stylist['id'], $date);
            $stylist['availability'] = $availability;
            
            // Get their assigned services/specialties
            $specialties = getStylistSpecialties($conn, $stylist['id']);
            $stylist['specialties'] = $specialties;
            
            // Make sure profile_image is correctly processed - no need to base64 encode if it's already a file path
            if (!empty($stylist['profile_image'])) {
                // If it doesn't look like base64 data, it's probably a file path - leave it as is
                if (strpos($stylist['profile_image'], '.jpg') !== false || 
                    strpos($stylist['profile_image'], '.png') !== false ||
                    strpos($stylist['profile_image'], '.jpeg') !== false ||
                    strpos($stylist['profile_image'], '.gif') !== false) {
                    // It's already a file path, do nothing
                } 
                // If it's already base64 data, also do nothing
                else if (strpos($stylist['profile_image'], 'data:image') === 0) {
                    // It's already in data URL format, do nothing
                } 
                // Otherwise, it might be binary data that needs to be base64 encoded
                else {
                    // Convert to base64 if it's binary data
                    $stylist['profile_image'] = 'data:image/jpeg;base64,' . base64_encode($stylist['profile_image']);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'stylists' => $stylists
        ]);
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('Error in handleGetStylists: ' . $e->getMessage());
        
        // Return a more helpful error message
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load stylists: ' . $e->getMessage()
        ]);
    }
}

// Function to get stylist's availability for a specific date
function getStylistAvailability($conn, $stylistId, $date) {
    try {
        // First check if we have the schedule table
        $scheduleTableExists = false;
        
        try {
            $checkTable = $conn->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_name = 'stylist_schedule'
            ");
            $checkTable->execute();
            $scheduleTableExists = $checkTable->fetchColumn() ? true : false;
        } catch (Exception $e) {
            $scheduleTableExists = false;
        }
        
        // Default availability (9am-5pm)
        $availability = [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true
        ];
        
        if ($scheduleTableExists) {
            // Get day of week from date (0 = Sunday, 6 = Saturday)
            $dayOfWeek = date('w', strtotime($date));
            
            $stmt = $conn->prepare("
                SELECT start_time, end_time, is_available
                FROM stylist_schedule
                WHERE stylist_id = :stylist_id
                AND day_of_week = :day_of_week
            ");
            
            $stmt->execute([
                ':stylist_id' => $stylistId,
                ':day_of_week' => $dayOfWeek
            ]);
            
            $scheduleData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($scheduleData) {
                $availability = [
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'is_available' => (bool)$scheduleData['is_available']
                ];
            }
        }
        
        // Get existing appointments for this stylist on this date
        $stmt = $conn->prepare("
            SELECT appointment_time, duration
            FROM appointments
            WHERE stylist_id = :stylist_id
            AND appointment_date = :date
            AND status != 'cancelled'
            ORDER BY appointment_time
        ");
        
        $stmt->execute([
            ':stylist_id' => $stylistId,
            ':date' => $date
        ]);
        
        $bookedSlots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $startTime = strtotime($row['appointment_time']);
            $duration = (int)$row['duration'];
            
            // Mark each 30-minute slot as booked
            for ($i = 0; $i < $duration; $i += 30) {
                $slotTime = date('H:i:s', $startTime + $i * 60);
                $bookedSlots[] = $slotTime;
            }
        }
        
        $availability['booked_slots'] = $bookedSlots;
        
        return $availability;
    } catch (Exception $e) {
        // Return default availability if there's an error
        return [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
            'booked_slots' => []
        ];
    }
}

// Function to get a stylist's specialties/service assignments
function getStylistSpecialties($conn, $stylistId) {
    try {
        // Check if staff_service_assignments table exists
        $assignmentTableExists = false;
        
        try {
            $checkTable = $conn->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_name = 'staff_service_assignments'
            ");
            $checkTable->execute();
            $assignmentTableExists = $checkTable->fetchColumn() ? true : false;
        } catch (Exception $e) {
            $assignmentTableExists = false;
        }
        
        if ($assignmentTableExists) {
            // Get services this stylist specializes in
            $stmt = $conn->prepare("
                SELECT s.id, s.name, s.category, ssa.specialty_level
                FROM services s
                JOIN staff_service_assignments ssa ON s.id = ssa.service_id
                WHERE ssa.staff_id = :stylist_id
                ORDER BY ssa.specialty_level DESC, s.category, s.name
            ");
            
            $stmt->execute([':stylist_id' => $stylistId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // If no staff assignments, check if stylist has a specialization field
        $stmt = $conn->prepare("
            SELECT specialization FROM stylists WHERE id = :stylist_id
        ");
        
        $stmt->execute([':stylist_id' => $stylistId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['specialization'])) {
            // Find services matching the specialization
            $stmt = $conn->prepare("
                SELECT id, name, category 
                FROM services 
                WHERE category = :specialization OR name LIKE :search
                ORDER BY name
            ");
            
            $searchTerm = '%' . $result['specialization'] . '%';
            $stmt->execute([
                ':specialization' => $result['specialization'],
                ':search' => $searchTerm
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

// Function to handle getting time slots
function handleGetTimeSlots($conn) {
    // Check for required parameters
    if (!isset($_GET['date'])) {
        throw new Exception('Missing date parameter');
    }
    
    $date = $_GET['date'];
    $stylistId = isset($_GET['stylist_id']) ? (int)$_GET['stylist_id'] : null;
    
    // Get available time slots
    // If stylist_id is provided, get specific slots for that stylist
    // Otherwise, get generally available slots
    $timeSlots = [];
    
    $startTime = strtotime('9:00 AM');
    $endTime = strtotime('5:00 PM');
    
    // Get booked slots for this date to avoid offering them
    $bookedSlots = [];
    
    try {
        if ($stylistId) {
            // Get slots already booked for specific stylist
            $stmt = $conn->prepare("
                SELECT appointment_time
                FROM appointments
                WHERE appointment_date = :date 
                AND stylist_id = :stylist_id
                AND status != 'cancelled'
            ");
            $stmt->execute([
                ':date' => $date,
                ':stylist_id' => $stylistId
            ]);
        } else {
            // Get all booked slots for this date across all stylists
            $stmt = $conn->prepare("
                SELECT appointment_time
                FROM appointments
                WHERE appointment_date = :date
                AND status != 'cancelled'
            ");
            $stmt->execute([':date' => $date]);
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bookedSlots[] = $row['appointment_time'];
        }
    } catch (Exception $e) {
        // If there's an error, just proceed with empty booked slots
        // This will still allow showing time slots
    }
    
    // Generate available time slots
    for ($time = $startTime; $time <= $endTime; $time += 30 * 60) {
        $timeString = date('H:i:s', $time);
        
        // Skip booked slots
        if (in_array($timeString, $bookedSlots)) {
            continue;
        }
        
        $timeSlots[] = [
            'start_time' => $timeString,
            'end_time' => date('H:i:s', $time + 30 * 60),
            'available' => true
        ];
    }
    
    echo json_encode([
        'success' => true,
        'time_slots' => $timeSlots
    ]);
}

// Function to handle creating an appointment
function handleCreateAppointment($conn) {
    global $logFile;
    global $rawInput;
    
    file_put_contents($logFile, "\n" . date('Y-m-d H:i:s') . " ========== New Appointment Creation ==========\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    
    // Log detailed info about the request
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - GET data: " . print_r($_GET, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n", FILE_APPEND);
    
    // For testing purposes, we'll use a hardcoded customer ID
    $customerId = 1;
    
    try {
        // SIMPLIFIED APPROACH - try all possible sources for data
        $data = [];
        
        // First check POST data
        if (!empty($_POST)) {
            $data = $_POST;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using POST data\n", FILE_APPEND);
        }
        // Then try to parse JSON from raw input
        else if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if ($jsonData !== null) {
                $data = $jsonData;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using JSON data from raw input\n", FILE_APPEND);
            } else {
                // Try to parse URL-encoded data
                parse_str($rawInput, $parsedData);
                if (!empty($parsedData)) {
                    $data = $parsedData;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using URL-encoded data from raw input\n", FILE_APPEND);
                }
            }
        }
        
        // As a last resort, check GET parameters
        if (empty($data)) {
            // Extract parameters from query string
            $data = $_GET;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using GET data\n", FILE_APPEND);
        }
        
        // Log what parameters we found
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Final data for processing: " . print_r($data, true) . "\n", FILE_APPEND);
        
        // Check for required parameters
        if (!isset($data['service_id']) || 
            !isset($data['stylist_id']) || 
            !isset($data['appointment_date']) || 
            !isset($data['start_time'])) {
            
            $missing = [];
            if (!isset($data['service_id'])) $missing[] = 'service_id';
            if (!isset($data['stylist_id'])) $missing[] = 'stylist_id';
            if (!isset($data['appointment_date'])) $missing[] = 'appointment_date';
            if (!isset($data['start_time'])) $missing[] = 'start_time';
            
            throw new Exception('Missing required parameters: ' . implode(', ', $missing));
        }
        
        // Get parameters
        $serviceId = (int)$data['service_id'];
        $stylistId = (int)$data['stylist_id'];
        $date = $data['appointment_date'];
        $time = $data['start_time'];
        $notes = isset($data['notes']) ? $data['notes'] : '';
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using parameters: service=$serviceId, stylist=$stylistId, date=$date, time=$time\n", FILE_APPEND);
        
        // Add seconds to time if needed (HH:MM -> HH:MM:SS)
        if (strlen($time) === 5 && substr_count($time, ':') === 1) {
            $time .= ':00';
        }
        
        // --- Format/Validate Date and Time --- 
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Try to fix date format if possible
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                $date = date('Y-m-d', $timestamp);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Fixed date format: $date\n", FILE_APPEND);
            } else {
                $errorMsg = "Invalid date format. Expected YYYY-MM-DD, got: $date";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
                throw new Exception($errorMsg);
            }
        }
        
        // Validate and fix time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            // Try to fix time format
            if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time .= ':00';
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Fixed time format: $time\n", FILE_APPEND);
            } else if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                // Handle single-digit hours
                $parts = explode(':', $time);
                $time = sprintf('%02d:%02d:00', $parts[0], $parts[1]);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Fixed time format: $time\n", FILE_APPEND);
            } else {
                $errorMsg = "Invalid time format. Expected HH:MM:SS, got: $time";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
                throw new Exception($errorMsg);
            }
        }
        
        // Additional validation for date and time
        $appointmentDateTime = strtotime("$date $time");
        if ($appointmentDateTime === false) {
            $errorMsg = "Invalid date/time combination: $date $time";
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
            throw new Exception($errorMsg);
        }
        
        // Check if the appointment is in the future
        if ($appointmentDateTime < time()) {
            $errorMsg = "Cannot book appointments in the past";
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
            throw new Exception($errorMsg);
        }
        
        // --- Proceed with Database Transaction --- 
        $conn->beginTransaction();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Started database transaction\n", FILE_APPEND);
        
        try {
            // Check if the time slot is still available
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE stylist_id = :stylist_id 
                AND appointment_date = :date 
                AND appointment_time = :time 
                AND status != 'cancelled'
            ");
            
            $stmt->execute([
                ':stylist_id' => $stylistId,
                ':date' => $date,
                ':time' => $time
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('This time slot has already been booked');
            }
            
            // Get service details using our utility function
            $service = getServiceById($conn, $serviceId);
            
            if (!$service) {
                throw new Exception('Invalid service selected');
            }
            
            // Create appointment
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    customer_id, service_id, stylist_id,
                    appointment_date, appointment_time,
                    duration, price, notes, status,
                    created_at
                ) VALUES (
                    :customer_id, :service_id, :stylist_id,
                    :date, :time,
                    :duration, :price, :notes, 'pending',
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':customer_id' => $customerId,
                ':service_id' => $serviceId,
                ':stylist_id' => $stylistId,
                ':date' => $date,
                ':time' => $time,
                ':duration' => $service['duration'],
                ':price' => $service['price'],
                ':notes' => $notes
            ]);
            
            $appointmentId = $conn->lastInsertId();
            
            // Create notification - this part is wrapped in its own try-catch
            try {
                // Use the new notification utility function
                $notificationMessage = 'Your appointment has been booked successfully!';
                $emailSubject = 'Harmony Heaven Spa - Appointment Confirmation';
                $additionalMessage = 'Thank you for booking with Harmony Heaven Spa. We look forward to serving you!';
                
                $notificationResult = sendAppointmentNotification(
                    $conn,
                    $customerId,
                    $appointmentId,
                    'appointment_confirmation',
                    $notificationMessage,
                    $emailSubject,
                    $additionalMessage
                );
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Notification result: " . print_r($notificationResult, true) . "\n", FILE_APPEND);
            } catch (Exception $e) {
                // Log the error but don't fail the appointment creation
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error creating notification: " . $e->getMessage() . "\n", FILE_APPEND);
                error_log('Error creating notification: ' . $e->getMessage());
                // We'll still continue with the appointment creation
            }
            
            // Commit transaction
            $conn->commit();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Committed database transaction\n", FILE_APPEND);
            
            // Validate we actually got an appointment ID
            if (empty($appointmentId)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - WARNING: Empty appointment ID after insert\n", FILE_APPEND);
                $appointmentId = 0; // Fallback to prevent JSON errors
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Successfully created appointment ID: $appointmentId\n", FILE_APPEND);
            }
            
            // Return success response
            $response = [
                'success' => true,
                'message' => 'Appointment booked successfully!',
                'appointment_id' => $appointmentId
            ];
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Sending success response: " . json_encode($response) . "\n", FILE_APPEND);
            echo json_encode($response);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Rolled back database transaction\n", FILE_APPEND);
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error in inner try block: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e; // Re-throw to be caught by outer try-catch
        }
        
    } catch (Exception $e) {
        // Return error response
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error in outer try block: " . $e->getMessage() . "\n", FILE_APPEND);
        
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Sending error response: " . json_encode($errorResponse) . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode($errorResponse);
    }
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - handleCreateAppointment() finished\n", FILE_APPEND);
}
?> 