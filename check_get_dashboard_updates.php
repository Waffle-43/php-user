<?php
// This script checks if get_dashboard_updates.php exists and how it handles notifications

$file_path = 'get_dashboard_updates.php';

if (file_exists($file_path)) {
    echo "File exists: $file_path\n";
    $content = file_get_contents($file_path);
    
    // Check if the file contains logic for notifications
    if (strpos($content, 'notification') !== false) {
        echo "File contains notification handling logic.\n";
        
        // Look for url generation with appointment_id
        if (preg_match('/appointment_id|url.*appointment/i', $content)) {
            echo "File appears to generate URLs with appointment_id.\n";
            
            // Suggest fixing it
            echo "Recommendation: The get_dashboard_updates.php file should be updated to only include appointment URLs when the appointment_id is available.\n";
        } else {
            echo "File doesn't appear to generate URLs with appointment_id.\n";
        }
    } else {
        echo "File does not contain notification handling logic.\n";
    }
} else {
    echo "File does not exist: $file_path\n";
}

// Check update_notification.php as well
$update_file = 'update_notification.php';
if (file_exists($update_file)) {
    echo "\nFile exists: $update_file\n";
    $content = file_get_contents($update_file);
    
    // Check if this file processes notifications
    if (strpos($content, 'notification') !== false) {
        echo "File contains notification processing logic.\n";
        
        // Look for appointment_id access
        if (preg_match('/\$notification\[([\'"])appointment_id\\1\]/', $content)) {
            echo "File appears to access notification['appointment_id'].\n";
            echo "Recommendation: The $update_file file should be updated to check if appointment_id exists before accessing it.\n";
        }
    }
}
?> 