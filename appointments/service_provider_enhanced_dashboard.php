<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Add error handling to prevent fatal errors
try {
    // Check if stylist_id is set (for testing purposes)
    $stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

    // Get stylist information
    $stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
    $stmt->execute([$stylist_id]);
    $stylist = $stmt->fetch();

    if (!$stylist) {
        die("Stylist not found");
    }

    // Get today's appointments
    $today = date('Y-m-d');
    $todayAppointments = $conn->prepare("
        SELECT 
            a.*,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            s.name as service_name,
            s.duration
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND a.appointment_date = ?
        ORDER BY 
            a.appointment_time ASC
    ");
    $todayAppointments->execute([$stylist_id, $today]);
    $todayAppts = $todayAppointments->fetchAll();
    $todayCount = count($todayAppts);

    // Get upcoming appointments (for the next 2 hours)
    $currentTime = date('H:i:s');
    $twoHoursLater = date('H:i:s', strtotime('+2 hours'));

    $upcomingAppointments = $conn->prepare("
        SELECT 
            a.*,
            c.name as customer_name,
            s.name as service_name
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND a.appointment_date = ?
            AND a.appointment_time BETWEEN ? AND ?
            AND a.status NOT IN ('cancelled', 'completed')
        ORDER BY 
            a.appointment_time ASC
    ");
    $upcomingAppointments->execute([$stylist_id, $today, $currentTime, $twoHoursLater]);
    $upcomingAppts = $upcomingAppointments->fetchAll();
    $upcomingCount = count($upcomingAppts);

    // Get recent cancellations/reschedules
    $recentChanges = $conn->prepare("
        SELECT 
            a.*,
            c.name as customer_name,
            s.name as service_name,
            CASE 
                WHEN a.status = 'cancelled' THEN 'Cancelled'
                ELSE 'Rescheduled'
            END as change_type
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND (a.status = 'cancelled' OR a.status = 'rescheduled')
            AND a.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY 
            a.updated_at DESC
        LIMIT 5
    ");
    $recentChanges->execute([$stylist_id]);
    $recentChangesList = $recentChanges->fetchAll();
    $changesCount = count($recentChangesList);

    // Get notifications for this stylist - using a compatible query for different schema structures
    $notifications = [];
    $notificationCount = 0;
    try {
        // Check if notifications table exists and what columns it has
        $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($tableCheck->rowCount() > 0) {
            // Check column structure
            $columnsCheck = $conn->query("DESCRIBE notifications");
            $columns = $columnsCheck->fetchAll(PDO::FETCH_COLUMN);
            
            // Construct query based on available columns
            if (in_array('user_id', $columns)) {
                $notifStmt = $conn->prepare("
                    SELECT * FROM notifications 
                    WHERE user_id = ? 
                    AND is_read = 0
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $notifStmt->execute([$stylist_id]);
            } elseif (in_array('recipient_id', $columns) && in_array('recipient_type', $columns)) {
                $notifStmt = $conn->prepare("
                    SELECT * FROM notifications 
                    WHERE recipient_type = 'stylist' 
                    AND recipient_id = ? 
                    AND is_read = 0
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $notifStmt->execute([$stylist_id]);
            } else {
                // Fallback for unexpected schema
                $notifStmt = $conn->prepare("
                    SELECT * FROM notifications 
                    WHERE (user_id = ? OR (recipient_id = ? AND recipient_type = 'stylist'))
                    AND is_read = 0
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $notifStmt->execute([$stylist_id, $stylist_id]);
            }
            
            $notifications = $notifStmt->fetchAll();
            $notificationCount = count($notifications);
        }
    } catch (PDOException $e) {
        // Handle the error silently and continue with empty notifications
        error_log("Error retrieving notifications: " . $e->getMessage());
    }

    // Get all appointments for the next 7 days for the detailed list
    $weekStart = $today;
    $weekEnd = date('Y-m-d', strtotime('+7 days'));

    $weekAppointments = $conn->prepare("
        SELECT 
            a.*,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            s.name as service_name,
            s.duration
        FROM 
            appointments a
        JOIN 
            services s ON a.service_id = s.id
        LEFT JOIN 
            customers c ON a.customer_id = c.id
        WHERE 
            a.stylist_id = ?
            AND a.appointment_date BETWEEN ? AND ?
        ORDER BY 
            a.appointment_date ASC,
            a.appointment_time ASC
    ");
    $weekAppointments->execute([$stylist_id, $weekStart, $weekEnd]);
    $weekAppts = $weekAppointments->fetchAll();

    // Get appointment statistics for charts - wrap in try-catch to prevent errors
    $bookingTrends = [];
    $serviceStats = [];
    
    try {
        // Monthly booking trends (last 30 days)
        $monthlyTrend = $conn->prepare("
            SELECT 
                DATE(created_at) as booking_date,
                COUNT(*) as booking_count
            FROM 
                appointments
            WHERE 
                stylist_id = ?
                AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY 
                DATE(created_at)
            ORDER BY 
                booking_date ASC
        ");
        $monthlyTrend->execute([$stylist_id]);
        $bookingTrends = $monthlyTrend->fetchAll();
        
        // Service distribution
        $serviceDistribution = $conn->prepare("
            SELECT 
                s.name as service_name,
                COUNT(*) as count
            FROM 
                appointments a
            JOIN 
                services s ON a.service_id = s.id
            WHERE 
                a.stylist_id = ?
                AND a.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY 
                a.service_id
            ORDER BY 
                count DESC
        ");
        $serviceDistribution->execute([$stylist_id]);
        $serviceStats = $serviceDistribution->fetchAll();
    } catch (PDOException $e) {
        error_log("Error retrieving statistics: " . $e->getMessage());
    }

    // Prepare data for charts (JSON format)
    $trendLabels = [];
    $trendData = [];
    foreach ($bookingTrends as $trend) {
        $trendLabels[] = date('M d', strtotime($trend['booking_date']));
        $trendData[] = intval($trend['booking_count']);
    }

    $serviceLabels = [];
    $serviceData = [];
    foreach ($serviceStats as $stat) {
        $serviceLabels[] = $stat['service_name'];
        $serviceData[] = intval($stat['count']);
    }

    $trendChartData = json_encode([
        'labels' => $trendLabels,
        'data' => $trendData
    ]);

    $serviceChartData = json_encode([
        'labels' => $serviceLabels,
        'data' => $serviceData
    ]);
} catch (PDOException $e) {
    // Handle fatal errors gracefully
    echo "<div style='margin: 50px auto; max-width: 600px; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
    echo "<h2 style='color: #d32f2f; margin-bottom: 15px;'>Dashboard Error</h2>";
    echo "<p>An error occurred while loading the dashboard. This may be due to a database configuration issue.</p>";
    echo "<p>Error details: " . $e->getMessage() . "</p>";
    echo "<p><a href='appointment.php' style='color: #6200ee;'>Go to Appointment Booking</a> | <a href='integrated_homepage.php' style='color: #6200ee;'>Back to Homepage</a></p>";
    echo "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Dashboard - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 10px;
        }
        .metric-card {
            transition: all 0.3s;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .animated-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-confirmed { background-color: #10b981; }
        .status-pending { background-color: #f59e0b; }
        .status-cancelled { background-color: #ef4444; }
        .status-completed { background-color: #6366f1; }
        .status-rescheduled { background-color: #3b82f6; }
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
                <div class="sidebar-menu">
                    <a href="service_provider_enhanced_dashboard.php?stylist_id=<?= $stylist_id ?>" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="service_provider_all_appointments.php?stylist_id=<?= $stylist_id ?>"><i class="far fa-calendar-alt"></i> All Appointments</a>
                    <a href="service_provider_add_appointment.php?stylist_id=<?= $stylist_id ?>"><i class="fas fa-plus-circle"></i> Add Appointment</a>
                    <a href="service_provider_manage_appointment.php?stylist_id=<?= $stylist_id ?>"><i class="fas fa-edit"></i> Manage Appointments</a>
                    <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>"><i class="fas fa-calendar-check"></i> Calendar View</a>
                    <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>"><i class="fas fa-spa"></i> Manage Services</a>
                    <a href="service_provider_reschedule.php?stylist_id=<?= $stylist_id ?>"><i class="fas fa-sync-alt"></i> Reschedule</a>
                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
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
                        <h2 class="text-lg font-semibold">Enhanced Provider Dashboard</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button id="notifications-btn" class="text-gray-600 hover:text-purple-600 relative">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if ($notificationCount > 0): ?>
                                <span class="notification-badge bg-red-500 text-white rounded-full h-5 w-5 flex items-center justify-center"><?= $notificationCount ?></span>
                                <?php endif; ?>
                            </button>
                            <!-- Notifications dropdown -->
                            <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-20">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                                    <h3 class="text-sm font-semibold">Notifications</h3>
                                    <?php if ($notificationCount > 0): ?>
                                    <a href="#" id="mark-all-read" class="text-xs text-blue-500 hover:text-blue-700">Mark all as read</a>
                                    <?php endif; ?>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <?php if ($notificationCount > 0): ?>
                                        <?php foreach ($notifications as $notification): ?>
                                        <div class="p-3 border-b border-gray-100 hover:bg-gray-50">
                                            <?php 
                                                $icon = 'fa-bell';
                                                $colorClass = 'text-blue-500';
                                                
                                                if (strpos($notification['type'], 'cancel') !== false) {
                                                    $icon = 'fa-calendar-xmark';
                                                    $colorClass = 'text-red-500';
                                                } elseif (strpos($notification['type'], 'reschedule') !== false) {
                                                    $icon = 'fa-calendar-plus';
                                                    $colorClass = 'text-yellow-500';
                                                } elseif (strpos($notification['type'], 'new') !== false || strpos($notification['type'], 'book') !== false) {
                                                    $icon = 'fa-calendar-plus';
                                                    $colorClass = 'text-green-500';
                                                } elseif (strpos($notification['type'], 'reminder') !== false) {
                                                    $icon = 'fa-clock';
                                                    $colorClass = 'text-purple-500';
                                                }
                                            ?>
                                            <div class="flex">
                                                <div class="mr-3 <?= $colorClass ?>">
                                                    <i class="fas <?= $icon ?> text-lg"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm"><?= htmlspecialchars($notification['message']) ?></p>
                                                    <p class="text-xs text-gray-500 mt-1"><?= date('M d, g:i A', strtotime($notification['created_at'])) ?></p>
                                                    <?php if (isset($notification['appointment_id']) && !empty($notification['appointment_id'])): ?>
                                                    <a href="service_provider_manage_appointment.php?id=<?= $notification['appointment_id'] ?>&stylist_id=<?= $stylist_id ?>" 
                                                       class="text-xs text-blue-500 hover:text-blue-700 mt-1 inline-block">
                                                        View Appointment Details
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-gray-500 text-sm">
                                            No new notifications
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 border-t border-gray-100 text-center">
                                    <a href="all_notifications.php?stylist_id=<?= $stylist_id ?>" class="text-xs text-purple-600 hover:text-purple-800">
                                        View All Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center focus:outline-none">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($stylist['name']) ?>&background=random" 
                                     class="h-8 w-8 rounded-full mr-2" alt="<?= htmlspecialchars($stylist['name']) ?>">
                                <span class="text-sm font-medium"><?= htmlspecialchars($stylist['name']) ?></span>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>
                            <!-- User dropdown -->
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-20">
                                <div class="py-1">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <div class="border-t border-gray-100"></div>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-6">
                <!-- Key Metrics Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Key Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Today's Bookings -->
                        <div class="bg-white rounded-lg shadow p-5 metric-card border-l-4 border-purple-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Today's Bookings</h4>
                                    <div class="mt-2 flex items-baseline">
                                        <p class="text-3xl font-semibold text-gray-900"><?= $todayCount ?></p>
                                        <p class="ml-2 text-sm text-gray-600">appointments</p>
                                    </div>
                                </div>
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                    <i class="fas fa-calendar-day text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#today-appointments" class="text-sm text-purple-600 hover:text-purple-800">
                                    View details <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Upcoming Appointments -->
                        <div class="bg-white rounded-lg shadow p-5 metric-card border-l-4 border-blue-500 
                                    <?= ($upcomingCount > 0) ? 'animated-pulse' : '' ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Upcoming (Next 2hrs)</h4>
                                    <div class="mt-2 flex items-baseline">
                                        <p class="text-3xl font-semibold text-gray-900"><?= $upcomingCount ?></p>
                                        <p class="ml-2 text-sm text-gray-600">appointments</p>
                                    </div>
                                </div>
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <?php if ($upcomingCount > 0): ?>
                                    <div class="text-sm text-gray-600">
                                        Next: <span class="font-medium"><?= date('g:i A', strtotime($upcomingAppts[0]['appointment_time'])) ?></span> - 
                                        <?= htmlspecialchars($upcomingAppts[0]['service_name']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-600">No upcoming appointments</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Changes -->
                        <div class="bg-white rounded-lg shadow p-5 metric-card border-l-4 border-amber-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Recent Changes (24hrs)</h4>
                                    <div class="mt-2 flex items-baseline">
                                        <p class="text-3xl font-semibold text-gray-900"><?= $changesCount ?></p>
                                        <p class="ml-2 text-sm text-gray-600">updates</p>
                                    </div>
                                </div>
                                <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                                    <i class="fas fa-calendar-xmark text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <?php if ($changesCount > 0): ?>
                                    <div class="flex items-center text-sm">
                                        <span class="inline-block w-3 h-3 rounded-full mr-1 bg-red-500"></span>
                                        <span class="text-gray-600">
                                            <?= count(array_filter($recentChangesList, function($item) { return $item['change_type'] === 'Cancelled'; })) ?> cancellations
                                        </span>
                                        <span class="mx-2 text-gray-400">|</span>
                                        <span class="inline-block w-3 h-3 rounded-full mr-1 bg-yellow-500"></span>
                                        <span class="text-gray-600">
                                            <?= count(array_filter($recentChangesList, function($item) { return $item['change_type'] === 'Rescheduled'; })) ?> reschedules
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-600">No recent changes</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two-column layout for alerts and appointments -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Real-time Notifications & Alerts Column -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-700">Real-time Alerts</h3>
                            </div>
                            <div class="p-5 space-y-4 max-h-96 overflow-y-auto">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $index => $notification): ?>
                                        <?php 
                                            $alertClass = '';
                                            $icon = 'fa-bell';
                                            
                                            if (strpos($notification['type'], 'cancel') !== false) {
                                                $alertClass = 'bg-red-50 border-red-200';
                                                $icon = 'fa-calendar-xmark';
                                            } elseif (strpos($notification['type'], 'reschedule') !== false) {
                                                $alertClass = 'bg-yellow-50 border-yellow-200';
                                                $icon = 'fa-calendar-plus';
                                            } elseif (strpos($notification['type'], 'new') !== false || strpos($notification['type'], 'book') !== false) {
                                                $alertClass = 'bg-green-50 border-green-200';
                                                $icon = 'fa-calendar-plus';
                                            } elseif (strpos($notification['type'], 'reminder') !== false) {
                                                $alertClass = 'bg-purple-50 border-purple-200';
                                                $icon = 'fa-clock';
                                            } else {
                                                $alertClass = 'bg-blue-50 border-blue-200';
                                            }
                                            
                                            // Add 'new' animation for the first 3 items
                                            $isNew = $index < 3 ? 'animated-pulse' : '';
                                        ?>
                                        <div class="p-4 rounded-lg border <?= $alertClass ?> <?= $isNew ?>">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <i class="fas <?= $icon ?>"></i>
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <p class="text-sm font-medium">
                                                        <?= htmlspecialchars($notification['message']) ?>
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        <?= date('M d, g:i A', strtotime($notification['created_at'])) ?>
                                                    </p>
                                                    <?php if (isset($notification['appointment_id']) && !empty($notification['appointment_id'])): ?>
                                                    <div class="mt-2">
                                                        <a href="service_provider_manage_appointment.php?id=<?= $notification['appointment_id'] ?>&stylist_id=<?= $stylist_id ?>" 
                                                           class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                                            View details
                                                        </a>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="mark-read-btn ml-2 text-gray-400 hover:text-gray-600" 
                                                        data-id="<?= $notification['id'] ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <img src="https://img.icons8.com/fluency/96/null/thumb-up.png" class="mx-auto mb-3 h-16 w-16 opacity-50" alt="All caught up">
                                        <p class="text-gray-500 text-sm">You're all caught up!</p>
                                        <p class="text-gray-400 text-xs mt-1">No new notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-lg">
                                <a href="all_notifications.php?stylist_id=<?= $stylist_id ?>" class="text-sm font-medium text-purple-600 hover:text-purple-800">
                                    View all notifications <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Appointment List Column -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-5 border-b border-gray-100 flex flex-wrap items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2 md:mb-0">Upcoming Appointments</h3>
                                <div class="flex flex-wrap items-center space-x-2">
                                    <input type="text" id="search-appointments" placeholder="Search client or service..." 
                                           class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <div class="relative inline-block text-left">
                                        <button id="filter-dropdown-btn" class="px-3 py-2 border border-gray-300 rounded-md text-sm flex items-center">
                                            <i class="fas fa-filter mr-2"></i> Filter
                                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                        </button>
                                        <div id="filter-dropdown" class="hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                            <div class="p-3 border-b border-gray-100">
                                                <h4 class="text-sm font-medium text-gray-700">Filter by Status</h4>
                                            </div>
                                            <div class="py-1">
                                                <button data-filter="all" class="filter-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                    All Appointments
                                                </button>
                                                <button data-filter="confirmed" class="filter-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                    <span class="status-indicator status-confirmed"></span> Confirmed
                                                </button>
                                                <button data-filter="pending" class="filter-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                    <span class="status-indicator status-pending"></span> Pending
                                                </button>
                                                <button data-filter="rescheduled" class="filter-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                    <span class="status-indicator status-rescheduled"></span> Rescheduled
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="appointments-list" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (count($weekAppts) > 0): ?>
                                            <?php foreach ($weekAppts as $appt): ?>
                                                <?php
                                                    $statusClass = '';
                                                    switch ($appt['status']) {
                                                        case 'confirmed':
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                            $statusIndicator = 'status-confirmed';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                                            $statusIndicator = 'status-pending';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'bg-red-100 text-red-800';
                                                            $statusIndicator = 'status-cancelled';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'bg-indigo-100 text-indigo-800';
                                                            $statusIndicator = 'status-completed';
                                                            break;
                                                        case 'rescheduled':
                                                            $statusClass = 'bg-blue-100 text-blue-800'; 
                                                            $statusIndicator = 'status-rescheduled';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-gray-100 text-gray-800';
                                                            $statusIndicator = '';
                                                    }
                                                    
                                                    // Format appointment date and time
                                                    $apptDate = new DateTime($appt['appointment_date']);
                                                    $apptTime = new DateTime($appt['appointment_time']);
                                                    $formattedDate = $apptDate->format('D, M j, Y');
                                                    $formattedTime = $apptTime->format('g:i A');
                                                    
                                                    // Check if the appointment is today
                                                    $isToday = $appt['appointment_date'] === $today;
                                                    $dateLabel = $isToday ? 'Today' : $formattedDate;
                                                    
                                                    // Calculate end time
                                                    $endTime = clone $apptTime;
                                                    $endTime->add(new DateInterval('PT' . $appt['duration'] . 'M'));
                                                    $formattedEndTime = $endTime->format('g:i A');
                                                ?>
                                                <tr class="appointment-row" data-status="<?= $appt['status'] ?>" data-search="<?= strtolower($appt['customer_name'] . ' ' . $appt['service_name']) ?>">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full 
                                                                        <?= $isToday ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-500' ?>">
                                                                <i class="fas <?= $isToday ? 'fa-calendar-day' : 'fa-calendar' ?>"></i>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900"><?= $dateLabel ?></div>
                                                                <div class="text-sm text-gray-500"><?= $formattedTime ?> - <?= $formattedEndTime ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($appt['customer_name']) ?></div>
                                                        <?php if(!empty($appt['customer_phone'])): ?>
                                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($appt['customer_phone']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($appt['service_name']) ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?= $appt['duration'] ?> min
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                            <span class="status-indicator <?= $statusIndicator ?> mr-1"></span>
                                                            <?= ucfirst($appt['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="service_provider_manage_appointment.php?id=<?= $appt['id'] ?>&stylist_id=<?= $stylist_id ?>" 
                                                           class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                            <i class="fas fa-eye" title="View Details"></i>
                                                        </a>
                                                        <?php if ($appt['status'] !== 'cancelled' && $appt['status'] !== 'completed'): ?>
                                                        <a href="service_provider_reschedule.php?id=<?= $appt['id'] ?>&stylist_id=<?= $stylist_id ?>" 
                                                           class="text-blue-600 hover:text-blue-900">
                                                            <i class="fas fa-calendar-day" title="Reschedule"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                                    <p>No appointments for the next 7 days</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-lg flex justify-between items-center">
                                <span class="text-sm text-gray-600">Showing appointments for the next 7 days</span>
                                <a href="service_provider_all_appointments.php?stylist_id=<?= $stylist_id ?>" class="text-sm font-medium text-purple-600 hover:text-purple-800">
                                    View all appointments <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics & Reporting Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Performance Analytics</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Booking Trends Chart -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4">Booking Trends (Last 30 Days)</h4>
                            <div class="h-64">
                                <canvas id="bookingTrendsChart"></canvas>
                            </div>
                            <div class="mt-4 text-xs text-gray-500 text-center">
                                Daily booking count over the last 30 days
                            </div>
                        </div>
                        
                        <!-- Service Distribution Chart -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4">Service Distribution</h4>
                            <div class="h-64">
                                <canvas id="serviceDistributionChart"></canvas>
                            </div>
                            <div class="mt-4 text-xs text-gray-500 text-center">
                                Distribution of services booked in the last 30 days
                            </div>
                        </div>
                    </div>
                    
                    <!-- Insights Section -->
                    <div class="mt-6 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow p-5">
                        <h4 class="text-sm font-semibold text-gray-700 mb-4">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Performance Insights
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php
                            // Calculate busiest day of week
                            $busiestDayQuery = $conn->prepare("
                                SELECT 
                                    DAYNAME(appointment_date) as day_name,
                                    COUNT(*) as appointment_count
                                FROM 
                                    appointments
                                WHERE 
                                    stylist_id = ?
                                    AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                                GROUP BY 
                                    DAYNAME(appointment_date)
                                ORDER BY 
                                    appointment_count DESC
                                LIMIT 1
                            ");
                            $busiestDayQuery->execute([$stylist_id]);
                            $busiestDay = $busiestDayQuery->fetch();
                            
                            // Calculate most booked service
                            $mostBookedQuery = $conn->prepare("
                                SELECT 
                                    s.name as service_name,
                                    COUNT(*) as booking_count
                                FROM 
                                    appointments a
                                JOIN 
                                    services s ON a.service_id = s.id
                                WHERE 
                                    a.stylist_id = ?
                                    AND a.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                                GROUP BY 
                                    a.service_id
                                ORDER BY 
                                    booking_count DESC
                                LIMIT 1
                            ");
                            $mostBookedQuery->execute([$stylist_id]);
                            $mostBooked = $mostBookedQuery->fetch();
                            
                            // Calculate busiest time slot
                            $busiestTimeQuery = $conn->prepare("
                                SELECT 
                                    TIME_FORMAT(appointment_time, '%H:00') as hour_slot,
                                    COUNT(*) as slot_count
                                FROM 
                                    appointments
                                WHERE 
                                    stylist_id = ?
                                    AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                                GROUP BY 
                                    hour_slot
                                ORDER BY 
                                    slot_count DESC
                                LIMIT 1
                            ");
                            $busiestTimeQuery->execute([$stylist_id]);
                            $busiestTime = $busiestTimeQuery->fetch();
                            
                            // Format the busiest time for display (convert 24-hour format to 12-hour format)
                            $formattedBusiestTime = '';
                            if ($busiestTime) {
                                $hour = intval(explode(':', $busiestTime['hour_slot'])[0]);
                                $ampm = $hour >= 12 ? 'PM' : 'AM';
                                $hour12 = $hour % 12;
                                $hour12 = $hour12 ? $hour12 : 12; // Convert 0 to 12 for 12 AM
                                $formattedBusiestTime = $hour12 . ':00 ' . $ampm . ' - ' . $hour12 . ':59 ' . $ampm;
                            }
                            ?>
                            
                            <!-- Busiest Day Insight -->
                            <div class="bg-white rounded-lg p-4 border border-purple-100">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                                        <i class="fas fa-calendar-week text-purple-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h5 class="text-sm font-medium text-gray-800">Busiest Day</h5>
                                        <p class="mt-1 text-xl font-semibold text-purple-600">
                                            <?= $busiestDay ? htmlspecialchars($busiestDay['day_name']) : 'N/A' ?>
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            <?= $busiestDay ? $busiestDay['appointment_count'] . ' appointments' : 'Insufficient data' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Most Booked Service Insight -->
                            <div class="bg-white rounded-lg p-4 border border-blue-100">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                                        <i class="fas fa-heart text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h5 class="text-sm font-medium text-gray-800">Most Popular Service</h5>
                                        <p class="mt-1 text-xl font-semibold text-blue-600">
                                            <?= $mostBooked ? htmlspecialchars($mostBooked['service_name']) : 'N/A' ?>
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            <?= $mostBooked ? $mostBooked['booking_count'] . ' bookings' : 'Insufficient data' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Peak Hours Insight -->
                            <div class="bg-white rounded-lg p-4 border border-amber-100">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-amber-100 rounded-full p-3">
                                        <i class="fas fa-clock text-amber-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h5 class="text-sm font-medium text-gray-800">Peak Time Slot</h5>
                                        <p class="mt-1 text-xl font-semibold text-amber-600">
                                            <?= $busiestTime ? $formattedBusiestTime : 'N/A' ?>
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            <?= $busiestTime ? $busiestTime['slot_count'] . ' bookings' : 'Insufficient data' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- New Services Management Section -->
            <div class="dashboard-section mt-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-spa mr-2"></i> Services Management</h5>
                        <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="btn btn-sm btn-light"><i class="fas fa-plus"></i> Add New Service</a>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">Manage Your Services</h6>
                        
                        <?php
                        // Get services associated with this provider
                        $servicesQuery = $conn->prepare("
                            SELECT id, name, category, price, promotion, price_after_discount, available 
                            FROM services 
                            ORDER BY id DESC 
                            LIMIT 5
                        ");
                        $servicesQuery->execute();
                        $servicesList = $servicesQuery->fetchAll();
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($servicesList)): ?>
                                        <?php foreach ($servicesList as $service): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($service['name']) ?></td>
                                                <td><?= htmlspecialchars($service['category']) ?></td>
                                                <td>
                                                    <?php if ((int)$service['promotion'] > 0): ?>
                                                        <span class="text-muted text-decoration-line-through">RM<?= number_format($service['price'], 2) ?></span>
                                                        <span class="text-danger">RM<?= number_format($service['price_after_discount'], 2) ?></span>
                                                    <?php else: ?>
                                                        RM<?= number_format($service['price'], 2) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($service['available']): ?>
                                                        <span class="badge badge-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Unavailable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>&edit=<?= $service['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No services found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> View All Services
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Services Management Section -->
            
            <div class="row mt-4">
                <script>
                    // Toggle sidebar
                    document.getElementById('sidebar-toggle').addEventListener('click', function() {
                        const sidebar = document.querySelector('.sidebar');
                        sidebar.classList.toggle('translate-x-0');
                        sidebar.classList.toggle('-translate-x-full');
                    });

                    // Toggle notifications dropdown
                    document.getElementById('notifications-btn').addEventListener('click', function() {
                        document.getElementById('notifications-dropdown').classList.toggle('hidden');
                        document.getElementById('user-dropdown').classList.add('hidden');
                    });

                    // Toggle user dropdown
                    document.getElementById('user-menu-btn').addEventListener('click', function() {
                        document.getElementById('user-dropdown').classList.toggle('hidden');
                        document.getElementById('notifications-dropdown').classList.add('hidden');
                    });

                    // Close dropdowns when clicking outside
                    document.addEventListener('click', function(event) {
                        const notificationsBtn = document.getElementById('notifications-btn');
                        const notificationsDropdown = document.getElementById('notifications-dropdown');
                        const userMenuBtn = document.getElementById('user-menu-btn');
                        const userDropdown = document.getElementById('user-dropdown');
                        const filterBtn = document.getElementById('filter-dropdown-btn');
                        const filterDropdown = document.getElementById('filter-dropdown');
                        
                        if (notificationsBtn && notificationsDropdown && 
                            !notificationsBtn.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                            notificationsDropdown.classList.add('hidden');
                        }
                        
                        if (userMenuBtn && userDropdown && 
                            !userMenuBtn.contains(event.target) && !userDropdown.contains(event.target)) {
                            userDropdown.classList.add('hidden');
                        }
                        
                        if (filterBtn && filterDropdown && 
                            !filterBtn.contains(event.target) && !filterDropdown.contains(event.target)) {
                            filterDropdown.classList.add('hidden');
                        }
                    });

                    // Mark notification as read
                    document.querySelectorAll('.mark-read-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const notificationId = this.getAttribute('data-id');
                            const notificationElement = this.closest('div.p-4'); // Get the parent notification element
                            
                            fetch('update_notification.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `notification_id=${notificationId}&action=mark_read`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Fade out and remove the notification
                                    notificationElement.style.opacity = '0.5';
                                    setTimeout(() => {
                                        notificationElement.remove();
                                        
                                        // Update notification badge count
                                        const badge = document.querySelector('.notification-badge');
                                        if (badge) {
                                            const currentCount = parseInt(badge.textContent);
                                            if (currentCount > 1) {
                                                badge.textContent = currentCount - 1;
                                            } else {
                                                badge.remove();
                                            }
                                        }
                                        
                                        // Check if there are no more notifications
                                        const remainingNotifications = document.querySelectorAll('.mark-read-btn').length;
                                        if (remainingNotifications === 0) {
                                            const container = document.querySelector('.max-h-96');
                                            container.innerHTML = `
                                                <div class="text-center py-4">
                                                    <img src="https://img.icons8.com/fluency/96/null/thumb-up.png" class="mx-auto mb-3 h-16 w-16 opacity-50" alt="All caught up">
                                                    <p class="text-gray-500 text-sm">You're all caught up!</p>
                                                    <p class="text-gray-400 text-xs mt-1">No new notifications</p>
                                                </div>
                                            `;
                                        }
                                    }, 300);
                                }
                            });
                        });
                    });

                    // Appointment list filtering and search
                    document.getElementById('filter-dropdown-btn').addEventListener('click', function() {
                        document.getElementById('filter-dropdown').classList.toggle('hidden');
                    });

                    // Filter buttons functionality
                    document.querySelectorAll('.filter-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const filterValue = this.getAttribute('data-filter');
                            const rows = document.querySelectorAll('.appointment-row');
                            
                            rows.forEach(row => {
                                if (filterValue === 'all') {
                                    row.style.display = '';
                                } else {
                                    if (row.getAttribute('data-status') === filterValue) {
                                        row.style.display = '';
                                    } else {
                                        row.style.display = 'none';
                                    }
                                }
                            });
                            
                            // Update filter button text
                            document.getElementById('filter-dropdown-btn').innerHTML = 
                                `<i class="fas fa-filter mr-2"></i> ${filterValue === 'all' ? 'Filter' : 'Filter: ' + filterValue.charAt(0).toUpperCase() + filterValue.slice(1)}
                                <i class="fas fa-chevron-down ml-2 text-xs"></i>`;
                            
                            // Hide dropdown
                            document.getElementById('filter-dropdown').classList.add('hidden');
                        });
                    });

                    // Search functionality
                    document.getElementById('search-appointments').addEventListener('input', function() {
                        const searchValue = this.value.toLowerCase();
                        const rows = document.querySelectorAll('.appointment-row');
                        
                        rows.forEach(row => {
                            const searchText = row.getAttribute('data-search');
                            if (searchText.includes(searchValue)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });

                    // Mark all notifications as read
                    const markAllReadBtn = document.getElementById('mark-all-read');
                    if (markAllReadBtn) {
                        markAllReadBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            fetch('update_notification.php?action=mark_all_read&stylist_id=<?= $stylist_id ?>', {
                                method: 'POST'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove notification badges and update UI
                                    const badges = document.querySelectorAll('.notification-badge');
                                    badges.forEach(badge => badge.remove());
                                    
                                    // Update notification dropdown
                                    document.getElementById('notifications-dropdown').classList.add('hidden');
                                    
                                    // Update real-time notifications panel
                                    const notificationsContainer = document.querySelector('.max-h-96');
                                    if (notificationsContainer) {
                                        notificationsContainer.innerHTML = `
                                            <div class="text-center py-4">
                                                <img src="https://img.icons8.com/fluency/96/null/thumb-up.png" class="mx-auto mb-3 h-16 w-16 opacity-50" alt="All caught up">
                                                <p class="text-gray-500 text-sm">You're all caught up!</p>
                                                <p class="text-gray-400 text-xs mt-1">No new notifications</p>
                                            </div>
                                        `;
                                    }
                                    
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 500);
                                }
                            });
                        });
                    }

                    // Chart initialization
                    document.addEventListener('DOMContentLoaded', function() {
                        // Chart color scheme
                        const chartColors = {
                            purple: {
                                primary: '#8b5cf6',
                                light: '#c4b5fd',
                                background: 'rgba(139, 92, 246, 0.1)'
                            },
                            blue: {
                                primary: '#3b82f6',
                                light: '#93c5fd',
                                background: 'rgba(59, 130, 246, 0.1)'
                            },
                            pink: {
                                primary: '#ec4899',
                                light: '#f9a8d4',
                                background: 'rgba(236, 72, 153, 0.1)'
                            },
                            green: {
                                primary: '#10b981',
                                light: '#6ee7b7',
                                background: 'rgba(16, 185, 129, 0.1)'
                            },
                            amber: {
                                primary: '#f59e0b',
                                light: '#fcd34d',
                                background: 'rgba(245, 158, 11, 0.1)'
                            }
                        };
                        
                        // Get chart data from PHP
                        const trendChartData = <?= $trendChartData ?>;
                        const serviceChartData = <?= $serviceChartData ?>;
                        
                        // Initialize booking trends chart
                        const trendCtx = document.getElementById('bookingTrendsChart').getContext('2d');
                        const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 300);
                        trendGradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
                        trendGradient.addColorStop(1, 'rgba(139, 92, 246, 0)');
                        
                        new Chart(trendCtx, {
                            type: 'line',
                            data: {
                                labels: trendChartData.labels,
                                datasets: [{
                                    label: 'Bookings',
                                    data: trendChartData.data,
                                    borderColor: chartColors.purple.primary,
                                    backgroundColor: trendGradient,
                                    tension: 0.3,
                                    fill: true,
                                    pointBackgroundColor: chartColors.purple.primary,
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                        titleColor: '#000',
                                        bodyColor: '#000',
                                        borderColor: '#e2e8f0',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        padding: 12,
                                        boxPadding: 8,
                                        callbacks: {
                                            title: function(tooltipItems) {
                                                return tooltipItems[0].label;
                                            },
                                            label: function(context) {
                                                return `Bookings: ${context.parsed.y}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0,
                                            stepSize: 1
                                        },
                                        grid: {
                                            drawBorder: false,
                                            color: '#f1f5f9'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                        
                        // Initialize service distribution chart
                        const serviceCtx = document.getElementById('serviceDistributionChart').getContext('2d');
                        const serviceColors = [
                            chartColors.purple.primary,
                            chartColors.blue.primary,
                            chartColors.pink.primary,
                            chartColors.amber.primary,
                            chartColors.green.primary
                        ];
                        
                        new Chart(serviceCtx, {
                            type: 'doughnut',
                            data: {
                                labels: serviceChartData.labels,
                                datasets: [{
                                    data: serviceChartData.data,
                                    backgroundColor: serviceColors,
                                    borderColor: '#fff',
                                    borderWidth: 2,
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            pointStyle: 'circle',
                                            padding: 15,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                        titleColor: '#000',
                                        bodyColor: '#000',
                                        borderColor: '#e2e8f0',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        padding: 12,
                                        boxPadding: 8,
                                        callbacks: {
                                            label: function(context) {
                                                const value = context.raw;
                                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                                const percentage = Math.round((value / total) * 100);
                                                return `${context.label}: ${value} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    });

                    // Real-time dashboard updates
                    function fetchDashboardUpdates() {
                        fetch('get_dashboard_updates.php?stylist_id=<?= $stylist_id ?>')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    updateDashboardMetrics(data.data);
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching dashboard updates:', error);
                            });
                    }
                    
                    function updateDashboardMetrics(data) {
                        // Update upcoming appointments count
                        const upcomingCount = document.querySelector('.metric-card:nth-child(2) .text-3xl');
                        if (upcomingCount) {
                            upcomingCount.textContent = data.upcoming_count;
                        }
                        
                        // Update the next appointment info if available
                        const nextAppointmentInfo = document.querySelector('.metric-card:nth-child(2) .mt-4');
                        if (nextAppointmentInfo && data.upcoming_appointments.length > 0) {
                            const nextAppt = data.upcoming_appointments[0];
                            nextAppointmentInfo.innerHTML = `
                                <div class="text-sm text-gray-600">
                                    Next: <span class="font-medium">${nextAppt.time.split(' - ')[0]}</span> - 
                                    ${nextAppt.service_name}
                                </div>
                            `;
                        } else if (nextAppointmentInfo && data.upcoming_appointments.length === 0) {
                            nextAppointmentInfo.innerHTML = `
                                <div class="text-sm text-gray-600">No upcoming appointments</div>
                            `;
                        }
                        
                        // Update recent changes count
                        const changesCount = document.querySelector('.metric-card:nth-child(3) .text-3xl');
                        if (changesCount) {
                            changesCount.textContent = data.changes_count;
                        }
                        
                        // Update notification badge
                        const notificationBadge = document.querySelector('.notification-badge');
                        if (data.notification_count > 0) {
                            if (notificationBadge) {
                                notificationBadge.textContent = data.notification_count;
                                notificationBadge.classList.remove('hidden');
                            } else {
                                // Create badge if it doesn't exist
                                const notificationsBtn = document.getElementById('notifications-btn');
                                if (notificationsBtn) {
                                    const badge = document.createElement('span');
                                    badge.className = 'notification-badge bg-red-500 text-white rounded-full h-5 w-5 flex items-center justify-center';
                                    badge.textContent = data.notification_count;
                                    notificationsBtn.appendChild(badge);
                                }
                            }
                        } else if (notificationBadge) {
                            notificationBadge.classList.add('hidden');
                        }
                        
                        // Update real-time alerts if new notifications exist
                        if (data.notification_count > 0) {
                            const alertsContainer = document.querySelector('.max-h-96');
                            if (alertsContainer && alertsContainer.querySelector('.text-center')) {
                                // Replace "all caught up" message with actual notifications
                                let alertsHTML = '';
                                data.notifications.forEach((notification, index) => {
                                    const isNew = index < 3 ? 'animated-pulse' : '';
                                    const alertClass = notification.color_class.replace('text-', 'bg-').replace('500', '50') + ' border-' + notification.color_class.replace('text-', '').replace('500', '200');
                                    
                                    alertsHTML += `
                                        <div class="p-4 rounded-lg border ${alertClass} ${isNew}">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <i class="fas ${notification.icon}"></i>
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <p class="text-sm font-medium">
                                                        ${notification.message}
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        ${notification.time}
                                                    </p>
                                                    ${notification.url && notification.appointment_id ? `
                                                    <div class="mt-2">
                                                        <a href="${notification.url}" 
                                                           class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                                            View details
                                                        </a>
                                                    </div>
                                                    ` : ''}
                                                </div>
                                                <button class="mark-read-btn ml-2 text-gray-400 hover:text-gray-600" 
                                                        data-id="${notification.id}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                if (alertsHTML) {
                                    alertsContainer.innerHTML = alertsHTML;
                                    
                                    // Re-add event listeners to new mark-read buttons
                                    document.querySelectorAll('.mark-read-btn').forEach(button => {
                                        button.addEventListener('click', markNotificationRead);
                                    });
                                }
                            }
                        }
                    }
                    
                    // Function to handle marking notifications as read
                    function markNotificationRead() {
                        const notificationId = this.getAttribute('data-id');
                        const notificationElement = this.closest('div.p-4');
                        
                        fetch('update_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `notification_id=${notificationId}&action=mark_read`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Fade out and remove the notification
                                notificationElement.style.opacity = '0.5';
                                setTimeout(() => {
                                    notificationElement.remove();
                                    
                                    // Update notification badge count
                                    const badge = document.querySelector('.notification-badge');
                                    if (badge) {
                                        const currentCount = parseInt(badge.textContent);
                                        if (currentCount > 1) {
                                            badge.textContent = currentCount - 1;
                                        } else {
                                            badge.remove();
                                        }
                                    }
                                    
                                    // Check if there are no more notifications
                                    const remainingNotifications = document.querySelectorAll('.mark-read-btn').length;
                                    if (remainingNotifications === 0) {
                                        const container = document.querySelector('.max-h-96');
                                        container.innerHTML = `
                                            <div class="text-center py-4">
                                                <img src="https://img.icons8.com/fluency/96/null/thumb-up.png" class="mx-auto mb-3 h-16 w-16 opacity-50" alt="All caught up">
                                                <p class="text-gray-500 text-sm">You're all caught up!</p>
                                                <p class="text-gray-400 text-xs mt-1">No new notifications</p>
                                            </div>
                                        `;
                                    }
                                }, 300);
                            }
                        });
                    }
                    
                    // Initial setup for mark-read buttons
                    document.querySelectorAll('.mark-read-btn').forEach(button => {
                        button.addEventListener('click', markNotificationRead);
                    });
                    
                    // Setup auto-refresh of dashboard data (every 60 seconds)
                    setInterval(fetchDashboardUpdates, 60000);
                </script>
            </div>
        </div>
    </div>
</body>
</html> 