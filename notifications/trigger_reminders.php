<?php
// Start session
session_start();

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// For security, this page should be accessible only to admins
// For demo purposes, we're not implementing full authentication
$isAdmin = true; // In production, replace with actual admin check

// For demo, process the reminder generation if requested
$reminderResult = null;
if (isset($_POST['generate_reminders'])) {
    // Include reminder generator script
    ob_start();
    include 'generate_appointment_reminders.php';
    $output = ob_get_clean();
    
    $reminderResult = [
        'success' => true,
        'message' => 'Reminders generated successfully',
        'details' => $output
    ];
}

// Get recent log entries if the log file exists
$logEntries = [];
$logFile = 'reminder_log.txt';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $logEntries = array_filter(array_slice(array_reverse($logLines), 0, 50)); // Get last 50 non-empty lines
}

// Get upcoming appointments for reference
try {
    // Database configuration
    $db_host = 'localhost';
    $db_name = 'salon_spa';
    $db_user = 'root';
    $db_pass = '';
    
    // Connect to database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current date
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $dayAfter = date('Y-m-d', strtotime('+2 days'));
    
    // Get upcoming appointments
    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.status,
               c.name as customer_name, s.name as service_name, st.name as stylist_name,
               (
                   SELECT COUNT(*)
                   FROM notifications n
                   WHERE (n.appointment_id = a.id OR n.related_id = a.id)
                   AND n.type LIKE '%reminder%'
               ) as reminder_count
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        JOIN stylists st ON a.stylist_id = st.id
        WHERE a.appointment_date BETWEEN :today AND :day_after
        AND a.status NOT IN ('cancelled', 'completed')
        ORDER BY a.appointment_date, a.appointment_time
    ");
    
    $stmt->execute([
        ':today' => $today,
        ':day_after' => $dayAfter
    ]);
    
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $upcomingAppointments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder Management - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-purple-600 to-blue-500 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-spa text-2xl mr-2"></i>
                <span class="text-xl font-bold">Harmony Heaven Spa - Admin</span>
            </div>
            <div>
                <a href="appointment.php" class="text-white hover:text-pink-200 mr-4">
                    <i class="fas fa-home mr-1"></i> Main Site
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?php if (!$isAdmin): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Access Denied:</strong> You need administrator privileges to access this page.
            </div>
        <?php else: ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Appointment Reminder Management</h1>
            
            <?php if ($reminderResult): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <strong>Success:</strong> <?= htmlspecialchars($reminderResult['message']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Trigger Reminders Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Generate Appointment Reminders</h2>
                <p class="text-gray-600 mb-4">
                    This will check for upcoming appointments and send reminder notifications to customers. 
                    In production, this should be automated with a scheduled task.
                </p>
                
                <form method="post" class="flex items-center">
                    <button type="submit" name="generate_reminders" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded shadow">
                        <i class="fas fa-bell mr-2"></i> Generate Reminders Now
                    </button>
                    <span class="ml-4 text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> 
                        This will only send reminders for appointments within the next 24 hours
                    </span>
                </form>
            </div>
            
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Upcoming Appointments</h2>
                
                <?php if (empty($upcomingAppointments)): ?>
                    <p class="text-gray-500">No upcoming appointments found for the next 3 days.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stylist</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reminders</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($upcomingAppointments as $appt): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $appt['id'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('D, M j, Y', strtotime($appt['appointment_date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('g:i A', strtotime($appt['appointment_time'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($appt['customer_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($appt['service_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($appt['stylist_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                                $statusClass = 'text-gray-600';
                                                if ($appt['status'] == 'confirmed') {
                                                    $statusClass = 'text-green-600';
                                                } elseif ($appt['status'] == 'pending') {
                                                    $statusClass = 'text-yellow-600';
                                                } elseif ($appt['status'] == 'rescheduled') {
                                                    $statusClass = 'text-blue-600';
                                                }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-opacity-10 <?= $statusClass ?> bg-current">
                                                <?= ucfirst($appt['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($appt['reminder_count'] > 0): ?>
                                                <span class="text-green-600">
                                                    <i class="fas fa-check-circle mr-1"></i> Sent (<?= $appt['reminder_count'] ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-500">
                                                    <i class="fas fa-times-circle mr-1"></i> Not sent
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Reminder Logs -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Reminder Logs</h2>
                
                <?php if (empty($logEntries)): ?>
                    <p class="text-gray-500">No log entries found. Run the reminder generator to create logs.</p>
                <?php else: ?>
                    <div class="bg-gray-50 p-4 rounded border overflow-auto h-96">
                        <?php foreach ($logEntries as $entry): ?>
                            <div class="text-sm font-mono mb-1 <?= strpos($entry, 'Error') !== false ? 'text-red-600' : 'text-gray-700' ?>">
                                <?= htmlspecialchars($entry) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Setup Instructions -->
            <div class="bg-blue-50 rounded-lg p-6 mt-8 border border-blue-200">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">Automatic Reminder Setup</h2>
                <p class="mb-4">To set up automatic reminders, add a scheduled task (cron job) to run the reminder script:</p>
                
                <div class="bg-gray-800 text-white p-3 rounded-md overflow-x-auto mb-4">
                    <code>0 * * * * php <?= realpath('generate_appointment_reminders.php') ?></code>
                </div>
                
                <p class="text-sm text-blue-700">This will run the reminder checker once every hour, sending notifications for upcoming appointments.</p>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-gray-800 text-white p-6 mt-12">
        <div class="container mx-auto text-center">
            <p>© <?= date('Y') ?> Harmony Heaven Spa - Administrator Panel</p>
        </div>
    </footer>
</body>
</html> 