<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
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
            a.appointment_date ASC, 
            a.appointment_time ASC
    ");
    
    $stmt->execute([':customer_id' => $customerId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $appointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Status Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .debug-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 0.5rem 0;
        }
        .debug-label {
            font-weight: bold;
            width: 200px;
        }
        .result-true {
            color: green;
            font-weight: bold;
        }
        .result-false {
            color: red;
        }
        .appointment-card {
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="p-8 bg-gray-50">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Appointment Status Diagnostics</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p>Error: <?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <p class="mb-6">This tool shows all appointments and their eligibility for rescheduling.</p>
        
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-2">Current Server Time:</h2>
            <p class="text-blue-600 font-mono"><?= date('Y-m-d H:i:s') ?></p>
        </div>
        
        <?php if (empty($appointments)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <p>No appointments found for this customer.</p>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $index => $appointment): ?>
                <?php
                    // Current date/time
                    $now = new DateTime();
                    
                    // Appointment date/time
                    $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                    
                    // Check conditions
                    $isFuture = $appointmentDateTime > $now;
                    $isNotCancelled = $appointment['status'] !== 'cancelled';
                    $isNotCompleted = $appointment['status'] !== 'completed';
                    
                    // Final eligibility
                    $canReschedule = $isFuture && $isNotCancelled && $isNotCompleted;
                ?>
                <div class="appointment-card bg-white shadow">
                    <h3 class="text-xl font-semibold mb-2">Appointment #<?= $appointment['id'] ?> - <?= htmlspecialchars($appointment['service_name']) ?></h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="debug-row">
                                <div class="debug-label">Date:</div>
                                <div><?= $appointment['appointment_date'] ?></div>
                            </div>
                            <div class="debug-row">
                                <div class="debug-label">Time:</div>
                                <div><?= $appointment['appointment_time'] ?></div>
                            </div>
                            <div class="debug-row">
                                <div class="debug-label">Status:</div>
                                <div class="font-semibold"><?= ucfirst($appointment['status']) ?></div>
                            </div>
                        </div>
                        <div>
                            <div class="debug-row">
                                <div class="debug-label">Stylist:</div>
                                <div><?= htmlspecialchars($appointment['stylist_name']) ?></div>
                            </div>
                            <div class="debug-row">
                                <div class="debug-label">Service ID:</div>
                                <div><?= $appointment['service_id'] ?></div>
                            </div>
                            <div class="debug-row">
                                <div class="debug-label">Stylist ID:</div>
                                <div><?= $appointment['stylist_id'] ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 bg-gray-100 p-4 rounded">
                        <h4 class="font-semibold mb-2">Reschedule Eligibility:</h4>
                        <div class="debug-row">
                            <div class="debug-label">Is Future Date:</div>
                            <div class="<?= $isFuture ? 'result-true' : 'result-false' ?>">
                                <?= $isFuture ? 'YES' : 'NO' ?>
                            </div>
                        </div>
                        <div class="debug-row">
                            <div class="debug-label">Not Cancelled:</div>
                            <div class="<?= $isNotCancelled ? 'result-true' : 'result-false' ?>">
                                <?= $isNotCancelled ? 'YES' : 'NO' ?>
                            </div>
                        </div>
                        <div class="debug-row">
                            <div class="debug-label">Not Completed:</div>
                            <div class="<?= $isNotCompleted ? 'result-true' : 'result-false' ?>">
                                <?= $isNotCompleted ? 'YES' : 'NO' ?>
                            </div>
                        </div>
                        <div class="debug-row border-t-2 border-gray-400 mt-2 pt-2">
                            <div class="debug-label">Can Reschedule:</div>
                            <div class="<?= $canReschedule ? 'result-true' : 'result-false' ?> text-lg">
                                <?= $canReschedule ? 'YES' : 'NO' ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="appointments" class="bg-pink-500 hover:bg-pink-600 text-white py-2 px-4 rounded">
                Return to Appointments
            </a>
        </div>
    </div>
</body>
</html> 