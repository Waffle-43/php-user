<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - direct connection for simplicity
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

// For testing purposes, we'll use a hardcoded customer ID
$customerId = 1;

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get appointments with service and stylist details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            st.name as stylist_name
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            stylists st ON a.stylist_id = st.id
        WHERE 
            a.customer_id = :customer_id
        ORDER BY 
            a.appointment_date DESC, 
            a.appointment_time DESC
    ");
    
    $stmt->execute([':customer_id' => $customerId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get notifications
    $notifStmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = :user_id 
        AND is_read = 0
        ORDER BY created_at DESC
    ");
    
    $notifStmt->execute([':user_id' => $customerId]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    $notificationCount = count($notifications);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $appointments = [];
    $notifications = [];
    $notificationCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-spa text-pink-500 text-2xl"></i>
                <span class="text-xl font-bold text-gray-800">Harmony Heaven Spa</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="appointment_ui.php" class="text-gray-600 hover:text-pink-500">
                    <i class="fas fa-calendar-plus mr-1"></i> Book New Appointment
                </a>
                <div class="relative">
                    <button id="notifications-btn" class="text-gray-600 hover:text-pink-500">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                <?= $notificationCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notifications Dropdown -->
                    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50">
                        <div class="p-4 border-b">
                            <h3 class="font-medium">Notifications</h3>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <?php if ($notificationCount > 0): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="p-4 border-b hover:bg-gray-50">
                                        <p class="text-gray-800"><?= htmlspecialchars($notification['message']) ?></p>
                                        <p class="text-gray-500 text-sm mt-1">
                                            <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-gray-500 text-center">
                                    No new notifications
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">My Appointments</h1>
            
            <div class="bg-white rounded-lg shadow-lg p-6 fade-in">
                <div id="appointments-container">
                    <?php if (isset($error)): ?>
                        <div class="text-center py-8">
                            <p class="text-red-500">Error: <?= htmlspecialchars($error) ?></p>
                            <a href="appointments_pdo.php" class="mt-4 inline-block px-6 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                                Retry
                            </a>
                        </div>
                    <?php elseif (empty($appointments)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">You don't have any appointments yet.</p>
                            <a href="appointment_ui.php" class="mt-4 inline-block px-6 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                                Book an Appointment
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <?php
                                // Determine status color
                                $statusColor = '';
                                switch ($appointment['status']) {
                                    case 'pending': $statusColor = 'bg-yellow-100 text-yellow-800'; break;
                                    case 'confirmed': $statusColor = 'bg-green-100 text-green-800'; break;
                                    case 'completed': $statusColor = 'bg-blue-100 text-blue-800'; break;
                                    case 'cancelled': $statusColor = 'bg-red-100 text-red-800'; break;
                                }
                                
                                // Format date and time
                                $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
                                $timeStart = date('g:i A', strtotime($appointment['appointment_time']));
                                $timeEnd = date('g:i A', strtotime($appointment['appointment_time']) + $appointment['duration'] * 60);
                                
                                // Check if cancellation is allowed (24+ hours in advance)
                                $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                                $now = new DateTime();
                                $interval = $now->diff($appointmentDateTime);
                                $hoursUntilAppointment = ($interval->days * 24) + $interval->h;
                                
                                $canCancel = $hoursUntilAppointment >= 24 && 
                                            $appointmentDateTime > $now && 
                                            $appointment['status'] !== 'cancelled' && 
                                            $appointment['status'] !== 'completed';
                            ?>
                            <div class="appointment-card bg-white border border-gray-200 rounded-lg p-4 mb-4 transition-all">
                                <div class="flex flex-col md:flex-row justify-between">
                                    <div>
                                        <div class="flex items-center mb-2">
                                            <span class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($appointment['service_name']) ?></span>
                                            <span class="ml-3 px-2 py-1 rounded text-xs font-medium <?= $statusColor ?>">
                                                <?= ucfirst($appointment['status']) ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-600 mb-2">
                                            <i class="far fa-calendar-alt mr-2 text-pink-500"></i>
                                            <?= $formattedDate ?>
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="far fa-clock mr-2 text-pink-500"></i>
                                            <?= $timeStart ?> - <?= $timeEnd ?>
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-user-tie mr-2 text-pink-500"></i>
                                            <?= htmlspecialchars($appointment['stylist_name']) ?>
                                        </p>
                                    </div>
                                    <div class="mt-4 md:mt-0">
                                        <p class="text-gray-600 mb-1">
                                            <i class="fas fa-dollar-sign mr-2 text-pink-500"></i>
                                            $<?= number_format($appointment['price'], 2) ?>
                                        </p>
                                        <p class="text-gray-600">
                                            <i class="fas fa-clock mr-2 text-pink-500"></i>
                                            <?= $appointment['duration'] ?> minutes
                                        </p>
                                        
                                        <?php if ($canCancel): ?>
                                            <button 
                                                onclick="cancelAppointment(<?= $appointment['id'] ?>)" 
                                                class="mt-4 px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200"
                                            >
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <p class="text-gray-700 text-sm">
                                            <i class="fas fa-sticky-note mr-2 text-pink-500"></i>
                                            <span class="font-medium">Notes:</span> <?= htmlspecialchars($appointment['notes']) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle notifications dropdown
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        
        notificationsBtn.addEventListener('click', () => {
            notificationsDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
                notificationsDropdown.classList.add('hidden');
            }
        });

        // Function to cancel an appointment
        function cancelAppointment(appointmentId) {
            if (!confirm('Are you sure you want to cancel this appointment?')) {
                return;
            }
            
            fetch('cancel_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `appointment_id=${appointmentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment cancelled successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error cancelling appointment:', error);
                alert('Failed to cancel appointment. Please try again.');
            });
        }
    </script>
</body>
</html> 