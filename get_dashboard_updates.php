<?php
// API endpoint to get real-time dashboard updates
header('Content-Type: application/json');
include 'config.php';

// Check if stylist_id is provided
if (!isset($_GET['stylist_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Stylist ID is required'
    ]);
    exit;
}

$stylist_id = intval($_GET['stylist_id']);

// Get the current date and time
$today = date('Y-m-d');
$currentTime = date('H:i:s');
$twoHoursLater = date('H:i:s', strtotime('+2 hours'));

try {
    // Get upcoming appointments for the next 2 hours
    $upcomingAppointments = $conn->prepare("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            c.name as customer_name,
            s.name as service_name,
            s.duration
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND a.appointment_date = ?
            AND a.appointment_time BETWEEN ? AND ?
            AND a.status NOT IN ('cancelled', 'completed')
        ORDER BY 
            a.appointment_time ASC
    ");
    $upcomingAppointments->execute([$stylist_id, $today, $currentTime, $twoHoursLater]);
    $upcomingAppts = $upcomingAppointments->fetchAll(PDO::FETCH_ASSOC);
    
    // Get new notifications (last 5)
    $newNotifications = $conn->prepare("
        SELECT 
            id,
            type,
            message,
            related_id,
            is_read,
            created_at
        FROM 
            notifications
        WHERE 
            user_id = ?
            AND is_read = 0
        ORDER BY
            created_at DESC
        LIMIT 5
    ");
    $newNotifications->execute([$stylist_id]);
    $notifications = $newNotifications->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent changes (cancellations/reschedules in last 24 hours)
    $recentChanges = $conn->prepare("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            c.name as customer_name,
            s.name as service_name,
            CASE 
                WHEN a.status = 'cancelled' THEN 'Cancelled'
                ELSE 'Rescheduled'
            END as change_type,
            a.updated_at
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND (a.status = 'cancelled' OR a.status = 'rescheduled')
            AND a.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY 
            a.updated_at DESC
        LIMIT 3
    ");
    $recentChanges->execute([$stylist_id]);
    $changes = $recentChanges->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for JSON response
    $formattedUpcoming = [];
    foreach ($upcomingAppts as $appt) {
        $apptTime = date('g:i A', strtotime($appt['appointment_time']));
        $endTime = date('g:i A', strtotime($appt['appointment_time'] . ' +' . $appt['duration'] . ' minutes'));
        
        $formattedUpcoming[] = [
            'id' => $appt['id'],
            'customer_name' => $appt['customer_name'],
            'service_name' => $appt['service_name'],
            'time' => $apptTime . ' - ' . $endTime,
            'status' => $appt['status'],
            'url' => 'service_provider_manage_appointment.php?id=' . $appt['id'] . '&stylist_id=' . $stylist_id
        ];
    }
    
    $formattedNotifications = [];
    foreach ($notifications as $notification) {
        $icon = 'fa-bell';
        $colorClass = 'text-blue-500';
        
        if (strpos($notification['type'], 'cancel') !== false) {
            $icon = 'fa-calendar-xmark';
            $colorClass = 'text-red-500';
        } elseif (strpos($notification['type'], 'reschedule') !== false) {
            $icon = 'fa-calendar-plus';
            $colorClass = 'text-yellow-500';
        } elseif (strpos($notification['type'], 'new') !== false || strpos($notification['type'], 'book') !== false) {
            $icon = 'fa-calendar-plus';
            $colorClass = 'text-green-500';
        } elseif (strpos($notification['type'], 'reminder') !== false) {
            $icon = 'fa-clock';
            $colorClass = 'text-purple-500';
        }
        
        $formattedNotifications[] = [
            'id' => $notification['id'],
            'message' => $notification['message'],
            'time' => date('M d, g:i A', strtotime($notification['created_at'])),
            'icon' => $icon,
            'color_class' => $colorClass,
            'appointment_id' => isset($notification['related_id']) ? $notification['related_id'] : null,
            'url' => (isset($notification['related_id']) && !empty($notification['related_id'])) ? 
                    'service_provider_manage_appointment.php?id=' . $notification['related_id'] . '&stylist_id=' . $stylist_id : null
        ];
    }
    
    $formattedChanges = [];
    foreach ($changes as $change) {
        $formattedChanges[] = [
            'id' => $change['id'],
            'customer_name' => $change['customer_name'],
            'service_name' => $change['service_name'],
            'date' => date('M d', strtotime($change['appointment_date'])),
            'time' => date('g:i A', strtotime($change['appointment_time'])),
            'change_type' => $change['change_type'],
            'updated_at' => date('M d, g:i A', strtotime($change['updated_at'])),
            'url' => 'service_provider_manage_appointment.php?id=' . $change['id'] . '&stylist_id=' . $stylist_id
        ];
    }
    
    // Return success response with the data
    echo json_encode([
        'success' => true,
        'data' => [
            'upcoming_appointments' => $formattedUpcoming,
            'upcoming_count' => count($formattedUpcoming),
            'notifications' => $formattedNotifications,
            'notification_count' => count($formattedNotifications),
            'recent_changes' => $formattedChanges,
            'changes_count' => count($formattedChanges),
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 