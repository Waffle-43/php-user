<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Get appointment ID and stylist ID
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

if (!$appointment_id) {
    die("Appointment ID is required");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';
    $notify_customer = isset($_POST['notify_customer']) ? 1 : 0;
    
    if (empty($new_date) || empty($new_time)) {
        $error = "Date and time are required";
    } else {
        try {
            // Get original appointment details
            $origStmt = $conn->prepare("
                SELECT a.*, s.name as service_name, s.duration as service_duration, c.id as customer_id, c.name as customer_name
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN customers c ON a.customer_id = c.id
                WHERE a.id = ?
            ");
            $origStmt->execute([$appointment_id]);
            $originalAppointment = $origStmt->fetch();
            
            if (!$originalAppointment) {
                die("Original appointment not found");
            }
            
            // Check for overlapping appointments
            $overlapStmt = $conn->prepare("
                SELECT COUNT(*) as overlap_count
                FROM appointments a
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
            ");
            
            $overlapStmt->execute([
                $stylist_id,
                $new_date,
                $appointment_id,
                $new_time,
                $new_time,
                $new_time,
                $originalAppointment['duration'],
                $new_time
            ]);
            
            $overlapCount = $overlapStmt->fetch()['overlap_count'];
            
            if ($overlapCount > 0) {
                $error = "The selected time slot overlaps with existing appointments. Please choose a different time.";
            } else {
                // Update appointment with new date and time
                $updateStmt = $conn->prepare("
                    UPDATE appointments 
                    SET appointment_date = ?, appointment_time = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$new_date, $new_time, $appointment_id]);
                
                // Create a record in the rescheduling history table if it exists
                try {
                    $historyStmt = $conn->prepare("
                        INSERT INTO appointment_reschedule_history
                        (appointment_id, original_date, original_time, new_date, new_time, rescheduled_by, created_at)
                        VALUES (?, ?, ?, ?, ?, 'provider', CURRENT_TIMESTAMP)
                    ");
                    $historyStmt->execute([
                        $appointment_id,
                        $originalAppointment['appointment_date'],
                        $originalAppointment['appointment_time'],
                        $new_date,
                        $new_time
                    ]);
                } catch (Exception $e) {
                    // Ignore if the table doesn't exist
                }
                
                // Notify customer if requested
                if ($notify_customer) {
                    $message = "Your appointment for {$originalAppointment['service_name']} has been rescheduled from " . 
                               date('F j, Y', strtotime($originalAppointment['appointment_date'])) . 
                               " at " . date('g:i A', strtotime($originalAppointment['appointment_time'])) . 
                               " to " . date('F j, Y', strtotime($new_date)) . 
                               " at " . date('g:i A', strtotime($new_time)) . ".";
                    
                    $notifStmt = $conn->prepare("
                        INSERT INTO notifications 
                        (user_id, type, message, related_id, is_read, created_at)
                        VALUES (?, 'appointment_modification', ?, ?, 0, CURRENT_TIMESTAMP)
                    ");
                    $notifStmt->execute([$originalAppointment['customer_id'], $message, $appointment_id]);
                }
                
                // Redirect to the appointment management page
                header("Location: service_provider_manage_appointment.php?id={$appointment_id}&stylist_id={$stylist_id}&success=rescheduled");
                exit;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get appointment details
try {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as service_name,
            s.duration as service_duration,
            c.name as customer_name
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
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
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get available time slots for the next 14 days
$days = [];
$today = date('Y-m-d');
for ($i = 0; $i < 14; $i++) {
    $date = date('Y-m-d', strtotime($today . " +$i days"));
    $days[] = $date;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .time-slot {
            transition: all 0.2s ease;
        }
        .time-slot:hover:not(.unavailable) {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .time-slot.unavailable {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
                        <h2 class="text-lg font-semibold">Reschedule Appointment</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="service_provider_manage_appointment.php?id=<?= $appointment_id ?>&stylist_id=<?= $stylist_id ?>" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm flex items-center hover:bg-gray-200">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Appointment
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-semibold"><?= htmlspecialchars($appointment['service_name']) ?></h3>
                            <p class="text-gray-500">Current appointment: <?= date('l, F j, Y', strtotime($appointment['appointment_date'])) ?> at <?= date('g:i A', strtotime($appointment['appointment_time'])) ?></p>
                            <p class="text-gray-500">Client: <?= htmlspecialchars($appointment['customer_name']) ?></p>
                        </div>
                        <div>
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
                    
                    <form method="POST" id="reschedule-form">
                        <input type="hidden" id="new_date" name="new_date" value="<?= $appointment['appointment_date'] ?>">
                        <input type="hidden" id="new_time" name="new_time" value="<?= $appointment['appointment_time'] ?>">
                        
                        <h4 class="font-medium mb-3">Select New Date</h4>
                        <div class="grid grid-cols-7 gap-2 mb-6">
                            <?php foreach($days as $date): ?>
                                <?php 
                                    $dateObj = new DateTime($date);
                                    $isToday = $date === date('Y-m-d');
                                    $isPast = $date < date('Y-m-d');
                                    $isSelected = $date === $appointment['appointment_date'];
                                    $dayName = $dateObj->format('D');
                                    $dayNum = $dateObj->format('j');
                                ?>
                                <button type="button" data-date="<?= $date ?>" 
                                    class="date-selector p-2 border rounded text-center <?= $isPast ? 'opacity-50 cursor-not-allowed' : '' ?> <?= $isSelected ? 'border-purple-500 bg-purple-100' : 'hover:border-purple-300' ?> <?= $isToday ? 'font-medium' : '' ?>">
                                    <div class="text-xs text-gray-500"><?= $dayName ?></div>
                                    <div class="text-lg <?= $isToday ? 'text-purple-600' : '' ?>"><?= $dayNum ?></div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <h4 class="font-medium mb-3">Select New Time</h4>
                        <div id="time-slots" class="grid grid-cols-4 md:grid-cols-6 gap-2 mb-6">
                            <!-- Time slots will be loaded via AJAX -->
                            <div class="col-span-full text-center py-8 text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Loading available time slots...
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="notify_customer" name="notify_customer" value="1" checked class="h-4 w-4 text-purple-600 focus:ring-purple-500 rounded">
                            <label for="notify_customer" class="ml-2 block text-sm text-gray-700">
                                Notify customer about the reschedule
                            </label>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
                                Confirm Reschedule
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentId = <?= $appointment_id ?>;
            const stylistId = <?= $stylist_id ?>;
            const appointmentDuration = <?= $appointment['service_duration'] ?>;
            const selectedDate = '<?= $appointment['appointment_date'] ?>';
            const selectedTime = '<?= $appointment['appointment_time'] ?>';
            
            // Load time slots for the initially selected date
            loadTimeSlots(selectedDate);
            
            // Date selector functionality
            document.querySelectorAll('.date-selector').forEach(button => {
                button.addEventListener('click', function() {
                    const date = this.dataset.date;
                    
                    // Skip if date is in the past
                    if (new Date(date) < new Date().setHours(0,0,0,0)) {
                        return;
                    }
                    
                    // Update UI for selected date
                    document.querySelectorAll('.date-selector').forEach(btn => {
                        btn.classList.remove('border-purple-500', 'bg-purple-100');
                    });
                    this.classList.add('border-purple-500', 'bg-purple-100');
                    
                    // Update hidden input
                    document.getElementById('new_date').value = date;
                    
                    // Clear time selection
                    document.getElementById('new_time').value = '';
                    
                    // Load time slots for the selected date
                    loadTimeSlots(date);
                });
            });
            
            // Function to load time slots
            function loadTimeSlots(date) {
                const timeSlotsContainer = document.getElementById('time-slots');
                timeSlotsContainer.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading available time slots...</div>';
                
                // Fetch available time slots from the server
                fetch(`get_available_time_slots.php?date=${date}&stylist_id=${stylistId}&appointment_id=${appointmentId}&duration=${appointmentDuration}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            timeSlotsContainer.innerHTML = `<div class="col-span-full text-center py-8 text-red-500">${data.error}</div>`;
                            return;
                        }
                        
                        if (!data.slots || data.slots.length === 0) {
                            timeSlotsContainer.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No available time slots for this date.</div>';
                            return;
                        }
                        
                        // Build time slot buttons
                        let html = '';
                        data.slots.forEach(slot => {
                            const formattedTime = formatTime(slot.time);
                            const isSelected = slot.time === selectedTime && date === selectedDate;
                            
                            html += `
                                <button type="button" data-time="${slot.time}" 
                                    class="time-slot p-2 border rounded text-center ${isSelected ? 'border-purple-500 bg-purple-100' : ''} ${!slot.available ? 'unavailable' : 'hover:border-purple-300'}"
                                    ${!slot.available ? 'disabled' : ''}>
                                    ${formattedTime}
                                </button>
                            `;
                        });
                        
                        timeSlotsContainer.innerHTML = html;
                        
                        // Add click event to time slots
                        document.querySelectorAll('.time-slot:not(.unavailable)').forEach(button => {
                            button.addEventListener('click', function() {
                                const time = this.dataset.time;
                                
                                // Update UI for selected time
                                document.querySelectorAll('.time-slot').forEach(btn => {
                                    btn.classList.remove('border-purple-500', 'bg-purple-100');
                                });
                                this.classList.add('border-purple-500', 'bg-purple-100');
                                
                                // Update hidden input
                                document.getElementById('new_time').value = time;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Error loading time slots:', error);
                        timeSlotsContainer.innerHTML = `
                            <div class="col-span-full text-center py-8">
                                <p class="text-red-500 mb-2">Failed to load time slots. Please try again.</p>
                                <button type="button" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700" onclick="loadTimeSlots('${date}')">
                                    <i class="fas fa-sync-alt mr-1"></i> Retry
                                </button>
                            </div>
                        `;
                    });
            }
            
            // Function to format time (HH:MM:SS -> h:MM AM/PM)
            function formatTime(timeString) {
                const [hours, minutes] = timeString.split(':');
                const hourNum = parseInt(hours);
                const ampm = hourNum >= 12 ? 'PM' : 'AM';
                const hour12 = hourNum % 12 || 12;
                return `${hour12}:${minutes} ${ampm}`;
            }
            
            // Form submission validation
            document.getElementById('reschedule-form').addEventListener('submit', function(e) {
                const date = document.getElementById('new_date').value;
                const time = document.getElementById('new_time').value;
                
                if (!date || !time) {
                    e.preventDefault();
                    alert('Please select both a date and time for rescheduling.');
                    return false;
                }
                
                return true;
            });
            
            // Toggle sidebar
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('hidden');
            });
        });
    </script>
</body>
</html> 