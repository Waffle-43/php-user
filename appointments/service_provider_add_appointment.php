<?php
include 'config.php';

// Get stylist ID from query string
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

// Get stylist info
$stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
$stmt->execute([$stylist_id]);
$stylist = $stmt->fetch();

if (!$stylist) {
    die("Stylist not found");
}

// Get all services
$servicesStmt = $conn->prepare("SELECT id, name, duration, price FROM services WHERE is_active = 1");
$servicesStmt->execute();
$allServices = $servicesStmt->fetchAll();

// Get all customers
$customersStmt = $conn->prepare("SELECT id, name, email, phone FROM customers");
$customersStmt->execute();
$allCustomers = $customersStmt->fetchAll();

// Handle form submission
$success = false;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process the form
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $time = isset($_POST['time']) ? $_POST['time'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    
    // Validation
    if (empty($customer_id) || empty($service_id) || empty($date) || empty($time)) {
        $error = "All required fields must be filled out";
    } else {
        try {
            // Get service duration and price
            $serviceDetailsStmt = $conn->prepare("SELECT duration, price FROM services WHERE id = ?");
            $serviceDetailsStmt->execute([$service_id]);
            $serviceDetails = $serviceDetailsStmt->fetch();
            
            if (!$serviceDetails) {
                throw new Exception("Selected service not found");
            }
            
            $duration = $serviceDetails['duration'];
            $price = $serviceDetails['price'];
            
            // Insert new appointment
            $insertStmt = $conn->prepare("
                INSERT INTO appointments 
                (customer_id, service_id, stylist_id, appointment_date, appointment_time, 
                duration, price, notes, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $customer_id, 
                $service_id, 
                $stylist_id, 
                $date, 
                $time, 
                $duration, 
                $price, 
                $notes, 
                $status
            ]);
            
            // Success
            $success = true;
            
            // Redirect back to calendar
            header("Location: service_provider_calendar.php?stylist_id={$stylist_id}&new_appointment=success");
            exit;
            
        } catch (Exception $e) {
            $error = "Error creating appointment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Appointment - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                <div>
                    <p class="text-xs uppercase text-indigo-200 mb-2">Settings</p>
                    <a href="#" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <a href="logout" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
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
                        <h2 class="text-lg font-semibold">Add New Appointment</h2>
                    </div>
                    <div class="flex items-center">
                        <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm flex items-center hover:bg-gray-200 mr-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Calendar
                        </a>
                        <div class="flex items-center">
                            <img src="https://randomuser.me/api/portraits/<?= ($stylist['id'] % 2 == 0) ? 'women' : 'men' ?>/<?= ($stylist['id'] * 13) % 100 ?>.jpg" alt="Profile" class="h-8 w-8 rounded-full mr-2">
                            <span class="text-sm font-medium"><?= htmlspecialchars($stylist['name']) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <div class="bg-white rounded-lg shadow p-6 max-w-4xl mx-auto">
                    <h3 class="text-xl font-semibold mb-6">Book New Appointment</h3>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <!-- Customer Selection -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Select Customer</label>
                                <select id="customer_id" name="customer_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach($allCustomers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?> (<?= htmlspecialchars($customer['phone']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="add-new-customer" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-plus mr-1"></i> Add New Customer
                                </button>
                            </div>
                            
                            <div>
                                <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Select Service</label>
                                <select id="service_id" name="service_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">-- Select Service --</option>
                                    <?php foreach($allServices as $service): ?>
                                        <option value="<?= $service['id'] ?>" data-duration="<?= $service['duration'] ?>" data-price="<?= $service['price'] ?>">
                                            <?= htmlspecialchars($service['name']) ?> - $<?= $service['price'] ?> (<?= $service['duration'] ?> mins)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Date, Time, Duration -->
                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" id="date" name="date" required min="<?= date('Y-m-d') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div>
                                <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                                <input type="time" id="time" name="time" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <div id="available-times" class="mt-2 text-sm"></div>
                            </div>
                            
                            <div>
                                <label for="duration-display" class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                                <input type="text" id="duration-display" readonly class="w-full bg-gray-100 border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                        
                        <!-- Status and Notes -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>
                        </div>
                        
                        <!-- Price Summary -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Service Price:</p>
                                    <p class="text-lg font-medium" id="price-display">$0.00</p>
                                </div>
                                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition-colors">
                                    Book Appointment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- New Customer Modal -->
    <div id="new-customer-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold">Add New Customer</h3>
                <button id="close-customer-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <div>
                        <label for="new-customer-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="new-customer-name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="new-customer-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="new-customer-email" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="new-customer-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" id="new-customer-phone" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                </div>
            </div>
            <div class="p-4 border-t flex justify-end">
                <button id="save-customer" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    Add Customer
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Service selection - update duration and price
            document.getElementById('service_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const duration = selectedOption.getAttribute('data-duration');
                const price = selectedOption.getAttribute('data-price');
                
                document.getElementById('duration-display').value = duration ? duration + ' minutes' : '';
                document.getElementById('price-display').textContent = price ? '$' + price : '$0.00';
            });
            
            // Date and time selection - check availability
            const dateInput = document.getElementById('date');
            const timeInput = document.getElementById('time');
            
            function checkAvailability() {
                const date = dateInput.value;
                const serviceId = document.getElementById('service_id').value;
                
                if (!date || !serviceId) return;
                
                // Fetch available time slots
                fetch(`get_available_time_slots.php?date=${date}&stylist_id=<?= $stylist_id ?>&service_id=${serviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        const availableTimesDiv = document.getElementById('available-times');
                        
                        if (data.error) {
                            availableTimesDiv.innerHTML = `<span class="text-red-500">${data.error}</span>`;
                            return;
                        }
                        
                        if (data.available_slots && data.available_slots.length > 0) {
                            availableTimesDiv.innerHTML = `
                                <span class="text-green-600">Available times: ${data.available_slots.join(', ')}</span>
                            `;
                        } else {
                            availableTimesDiv.innerHTML = `
                                <span class="text-yellow-500">No available times for this date</span>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking availability:', error);
                    });
            }
            
            dateInput.addEventListener('change', checkAvailability);
            document.getElementById('service_id').addEventListener('change', checkAvailability);
            
            // New customer modal
            const newCustomerModal = document.getElementById('new-customer-modal');
            
            document.getElementById('add-new-customer').addEventListener('click', function() {
                newCustomerModal.classList.remove('hidden');
            });
            
            document.getElementById('close-customer-modal').addEventListener('click', function() {
                newCustomerModal.classList.add('hidden');
            });
            
            // Save new customer
            document.getElementById('save-customer').addEventListener('click', function() {
                const name = document.getElementById('new-customer-name').value;
                const email = document.getElementById('new-customer-email').value;
                const phone = document.getElementById('new-customer-phone').value;
                
                if (!name) {
                    alert('Please enter customer name');
                    return;
                }
                
                // Save new customer via AJAX
                fetch('add_customer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&phone=${encodeURIComponent(phone)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    // Add new customer to dropdown and select it
                    const customerSelect = document.getElementById('customer_id');
                    const newOption = document.createElement('option');
                    newOption.value = data.id;
                    newOption.text = `${name} (${phone})`;
                    customerSelect.add(newOption);
                    customerSelect.value = data.id;
                    
                    // Close modal
                    newCustomerModal.classList.add('hidden');
                })
                .catch(error => {
                    console.error('Error saving customer:', error);
                    alert('Failed to save customer. Please try again.');
                });
            });
            
            // Sidebar toggle
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('hidden');
            });
        });
    </script>
</body>
</html> 