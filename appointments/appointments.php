<?php
// Start session
session_start();

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

// Let's also set up a session user_id for consistency
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $customerId;
}

require_once 'connect.php';

// Helper function to get CSS class for appointment status
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'text-yellow-600 font-medium';
        case 'confirmed':
            return 'text-green-600 font-medium';
        case 'cancelled':
            return 'text-red-600 font-medium';
        case 'completed':
            return 'text-blue-600 font-medium';
        case 'rescheduled':
            return 'text-purple-600 font-medium';
        default:
            return 'text-gray-600';
    }
}

try {
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get appointments with service and stylist details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            s.id as service_id,
            s.price,
            st.name as stylist_name,
            st.id as stylist_id
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

// Handle reschedule appointment
if (isset($_POST['reschedule_appointment'])) {
    $appointmentId = $_POST['appointment_id'];
    $newDate = $_POST['new_date'];
    $newTime = $_POST['new_time'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update the appointment with new date and time
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET appointment_date = :date, 
                appointment_time = :time, 
                status = 'rescheduled'
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':date' => $newDate,
            ':time' => $newTime,
            ':id' => $appointmentId
        ]);
        
        // Create notification about rescheduled appointment
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, type, message, related_id, is_read, created_at
            ) VALUES (
                :user_id, 'appointment_rescheduled', :message, :related_id, 0, NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':message' => 'Your appointment has been rescheduled successfully!',
            ':related_id' => $appointmentId
        ]);
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = 'Appointment rescheduled successfully!';
        header('Location: appointments.php');
        exit;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle cancel appointment
