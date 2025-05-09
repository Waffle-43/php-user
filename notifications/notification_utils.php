<?php
// Utility functions for notifications and email sending

/**
 * Create a notification in the database
 * 
 * @param PDO $conn Database connection
 * @param string $recipientType Type of recipient ('customer' or 'stylist')
 * @param int $recipientId ID of the recipient
 * @param int $appointmentId ID of the related appointment
 * @param string $type Type of notification (confirmation, cancellation, etc.)
 * @param string $message Notification message
 * @return int|bool ID of the created notification or false on failure
 */
function createNotification($conn, $recipientType, $recipientId, $appointmentId, $type, $message) {
    try {
        // Check structure of notifications table
        $checkColumns = $conn->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'notifications'
        ");
        $checkColumns->execute();
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine which version of the notifications table we have
        if (in_array('recipient_type', $columns) && in_array('recipient_id', $columns)) {
            // New structure with recipient_type and recipient_id
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    recipient_type, recipient_id, appointment_id,
                    type, message, is_read, created_at
                ) VALUES (
                    :recipient_type, :recipient_id, :appointment_id,
                    :type, :message, 0, NOW()
                )
            ");
            
            $stmt->execute([
                ':recipient_type' => $recipientType,
                ':recipient_id' => $recipientId,
                ':appointment_id' => $appointmentId,
                ':type' => $type,
                ':message' => $message
            ]);
        } 
        else if (in_array('user_id', $columns) && in_array('related_id', $columns)) {
            // Old structure with user_id and related_id
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id, type, message,
                    related_id, is_read, created_at
                ) VALUES (
                    :user_id, :type, :message,
                    :related_id, 0, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $recipientId,
                ':type' => $type,
                ':message' => $message,
                ':related_id' => $appointmentId
            ]);
        } 
        else {
            // Fallback to a simpler version with whatever columns we can find
            $columnsArray = [];
            $values = [];
            
            // Add basic values that most notification tables should have
            if (in_array('message', $columns)) {
                $columnsArray[] = 'message';
                $values[':message'] = $message;
            }
            
            if (in_array('type', $columns)) {
                $columnsArray[] = 'type';
                $values[':type'] = $type;
            }
            
            if (in_array('is_read', $columns)) {
                $columnsArray[] = 'is_read';
                $values[':is_read'] = 0;
            }
            
            if (in_array('created_at', $columns)) {
                $columnsArray[] = 'created_at';
                $values[':created_at'] = date('Y-m-d H:i:s');
            }
            
            // User identifier - try various column names
            foreach (['recipient_id', 'user_id', 'customer_id'] as $userCol) {
                if (in_array($userCol, $columns)) {
                    $columnsArray[] = $userCol;
                    $values[':' . $userCol] = $recipientId;
                    break;
                }
            }
            
            // Appointment identifier - try various column names
            foreach (['appointment_id', 'related_id', 'reference_id'] as $apptCol) {
                if (in_array($apptCol, $columns)) {
                    $columnsArray[] = $apptCol;
                    $values[':' . $apptCol] = $appointmentId;
                    break;
                }
            }
            
            // Only attempt to insert if we have columns
            if (!empty($columnsArray)) {
                $columnsString = implode(', ', $columnsArray);
                $placeholders = implode(', ', array_keys($values));
                
                $stmt = $conn->prepare("
                    INSERT INTO notifications ($columnsString)
                    VALUES ($placeholders)
                ");
                
                $stmt->execute($values);
            } else {
                return false;
            }
        }
        
        return $conn->lastInsertId();
    } catch (Exception $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send an email notification
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML or plain text)
 * @param string $from Sender email address
 * @return bool True if email sent successfully, false otherwise
 */
function sendEmailNotification($to, $subject, $message, $from = '') {
    // Default sender if not provided
    if (empty($from)) {
        $from = 'noreply@harmonyheaven.com';
    }
    
    // Set headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Harmony Heaven Spa <" . $from . ">" . "\r\n";
    
    // Log the email for debugging in development
    error_log("Email to: $to, Subject: $subject, From: $from");
    error_log("Message: $message");
    
    // In production, uncomment this line to actually send emails
    // return mail($to, $subject, $message, $headers);
    
    // For development, just return true and log instead of sending
    return true;
}

/**
 * Generate an HTML email template for appointments
 * 
 * @param array $appointment Appointment details
 * @param string $type Type of email (confirmation, cancellation, etc.)
 * @param string $additionalMessage Any additional message to include
 * @return string HTML content for email
 */
