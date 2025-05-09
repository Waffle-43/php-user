<?php
require_once __DIR__ . '/../utils_files/connect.php'; // Database connection
require_once __DIR__ . '/../utils/service_utils.php'; // Utility functions for service management
require_once __DIR__ . '/../utils/service_integration.php'; // Utility functions for service integration

// Check if a specific service was requested
$selectedServiceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;
$selectedService = null;

try {
    // Get all active services using our utility function
    $services = getServicesForBookingInterface($conn);
    
    // If a service_id was passed, get its details
    if ($selectedServiceId) {
        $selectedService = getServiceDetails($selectedServiceId, $conn);
    }
    
} catch (Exception $e) {
    // Handle error gracefully
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>
            <strong>Error:</strong> " . $e->getMessage() . 
         "</div>";
    $services = []; // Empty array so the page can still load
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Book Appointment - Harmony Heaven Spa</title>
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
        
        .calendar-day {
            transition: all 0.2s ease;
        }
        
        .calendar-day:hover:not(.disabled) {
            background-color: #f0f9ff;
            transform: scale(1.05);
        }
        
        .time-slot {
            transition: all 0.2s ease;
        }
        
        .time-slot:hover:not(.booked) {
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(to right, #8e2de2, #4a00e0);
        }
        .service-card {
            transition: all .3s ease;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .service-card.active {
            border-color: #8e2de2;
            background-color: #f3f0ff;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="header text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-spa text-2xl mr-2"></i>
                <span class="text-xl font-bold text-white">Harmony Heaven Spa</span>
            </div>
            <div>
                <a href="integrated_homepage.php" class="text-white hover:text-pink-200 mr-4">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
                <a href="appointments.php" class="text-white hover:text-pink-200 mr-4">
                    <i class="fas fa-calendar-check mr-1"></i> My Appointments
                </a>
                <!-- Add notification bell with dropdown -->
                <div class="relative inline-block mr-4">
                    <button id="notifications-btn" class="text-white hover:text-pink-200 relative">
                        <i class="fas fa-bell text-xl"></i>
                        <span id="notification-badge" class="notification-badge hidden absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </button>
                    <!-- Notifications dropdown -->
                    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50">
                        <div class="p-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold">Notifications</h3>
                        </div>
                        <div id="notifications-list" class="max-h-80 overflow-y-auto">
                            <div class="p-4 text-center text-gray-500">
                                <i class="fas fa-bell-slash text-gray-300 text-2xl mb-2"></i>
                                <p>No new notifications</p>
                            </div>
                        </div>
                        <div class="p-3 text-center border-t">
                            <a href="#" id="mark-all-read" class="text-blue-600 text-sm font-medium">Mark all as read</a>
                        </div>
                    </div>
                </div>
                <a href="#" class="bg-white text-purple-600 px-4 py-2 rounded-lg shadow hover:bg-gray-100">
                    <i class="fas fa-user mr-1"></i> My Account
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Booking Steps -->
            <div class="border-b">
                <div class="flex">
                    <div class="w-1/4 py-4 text-center" id="step-1-indicator">
                        <span class="bg-blue-600 text-white rounded-full w-8 h-8 inline-flex items-center justify-center mr-2">1</span>
                        Select Service
                    </div>
                    <div class="w-1/4 py-4 text-center text-gray-500" id="step-2-indicator">
                        <span class="bg-gray-200 text-gray-600 rounded-full w-8 h-8 inline-flex items-center justify-center mr-2">2</span>
                        Choose Date
                    </div>
                    <div class="w-1/4 py-4 text-center text-gray-500" id="step-3-indicator">
                        <span class="bg-gray-200 text-gray-600 rounded-full w-8 h-8 inline-flex items-center justify-center mr-2">3</span>
                        Select Stylist
                    </div>
                    <div class="w-1/4 py-4 text-center text-gray-500" id="step-4-indicator">
                        <span class="bg-gray-200 text-gray-600 rounded-full w-8 h-8 inline-flex items-center justify-center mr-2">4</span>
                        Confirm
                    </div>
                </div>
            </div>

            <!-- Step 1: Service Selection -->
            <div id="step-1" class="p-6 fade-in">
                <h3 class="text-xl font-semibold mb-6 text-gray-700">Select a Service</h3>
                
                <!-- Category Filter -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category:</label>
                    <div class="flex flex-wrap gap-2">
                        <button class="category-filter active px-3 py-1 text-sm rounded-full bg-blue-500 text-white hover:bg-blue-600" 
                                data-category="all">All</button>
                        <?php 
                        $categories = getAllServiceCategories($conn);
                        foreach($categories as $category): ?>
                            <button class="category-filter px-3 py-1 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300" 
                                    data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card border rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors"
                             data-service-id="<?= $service['id'] ?>"
                             data-service-duration="<?= $service['duration'] ?>"
                             data-service-price="<?= $service['price'] ?>"
                             data-category="<?= htmlspecialchars($service['category']) ?>">
                            <div class="flex items-start">
                                <?php
                                // Check service name to determine which image to use
                                $imagePath = '';
                                $serviceName = strtolower($service['name']);
                                
                                if (strpos($serviceName, 'facial') !== false) {
                                    $imagePath = '../images/facial.jpg';
                                } elseif (strpos($serviceName, 'hair color') !== false || strpos($serviceName, 'coloring') !== false) {
                                    $imagePath = '../images/hair_coloring.jpg';
                                } elseif (strpos($serviceName, 'haircut') !== false || strpos($serviceName, 'styling') !== false || strpos($serviceName, 'hair') !== false) {
                                    $imagePath = '../images/haircut_style.jpg';
                                } elseif (strpos($serviceName, 'manicure') !== false) {
                                    $imagePath = '../images/manicure.jpg';
                                } elseif (strpos($serviceName, 'pedicure') !== false) {
                                    $imagePath = '../images/peducure.jpg';
                                }
                                
                                if (!empty($service['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($service['image']) ?>"
                                         alt="<?= htmlspecialchars($service['name']) ?>"
                                         class="w-20 h-20 object-cover rounded-lg mr-4">
                                <?php elseif (!empty($imagePath)): ?>
                                    <img src="<?= $imagePath ?>"
                                         alt="<?= htmlspecialchars($service['name']) ?>"
                                         class="w-20 h-20 object-cover rounded-lg mr-4">
                                <?php else: ?>
                                    <div class="w-20 h-20 bg-gray-200 rounded-lg mr-4 flex items-center justify-center">
                                        <i class="fas fa-spa text-gray-400 text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-semibold text-lg"><?= htmlspecialchars($service['name']) ?></h4>
                                        <div class="flex flex-col items-end">
                                            <?php if (isset($service['promotion']) && $service['promotion'] > 0): ?>
                                                <span class="text-gray-500 line-through text-sm">
                                                    RM<?= number_format(isset($service['original_price']) ? $service['original_price'] : $service['price'], 2) ?>
                                                </span>
                                                <span class="text-blue-600 font-medium">
                                                    RM<?= number_format($service['price'], 2) ?>
                                                    <span class="ml-1 text-xs bg-red-100 text-red-800 px-1 rounded-sm">-<?= $service['promotion'] ?>%</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-blue-600 font-medium">RM<?= number_format($service['price'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($service['description']) ?></p>
                                    <div class="flex items-center flex-wrap gap-2 mt-2">
                                        <span class="text-gray-500 text-sm flex items-center">
                                            <i class="fas fa-clock mr-1"></i> <?= $service['duration'] ?> mins
                                        </span>
                                        <?php if (!empty($service['category'])): ?>
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                                <?= htmlspecialchars($service['category']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($service['location'])): ?>
                                            <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($service['location']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step 2: Date Selection -->
            <div id="step-2" class="p-6 hidden fade-in">
                <h3 class="text-xl font-semibold mb-6 text-gray-700">Select a Date and Time</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-4">Choose Date</h4>
                        <div id="calendar" class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <button id="prev-month" class="text-gray-600 hover:text-blue-600">
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </button>
                                <h4 id="current-month" class="text-lg font-medium"></h4>
                                <button id="next-month" class="text-gray-600 hover:text-blue-600">
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-2 mb-2">
                                <div class="text-center font-medium text-gray-500">Sun</div>
                                <div class="text-center font-medium text-gray-500">Mon</div>
                                <div class="text-center font-medium text-gray-500">Tue</div>
                                <div class="text-center font-medium text-gray-500">Wed</div>
                                <div class="text-center font-medium text-gray-500">Thu</div>
                                <div class="text-center font-medium text-gray-500">Fri</div>
                                <div class="text-center font-medium text-gray-500">Sat</div>
                            </div>
                            <div id="calendar-days" class="grid grid-cols-7 gap-2"></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-4">Available Time Slots</h4>
                        <div id="time-slots-container" class="mb-4">
                            <p class="text-gray-500 text-center py-8">Please select a date first</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Stylist Selection -->
            <div id="step-3" class="p-6 hidden fade-in">
                <h3 class="text-xl font-semibold mb-6 text-gray-700">Select a Stylist</h3>
                <div id="stylists-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
            </div>

            <!-- Step 4: Confirmation -->
            <div id="step-4" class="p-6 hidden fade-in">
                <h3 class="text-xl font-semibold mb-6 text-gray-700">Confirm Your Booking</h3>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Appointment Details</h4>
                            <div class="space-y-2">
                                <p class="text-gray-600">
                                    <i class="fas fa-calendar mr-2 text-blue-500"></i>
                                    <span id="confirm-date"></span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-clock mr-2 text-blue-500"></i>
                                    <span id="confirm-time"></span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-user mr-2 text-blue-500"></i>
                                    <span id="confirm-stylist"></span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Service Details</h4>
                            <div class="space-y-2">
                                <p class="text-gray-600">
                                    <i class="fas fa-cut mr-2 text-blue-500"></i>
                                    <span id="confirm-service"></span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-clock mr-2 text-blue-500"></i>
                                    <span id="confirm-duration"></span> minutes
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-dollar-sign mr-2 text-blue-500"></i>
                                    RM<span id="confirm-price"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-gray-700 mb-2">Additional Notes (Optional)</label>
                        <textarea id="booking-notes" class="w-full border rounded-lg p-2" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-between">
                <button id="prev-step" class="text-gray-600 hover:text-blue-600 px-4 py-2 rounded-md border border-gray-300 hover:border-blue-300 transition hidden">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </button>
                <button id="next-step" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition ml-auto">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Debug output div -->
    <div id="debug-output" style="position: fixed; bottom: 10px; right: 10px; background: #f0f0f0; border: 1px solid #ccc; padding: 10px; max-width: 400px; max-height: 300px; overflow: auto; z-index: 9999;"></div>

    <script>
        // Booking state
        const bookingState = {
            currentStep: 1,
            selectedService: null,
            selectedDate: null,
            selectedTime: null,
            selectedStylist: null
        };

        // DOM Elements
        const stepElements = {
            1: document.getElementById('step-1'),
            2: document.getElementById('step-2'),
            3: document.getElementById('step-3'),
            4: document.getElementById('step-4')
        };

        const stepIndicators = {
            1: document.getElementById('step-1-indicator'),
            2: document.getElementById('step-2-indicator'),
            3: document.getElementById('step-3-indicator'),
            4: document.getElementById('step-4-indicator')
        };

        const prevButton = document.getElementById('prev-step');
        const nextButton = document.getElementById('next-step');

        // Event Listeners
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.service-card').forEach(c => 
                    c.classList.remove('border-blue-500'));
                card.classList.add('border-blue-500');
                
                bookingState.selectedService = {
                    id: card.dataset.serviceId,
                    duration: card.dataset.serviceDuration,
                    price: card.dataset.servicePrice,
                    name: card.querySelector('h4').textContent,
                    category: card.dataset.category
                };
                
                nextButton.disabled = false;
            });
        });

        // Calendar Navigation
        const calendar = {
            currentDate: new Date(),
            selectedDate: null,

            init() {
                this.renderCalendar();
                document.getElementById('prev-month').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.renderCalendar();
                });
                document.getElementById('next-month').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.renderCalendar();
                });
            },

            renderCalendar() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                
                document.getElementById('current-month').textContent = 
                    new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });
                
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDay = firstDay.getDay();
                
                const calendarDays = document.getElementById('calendar-days');
                calendarDays.innerHTML = '';
                
                // Add empty cells for days before the first day of the month
                for (let i = 0; i < startingDay; i++) {
                    calendarDays.appendChild(this.createDayElement(''));
                }
                
                // Add cells for each day of the month
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isDisabled = date < new Date().setHours(0, 0, 0, 0);
                    
                    const dayElement = this.createDayElement(day, isDisabled);
                    if (!isDisabled) {
                        dayElement.addEventListener('click', () => this.selectDate(date));
                    }
                    calendarDays.appendChild(dayElement);
                }
            },

            createDayElement(day, isDisabled = false) {
                const div = document.createElement('div');
                div.className = `calendar-day h-12 flex items-center justify-center rounded-lg 
                                ${isDisabled ? 'text-gray-300 cursor-not-allowed' : 'cursor-pointer hover:bg-blue-50'}
                                ${this.selectedDate && day && this.isSameDay(new Date(this.selectedDate), new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), day)) ? 'bg-blue-500 text-white' : ''}`;
                div.textContent = day;
                return div;
            },

            selectDate(date) {
                this.selectedDate = date;
                // Ensure date is in YYYY-MM-DD format
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                bookingState.selectedDate = `${year}-${month}-${day}`;
                
                console.log('Selected date (formatted):', bookingState.selectedDate);
                this.renderCalendar();
                loadTimeSlots(bookingState.selectedDate);
            },

            isSameDay(date1, date2) {
                return date1.getFullYear() === date2.getFullYear() &&
                       date1.getMonth() === date2.getMonth() &&
                       date1.getDate() === date2.getDate();
            }
        };

        // Initialize calendar
        calendar.init();

        // Load time slots for the selected date
        async function loadTimeSlots(date) {
            try {
                document.getElementById('time-slots-container').innerHTML = `
                    <div class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                `;
                
                const response = await fetch(`book_appointment.php?action=get_time_slots&date=${date}`);
                const data = await response.json();
                
                const container = document.getElementById('time-slots-container');
                container.innerHTML = '';
                
                if (!data.success || data.time_slots.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">No time slots available for this date</p>';
                    return;
                }
                
                const timeGrid = document.createElement('div');
                timeGrid.className = 'grid grid-cols-3 sm:grid-cols-4 gap-3';
                
                data.time_slots.forEach(slot => {
                    const timeSlot = document.createElement('div');
                    timeSlot.className = 'time-slot px-3 py-2 bg-white border rounded text-center cursor-pointer transition hover:bg-blue-50';
                    if (bookingState.selectedTime === slot.start_time) {
                        timeSlot.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
                    }
                    
                    timeSlot.textContent = formatTime(slot.start_time);
                    timeSlot.dataset.rawTime = slot.start_time; // Store the raw time value for submission
                    
                    timeSlot.addEventListener('click', () => {
                        // Reset all time slots
                        document.querySelectorAll('.time-slot').forEach(ts => {
                            ts.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                            ts.classList.add('bg-white');
                        });
                        
                        // Highlight selected time slot
                        timeSlot.classList.remove('bg-white');
                        timeSlot.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
                        
                        // Ensure time is in HH:MM:SS format
                        let timeValue = slot.start_time;
                        if (timeValue.includes(':')) {
                            // Make sure it has seconds
                            if (timeValue.split(':').length === 2) {
                                timeValue += ':00';
                            }
                        } else {
                            timeValue += ':00:00';
                        }
                        
                        bookingState.selectedTime = timeValue;
                        console.log('Selected time (formatted):', bookingState.selectedTime);
                        nextButton.disabled = false;
                    });
                    
                    timeGrid.appendChild(timeSlot);
                });
                
                container.appendChild(timeGrid);
                
            } catch (error) {
                console.error('Error loading time slots:', error);
                document.getElementById('time-slots-container').innerHTML = `
                    <p class="text-red-500 text-center py-4">Failed to load time slots</p>
                    <button onclick="loadTimeSlots('${date}')" class="mx-auto block px-4 py-2 bg-blue-500 text-white rounded-md">Retry</button>
                `;
            }
        }

        // Stylist selection
        async function loadStylists() {
            try {
                const container = document.getElementById('stylists-container');
                container.innerHTML = `
                    <div class="col-span-full flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                `;
                
                // Include the selected service ID to get specialists for this service
                const response = await fetch(`book_appointment.php?action=get_stylists&date=${bookingState.selectedDate}&service_id=${bookingState.selectedService.id}`);
                const data = await response.json();
                
                container.innerHTML = '';
                
                if (!data.success) {
                    container.innerHTML = `
                        <p class="text-red-500 text-center py-4 col-span-full">Failed to load stylists: ${data.message}</p>
                        <button onclick="loadStylists()" class="mx-auto block px-4 py-2 bg-blue-500 text-white rounded-md">Retry</button>
                    `;
                    return;
                }
                
                if (data.stylists.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4 col-span-full">No stylists available for this date</p>';
                    return;
                }
                
                data.stylists.forEach(stylist => {
                    try {
                        const card = document.createElement('div');
                        card.className = 'stylist-card bg-white rounded-lg shadow-md p-4 cursor-pointer transition-all duration-300 hover:shadow-lg';
                        
                        if (bookingState.selectedStylist && bookingState.selectedStylist.id === stylist.id) {
                            card.classList.add('border-2', 'border-blue-500');
                        }
                        
                        // Basic stylist info
                        let cardHTML = `
                            <div class="flex items-center mb-4">
                                ${stylist.profile_image ?
                                    `<img src="${stylist.profile_image}" alt="${stylist.name || 'Stylist'}" class="w-16 h-16 rounded-full object-cover mr-4">` :
                                    `<div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                                    </div>`
                                }
                                <div>
                                    <h4 class="font-medium text-lg">${stylist.name || 'Stylist'}</h4>
                                    <p class="text-gray-500">${stylist.specialization || 'General Stylist'}</p>
                                </div>
                            </div>
                        `;
                        
                        // Add bio if available
                        if (stylist.bio) {
                            cardHTML += `<p class="text-gray-600 text-sm mb-4">${stylist.bio}</p>`;
                        }
                        
                        // Add specialties/service assignments
                        if (stylist.specialties && Array.isArray(stylist.specialties) && stylist.specialties.length > 0) {
                            cardHTML += `
                                <div class="mb-3">
                                    <p class="text-sm font-medium text-gray-700 mb-1">Specialties:</p>
                                    <div class="flex flex-wrap gap-1">
                            `;
                            
                            // Show up to 3 specialties
                            const displaySpecialties = stylist.specialties.slice(0, 3);
                            displaySpecialties.forEach(specialty => {
                                if (specialty && specialty.name) {
                                    cardHTML += `
                                        <span class="inline-block px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded-full">
                                            ${specialty.name}
                                        </span>
                                    `;
                                }
                            });
                            
                            // Show count of additional specialties
                            if (stylist.specialties.length > 3) {
                                cardHTML += `
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                                        +${stylist.specialties.length - 3} more
                                    </span>
                                `;
                            }
                            
                            cardHTML += `
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add availability info
                        if (stylist.availability) {
                            const isAvailable = Boolean(stylist.availability.is_available);
                            const availText = isAvailable ? 
                                `Available ${formatTime(stylist.availability.start_time)} - ${formatTime(stylist.availability.end_time)}` :
                                'Not available on this date';
                                
                            cardHTML += `
                                <div class="mb-3">
                                    <p class="text-sm ${isAvailable ? 'text-green-600' : 'text-red-600'}">
                                        <i class="fas fa-${isAvailable ? 'check-circle' : 'times-circle'} mr-1"></i>
                                        ${availText}
                                    </p>
                                </div>
                            `;
                        }
                        
                        // Add rating if available
                        if (stylist.rating !== undefined && stylist.rating !== null) {
                            const ratingValue = parseFloat(stylist.rating);
                            if (!isNaN(ratingValue)) {
                                cardHTML += `
                                    <div class="flex items-center">
                                        <div class="flex text-yellow-400">
                                            ${getStarRating(ratingValue)}
                                        </div>
                                        <span class="text-gray-600 text-sm ml-2">${ratingValue.toFixed(1)}</span>
                                    </div>
                                `;
                            }
                        }
                        
                        card.innerHTML = cardHTML;
                        
                        card.addEventListener('click', () => {
                            // Skip if stylist is not available for this date
                            if (stylist.availability && !stylist.availability.is_available) {
                                alert('This stylist is not available on the selected date. Please choose another stylist or date.');
                                return;
                            }
                            
                            // Reset all stylist cards
                            document.querySelectorAll('.stylist-card').forEach(c => {
                                c.classList.remove('border-2', 'border-blue-500');
                            });
                            
                            // Highlight selected stylist
                            card.classList.add('border-2', 'border-blue-500');
                            
                            bookingState.selectedStylist = stylist;
                            nextButton.disabled = false;
                        });
                        
                        container.appendChild(card);
                    } catch (error) {
                        console.error('Error creating stylist card:', error, stylist);
                        // If there's an error with one stylist, continue with others
                    }
                });
            } catch (error) {
                console.error('Error loading stylists:', error);
                document.getElementById('stylists-container').innerHTML = `
                    <p class="text-red-500 text-center py-4 col-span-full">Failed to load stylists: ${error.message}</p>
                    <button onclick="loadStylists()" class="mx-auto block px-4 py-2 bg-blue-500 text-white rounded-md">Retry</button>
                `;
            }
        }

        function getStarRating(rating) {
            // Ensure rating is a valid number
            const numRating = parseFloat(rating);
            if (isNaN(numRating) || numRating <= 0) {
                return '<i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>';
            }
            
            let stars = '';
            const fullStars = Math.floor(numRating);
            const halfStar = (numRating % 1) >= 0.5;
            
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star"></i>';
            }
            
            if (halfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            }
            
            const emptyStars = 5 - Math.ceil(numRating);
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star"></i>';
            }
            
            return stars;
        }

        function formatTime(time) {
            return new Date(`2000-01-01T${time}`).toLocaleTimeString([], { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }

        // Navigation
        function updateStep(step) {
            Object.values(stepElements).forEach(el => el.classList.add('hidden'));
            stepElements[step].classList.remove('hidden');
            
            Object.entries(stepIndicators).forEach(([num, el]) => {
                if (num <= step) {
                    el.classList.remove('text-gray-500');
                    el.classList.add('text-blue-600');
                    el.querySelector('span').classList.remove('bg-gray-200');
                    el.querySelector('span').classList.add('bg-blue-600', 'text-white');
                } else {
                    el.classList.add('text-gray-500');
                    el.classList.remove('text-blue-600');
                    el.querySelector('span').classList.add('bg-gray-200');
                    el.querySelector('span').classList.remove('bg-blue-600', 'text-white');
                }
            });
            
            bookingState.currentStep = step;
            
            prevButton.classList.toggle('hidden', step === 1);
            nextButton.textContent = step === 4 ? 'Confirm Booking' : 'Next';
            
            if (step === 2) {
                calendar.renderCalendar();
                if (bookingState.selectedDate) {
                    loadTimeSlots(bookingState.selectedDate);
                }
            } else if (step === 3) {
                loadStylists();
            } else if (step === 4) {
                updateConfirmationDetails();
            }
            
            // Initialize next button state based on selections
            nextButton.disabled = (step === 1 && !bookingState.selectedService) || 
                                 (step === 2 && (!bookingState.selectedDate || !bookingState.selectedTime)) ||
                                 (step === 3 && !bookingState.selectedStylist);
        }

        function updateConfirmationDetails() {
            document.getElementById('confirm-service').textContent = bookingState.selectedService.name;
            document.getElementById('confirm-duration').textContent = bookingState.selectedService.duration;
            document.getElementById('confirm-price').textContent = bookingState.selectedService.price;
            document.getElementById('confirm-date').textContent = new Date(bookingState.selectedDate).toLocaleDateString();
            document.getElementById('confirm-time').textContent = formatTime(bookingState.selectedTime);
            document.getElementById('confirm-stylist').textContent = bookingState.selectedStylist.name;
            
            // Add verification to ensure all required data is available
            const missingData = [];
            
            if (!bookingState.selectedService?.id) missingData.push('service');
            if (!bookingState.selectedStylist?.id) missingData.push('stylist');
            if (!bookingState.selectedDate) missingData.push('date');
            if (!bookingState.selectedTime) missingData.push('time');
            
            if (missingData.length > 0) {
                console.error('Missing booking data:', missingData);
                alert(`Some required booking information is missing: ${missingData.join(', ')}. Please go back and select all required options.`);
                nextButton.disabled = true;
            } else {
                console.log('All booking data is available for submission:', {
                    service_id: bookingState.selectedService.id,
                    stylist_id: bookingState.selectedStylist.id,
                    appointment_date: bookingState.selectedDate,
                    start_time: bookingState.selectedTime
                });
                nextButton.disabled = false;
            }
        }

        prevButton.addEventListener('click', () => {
            if (bookingState.currentStep > 1) {
                updateStep(bookingState.currentStep - 1);
            }
        });

        nextButton.addEventListener('click', async () => {
            // Validate current step before proceeding
            if (bookingState.currentStep === 1 && !bookingState.selectedService) {
                alert('Please select a service first');
                return;
            } else if (bookingState.currentStep === 2 && (!bookingState.selectedDate || !bookingState.selectedTime)) {
                alert('Please select both date and time');
                return;
            } else if (bookingState.currentStep === 3 && !bookingState.selectedStylist) {
                alert('Please select a stylist');
                return;
            }
            
            if (bookingState.currentStep < 4) {
                updateStep(bookingState.currentStep + 1);
            } else {
                // Submit booking
                try {
                    // --- RIGOROUS PRE-SUBMISSION CHECK --- 
                    console.log('[PRE-SUBMIT CHECK] Current Booking State:', JSON.stringify(bookingState, null, 2));
                    
                    // Validate all required fields
                    const missingFields = [];
                    if (!bookingState.selectedService?.id) missingFields.push('service');
                    if (!bookingState.selectedStylist?.id) missingFields.push('stylist');
                    if (!bookingState.selectedDate) missingFields.push('date');
                    if (!bookingState.selectedTime) missingFields.push('time');
                    
                    if (missingFields.length > 0) {
                        alert(`Missing required information: ${missingFields.join(', ')}`);
                        return;
                    }
                    
                    // Ensure values are in correct format
                    let formattedDate = bookingState.selectedDate;
                    let formattedTime = bookingState.selectedTime;
                    
                    // Re-validate the date format
                    if (!formattedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        // Try to reformat if it's a Date object or some other format
                        const dateObj = new Date(formattedDate);
                        if (!isNaN(dateObj.getTime())) {
                            const year = dateObj.getFullYear();
                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const day = String(dateObj.getDate()).padStart(2, '0');
                            formattedDate = `${year}-${month}-${day}`;
                        } else {
                            console.error('Unable to format date:', formattedDate);
                            alert('There was a problem with the date format. Please go back and try again.');
                            nextButton.disabled = false;
                            nextButton.textContent = 'Confirm Booking';
                            return;
                        }
                    }
                    
                    // Re-validate the time format
                    if (!formattedTime.match(/^\d{2}:\d{2}(:\d{2})?$/)) {
                        console.error('Invalid time format:', formattedTime);
                        alert('There was a problem with the time format. Please go back and try again.');
                        nextButton.disabled = false;
                        nextButton.textContent = 'Confirm Booking';
                        return;
                    }
                    
                    // Ensure time has seconds
                    if (formattedTime.split(':').length === 2) {
                        formattedTime += ':00';
                    }
                    
                    console.log('Final formatted date:', formattedDate);
                    console.log('Final formatted time:', formattedTime);
                    
                    // Create data as URL parameters for most reliable transmission
                    const appointmentData = new URLSearchParams();
                    appointmentData.append('service_id', bookingState.selectedService.id);
                    appointmentData.append('stylist_id', bookingState.selectedStylist.id);
                    appointmentData.append('appointment_date', formattedDate);
                    appointmentData.append('start_time', formattedTime);
                    appointmentData.append('notes', document.getElementById('booking-notes').value || '');
                    
                    // Also add parameters to URL as a backup
                    const apiUrl = `book_appointment.php?action=create_appointment&service_id=${
                        encodeURIComponent(bookingState.selectedService.id)
                    }&stylist_id=${
                        encodeURIComponent(bookingState.selectedStylist.id)
                    }&appointment_date=${
                        encodeURIComponent(formattedDate)
                    }&start_time=${
                        encodeURIComponent(formattedTime)
                    }`;
                    
                    // Log the exact data being sent
                    console.log('API URL:', apiUrl);
                    console.log('Sending data (URL encoded):', appointmentData.toString());
                    
                    // Disable button and show loading state
                    nextButton.disabled = true;
                    nextButton.innerHTML = `
                        <span class="inline-block animate-spin mr-2 h-4 w-4 border-t-2 border-white rounded-full"></span>
                        Processing...
                    `;
                    
                    // IMPORTANT: Use URL encoded format and ensure Content-Type is properly set
                    try {
                        console.log('ABOUT TO SEND REQUEST');
                        document.getElementById('debug-output').innerHTML = `
                            <p>Attempting to send booking with:</p>
                            <pre>${JSON.stringify({
                                service_id: bookingState.selectedService.id,
                                stylist_id: bookingState.selectedStylist.id,
                                appointment_date: formattedDate,
                                start_time: formattedTime
                            }, null, 2)}</pre>
                        `;
                        
                        // SIMPLIFIED VERSION - just focus on the core parameters
                        const simpleParams = new URLSearchParams();
                        simpleParams.append('service_id', bookingState.selectedService.id);
                        simpleParams.append('stylist_id', bookingState.selectedStylist.id);
                        simpleParams.append('appointment_date', formattedDate);
                        simpleParams.append('start_time', formattedTime);
                        
                        // Try the most direct method possible
                        const response = await fetch('book_appointment.php?action=create_appointment', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: simpleParams.toString()
                        });
                        
                        console.log('Response status:', response.status);
                        const responseText = await response.text();
                        console.log('Raw response:', responseText);
                        
                        // Parse response
                        let result;
                        try {
                            result = JSON.parse(responseText);
                            console.log('Parsed result:', result);
                        } catch (parseError) {
                            console.error('Failed to parse response as JSON:', parseError);
                            alert('Server returned an invalid response. Please try again.');
                            console.error('Raw response text:', responseText);
                            nextButton.disabled = false;
                            nextButton.textContent = 'Confirm Booking';
                            return;
                        }
                        
                        if (result.success) {
                            alert('Booking confirmed! You will receive a confirmation notification.');
                            window.location.href = 'appointments.php';
                        } else {
                            alert('Error: ' + result.message);
                            console.error('Server error:', result.message);
                            nextButton.disabled = false;
                            nextButton.textContent = 'Confirm Booking';
                        }
                    } catch (error) {
                        console.error('Network error during booking:', error);
                        alert('A network error occurred. Please check your connection and try again.');
                        nextButton.disabled = false;
                        nextButton.textContent = 'Confirm Booking';
                    }
                } catch (error) {
                    console.error('Error creating appointment:', error);
                    alert('An error occurred while creating the appointment. Please try again.');
                    nextButton.disabled = false;
                    nextButton.textContent = 'Confirm Booking';
                }
            }
        });

        // Initialize first step
        updateStep(1);
        
        // Set up category filtering
        document.querySelectorAll('.category-filter').forEach(button => {
            button.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.category-filter').forEach(btn => {
                    btn.classList.remove('bg-blue-500', 'text-white');
                    btn.classList.add('bg-gray-200', 'text-gray-700');
                });
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('bg-blue-500', 'text-white');
                
                const category = this.getAttribute('data-category');
                
                // Filter services
                document.querySelectorAll('.service-card').forEach(card => {
                    if (category === 'all' || card.getAttribute('data-category') === category) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>

    <!-- Notification system script -->
    <script>
        // Notification system
        document.addEventListener('DOMContentLoaded', function() {
            const notificationsBtn = document.getElementById('notifications-btn');
            const notificationsDropdown = document.getElementById('notifications-dropdown');
            const notificationBadge = document.getElementById('notification-badge');
            const notificationsList = document.getElementById('notifications-list');
            const markAllReadBtn = document.getElementById('mark-all-read');
            
            // Toggle notifications dropdown
            notificationsBtn.addEventListener('click', function() {
                notificationsDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
                    notificationsDropdown.classList.add('hidden');
                }
            });
            
            // Mark all as read
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                markAllNotificationsAsRead();
            });
            
            // Function to fetch notifications
            function fetchNotifications() {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateNotificationDisplay(data.notifications);
                        } else {
                            console.error('Error fetching notifications:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
            
            // Function to update notification display
            function updateNotificationDisplay(notifications) {
                const unreadCount = notifications.filter(n => !n.is_read).length;
                
                // Update badge
                if (unreadCount > 0) {
                    notificationBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                    notificationBadge.classList.remove('hidden');
                } else {
                    notificationBadge.classList.add('hidden');
                }
                
                // Update list
                if (notifications.length === 0) {
                    notificationsList.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-bell-slash text-gray-300 text-2xl mb-2"></i>
                            <p>No new notifications</p>
                        </div>
                    `;
                    return;
                }
                
                notificationsList.innerHTML = '';
                notifications.slice(0, 5).forEach(notification => {
                    const timeAgo = formatTimeAgo(new Date(notification.created_at));
                    let iconClass = 'fas fa-bell';
                    let bgColorClass = 'bg-blue-100';
                    let textColorClass = 'text-blue-600';
                    
                    // Set icon and colors based on notification type
                    if (notification.type.includes('confirmation') || notification.type.includes('booked')) {
                        iconClass = 'fas fa-check-circle';
                        bgColorClass = 'bg-green-100';
                        textColorClass = 'text-green-600';
                    } else if (notification.type.includes('cancellation') || notification.type.includes('cancelled')) {
                        iconClass = 'fas fa-times-circle';
                        bgColorClass = 'bg-red-100';
                        textColorClass = 'text-red-600';
                    } else if (notification.type.includes('reminder')) {
                        iconClass = 'fas fa-clock';
                        bgColorClass = 'bg-yellow-100';
                        textColorClass = 'text-yellow-600';
                    } else if (notification.type.includes('modification') || notification.type.includes('rescheduled')) {
                        iconClass = 'fas fa-calendar-alt';
                        bgColorClass = 'bg-purple-100';
                        textColorClass = 'text-purple-600';
                    }
                    
                    // Check if we have detailed appointment data
                    const hasAppointmentDetails = notification.service_name || notification.appointment_date;
                    
                    const notificationItem = document.createElement('div');
                    notificationItem.className = `p-3 border-b hover:bg-gray-50 cursor-pointer ${notification.is_read ? 'opacity-60' : ''}`;
                    
                    // Create enhanced notification HTML with more booking details
                    notificationItem.innerHTML = `
                        <div class="flex items-start">
                            <div class="${bgColorClass} p-2 rounded-full mr-3">
                                <i class="${iconClass} ${textColorClass}"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="text-sm font-medium">${notification.message.split('.')[0]}</p>
                                    <span class="text-xs text-gray-500 ml-2 whitespace-nowrap">${timeAgo}</span>
                                </div>
                                
                                ${hasAppointmentDetails ? `
                                <div class="mt-1 pt-1 border-t border-gray-100">
                                    ${notification.service_name ? `
                                    <p class="text-xs text-gray-600">
                                        <i class="fas fa-spa mr-1 ${textColorClass}"></i> 
                                        ${notification.service_name}
                                    </p>` : ''}
                                    
                                    ${notification.formatted_date ? `
                                    <p class="text-xs text-gray-600">
                                        <i class="far fa-calendar-alt mr-1 ${textColorClass}"></i> 
                                        ${notification.formatted_date}
                                    </p>` : ''}
                                    
                                    ${notification.formatted_time ? `
                                    <p class="text-xs text-gray-600">
                                        <i class="far fa-clock mr-1 ${textColorClass}"></i> 
                                        ${notification.formatted_time}
                                    </p>` : ''}
                                    
                                    ${notification.stylist_name ? `
                                    <p class="text-xs text-gray-600">
                                        <i class="fas fa-user-tie mr-1 ${textColorClass}"></i> 
                                        ${notification.stylist_name}
                                    </p>` : ''}
                                    
                                    ${notification.status ? `
                                    <p class="text-xs text-gray-600">
                                        <i class="fas fa-info-circle mr-1 ${textColorClass}"></i> 
                                        Status: ${notification.status.charAt(0).toUpperCase() + notification.status.slice(1)}
                                    </p>` : ''}
                                </div>` : ''}
                                
                                <div class="mt-1 text-right">
                                    <a href="appointments.php" class="text-xs ${textColorClass}">View details</a>
                                </div>
                            </div>
                            ${!notification.is_read ? '<span class="h-2 w-2 rounded-full bg-blue-500 flex-shrink-0 mt-1"></span>' : ''}
                        </div>
                    `;
                    
                    notificationItem.addEventListener('click', () => {
                        markNotificationAsRead(notification.id);
                    });
                    
                    notificationsList.appendChild(notificationItem);
                });
                
                // Add "See all" link if there are more than 5 notifications
                if (notifications.length > 5) {
                    const seeAllLink = document.createElement('div');
                    seeAllLink.className = 'p-3 text-center border-t';
                    seeAllLink.innerHTML = '<a href="all_notifications.php" class="text-blue-600 text-sm">See all notifications</a>';
                    notificationsList.appendChild(seeAllLink);
                }
            }
            
            // Function to mark a notification as read
            function markNotificationAsRead(notificationId) {
                fetch('update_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchNotifications();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            // Function to mark all notifications as read
            function markAllNotificationsAsRead() {
                fetch('update_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=mark_all_read'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchNotifications();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            // Format time ago function
            function formatTimeAgo(date) {
                const seconds = Math.floor((new Date() - date) / 1000);
                
                let interval = Math.floor(seconds / 31536000);
                if (interval >= 1) return interval + ' year' + (interval > 1 ? 's' : '') + ' ago';
                
                interval = Math.floor(seconds / 2592000);
                if (interval >= 1) return interval + ' month' + (interval > 1 ? 's' : '') + ' ago';
                
                interval = Math.floor(seconds / 86400);
                if (interval >= 1) return interval + ' day' + (interval > 1 ? 's' : '') + ' ago';
                
                interval = Math.floor(seconds / 3600);
                if (interval >= 1) return interval + ' hour' + (interval > 1 ? 's' : '') + ' ago';
                
                interval = Math.floor(seconds / 60);
                if (interval >= 1) return interval + ' minute' + (interval > 1 ? 's' : '') + ' ago';
                
                return 'just now';
            }
            
            // Initial fetch
            fetchNotifications();
            
            // Set up periodic refresh every 30 seconds
            setInterval(fetchNotifications, 30000);
        });
    </script>
</body>
</html> 