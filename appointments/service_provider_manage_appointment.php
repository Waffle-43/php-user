<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Get appointment ID and stylist ID
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

if (!$appointment_id) {
    die("Appointment ID is required");
}

// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $notifyCustomer = isset($_POST['notify_customer']) ? 1 : 0;
    
    try {
        // Update appointment status and notes
        $updateStmt = $conn->prepare("
            UPDATE appointments 
            SET status = ?, notes = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$status, $notes, $appointment_id]);
        
        // If notifying customer, create a notification
        if ($notifyCustomer) {
            // Get appointment details for notification
            $apptStmt = $conn->prepare("
                SELECT a.*, c.id as customer_id, c.name as customer_name, s.name as service_name
                FROM appointments a
                JOIN customers c ON a.customer_id = c.id
                JOIN services s ON a.service_id = s.id
                WHERE a.id = ?
            ");
            $apptStmt->execute([$appointment_id]);
            $appt = $apptStmt->fetch();
            
            // Create notification message based on status
            $message = '';
            switch ($status) {
                case 'confirmed':
                    $message = "Your appointment for {$appt['service_name']} on " . date('F j, Y', strtotime($appt['appointment_date'])) . " at " . date('g:i A', strtotime($appt['appointment_time'])) . " has been confirmed.";
                    $notifType = 'appointment_confirmation';
                    break;
                case 'cancelled':
                    $message = "Your appointment for {$appt['service_name']} on " . date('F j, Y', strtotime($appt['appointment_date'])) . " at " . date('g:i A', strtotime($appt['appointment_time'])) . " has been cancelled.";
                    $notifType = 'appointment_cancellation';
                    break;
                case 'completed':
                    $message = "Your appointment for {$appt['service_name']} has been marked as completed. Thank you for visiting us!";
                    $notifType = 'appointment_completion';
                    break;
                default:
                    $message = "There has been an update to your appointment for {$appt['service_name']} on " . date('F j, Y', strtotime($appt['appointment_date'])) . " at " . date('g:i A', strtotime($appt['appointment_time'])) . ".";
                    $notifType = 'appointment_modification';
            }
            
            // Insert notification
            $notifStmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, message, related_id, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
            ");
            $notifStmt->execute([$appt['customer_id'], $notifType, $message, $appointment_id]);
        }
        
        // Redirect back with success message
        header("Location: service_provider_manage_appointment.php?id={$appointment_id}&stylist_id={$stylist_id}&success=1");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get appointment details
try {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            s.price,
            s.duration as service_duration,
            s.description as service_description,
            st.name as stylist_name,
            st.id as stylist_id,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            stylists st ON a.stylist_id = st.id
        JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.id = ?
    ");
    
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        die("Appointment not found");
    }
    
    // Check if this appointment is for the specified stylist
    if ($appointment['stylist_id'] != $stylist_id) {
        // Optionally redirect to the correct stylist's page
        header("Location: service_provider_manage_appointment.php?id={$appointment_id}&stylist_id={$appointment['stylist_id']}");
        exit;
    }
    
    // Format date and time for display
    $appointment['formatted_date'] = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $appointment['formatted_time'] = date('g:i A', strtotime($appointment['appointment_time']));
    $appointment['end_time'] = date('g:i A', strtotime($appointment['appointment_time']) + $appointment['duration'] * 60);
    
    // Check for overlapping appointments
    $overlapStmt = $conn->prepare("
        SELECT 
            a.id, 
            a.appointment_time,
            a.duration,
            s.name as service_name,
            c.name as customer_name
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ? 
            AND a.appointment_date = ?
            AND a.id != ?
            AND a.status != 'cancelled'
            AND (
                (a.appointment_time <= ? AND TIME(ADDTIME(a.appointment_time, SEC_TO_TIME(a.duration * 60))) > ?)
                OR
                (a.appointment_time < TIME(ADDTIME(?, SEC_TO_TIME(? * 60))) AND a.appointment_time >= ?)
            )
        ORDER BY 
            a.appointment_time
    ");
    
    $overlapStmt->execute([
        $stylist_id,
        $appointment['appointment_date'],
        $appointment_id,
        $appointment['appointment_time'],
        $appointment['appointment_time'],
        $appointment['appointment_time'],
        $appointment['duration'],
        $appointment['appointment_time']
    ]);
    
    $overlappingAppointments = $overlapStmt->fetchAll();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointment - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar text-white w-64 flex-shrink-0">
            <div class="p-4 border-b border-indigo-400 border-opacity-20">
                <h1 class="text-xl font-bold flex items-center">
                    <i class="fas fa-spa mr-2"></i>
                    Harmony Heaven Spa
                </h1>
                <p class="text-xs text-indigo-100 mt-1">Therapist Portal</p>
            </div>
            <nav class="p-4">
                <div class="mb-6">
                    <p class="text-xs uppercase text-indigo-200 mb-2">Navigation</p>
                    <a href="home" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-home mr-2"></i> Back to Homepage
                    </a>
                    <a href="service_provider_dashboard.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="service_provider_clients.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-users mr-2"></i> Clients
                    </a>
                    <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-concierge-bell mr-2"></i> Services
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center p-4">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-600">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="text-lg font-semibold">Manage Appointment</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm flex items-center hover:bg-gray-200">
                            <i class="fas fa-calendar-alt mr-1"></i> Back to Calendar
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>Appointment updated successfully.</p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Appointment Details Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="border-b pb-4 mb-4">
                            <h3 class="text-xl font-semibold"><?= htmlspecialchars($appointment['service_name']) ?></h3>
                            <p class="text-gray-500"><?= $appointment['formatted_date'] ?></p>
                            <p class="text-gray-500"><?= $appointment['formatted_time'] ?> - <?= $appointment['end_time'] ?></p>
                            <div class="mt-2">
                                <span class="inline-block px-2 py-1 text-xs rounded-full 
                                <?php
                                    switch ($appointment['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'confirmed': echo 'bg-green-100 text-green-800'; break;
                                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                        case 'completed': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Client</h4>
                                <p class="mt-1"><?= htmlspecialchars($appointment['customer_name']) ?></p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Contact</h4>
                                <p class="mt-1">
                                    <a href="tel:<?= htmlspecialchars($appointment['customer_phone']) ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($appointment['customer_phone']) ?>
                                    </a>
                                </p>
                                <p>
                                    <a href="mailto:<?= htmlspecialchars($appointment['customer_email']) ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($appointment['customer_email']) ?>
                                    </a>
                                </p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Price</h4>
                                <p class="mt-1">$<?= number_format($appointment['price'], 2) ?></p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Duration</h4>
                                <p class="mt-1"><?= $appointment['duration'] ?> minutes</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t flex space-x-2">
                            <a href="service_provider_reschedule.php?id=<?= $appointment_id ?>&stylist_id=<?= $stylist_id ?>" class="bg-blue-100 text-blue-700 px-4 py-2 rounded hover:bg-blue-200 text-sm">
                                <i class="fas fa-clock mr-1"></i> Reschedule
                            </a>
                            <?php if ($appointment['status'] !== 'cancelled' && $appointment['status'] !== 'completed'): ?>
                            <a href="cancel_appointment.php?id=<?= $appointment_id ?>&stylist_id=<?= $stylist_id ?>&redirect=manage" class="bg-red-100 text-red-700 px-4 py-2 rounded hover:bg-red-200 text-sm" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Update Appointment Status -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold mb-4">Update Appointment</h3>
                        
                        <form method="POST">
                            <div class="space-y-4">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                                        <option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="completed" <?= $appointment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50"><?= htmlspecialchars($appointment['notes'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="notify_customer" name="notify_customer" value="1" class="h-4 w-4 text-purple-600 focus:ring-purple-500 rounded">
                                    <label for="notify_customer" class="ml-2 block text-sm text-gray-700">
                                        Notify customer about changes
                                    </label>
                                </div>
                                
                                <div>
                                    <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Scheduling Conflicts -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold mb-4">Scheduling Conflicts</h3>
                        
                        <?php if (empty($overlappingAppointments)): ?>
                            <div class="p-4 bg-green-50 text-green-700 rounded">
                                <p class="flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No scheduling conflicts detected.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-red-50 text-red-700 rounded mb-4">
                                <p class="flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    This appointment overlaps with <?= count($overlappingAppointments) ?> other appointment(s).
                                </p>
                            </div>
                            
                            <div class="space-y-3">
                                <?php foreach($overlappingAppointments as $overlap): ?>
                                    <div class="border-l-4 border-red-400 pl-3 py-2">
                                        <p class="font-medium"><?= date('g:i A', strtotime($overlap['appointment_time'])) ?> - <?= date('g:i A', strtotime($overlap['appointment_time']) + $overlap['duration'] * 60) ?></p>
                                        <p class="text-sm"><?= htmlspecialchars($overlap['customer_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($overlap['service_name']) ?> (<?= $overlap['duration'] ?> min)</p>
                                        <div class="mt-2">
                                            <a href="service_provider_manage_appointment.php?id=<?= $overlap['id'] ?>&stylist_id=<?= $stylist_id ?>" class="text-xs text-blue-600 hover:text-blue-800">
                                                View Appointment
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">Suggested Actions:</p>
                                <ul class="list-disc pl-5 mt-2 text-sm text-gray-600 space-y-1">
                                    <li>Reschedule this appointment to a different time</li>
                                    <li>Assign a different service provider if available</li>
                                    <li>Inform the customer about the scheduling issue</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('hidden');
        });
    </script>
</body>
</html> 