<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - Harmony Heaven Spa</title>
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
        
        .time-slot:hover:not(.booked) {
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
                <a href="appointments.php" class="text-gray-600 hover:text-pink-500">
                    <i class="fas fa-calendar-check mr-1"></i> My Appointments
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-6">
                <a href="appointments.php" class="text-gray-600 hover:text-pink-500 mr-4">
                    <i class="fas fa-arrow-left"></i> Back to My Appointments
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Reschedule Appointment</h1>
            </div>

            <!-- Service Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Current Service</h2>
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div>
                        <p class="text-lg font-medium text-gray-700" id="serviceName">Loading...</p>
                        <div class="flex items-center text-gray-600 mt-1">
                            <p class="mr-4"><i class="fas fa-clock mr-2 text-pink-500"></i><span id="serviceDuration">0</span> minutes</p>
                            <p><i class="fas fa-dollar-sign mr-2 text-pink-500"></i>$<span id="servicePrice">0.00</span></p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 text-right">
                        <p class="text-gray-700 font-medium">Original Stylist</p>
                        <p class="text-gray-600" id="stylistName">Loading...</p>
                    </div>
                </div>
            </div>

            <!-- Step 1: Date Selection -->
            <div id="step1" class="bg-white rounded-lg shadow-lg p-6 fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Choose a New Date & Time</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Select a Date</h3>
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <button id="prevMonth" class="text-gray-600 hover:text-pink-500">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <h3 id="currentMonth" class="text-xl font-semibold text-gray-700">Loading...</h3>
                                <button id="nextMonth" class="text-gray-600 hover:text-pink-500">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-2 mb-2">
                                <div class="text-center text-sm font-medium text-gray-500">Sun</div>
                                <div class="text-center text-sm font-medium text-gray-500">Mon</div>
                                <div class="text-center text-sm font-medium text-gray-500">Tue</div>
                                <div class="text-center text-sm font-medium text-gray-500">Wed</div>
                                <div class="text-center text-sm font-medium text-gray-500">Thu</div>
                                <div class="text-center text-sm font-medium text-gray-500">Fri</div>
                                <div class="text-center text-sm font-medium text-gray-500">Sat</div>
                            </div>
                            <div id="calendar" class="grid grid-cols-7 gap-2">
                                <!-- Calendar days will be generated here -->
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Select a Time</h3>
                        <div id="timeSlotContainer" class="grid grid-cols-2 gap-2">
                            <p class="col-span-2 text-gray-500 text-center">Please select a date first</p>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 mb-2">Time slot availability:</p>
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mr-1"></div>
                                    <span class="text-xs text-gray-600">High</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-1"></div>
                                    <span class="text-xs text-gray-600">Medium</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-red-500 rounded-full mr-1"></div>
                                    <span class="text-xs text-gray-600">Low</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Confirmation -->
            <div id="step2" class="bg-white rounded-lg shadow-lg p-6 hidden fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Confirm Your Reschedule</h2>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">New Appointment Details</h3>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <i class="far fa-calendar-alt mr-2 text-pink-500"></i>
                                    <span id="confirmDate">Loading...</span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="far fa-clock mr-2 text-pink-500"></i>
                                    <span id="confirmTime">Loading...</span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Service Details</h3>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <i class="fas fa-spa mr-2 text-pink-500"></i>
                                    <span id="confirmService">Loading...</span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-clock mr-2 text-pink-500"></i>
                                    <span id="confirmDuration">0 minutes</span>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-dollar-sign mr-2 text-pink-500"></i>
                                    <span id="confirmPrice">$0.00</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Additional Notes (Optional)</label>
                    <textarea id="bookingNotes" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" rows="3" placeholder="Any special requests or notes for your rescheduled appointment..."></textarea>
                </div>
                <div class="flex justify-between">
                    <button onclick="showStep(1)" class="px-4 py-2 text-gray-600 hover:text-pink-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                    <button onclick="confirmReschedule()" class="px-6 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                        Confirm Reschedule <i class="fas fa-check ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let appointmentId = null;
        let serviceId = null;
        let serviceName = null;
        let servicePrice = null;
        let serviceDuration = null;
        let stylistId = null;
        let stylistName = null;
        let selectedDate = null;
        let selectedTime = null;
        let currentMonth = new Date();
        let availableDates = [];
        let availableTimeSlots = [];

        // Function to show specific step
        function showStep(step) {
            document.querySelectorAll('[id^="step"]').forEach(el => el.classList.add('hidden'));
            document.getElementById(`step${step}`).classList.remove('hidden');
        }

        // Function to load appointment details from sessionStorage
        function loadAppointmentDetails() {
            appointmentId = sessionStorage.getItem('reschedule_appointment_id');
            serviceName = sessionStorage.getItem('reschedule_service_name');
            serviceDuration = sessionStorage.getItem('reschedule_duration');
            servicePrice = sessionStorage.getItem('reschedule_price');
            serviceId = sessionStorage.getItem('reschedule_service_id');
            stylistId = sessionStorage.getItem('reschedule_stylist_id');
            
            if (!appointmentId) {
                alert('No appointment selected for rescheduling');
                window.location.href = 'appointments.php';
                return;
            }
            
            // Load stylist info
            fetch(`get_stylist_info.php?stylist_id=${stylistId}`)
                .then(response => response.json())
                .then(data => {
                    stylistName = data.name;
                    document.getElementById('stylistName').textContent = stylistName;
                })
                .catch(error => {
                    console.error('Error loading stylist info:', error);
                    document.getElementById('stylistName').textContent = 'Unknown Stylist';
                });
            
            // Update service info display
            document.getElementById('serviceName').textContent = serviceName;
            document.getElementById('serviceDuration').textContent = serviceDuration;
            document.getElementById('servicePrice').textContent = servicePrice;
            
            // Also update confirmation info
            document.getElementById('confirmService').textContent = serviceName;
            document.getElementById('confirmDuration').textContent = `${serviceDuration} minutes`;
            document.getElementById('confirmPrice').textContent = `$${servicePrice}`;
            
            // Load available dates
            loadAvailableDates();
        }

        // Function to load available dates
        function loadAvailableDates() {
            // Show loading indicator in calendar
            document.getElementById('calendar').innerHTML = `
                <div class="col-span-7 text-center py-8">
                    <div class="mt-4 flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                    </div>
                </div>
            `;
            
            fetch(`get_available_dates.php?service_id=${serviceId}&stylist_id=${stylistId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(dates => {
                    availableDates = dates;
                    renderCalendar();
                })
                .catch(error => {
                    console.error('Error loading dates:', error);
                    document.getElementById('calendar').innerHTML = `
                        <div class="col-span-7 text-center py-8">
                            <p class="text-red-500">Failed to load available dates. Please try again.</p>
                            <button onclick="loadAvailableDates()" class="mt-4 px-4 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600">
                                Retry
                            </button>
                        </div>
                    `;
                });
        }

        // Function to render calendar
        function renderCalendar() {
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            
            // Update month display
            document.getElementById('currentMonth').textContent = 
                currentMonth.toLocaleString('default', { month: 'long', year: 'numeric' });
            
            // Get first day of month and total days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const totalDays = lastDay.getDate();
            
            // Generate calendar HTML
            let calendarHTML = '';
            
            // Add empty cells for days before first day of month
            for (let i = 0; i < firstDay.getDay(); i++) {
                calendarHTML += '<div class="h-10"></div>';
            }
            
            // Add days of month
            for (let day = 1; day <= totalDays; day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                
                // Check if date is in available dates
                const availableDate = availableDates.find(d => d.date === dateStr);
                const isAvailable = !!availableDate;
                const isPast = date < new Date().setHours(0,0,0,0);
                const isToday = new Date().toISOString().split('T')[0] === dateStr;
                
                // If it's past or not available, it's disabled
                const isDisabled = isPast || !isAvailable;
                
                // Calendar day classes
                let dayClasses = 'w-10 h-10 rounded-full flex items-center justify-center';
                
                if (selectedDate === dateStr) {
                    dayClasses += ' bg-pink-500 text-white'; 
                } else if (isToday) {
                    dayClasses += ' bg-pink-100 text-pink-800';
                } else if (isDisabled) {
                    dayClasses += ' text-gray-300 cursor-not-allowed';
                } else {
                    dayClasses += ' text-gray-700 hover:bg-pink-100 cursor-pointer';
                }
                
                // For weekend days, add a background tint
                if (date.getDay() === 0 || date.getDay() === 6) {
                    if (!isDisabled && !isToday && selectedDate !== dateStr) {
                        dayClasses += ' bg-gray-50';
                    }
                }
                
                calendarHTML += `
                    <div class="h-10 flex items-center justify-center">
                        <div 
                            class="${dayClasses}"
                            onclick="${isDisabled ? '' : `selectDate('${dateStr}')`}"
                            ${isAvailable ? `data-slots="${availableDate.available_slots}"` : ''}
                        >
                            ${day}
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('calendar').innerHTML = calendarHTML;
        }

        // Function to select a date
        function selectDate(dateStr) {
            selectedDate = dateStr;
            
            // Reset time selection
            selectedTime = null;
            
            // Update calendar display
            renderCalendar();
            
            // Load time slots for this date
            loadTimeSlots(dateStr);
        }
        
        // Function to load time slots
        function loadTimeSlots(dateStr) {
            const container = document.getElementById('timeSlotContainer');
            
            // Show loading indicator
            container.innerHTML = `
                <div class="col-span-2 text-center py-8">
                    <p class="text-gray-500">Loading available times...</p>
                    <div class="mt-4 flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                    </div>
                </div>
            `;
            
            // Get time slots for the selected date
            fetch(`get_available_time_slots.php?date=${dateStr}&service_id=${serviceId}&stylist_id=${stylistId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Check if the API returned an error
                    if (data.error) {
                        throw new Error(data.message || data.error);
                    }
                
                    // Check if the response has a 'slots' property (new format) or is an array (old format)
                    const timeSlots = data.slots || data;
                    availableTimeSlots = timeSlots;
                    
                    if (!timeSlots || timeSlots.length === 0) {
                        container.innerHTML = `
                            <div class="col-span-2 text-center py-8">
                                <p class="text-gray-500">No available time slots for this date.</p>
                                <button onclick="loadTimeSlots('${dateStr}')" class="mt-2 px-4 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600 transition">
                                    <i class="fas fa-sync-alt mr-1"></i> Try Again
                                </button>
                            </div>
                        `;
                        return;
                    }
                    
                    // Render time slots
                    container.innerHTML = timeSlots.map(slot => {
                        // Determine availability color
                        let availabilityColor = '';
                        switch (slot.availability) {
                            case 1: availabilityColor = 'bg-red-500'; break;  // Low availability
                            case 2: availabilityColor = 'bg-yellow-500'; break; // Medium availability
                            case 3: availabilityColor = 'bg-green-500'; break; // High availability
                            default: availabilityColor = 'bg-green-500'; break; // Default to high
                        }
                        
                        // Determine if peak hour (styled differently)
                        const peakClass = slot.is_peak ? 'border border-pink-300 bg-pink-50' : '';
                        
                        // Safely get formatted times with fallbacks
                        const formattedTime = slot.formatted_time || formatTimeManually(slot.time);
                        const formattedEndTime = slot.formatted_end_time || formatTimeManually(slot.end_time);
                        
                        return `
                            <div 
                                class="time-slot p-3 rounded-md border border-gray-200 cursor-pointer hover:border-pink-500 ${peakClass} ${selectedTime === slot.time ? 'border-pink-500 bg-pink-50' : ''}"
                                onclick="selectTime('${slot.time}')"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-800">${formattedTime}</span>
                                    <div class="w-3 h-3 rounded-full ${availabilityColor}"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    ${formattedTime} - ${formattedEndTime}
                                </div>
                            </div>
                        `;
                    }).join('');
                })
                .catch(error => {
                    console.error('Error loading time slots:', error);
                    container.innerHTML = `
                        <div class="col-span-2 text-center py-8">
                            <p class="text-red-500 mb-2">Failed to load time slots. Please try again.</p>
                            ${error.message ? `<p class="text-gray-500 text-sm mb-2">${error.message}</p>` : ''}
                            <button onclick="loadTimeSlots('${dateStr}')" class="mt-2 px-4 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600 transition">
                                <i class="fas fa-sync-alt mr-1"></i> Retry
                            </button>
                        </div>
                    `;
                });
        }
        
        // Function to select a time
        function selectTime(time) {
            selectedTime = time;
            
            // Update time slot display
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('border-pink-500', 'bg-pink-50');
            });
            
            const selectedSlot = document.querySelector(`.time-slot[onclick="selectTime('${time}')"]`);
            if (selectedSlot) {
                selectedSlot.classList.add('border-pink-500', 'bg-pink-50');
            }
            
            // Get the selected time slot details
            const timeSlot = availableTimeSlots.find(slot => slot.time === time);
            
            // Update confirmation details
            document.getElementById('confirmDate').textContent = 
                new Date(selectedDate).toLocaleString('default', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            document.getElementById('confirmTime').textContent = 
                timeSlot ? `${timeSlot.formatted_time} - ${timeSlot.formatted_end_time}` : '';
            
            // Move to confirmation step
            showStep(2);
        }

        // Function to confirm rescheduling
        function confirmReschedule() {
            if (!selectedDate || !selectedTime) {
                alert('Please select a date and time');
                return;
            }

            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('date', selectedDate);
            formData.append('time', selectedTime);
            formData.append('notes', document.getElementById('bookingNotes').value);

            // Show loading state
            const confirmButton = document.querySelector('button[onclick="confirmReschedule()"]');
            const originalText = confirmButton.innerHTML;
            confirmButton.disabled = true;
            confirmButton.innerHTML = `
                <div class="inline-block animate-spin h-4 w-4 border-2 border-white rounded-full border-t-transparent mr-2"></div>
                Processing...
            `;

            fetch('reschedule_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                confirmButton.disabled = false;
                confirmButton.innerHTML = originalText;

                if (data.success) {
                    alert('Appointment rescheduled successfully!');
                    // Clear session storage
                    sessionStorage.removeItem('reschedule_appointment_id');
                    sessionStorage.removeItem('reschedule_service_name');
                    sessionStorage.removeItem('reschedule_duration');
                    sessionStorage.removeItem('reschedule_price');
                    sessionStorage.removeItem('reschedule_service_id');
                    sessionStorage.removeItem('reschedule_stylist_id');
                    
                    // Redirect to appointments page
                    window.location.href = 'appointments.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // Reset button
                confirmButton.disabled = false;
                confirmButton.innerHTML = originalText;

                console.error('Error rescheduling appointment:', error);
                alert('Failed to reschedule appointment. Please try again.');
            });
        }

        // Event listeners for month navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            renderCalendar();
        });

        // Function to manually format time (HH:MM:SS -> h:MM AM/PM)
        function formatTimeManually(timeString) {
            if (!timeString) return 'N/A';
            
            try {
                // Parse the time string
                const [hours, minutes] = timeString.split(':');
                const hourNum = parseInt(hours);
                const ampm = hourNum >= 12 ? 'PM' : 'AM';
                const hour12 = hourNum % 12 || 12;
                return `${hour12}:${minutes} ${ampm}`;
            } catch (e) {
                console.error('Error formatting time:', e);
                return timeString; // Return original if parsing fails
            }
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            loadAppointmentDetails();
        });
    </script>
</body>
</html> 