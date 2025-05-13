<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

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

// Get pending/upcoming appointments
$pendingAppointments = $conn->prepare("
    SELECT COUNT(*) as count
    FROM appointments
    WHERE stylist_id = ?
    AND status = 'pending'
");
$pendingAppointments->execute([$stylist_id]);
$pendingCount = $pendingAppointments->fetch()['count'];

// Calculate room occupancy based on appointments instead of time_slots
$roomOccupancy = $conn->prepare("
    SELECT COUNT(*) as booked_slots,
    (SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND appointment_date = ? AND status != 'cancelled') as total_slots,
    (SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND appointment_date = ? AND status NOT IN ('cancelled', 'completed')) as active_slots
");
$roomOccupancy->execute([$stylist_id, $today, $stylist_id, $today]);
$occupancyResult = $roomOccupancy->fetch();
// Calculate occupancy rate - default to 0 if no slots
$totalSlots = max(1, $occupancyResult['total_slots']); // Avoid division by zero
$activeSlots = $occupancyResult['active_slots'];
$occupancyRate = round(($activeSlots / max(8, $totalSlots)) * 100); // Assume 8 slots minimum per day

// Get waitlist count
$waitlistStmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM waitlist
    WHERE service_date = ?
");
$waitlistStmt->execute([$today]);
$waitlistResult = $waitlistStmt->fetch();
$waitlistCount = $waitlistResult['count'] ?: 0;

// Get pending staff count (requires PERMISSION_MANAGE_STAFF)
$pendingStaffCount = 0;
if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_STAFF)) {
    $pendingStaffStmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM staff
        WHERE status = 'pending'
    ");
    $pendingStaffStmt->execute();
    $pendingStaffCount = $pendingStaffStmt->fetch()['count'];
}

