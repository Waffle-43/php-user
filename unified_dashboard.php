<?php
/**
 * Unified Dashboard for Service Providers
 * 
 * This dashboard integrates service management (Module 2) and 
 * appointment booking (Module 3) into a single interface.
 */

require_once __DIR__ . '/utils_files/connect.php'; // Database connection
require_once __DIR__ . '/utils/service_utils.php'; // Utility functions for service management
require_once __DIR__ . '/utils/service_integration.php'; // Utility functions for service integration

// Authentication check (placeholder - implement actual authentication)
$provider_id = 1; // Assuming provider ID 1 for demo purposes
$provider_name = "Jessica Parker"; // Sample provider name

// Get services data
try {
    // Get all active services
    $allServices = getAllActiveServices($conn);
    
    // Get service categories
    $serviceCategories = getAllServiceCategoriesWrapper($conn);
    
    // Get upcoming appointments (simplified - in production you would filter by provider)
    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
               c.name AS customer_name, s.name AS service_name, s.duration
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN customers c ON a.customer_id = c.id
        WHERE a.stylist_id = ? AND a.status != 'cancelled'
        ORDER BY a.appointment_date, a.appointment_time
        LIMIT 10
    ");
    
    if ($conn instanceof mysqli) {
        $stmt->bind_param('i', $provider_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $upcomingAppointments = [];
        while ($row = $result->fetch_assoc()) {
            $upcomingAppointments[] = $row;
        }
    } else {
        // PDO implementation
        $stmt->bindParam(1, $provider_id, PDO::PARAM_INT);
        $stmt->execute();
        $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calculate daily stats - total appointments for today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS today_count
        FROM appointments
        WHERE stylist_id = ? AND appointment_date = ? AND status != 'cancelled'
    ");
    
    if ($conn instanceof mysqli) {
        $stmt->bind_param('is', $provider_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $todayStats = $result->fetch_assoc();
        $todayAppointments = $todayStats['today_count'];
    } else {
        // PDO implementation
        $stmt->bindParam(1, $provider_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $today, PDO::PARAM_STR);
        $stmt->execute();
        $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $todayAppointments = $todayStats['today_count'];
    }
    
    // Calculate pending notifications
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS notification_count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    
    if ($conn instanceof mysqli) {
        $stmt->bind_param('i', $provider_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notificationStats = $result->fetch_assoc();
        $unreadNotifications = $notificationStats['notification_count'];
    } else {
        // PDO implementation
        $stmt->bindParam(1, $provider_id, PDO::PARAM_INT);
        $stmt->execute();
        $notificationStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $unreadNotifications = $notificationStats['notification_count'];
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Dashboard - Harmony Spa & Salon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <nav class="bg-purple-800 text-white shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="integrated_homepage.php" class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold">Harmony Spa & Salon</h1>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button class="relative p-1 rounded-full hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-purple-800 focus:ring-white">
                            <span class="sr-only">View notifications</span>
                            <i class="fas fa-bell"></i>
                            <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                <span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-500 text-xs text-white text-center">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">JP</span>
                            </div>
                            <span><?= htmlspecialchars($provider_name) ?></span>
                        </div>
                        <a href="logout" class="text-sm hover:underline">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex-1 flex">
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-md hidden md:block">
                <div class="h-full flex flex-col">
                    <div class="p-4 border-b">
                        <div class="text-center">
                            <div class="h-20 w-20 rounded-full bg-purple-200 mx-auto mb-2 flex items-center justify-center">
                                <span class="text-2xl font-bold text-purple-800">JP</span>
                            </div>
                            <h2 class="font-medium"><?= htmlspecialchars($provider_name) ?></h2>
                            <p class="text-sm text-gray-500">Service Provider</p>
                        </div>
                    </div>
                    <nav class="p-4 flex-1">
                        <ul class="space-y-1">
                            <li>
                                <a href="home" class="flex items-center px-4 py-2 text-gray-700 bg-purple-100 rounded-md">
                                    <i class="fas fa-tachometer-alt mr-3 text-purple-600"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="all-appointments" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 rounded-md">
                                    <i class="fas fa-calendar-alt mr-3 text-purple-600"></i>
                                    <span>Appointments</span>
                                </a>
                            </li>
                            <li>
                                <a href="manage-services" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 rounded-md">
                                    <i class="fas fa-spa mr-3 text-purple-600"></i>
                                    <span>Services</span>
                                </a>
                            </li>
                            <li>
                                <a href="all-notifications" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 rounded-md">
                                    <i class="fas fa-bell mr-3 text-purple-600"></i>
                                    <span>Notifications</span>
                                    <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            <?= $unreadNotifications ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 rounded-md">
                                    <i class="fas fa-chart-bar mr-3 text-purple-600"></i>
                                    <span>Reports</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 rounded-md">
                                    <i class="fas fa-cog mr-3 text-purple-600"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 overflow-auto">
                <main class="p-6">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p class="text-gray-600">Welcome back, <?= htmlspecialchars($provider_name) ?>! Here's an overview of your services and appointments.</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 mr-4">
                                    <i class="fas fa-calendar-day text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Today's Appointments</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?= isset($todayAppointments) ? $todayAppointments : 0 ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 mr-4">
                                    <i class="fas fa-spa text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Active Services</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?= isset($allServices) ? count($allServices) : 0 ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 mr-4">
                                    <i class="fas fa-bell text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Notifications</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?= isset($unreadNotifications) ? $unreadNotifications : 0 ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Dashboard Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Upcoming Appointments -->
                        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="font-semibold text-lg text-gray-900">Upcoming Appointments</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <?php if (isset($upcomingAppointments) && !empty($upcomingAppointments)): ?>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?= date("M d, Y", strtotime($appointment['appointment_date'])) ?> at 
                                                        <?= date("h:i A", strtotime($appointment['appointment_time'])) ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?= htmlspecialchars($appointment['customer_name'] ?? 'Guest') ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?= htmlspecialchars($appointment['service_name']) ?>
                                                        <span class="text-xs text-gray-500 ml-1">
                                                            (<?= $appointment['duration'] ?> min)
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                    <?= $appointment['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                                       ($appointment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                                            <?= ucfirst($appointment['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                        <a href="service_provider_manage_appointment.php?id=<?= $appointment['id'] ?>" class="text-purple-600 hover:text-purple-900">
                                                            Manage
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-6 text-center text-gray-500">
                                        No upcoming appointments found.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($upcomingAppointments) && !empty($upcomingAppointments)): ?>
                                <div class="px-6 py-3 bg-gray-50 text-right">
                                    <a href="all-appointments" class="text-sm text-purple-600 hover:text-purple-900">
                                        View all appointments <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Service Management & Quick Actions -->
                        <div class="space-y-6">
                            <!-- Quick Actions -->
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="font-semibold text-lg text-gray-900">Quick Actions</h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <a href="add-appointments" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                        <div class="p-2 bg-purple-200 rounded-md mr-3">
                                            <i class="fas fa-calendar-plus text-purple-700"></i>
                                        </div>
                                        <span>Add New Appointment</span>
                                    </a>
                                    <a href="add-services" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                        <div class="p-2 bg-blue-200 rounded-md mr-3">
                                            <i class="fas fa-plus-circle text-blue-700"></i>
                                        </div>
                                        <span>Add New Service</span>
                                    </a>
                                    <a href="reschedule-services" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                                        <div class="p-2 bg-yellow-200 rounded-md mr-3">
                                            <i class="fas fa-clock text-yellow-700"></i>
                                        </div>
                                        <span>Reschedule Appointment</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Services Overview -->
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="font-semibold text-lg text-gray-900">Services Overview</h2>
                                </div>
                                <div class="p-6">
                                    <canvas id="serviceChart" width="400" height="250"></canvas>
                                </div>
                                <div class="px-6 py-3 bg-gray-50 text-right">
                                    <a href="manage-services" class="text-sm text-purple-600 hover:text-purple-900">
                                        Manage services <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
        // Simple chart to display service categories
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('serviceChart').getContext('2d');
            
            // Group services by category and count them
            const serviceCategories = <?= json_encode($serviceCategories ?? []) ?>;
            const allServices = <?= json_encode($allServices ?? []) ?>;
            
            const categoryCounts = {};
            serviceCategories.forEach(category => categoryCounts[category] = 0);
            
            allServices.forEach(service => {
                if (service.category in categoryCounts) {
                    categoryCounts[service.category]++;
                } else {
                    categoryCounts[service.category] = 1;
                }
            });
            
            const labels = Object.keys(categoryCounts);
            const data = Object.values(categoryCounts);
            
            // Define colors for chart
            const backgroundColors = [
                'rgba(147, 51, 234, 0.6)',  // Purple
                'rgba(59, 130, 246, 0.6)',  // Blue
                'rgba(16, 185, 129, 0.6)',  // Green
                'rgba(245, 158, 11, 0.6)',  // Amber
                'rgba(239, 68, 68, 0.6)',   // Red
                'rgba(236, 72, 153, 0.6)',  // Pink
            ];
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors.slice(0, labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 