function generateAppointmentEmailTemplate($appointment, $type, $additionalMessage = '') {
    // Format appointment date and time
    $dateFormatted = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $timeFormatted = date('g:i A', strtotime($appointment['appointment_time']));
    
    // Set email color theme based on type
    $mainColor = '#a21caf'; // Default purple
    $actionText = 'View Appointment';
    $headerText = 'Appointment Details';
    
    switch ($type) {
        case 'confirmation':
            $mainColor = '#16a34a'; // Green
            $headerText = 'Appointment Confirmed';
            break;
        case 'cancellation':
            $mainColor = '#dc2626'; // Red
            $headerText = 'Appointment Cancelled';
            break;
        case 'rescheduled':
            $mainColor = '#2563eb'; // Blue
            $headerText = 'Appointment Rescheduled';
            break;
        case 'reminder':
            $mainColor = '#f59e0b'; // Amber
            $headerText = 'Appointment Reminder';
            break;
    }
    
    // Build the HTML email
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $headerText . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: ' . $mainColor . ';
                padding: 20px;
                color: white;
                text-align: center;
            }
            .content {
                background: #fff;
                padding: 20px;
                border: 1px solid #ddd;
            }
            .details {
                margin: 20px 0;
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
            }
            .detail-row {
                margin-bottom: 10px;
            }
            .detail-label {
                font-weight: bold;
                color: #555;
            }
            .button {
                display: inline-block;
                background-color: ' . $mainColor . ';
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 4px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . $headerText . '</h1>
            </div>
            <div class="content">
                <p>Dear ' . ($appointment['customer_name'] ?? 'Customer') . ',</p>';
    
    // Add type-specific message
    switch ($type) {
        case 'confirmation':
            $html .= '<p>Your appointment has been confirmed. We look forward to seeing you!</p>';
            break;
        case 'cancellation':
            $html .= '<p>Your appointment has been cancelled. If you did not request this cancellation, please contact us.</p>';
            break;
        case 'rescheduled':
            $html .= '<p>Your appointment has been rescheduled to the date and time shown below.</p>';
            break;
        case 'reminder':
            $html .= '<p>This is a friendly reminder about your upcoming appointment.</p>';
            break;
        default:
            $html .= '<p>Here are your appointment details.</p>';
    }
    
    // Add additional message if provided
    if (!empty($additionalMessage)) {
        $html .= '<p>' . $additionalMessage . '</p>';
    }
    
    // Add appointment details
    $html .= '
                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">Service:</span> ' . ($appointment['service_name'] ?? 'N/A') . '
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span> ' . $dateFormatted . '
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time:</span> ' . $timeFormatted . '
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span> ' . ($appointment['duration'] ?? 'N/A') . ' minutes
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Stylist:</span> ' . ($appointment['stylist_name'] ?? 'N/A') . '
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span> ' . ucfirst($appointment['status'] ?? 'pending') . '
                    </div>
                </div>
                
                <p>If you need to make any changes to your appointment, please visit our website or contact us.</p>
                
                <div style="text-align: center;">
                    <a href="https://yourwebsite.com/appointments.php" class="button">' . $actionText . '</a>
                </div>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Harmony Heaven Spa. All rights reserved.</p>
                <p>If you have any questions, please contact us at info@harmonyheaven.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Get customer email by customer ID
 * 
 * @param PDO $conn Database connection
 * @param int $customerId Customer ID
 * @return string|null Customer email or null if not found
 */
function getCustomerEmail($conn, $customerId) {
    try {
        $stmt = $conn->prepare("
            SELECT email FROM customers 
            WHERE id = :customer_id
        ");
        $stmt->execute([':customer_id' => $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['email'] : null;
    } catch (Exception $e) {
        error_log('Error getting customer email: ' . $e->getMessage());
        return null;
    }
}

/**
 * Send appointment notification via both database and email
 * 
 * @param PDO $conn Database connection
 * @param int $customerId Customer ID
 * @param int $appointmentId Appointment ID
 * @param string $type Notification type
 * @param string $message Notification message
 * @param string $emailSubject Email subject
 * @param string $additionalMessage Additional message for email
 * @return array Result array with database and email notification status
 */
function sendAppointmentNotification($conn, $customerId, $appointmentId, $type, $message, $emailSubject, $additionalMessage = '') {
    $result = [
        'database_notification' => false,
        'email_sent' => false
    ];
    
    try {
        // Create database notification
        $notificationId = createNotification(
            $conn, 
            'customer', 
            $customerId, 
            $appointmentId, 
            $type, 
            $message
        );
        
        $result['database_notification'] = ($notificationId !== false);
        
        // Get appointment details for email
        $stmt = $conn->prepare("
            SELECT a.*, c.name as customer_name, c.email as customer_email,
                   s.name as service_name, st.name as stylist_name
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN stylists st ON a.stylist_id = st.id
            WHERE a.id = :appointment_id
        ");
        
        $stmt->execute([':appointment_id' => $appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appointment) {
            // Get customer email - check if it's in the appointment data or get it separately
            $customerEmail = $appointment['customer_email'] ?? getCustomerEmail($conn, $customerId);
            
            if ($customerEmail) {
                // Generate email content
                $emailContent = generateAppointmentEmailTemplate($appointment, $type, $additionalMessage);
                
                // Send email
                $result['email_sent'] = sendEmailNotification($customerEmail, $emailSubject, $emailContent);
            }
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('Error sending appointment notification: ' . $e->getMessage());
        return $result;
    }
}
?> 