// Get recent cancellations/reschedules (using the updated table schema)
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
        AND (a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
    ORDER BY
        a.created_at DESC
    LIMIT 5
");
$recentChanges->execute([$stylist_id]);
$recentChangesList = $recentChanges->fetchAll();
$changesCount = count($recentChangesList);

// Get all stylists for dropdown
$stylistsStmt = $conn->prepare("SELECT id, name FROM stylists WHERE is_active = 1");
$stylistsStmt->execute();
$allStylists = $stylistsStmt->fetchAll();

// Get notifications for this stylist
$notifStmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 10
");
$notifStmt->execute([$stylist_id]);
$notifications = $notifStmt->fetchAll();
$notificationCount = count($notifications);

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

// Get appointment statistics for charts
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

// Performance insights
// Busiest day of the week
$busiestDay = $conn->prepare("
    SELECT 
        DAYNAME(appointment_date) as day_name,
        COUNT(*) as appointment_count
    FROM 
        appointments
    WHERE 
        stylist_id = ?
        AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY 
        day_name
    ORDER BY 
        appointment_count DESC
    LIMIT 1
");
$busiestDay->execute([$stylist_id]);
$busiestDayInfo = $busiestDay->fetch();

// Most popular service
$popularService = $conn->prepare("
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
$popularService->execute([$stylist_id]);
$popularServiceInfo = $popularService->fetch();

// Peak time slot
$peakTime = $conn->prepare("
    SELECT 
        HOUR(appointment_time) as hour_of_day,
        COUNT(*) as appointment_count
    FROM 
        appointments
    WHERE 
        stylist_id = ?
        AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY 
        hour_of_day
    ORDER BY 
        appointment_count DESC
    LIMIT 1
");
$peakTime->execute([$stylist_id]);
$peakTimeInfo = $peakTime->fetch();

// Format peak time for display
$peakHour = isset($peakTimeInfo['hour_of_day']) ? $peakTimeInfo['hour_of_day'] : 0;
$formattedPeakTime = date('g:i A', strtotime($peakHour . ':00'));

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Dashboard - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
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
        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            padding: 2px 4px;
        }
        .overbooked {
            background-color: #fecaca !important;
            border-color: #f87171 !important;
        }
        .rescheduled {
            background-color: #fef08a !important;
            border-color: #facc15 !important;
        }
        .treatment-massage {
            background-color: #a78bfa !important;
            border-color: #8b5cf6 !important;
        }
        .treatment-facial {
            background-color: #f9a8d4 !important;
            border-color: #f472b6 !important;
        }
        .treatment-body {
            background-color: #86efac !important;
            border-color: #4ade80 !important;
        }
        .treatment-waxing {
            background-color: #fca5a5 !important;
            border-color: #f87171 !important;
        }
        .animated-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .spa-gradient-bg {
            background: linear-gradient(to right, #fbc2eb, #a6c1ee);
        }
        .room-availability {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .available {
            background-color: #10b981;
        }
        .occupied {
            background-color: #ef4444;
        }
        .soon-available {
            background-color: #f59e0b;
        }
        .metric-card {
            transition: all 0.3s;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
                <div class="mb-6">
                    <p class="text-xs uppercase text-indigo-200 mb-2">Navigation</p>
                    <a href="home" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-home mr-2"></i> Back to Homepage
                    </a>
                    <a href="service-dashboard?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded bg-white bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="calendar-appt?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="all-appointments?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-check mr-2"></i> All Appointments
                    </a>
                    <a href="add-services" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-plus-circle mr-2"></i> Add Service
                    </a>
                    <a href="edit-services" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-edit mr-2"></i> Manage Services
                    </a>
                </div>
                <div class="mb-6">
                    <p class="text-xs uppercase text-indigo-200 mb-2">Administration</p>
                 
                    <a href="users-manage" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-users mr-2"></i> Customers
                    </a>
                   
                    <a href="staff-manage" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-user-tie mr-2"></i> Staff Management
                    </a>
                  
                </div>
                <div>
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
                        <h2 class="text-lg font-semibold">Service Provider Dashboard</h2>
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
                                    <?php if (count($notifications) > 0): ?>
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
                                                    <?php if ($notification['related_id']): ?>
                                                    <a href="manage-appointment?id=<?= $notification['related_id'] ?>&stylist_id=<?= $stylist_id ?>" 
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
                                    <a href="all-notifications?stylist_id=<?= $stylist_id ?>" class="text-xs text-purple-600 hover:text-purple-800">
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
                                    <a href="profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <div class="border-t border-gray-100"></div>
                                    <a href="logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
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
                        
                        <!-- Pending Staff Approvals -->
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_STAFF)): ?>
                        <div class="bg-white rounded-lg shadow p-5 metric-card border-l-4 border-orange-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Pending Staff Approvals</h4>
                                    <div class="mt-2 flex items-baseline">
                                        <p class="text-3xl font-semibold text-gray-900"><?= $pendingStaffCount ?></p>
                                        <p class="ml-2 text-sm text-gray-600">pending</p>
                                    </div>
                                </div>
                                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                                    <i class="fas fa-user-plus text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="/staff-manage" class="text-sm text-orange-600 hover:text-orange-800">
                                    Review applications <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Performance Insights -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Performance Insights</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Busiest Day -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Busiest Day of Week</h4>
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <i class="fas fa-calendar-week text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-xl font-semibold text-gray-900">
                                        <?= isset($busiestDayInfo['day_name']) ? $busiestDayInfo['day_name'] : 'N/A' ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?= isset($busiestDayInfo['appointment_count']) ? $busiestDayInfo['appointment_count'] . ' appointments' : 'No data available' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Most Popular Service -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Most Popular Service</h4>
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-hand-sparkles text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-xl font-semibold text-gray-900">
                                        <?= isset($popularServiceInfo['service_name']) ? htmlspecialchars($popularServiceInfo['service_name']) : 'N/A' ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?= isset($popularServiceInfo['booking_count']) ? $popularServiceInfo['booking_count'] . ' bookings' : 'No data available' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Peak Time Slot -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Peak Time Slot</h4>
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-rose-100 text-rose-600 mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-xl font-semibold text-gray-900">
                                        <?= isset($formattedPeakTime) ? $formattedPeakTime : 'N/A' ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?= isset($peakTimeInfo['appointment_count']) ? $peakTimeInfo['appointment_count'] . ' bookings' : 'No data available' ?>
                                    </p>
                                </div>
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
                                        ?>
                                        <div class="p-4 rounded-lg border <?= $alertClass ?>">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mt-1">
                                                    <i class="fas <?= $icon ?>"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($notification['message']) ?></p>
                                                    <p class="text-xs text-gray-500 mt-1"><?= date('M d, g:i A', strtotime($notification['created_at'])) ?></p>
                                                    <?php if ($notification['related_id']): ?>
                                                    <a href="manage-appointment?id=<?= $notification['related_id'] ?>&stylist_id=<?= $stylist_id ?>" 
                                                       class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                                        <i class="fas fa-external-link-alt mr-1"></i> View Details
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                        <p>No new notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (count($notifications) > 0): ?>
                            <div class="p-3 border-t border-gray-100 text-center">
                                <a href="all-notifications?stylist_id=<?= $stylist_id ?>" class="text-sm text-purple-600 hover:text-purple-800">
                                    View All Notifications
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Recent Changes Widget -->
                        <div class="bg-white rounded-lg shadow mt-6">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-700">Recent Cancellations/Reschedules</h3>
                            </div>
                            <div class="p-5 space-y-4 max-h-96 overflow-y-auto">
                                <?php if ($changesCount > 0): ?>
                                    <?php foreach ($recentChangesList as $change): ?>
                                        <div class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                                            <?php if ($change['change_type'] == 'Cancelled'): ?>
                                                <div class="h-10 w-10 rounded-full bg-red-100 text-red-500 flex items-center justify-center flex-shrink-0">
                                                    <i class="fas fa-calendar-xmark"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-yellow-100 text-yellow-500 flex items-center justify-center flex-shrink-0">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium"><?= htmlspecialchars($change['customer_name']) ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?= $change['change_type'] ?> a <?= htmlspecialchars($change['service_name']) ?> appointment
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    <?= date('M d, g:i A', strtotime($change['appointment_date'] . ' ' . $change['appointment_time'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-gray-500">
                                        <i class="fas fa-check-circle text-3xl mb-2"></i>
                                        <p>No recent cancellations or rescheduling</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's & Upcoming Appointments Column -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow" id="today-appointments">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-700">Today's Appointments</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <?php if ($todayCount > 0): ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($todayAppts as $appt): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Duration: <?= $appt['duration'] ?> min
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?= urlencode($appt['customer_name']) ?>&background=random" alt="">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($appt['customer_name']) ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            <?= htmlspecialchars($appt['customer_phone']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($appt['service_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusClass = '';
                                                $statusLabel = ucfirst($appt['status']);
                                                
                                                switch ($appt['status']) {
                                                    case 'confirmed':
                                                        $statusClass = 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'bg-indigo-100 text-indigo-800';
                                                        break;
                                                    case 'rescheduled':
                                                        $statusClass = 'bg-blue-100 text-blue-800';
                                                        break;
                                                }
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                    <?= $statusLabel ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                                <a href="manage-appointment?id=<?= $appt['id'] ?>&stylist_id=<?= $stylist_id ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($appt['status'] != 'completed' && $appt['status'] != 'cancelled'): ?>
                                                <a href="reschedule-services?id=<?= $appt['id'] ?>&stylist_id=<?= $stylist_id ?>" class="text-amber-600 hover:text-amber-900 mr-3">
                                                    <i class="fas fa-calendar-plus"></i> Reschedule
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($appt['status'] == 'confirmed' || $appt['status'] == 'pending'): ?>
                                                <a href="#" class="text-green-600 hover:text-green-900 mr-3 complete-btn" data-id="<?= $appt['id'] ?>">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="p-8 text-center text-gray-500">
                                    <i class="fas fa-calendar-day text-5xl mb-4 text-gray-300"></i>
                                    <p class="text-lg">No appointments scheduled for today</p>
                                    <p class="text-sm mt-2">Enjoy your day off!</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Weekly Appointment Overview -->
                        <div class="bg-white rounded-lg shadow mt-6">
                            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-700">Weekly Appointment Overview</h3>
                                <div class="text-sm">
                                    <span class="text-gray-500"><?= date('M d', strtotime($weekStart)) ?></span> - 
                                    <span class="text-gray-500"><?= date('M d', strtotime($weekEnd)) ?></span>
                                </div>
                            </div>
                            <div class="p-6">
                                <?php
                                // Group appointments by date
                                $appointmentsByDate = [];
                                foreach ($weekAppts as $appt) {
                                    $date = $appt['appointment_date'];
                                    if (!isset($appointmentsByDate[$date])) {
                                        $appointmentsByDate[$date] = [];
                                    }
                                    $appointmentsByDate[$date][] = $appt;
                                }
                                
                                // Sort by date
                                ksort($appointmentsByDate);
                                ?>
                                
                                <?php if (count($appointmentsByDate) > 0): ?>
                                <div class="space-y-6">
                                    <?php foreach ($appointmentsByDate as $date => $appts): ?>
                                    <div>
                                        <h4 class="text-md font-medium text-gray-700 mb-3">
                                            <?= date('l, F j', strtotime($date)) ?>
                                            <?php if ($date == $today): ?>
                                            <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full ml-2">Today</span>
                                            <?php endif; ?>
                                        </h4>
                                        <div class="space-y-2">
                                            <?php foreach ($appts as $appt): ?>
                                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                                <div class="w-16 flex-shrink-0">
                                                    <span class="font-medium"><?= date('g:i A', strtotime($appt['appointment_time'])) ?></span>
                                                </div>
                                                <div class="flex-grow">
                                                    <div class="font-medium"><?= htmlspecialchars($appt['customer_name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($appt['service_name']) ?> (<?= $appt['duration'] ?> min)</div>
                                                </div>
                                                <div class="w-24 flex-shrink-0 text-right">
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($appt['status']) {
                                                        case 'confirmed':
                                                            $statusClass = 'status-confirmed';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'status-pending';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'status-cancelled';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'status-completed';
                                                            break;
                                                        case 'rescheduled':
                                                            $statusClass = 'status-rescheduled';
                                                            break;
                                                    }
                                                    ?>
                                                    <div class="text-sm">
                                                        <span class="status-indicator <?= $statusClass ?>"></span>
                                                        <?= ucfirst($appt['status']) ?>
                                                    </div>
                                                </div>
                                                <div class="w-24 flex-shrink-0 text-right">
                                                    <a href="manage-appointment?id=<?= $appt['id'] ?>&stylist_id=<?= $stylist_id ?>" class="text-blue-600 hover:text-blue-900">
                                                        <i class="fas fa-eye"></i> Details
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="p-8 text-center text-gray-500">
                                    <i class="fas fa-calendar text-5xl mb-4 text-gray-300"></i>
                                    <p class="text-lg">No appointments scheduled for this week</p>
                                    <p class="text-sm mt-2">Check back later for updates</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 border-t border-gray-100 text-center">
                                <a href="calendar-appt?stylist_id=<?= $stylist_id ?>" class="text-sm text-purple-600 hover:text-purple-800">
                                    View Full Calendar <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Visualization Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Booking Analytics</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Booking Trends Chart -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Booking Trends (Last 30 Days)</h4>
                            <div class="h-64">
                                <canvas id="booking-trends-chart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Service Distribution Chart -->
                        <div class="bg-white rounded-lg shadow p-5">
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Service Distribution</h4>
                            <div class="h-64">
                                <canvas id="service-distribution-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        // Dropdown toggles
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');

        // Toggle notifications dropdown
        notificationsBtn?.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('hidden');
            // Hide user dropdown if open
            if (!userDropdown.classList.contains('hidden')) {
                userDropdown.classList.add('hidden');
            }
        });

        // Toggle user dropdown
        userMenuBtn?.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            // Hide notifications dropdown if open
            if (!notificationsDropdown.classList.contains('hidden')) {
                notificationsDropdown.classList.add('hidden');
            }
        });

        // Toggle sidebar on mobile
        sidebarToggle?.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            if (!notificationsDropdown.classList.contains('hidden')) {
                notificationsDropdown.classList.add('hidden');
            }
            if (!userDropdown.classList.contains('hidden')) {
                userDropdown.classList.add('hidden');
            }
        });

        // Prevent dropdown close when clicking inside
        notificationsDropdown?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        userDropdown?.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Mark all notifications as read
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                fetch('update_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `mark_all=1&stylist_id=<?= $stylist_id ?>`,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-badge').forEach(badge => {
                            badge.classList.add('hidden');
                        });
                        
                        const notificationsContent = document.querySelector('#notifications-dropdown .max-h-64');
                        if (notificationsContent) {
                            notificationsContent.innerHTML = `<div class="p-4 text-center text-gray-500 text-sm">No new notifications</div>`;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        }

        // Initialize booking trends chart
        const bookingTrendsChart = document.getElementById('booking-trends-chart');
        if (bookingTrendsChart) {
            const trendsData = <?= $trendChartData ?>;
            
            new Chart(bookingTrendsChart, {
                type: 'line',
                data: {
                    labels: trendsData.labels,
                    datasets: [{
                        label: 'Number of Bookings',
                        data: trendsData.data,
                        fill: false,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgba(99, 102, 241, 0.8)',
                        tension: 0.1,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItem) {
                                    return tooltipItem[0].label;
                                },
                                label: function(context) {
                                    return context.parsed.y + ' bookings';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize service distribution chart
        const serviceDistributionChart = document.getElementById('service-distribution-chart');
        if (serviceDistributionChart) {
            const serviceData = <?= $serviceChartData ?>;
            
            new Chart(serviceDistributionChart, {
                type: 'doughnut',
                data: {
                    labels: serviceData.labels,
                    datasets: [{
                        data: serviceData.data,
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(168, 85, 247, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return label + ': ' + value + ' bookings (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Complete appointment functionality
        const completeButtons = document.querySelectorAll('.complete-btn');
        completeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const appointmentId = this.getAttribute('data-id');
                
                if (confirm('Mark this appointment as completed?')) {
                    fetch('appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=complete&id=${appointmentId}&stylist_id=<?= $stylist_id ?>`,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload page to show updated status
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            });
        });

        // AJAX dashboard refresh for real-time updates
        function refreshDashboardData() {
            fetch('get_dashboard_updates.php?stylist_id=<?= $stylist_id ?>')
            .then(response => response.json())
            .then(data => {
                // Update notification count
                const notificationBadge = document.querySelector('.notification-badge');
                if (notificationBadge) {
                    if (data.notification_count > 0) {
                        notificationBadge.textContent = data.notification_count;
                        notificationBadge.classList.remove('hidden');
                    } else {
                        notificationBadge.classList.add('hidden');
                    }
                }
                
                // Check for new notifications and update UI if needed
                if (data.has_new_notifications) {
                    // Play notification sound if available
                    const notificationSound = new Audio('notification.mp3');
                    notificationSound.play().catch(e => console.log('Notification sound error:', e));
                    
                    // Shake the notification bell
                    notificationsBtn.classList.add('animate-wiggle');
                    setTimeout(() => {
                        notificationsBtn.classList.remove('animate-wiggle');
                    }, 1000);
                }
                
                // Schedule next refresh
                setTimeout(refreshDashboardData, 30000); // Refresh every 30 seconds
            })
            .catch(error => {
                console.error('Dashboard refresh error:', error);
                // Try again later even if there was an error
                setTimeout(refreshDashboardData, 60000);
            });
        }
        
        // Start the dashboard refresh cycle
        setTimeout(refreshDashboardData, 30000);
    });
    </script>
</body>
</html>
