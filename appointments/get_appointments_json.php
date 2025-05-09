<?php
// Include database configuration
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Set headers for JSON response
header('Content-Type: application/json');

// Get stylist ID (required)
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : null;

if (!$stylist_id) {
    echo json_encode(['error' => 'Stylist ID is required']);
    exit;
}

// Optional filters
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;
$start_date = isset($_GET['start']) ? $_GET['start'] : null;
$end_date = isset($_GET['end']) ? $_GET['end'] : null;

try {
    // Build query
    $query = "
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.duration,
            a.status,
            a.notes,
            s.name as service_name,
            s.id as service_id,
            c.name as customer_name,
            c.id as customer_id
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = :stylist_id
    ";
    
    $params = [':stylist_id' => $stylist_id];
    
    // Add optional filters
    if ($service_id) {
        $query .= " AND a.service_id = :service_id";
        $params[':service_id'] = $service_id;
    }
    
    if ($start_date) {
        $query .= " AND a.appointment_date >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND a.appointment_date <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    $query .= " ORDER BY a.appointment_date, a.appointment_time";
    
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
    // Transform data for FullCalendar
    $events = [];
    foreach ($appointments as $appointment) {
        // Determine event classes based on service type and status
        $eventClasses = [];
        
        // Add service type class
        $serviceName = strtolower(explode(' ', $appointment['service_name'])[0]);
        if (in_array($serviceName, ['massage', 'facial', 'body', 'hair', 'nail'])) {
            $eventClasses[] = $serviceName . '-service'; 
        } else {
            // Default fallback based on service_id
            $serviceCategories = [
                1 => 'hair-service',
                2 => 'hair-service',
                3 => 'nail-service',
                4 => 'nail-service',
                5 => 'facial-service'
            ];
            $eventClasses[] = $serviceCategories[$appointment['service_id']] ?? 'other-service';
        }
        
        // Add status class
        $eventClasses[] = 'status-' . $appointment['status'];
        
        // Format start and end times
        $start = $appointment['appointment_date'] . 'T' . $appointment['appointment_time'];
        $end_time = date('H:i:s', strtotime($appointment['appointment_time']) + ($appointment['duration'] * 60));
        $end = $appointment['appointment_date'] . 'T' . $end_time;
        
        $events[] = [
            'id' => $appointment['id'],
            'title' => htmlspecialchars($appointment['customer_name']) . ' - ' . htmlspecialchars($appointment['service_name']),
            'start' => $start,
            'end' => $end,
            'className' => $eventClasses,
            'extendedProps' => [
                'customer_id' => $appointment['customer_id'],
                'service_id' => $appointment['service_id'],
                'status' => $appointment['status'],
                'notes' => $appointment['notes']
            ]
        ];
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 