<?php
// Start session
session_start();

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

// For development purposes, use a hardcoded customer ID if session is not set
$customerId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check structure of notifications table
    $checkColumns = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'notifications'
    ");
    $checkColumns->execute();
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    
    // Try to get notifications using the appropriate structure
    if (in_array('recipient_type', $columns) && in_array('recipient_id', $columns)) {
        // New structure with recipient_type and recipient_id
        $stmt = $conn->prepare("
            SELECT n.*, a.service_id, a.appointment_date, a.appointment_time,
                   s.name as service_name 
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE n.recipient_type = 'customer' AND n.recipient_id = :recipient_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':recipient_id' => $customerId]);
    }
    else if (in_array('user_id', $columns)) {
        // Old structure with user_id
        $stmt = $conn->prepare("
            SELECT n.*, a.service_id, a.appointment_date, a.appointment_time,
                   s.name as service_name 
            FROM notifications n
            LEFT JOIN appointments a ON n.related_id = a.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':user_id' => $customerId]);
    }
    else {
        // Fall back to a simplistic approach if structure is unknown
        throw new Exception("Notifications table structure is not compatible");
    }
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark all as read if requested
    if (isset($_POST['mark_all_read'])) {
        if (in_array('recipient_type', $columns) && in_array('recipient_id', $columns)) {
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE recipient_type = 'customer' AND recipient_id = :recipient_id
            ");
            $stmt->execute([':recipient_id' => $customerId]);
        }
        else if (in_array('user_id', $columns)) {
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $customerId]);
        }
        
        // Redirect to refresh page
        header('Location: all_notifications.php');
        exit;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-purple-600 to-blue-500 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-spa text-2xl mr-2"></i>
                <span class="text-xl font-bold">Harmony Heaven Spa</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="appointments.php" class="text-white hover:text-pink-200">
                    <i class="fas fa-calendar-check mr-1"></i> My Appointments
                </a>
                <a href="appointment.php" class="text-white hover:text-pink-200">
                    <i class="fas fa-calendar-plus mr-1"></i> Book Appointment
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">All Notifications</h1>
            
            <form method="post">
                <button type="submit" name="mark_all_read" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                    Mark All as Read
                </button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-bell-slash text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">You don't have any notifications yet.</p>
                <a href="appointment.php" class="inline-block mt-4 px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                    Book an Appointment
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    // Determine color and icon based on notification type
                    $type = $notification['type'] ?? '';
                    $iconClass = 'fas fa-bell';
                    $bgColorClass = 'bg-blue-100';
                    $textColorClass = 'text-blue-600';
                    
                    if (strpos($type, 'confirmation') !== false || strpos($type, 'booked') !== false) {
                        $iconClass = 'fas fa-check-circle';
                        $bgColorClass = 'bg-green-100';
                        $textColorClass = 'text-green-600';
                    } else if (strpos($type, 'cancellation') !== false || strpos($type, 'cancelled') !== false) {
                        $iconClass = 'fas fa-times-circle';
                        $bgColorClass = 'bg-red-100';
                        $textColorClass = 'text-red-600';
                    } else if (strpos($type, 'reminder') !== false) {
                        $iconClass = 'fas fa-clock';
                        $bgColorClass = 'bg-yellow-100';
                        $textColorClass = 'text-yellow-600';
                    } else if (strpos($type, 'modification') !== false || strpos($type, 'rescheduled') !== false) {
                        $iconClass = 'fas fa-calendar-alt';
                        $bgColorClass = 'bg-purple-100';
                        $textColorClass = 'text-purple-600';
                    }
                    
                    // Format time
                    $createdTime = new DateTime($notification['created_at']);
                    $now = new DateTime();
                    $interval = $now->diff($createdTime);
                    
                    if ($interval->days > 0) {
                        if ($interval->days == 1) {
                            $timeAgo = 'Yesterday';
                        } else {
                            $timeAgo = $interval->days . ' days ago';
                        }
                    } elseif ($interval->h > 0) {
                        $timeAgo = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                    } elseif ($interval->i > 0) {
                        $timeAgo = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                    } else {
                        $timeAgo = 'Just now';
                    }
                    ?>
                    <div class="p-4 border-b hover:bg-gray-50 <?= $notification['is_read'] ? 'opacity-70' : '' ?>">
                        <div class="flex items-start">
                            <div class="<?= $bgColorClass ?> p-3 rounded-full mr-4">
                                <i class="<?= $iconClass ?> <?= $textColorClass ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-gray-800 font-medium">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </p>
                                        
                                        <?php if (!empty($notification['appointment_date'])): ?>
                                        <p class="text-gray-600 text-sm mt-1">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <?= date('F j, Y', strtotime($notification['appointment_date'])) ?>
                                            
                                            <?php if (!empty($notification['appointment_time'])): ?>
                                                <i class="far fa-clock ml-2 mr-1"></i>
                                                <?= date('g:i A', strtotime($notification['appointment_time'])) ?>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($notification['service_name'])): ?>
                                                <i class="fas fa-spa ml-2 mr-1"></i>
                                                <?= htmlspecialchars($notification['service_name']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-gray-500 text-sm ml-4"><?= $timeAgo ?></span>
                                </div>
                                
                                <?php if (!empty($notification['related_id']) || !empty($notification['appointment_id'])): ?>
                                <div class="mt-2">
                                    <a href="appointments.php" class="text-blue-500 hover:text-blue-600 text-sm">
                                        View Appointment
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$notification['is_read']): ?>
                            <span class="h-2 w-2 bg-blue-500 rounded-full"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-gray-800 text-white p-6 mt-12">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row justify-between">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-2">Harmony Heaven Spa</h3>
                    <p class="text-gray-400">Your sanctuary for relaxation and rejuvenation</p>
                </div>
                <div>
                    <h4 class="text-lg font-medium mb-2">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="appointment.php" class="text-gray-400 hover:text-white">Book Appointment</a></li>
                        <li><a href="appointments.php" class="text-gray-400 hover:text-white">My Appointments</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Services</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-4 border-t border-gray-700 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> Harmony Heaven Spa. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 