<?php
include 'config.php';

// Get stylist ID from query string (for testing purposes)
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

// Get stylist info
$stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
$stmt->execute([$stylist_id]);
$stylist = $stmt->fetch();

if (!$stylist) {
    die("Stylist not found");
}

// For testing - comment out in production
// echo "<pre>Selected stylist: ";
// print_r($stylist);
// echo "</pre>";

// Get all stylists for the dropdown
$stylistsStmt = $conn->prepare("SELECT id, name FROM stylists WHERE is_active = 1");
$stylistsStmt->execute();
$allStylists = $stylistsStmt->fetchAll();

// Get all services for the filter
$servicesStmt = $conn->prepare("SELECT id, name FROM services WHERE is_active = 1");
$servicesStmt->execute();
$allServices = $servicesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Management - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            padding: 2px 4px;
        }
        .massage-service {
            background-color: #a78bfa !important;
            border-color: #8b5cf6 !important;
        }
        .facial-service {
            background-color: #f9a8d4 !important;
            border-color: #f472b6 !important;
        }
        .body-service {
            background-color: #86efac !important;
            border-color: #4ade80 !important;
        }
        .hair-service {
            background-color: #fca5a5 !important;
            border-color: #f87171 !important;
        }
        .nail-service {
            background-color: #93c5fd !important;
            border-color: #3b82f6 !important;
        }
        .status-confirmed {
            border-left: 4px solid #10b981 !important;
        }
        .status-pending {
            border-left: 4px solid #f59e0b !important;
        }
        .status-cancelled {
            border-left: 4px solid #ef4444 !important;
        }
        .status-completed {
            border-left: 4px solid #3b82f6 !important;
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
                    <a href="integrated_homepage.php" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-home mr-2"></i> Back to Homepage
                    </a>
                    <a href="service_provider_dashboard.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded bg-white bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="service_provider_clients.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-users mr-2"></i> Clients
                    </a>
                    <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-concierge-bell mr-2"></i> Services
                    </a>
                </div>
                <div>
                    <p class="text-xs uppercase text-indigo-200 mb-2">Settings</p>
                    <a href="#" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <a href="#" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
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
                        <h2 class="text-lg font-semibold">Calendar Management</h2>
                    </div>
                    <div class="flex items-center">
                        <div class="relative mr-4">
                            <select id="stylist-selector" class="appearance-none bg-gray-100 border border-gray-300 rounded px-3 py-1 pr-8 text-sm">
                                <?php foreach($allStylists as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= intval($s['id']) == $stylist_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-2 top-2 text-xs text-gray-500"></i>
                        </div>
                        <div class="flex items-center">
                            <img src="https://randomuser.me/api/portraits/<?= ($stylist['id'] % 2 == 0) ? 'women' : 'men' ?>/<?= ($stylist['id'] * 13) % 100 ?>.jpg" alt="Profile" class="h-8 w-8 rounded-full mr-2">
                            <span id="stylist-name-display" class="text-sm font-medium"><?= htmlspecialchars($stylist['name']) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">Appointment Calendar</h3>
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <select id="service-filter" class="appearance-none bg-gray-100 border border-gray-300 rounded px-3 py-1 pr-8 text-sm">
                                    <option value="all">All Services</option>
                                    <?php foreach($allServices as $service): ?>
                                    <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-2 top-2 text-xs text-gray-500"></i>
                            </div>
                            <button id="print-schedule" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm flex items-center hover:bg-gray-200">
                                <i class="fas fa-print mr-1"></i> Print
                            </button>
                            <a href="service_provider_add_appointment.php?stylist_id=<?= $stylist_id ?>" class="bg-purple-600 text-white px-3 py-1 rounded text-sm flex items-center hover:bg-purple-700">
                                <i class="fas fa-plus mr-1"></i> New Booking
                            </a>
                        </div>
                    </div>
                    <div id="calendar" class="h-[36rem]"></div>
                    
                    <div class="mt-4 flex flex-wrap gap-2">
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 rounded bg-purple-500 mr-1"></div> Massage
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 rounded bg-pink-400 mr-1"></div> Facial
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 rounded bg-green-400 mr-1"></div> Body Treatment
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 rounded bg-red-400 mr-1"></div> Hair Service
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 rounded bg-blue-400 mr-1"></div> Nail Service
                        </div>
                        <div class="flex items-center text-xs ml-4">
                            <div class="w-3 h-3 border-l-4 border-green-500 mr-1"></div> Confirmed
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 border-l-4 border-yellow-500 mr-1"></div> Pending
                        </div>
                        <div class="flex items-center text-xs">
                            <div class="w-3 h-3 border-l-4 border-red-500 mr-1"></div> Cancelled
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div id="appointment-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold" id="modal-title">Appointment Details</h3>
                <button id="close-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4" id="appointment-details">
                <!-- Details will be filled by JavaScript -->
            </div>
            <div class="p-4 border-t flex justify-end space-x-2">
                <button id="cancel-appointment" class="bg-red-100 text-red-700 px-4 py-2 rounded hover:bg-red-200">
                    Cancel Appointment
                </button>
                <button id="reschedule-appointment" class="bg-blue-100 text-blue-700 px-4 py-2 rounded hover:bg-blue-200">
                    Reschedule
                </button>
                <a id="manage-appointment-link" href="#" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    Manage Details
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize calendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                slotMinTime: '08:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                height: 'auto',
                events: 'get_appointments_json.php?stylist_id=' + <?= $stylist_id ?>,
                eventClick: function(info) {
                    showAppointmentDetails(info.event.id);
                }
            });
            calendar.render();
            
            // Verify selected stylist name matches displayed stylist
            const stylistSelect = document.getElementById('stylist-selector');
            const selectedStylistId = stylistSelect.value;
            const selectedStylistName = stylistSelect.options[stylistSelect.selectedIndex].text;
            const displayedStylistName = document.getElementById('stylist-name-display').textContent;
            
            // If there's a mismatch, reload the page with the correct stylist ID
            if (displayedStylistName.trim() !== selectedStylistName.trim()) {
                window.location.href = 'service_provider_calendar.php?stylist_id=' + selectedStylistId;
            }
            
            // Function to fetch and display appointment details
            function showAppointmentDetails(appointmentId) {
                fetch('get_appointment_details.php?id=' + appointmentId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert('Error: ' + data.error);
                            return;
                        }
                        
                        const appointment = data.appointment;
                        
                        // Set appointment details in modal
                        document.getElementById('modal-title').textContent = 'Appointment: ' + appointment.service_name;
                        
                        const detailsHtml = `
                            <div class="space-y-3">
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Client:</div>
                                    <div class="w-2/3 font-medium">${appointment.customer_name}</div>
                                </div>
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Date & Time:</div>
                                    <div class="w-2/3 font-medium">${appointment.formatted_date} at ${appointment.formatted_time}</div>
                                </div>
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Duration:</div>
                                    <div class="w-2/3">${appointment.duration} minutes</div>
                                </div>
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Price:</div>
                                    <div class="w-2/3">$${appointment.price}</div>
                                </div>
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Status:</div>
                                    <div class="w-2/3">
                                        <span class="px-2 py-1 text-xs rounded-full ${
                                            appointment.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                            appointment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            appointment.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                            'bg-blue-100 text-blue-800'
                                        }">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span>
                                    </div>
                                </div>
                                ${appointment.notes ? `
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Notes:</div>
                                    <div class="w-2/3">${appointment.notes}</div>
                                </div>` : ''}
                                <div class="flex">
                                    <div class="w-1/3 text-gray-600">Contact:</div>
                                    <div class="w-2/3">
                                        <a href="tel:${appointment.customer_phone}" class="text-blue-600 hover:text-blue-800">
                                            ${appointment.customer_phone}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('appointment-details').innerHTML = detailsHtml;
                        
                        // Set up action buttons
                        document.getElementById('manage-appointment-link').href = 'service_provider_manage_appointment.php?id=' + appointment.id + '&stylist_id=' + <?= $stylist_id ?>;
                        
                        // Disable cancel button if already cancelled or completed
                        const cancelBtn = document.getElementById('cancel-appointment');
                        if (appointment.status === 'cancelled' || appointment.status === 'completed') {
                            cancelBtn.disabled = true;
                            cancelBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            cancelBtn.disabled = false;
                            cancelBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            cancelBtn.onclick = function() {
                                if (confirm('Are you sure you want to cancel this appointment?')) {
                                    // Use the updated cancel_appointment.php with proper ID parameter
                                    window.location.href = 'cancel_appointment.php?id=' + appointment.id + '&stylist_id=' + <?= $stylist_id ?> + '&redirect=calendar';
                                }
                            };
                        }
                        
                        // Disable reschedule button if cancelled or completed
                        const rescheduleBtn = document.getElementById('reschedule-appointment');
                        if (appointment.status === 'cancelled' || appointment.status === 'completed') {
                            rescheduleBtn.disabled = true;
                            rescheduleBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            rescheduleBtn.disabled = false;
                            rescheduleBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            rescheduleBtn.onclick = function() {
                                window.location.href = 'service_provider_reschedule.php?id=' + appointment.id + '&stylist_id=' + <?= $stylist_id ?>;
                            };
                        }
                        
                        // Show modal
                        document.getElementById('appointment-modal').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error fetching appointment details:', error);
                        alert('Failed to load appointment details. Please try again.');
                    });
            }
            
            // Close modal when X is clicked
            document.getElementById('close-modal').addEventListener('click', function() {
                document.getElementById('appointment-modal').classList.add('hidden');
            });
            
            // Close modal when clicking outside
            document.getElementById('appointment-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
            
            // Filter by service type
            document.getElementById('service-filter').addEventListener('change', function() {
                const serviceId = this.value;
                
                if (serviceId === 'all') {
                    // Reload all events
                    calendar.refetchEvents();
                } else {
                    // Fetch filtered events
                    calendar.getEventSources().forEach(source => source.remove());
                    calendar.addEventSource('get_appointments_json.php?stylist_id=' + <?= $stylist_id ?> + '&service_id=' + serviceId);
                }
            });
            
            // Stylist selector change handler
            document.getElementById('stylist-selector').addEventListener('change', function() {
                window.location.href = 'service_provider_calendar.php?stylist_id=' + this.value;
            });
            
            // Print button
            document.getElementById('print-schedule').addEventListener('click', function() {
                window.print();
            });
            
            // Toggle sidebar
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('hidden');
            });
        });
    </script>
</body>
</html> 