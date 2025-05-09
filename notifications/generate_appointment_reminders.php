<?php
/**
 * Appointment Reminder Generator Script
 * 
 * This script checks for upcoming appointments and sends reminder notifications
 * Should be scheduled to run automatically via cron job or task scheduler
 * 
 * Suggested cron setup: Run hourly
 * 0 * * * * php /path/to/your/website/generate_appointment_reminders.php
 */

// Display all errors for debugging when run manually
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include notification utilities
require_once 'notification_utils.php';

// Database configuration
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

// Set up logging
$logFile = 'reminder_log.txt';
$isRunningFromCLI = (php_sapi_name() === 'cli');

function logMessage($message) {
    global $logFile, $isRunningFromCLI;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // If running from command line, output to console too
    if ($isRunningFromCLI) {
        echo $logEntry;
    }
}

// Log script start
logMessage("Appointment reminder generator started");

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current date and time
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
    
    logMessage("Checking for appointments on $today and $tomorrow");
    
    // Find appointments within the next 24 hours that haven't been reminded yet
    $stmt = $conn->prepare("
        SELECT a.*, c.id as customer_id, c.name as customer_name, c.email as customer_email,
               s.name as service_name, st.name as stylist_name, 
               CONCAT(a.appointment_date, ' ', a.appointment_time) as appointment_datetime
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        JOIN stylists st ON a.stylist_id = st.id
        WHERE (a.appointment_date = :today OR a.appointment_date = :tomorrow)
        AND a.status NOT IN ('cancelled', 'completed')
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE (
                (n.appointment_id = a.id OR n.related_id = a.id)
                AND n.type LIKE '%reminder%'
            )
        )
        ORDER BY a.appointment_date, a.appointment_time
    ");
    
    $stmt->execute([
        ':today' => $today,
        ':tomorrow' => $tomorrow
    ]);
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reminderCount = 0;
    
    foreach ($appointments as $appointment) {
        try {
            // Calculate how many hours until the appointment
            $apptDateTime = new DateTime($appointment['appointment_datetime']);
            $hoursUntil = round(($apptDateTime->getTimestamp() - $now->getTimestamp()) / 3600);
            
            // Only send reminders for appointments within 24 hours
            if ($hoursUntil <= 24 && $hoursUntil > 0) {
                // Prepare notification message
                $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
                $formattedTime = date('g:i A', strtotime($appointment['appointment_time']));
                
                $notificationMessage = "REMINDER: Your appointment for {$appointment['service_name']} on {$formattedDate} at {$formattedTime} is coming up soon.";
                $emailSubject = 'Harmony Heaven Spa - Appointment Reminder';
                
                // Choose appropriate message based on time until appointment
                if ($hoursUntil <= 3) {
                    $additionalMessage = "Your appointment is in less than 3 hours. We're looking forward to seeing you soon!";
                } else if ($hoursUntil <= 12) {
                    $additionalMessage = "Your appointment is later today. Please arrive 10 minutes early to complete check-in.";
                } else {
                    $additionalMessage = "This is a friendly reminder about your upcoming appointment tomorrow. Please call us if you need to reschedule.";
                }
                
                logMessage("Sending reminder for Appointment ID: {$appointment['id']} - {$hoursUntil} hours until appointment");
                
                // Send notification
                $result = sendAppointmentNotification(
                    $conn,
                    $appointment['customer_id'],
                    $appointment['id'],
                    'appointment_reminder',
                    $notificationMessage,
                    $emailSubject,
                    $additionalMessage
                );
                
                if ($result['database_notification']) {
                    $reminderCount++;
                    logMessage("Reminder sent successfully for appointment ID: {$appointment['id']}");
                } else {
                    logMessage("Failed to create reminder notification for appointment ID: {$appointment['id']}");
                }
            }
        } catch (Exception $e) {
            logMessage("Error processing appointment ID: {$appointment['id']} - " . $e->getMessage());
            continue; // Skip to next appointment if there's an error
        }
    }
    
    logMessage("Reminder generator completed. Sent $reminderCount reminders");
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    if ($isRunningFromCLI) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// If not in CLI mode, return success message
if (!$isRunningFromCLI) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Appointment reminder check completed. Sent $reminderCount reminders."
    ]);
}
?> 