if (isset($_POST['cancel_appointment'])) {
    $appointmentId = $_POST['appointment_id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update appointment status to canceled
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = 'cancelled'
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $appointmentId
        ]);
        
        // Create notification about canceled appointment
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, type, message, related_id, is_read, created_at
            ) VALUES (
                :user_id, 'appointment_cancelled', :message, :related_id, 0, NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':message' => 'Your appointment has been cancelled.',
            ':related_id' => $appointmentId
        ]);
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = 'Appointment cancelled successfully!';
        header('Location: appointments.php');
        exit;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
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
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f9fafb;
        }
        
        .appointment-card {
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background-color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-cancelled {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        
        .status-confirmed {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .status-completed {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 500;
            display: inline-block;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-cancel {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .btn-cancel:hover {
            background-color: #fecaca;
        }
        
        .btn-reschedule {
            background-color: #dbeafe;
            color: #1e40af;
            margin-left: 0.5rem;
        }
        
        .btn-reschedule:hover {
            background-color: #bfdbfe;
        }
        
        .pink-icon {
            color: #ec4899;
            width: 1.5rem;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .appointment-info {
            margin: 0.5rem 0;
        }
        
        .animated-indicator {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md p-4">
        <div class="container mx-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-spa text-pink-500 text-2xl"></i>
                    <span class="text-xl font-bold text-gray-800">Harmony Heaven Spa</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="appointments" class="text-gray-600 hover:text-pink-500">
                        <i class="fas fa-calendar-plus mr-1"></i> Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">My Appointments</h1>
        
        <?php if (isset($error)): ?>
            <div class="text-center py-8 bg-white rounded-lg shadow">
                <p class="text-red-500">Error: <?= htmlspecialchars($error) ?></p>
                <a href="appointments.php" class="mt-4 inline-block px-6 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                    Retry
                </a>
            </div>
        <?php elseif (empty($appointments)): ?>
            <div class="text-center py-8 bg-white rounded-lg shadow">
                <p class="text-gray-500">You don't have any appointments yet.</p>
                <a href="appointment_ui.php" class="mt-4 inline-block px-6 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                    Book an Appointment
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                        // Debug timestamp information
                        //echo "<!-- Appointment date: " . $appointment['appointment_date'] . " " . $appointment['appointment_time'] . " -->";
                        //echo "<!-- Current date: " . date('Y-m-d H:i:s') . " -->";
                        
                        // Determine status class
                        $statusClass = '';
                        switch ($appointment['status']) {
                            case 'pending': $statusClass = 'status-pending'; break;
                            case 'confirmed': $statusClass = 'status-confirmed'; break;
                            case 'completed': $statusClass = 'status-completed'; break;
                            case 'cancelled': $statusClass = 'status-cancelled'; break;
                        }
                        
                        // Format date and time
                        $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
                        $timeStart = date('g:i A', strtotime($appointment['appointment_time']));
                        $timeEnd = date('g:i A', strtotime($appointment['appointment_time']) + $appointment['duration'] * 60);
                        
                        // Check if appointment is in the future - more reliable method
                        $now = new DateTime();
                        $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                        $isFuture = $appointmentDateTime > $now;
                        
                        // Appointment can be cancelled if it's more than 24 hours away
                        $interval = $now->diff($appointmentDateTime);
                        $hoursUntilAppointment = ($interval->days * 24) + $interval->h;
                        $canCancel = $hoursUntilAppointment >= 24 && 
                                    $isFuture && 
                                    $appointment['status'] !== 'cancelled' && 
                                    $appointment['status'] !== 'completed';
                        
                        // Appointment can be rescheduled if it's in the future and not cancelled/completed
                        $canReschedule = $isFuture && 
                                       $appointment['status'] !== 'cancelled' && 
                                       $appointment['status'] !== 'completed';

                        // Make sure service_id and stylist_id are set
                        $service_id = isset($appointment['service_id']) ? $appointment['service_id'] : 0;
                        $stylist_id = isset($appointment['stylist_id']) ? $appointment['stylist_id'] : 0;
                    ?>
                    <div class="appointment-card">
                        <div class="flex items-center">
                            <h2 class="text-xl font-semibold"><?= htmlspecialchars($appointment['service_name']) ?></h2>
                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($appointment['status']) ?></span>
                        </div>
                        
                        <div class="my-4 space-y-2">
                            <p class="appointment-info">
                                <i class="far fa-calendar-alt pink-icon"></i>
                                <?= $formattedDate ?>
                            </p>
                            <p class="appointment-info">
                                <i class="far fa-clock pink-icon"></i>
                                <?= $timeStart ?> - <?= $timeEnd ?>
                            </p>
                            <p class="appointment-info">
                                <i class="fas fa-user-tie pink-icon"></i>
                                <?= htmlspecialchars($appointment['stylist_name']) ?>
                            </p>
                            <p class="appointment-info">
                                <i class="fas fa-dollar-sign pink-icon"></i>
                                $<?= number_format($appointment['price'], 2) ?>
                            </p>
                            <p class="appointment-info">
                                <i class="far fa-clock pink-icon"></i>
                                <?= $appointment['duration'] ?> minutes
                            </p>
                        </div>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                Status: 
                                <span class="<?= getStatusClass($appointment['status']) ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </span>
                            <?php if ($appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
                                <div class="flex space-x-2">
                                    <button 
                                        class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600"
                                        onclick="openRescheduleModal(<?= $appointment['id'] ?>, '<?= $appointment['appointment_date'] ?>', '<?= $appointment['appointment_time'] ?>')">
                                        Reschedule
                                    </button>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <button 
                                            type="submit"
                                            name="cancel_appointment"
                                            class="bg-red-500 text-white px-3 py-1 rounded-md text-sm hover:bg-red-600">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-8 p-4 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Need Help?</h2>
            <p class="text-gray-600 mb-4">Having trouble with your appointments? Try our diagnostic tool:</p>
            <a href="appointment_status_check.php" class="inline-block px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Appointment Status Check Tool
            </a>
        </div>
    </main>

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
        
        // Function to reschedule appointment
        function rescheduleAppointment(appointmentId, serviceName, duration, price, serviceId, stylistId) {
            // Debug output to console
            console.log('Reschedule button clicked with these values:', {
                appointmentId,
                serviceName, 
                duration,
                price,
                serviceId,
                stylistId
            });
            
            // Store appointment details in sessionStorage
            sessionStorage.setItem('reschedule_appointment_id', appointmentId);
            sessionStorage.setItem('reschedule_service_name', serviceName);
            sessionStorage.setItem('reschedule_duration', duration);
            sessionStorage.setItem('reschedule_price', price);
            sessionStorage.setItem('reschedule_service_id', serviceId);
            sessionStorage.setItem('reschedule_stylist_id', stylistId);
            
            // Redirect to reschedule page
            window.location.href = 'reschedule_appointment.php';
        }
    </script>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h2 class="text-2xl font-bold mb-4">Reschedule Appointment</h2>
            
            <form method="post" id="rescheduleForm">
                <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
                
                <div class="mb-4">
                    <label for="new_date" class="block text-gray-700 font-medium mb-2">New Date</label>
                    <input 
                        type="date" 
                        id="new_date" 
                        name="new_date" 
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                
                <div class="mb-4">
                    <label for="new_time" class="block text-gray-700 font-medium mb-2">New Time</label>
                    <input 
                        type="time" 
                        id="new_time" 
                        name="new_time" 
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button 
                        type="button" 
                        onclick="closeRescheduleModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        name="reschedule_appointment"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                    >
                        Reschedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to open the reschedule modal
        function openRescheduleModal(appointmentId, currentDate, currentTime) {
            document.getElementById('reschedule_appointment_id').value = appointmentId;
            document.getElementById('new_date').value = currentDate;
            document.getElementById('new_time').value = currentTime.substring(0, 5); // Format HH:MM
            document.getElementById('rescheduleModal').classList.remove('hidden');
            document.getElementById('rescheduleModal').classList.add('flex');
        }
        
        // Function to close the reschedule modal
        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').classList.remove('flex');
            document.getElementById('rescheduleModal').classList.add('hidden');
        }
    </script>
</body>
</html> 