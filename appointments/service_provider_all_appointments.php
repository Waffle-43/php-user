<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Get stylist ID from query string (for testing purposes)
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'upcoming';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get stylist info
$stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
$stmt->execute([$stylist_id]);
$stylist = $stmt->fetch();

if (!$stylist) {
    die("Stylist not found");
}

// Build query for appointments
$query = "
    SELECT 
        a.*,
        c.name as customer_name,
        c.email as customer_email,
        c.phone as customer_phone,
        s.name as service_name,
        s.price
    FROM 
        appointments a
    JOIN 
        services s ON a.service_id = s.id
    JOIN 
        customers c ON a.customer_id = c.id
    WHERE 
        a.stylist_id = :stylist_id
";

$params = [':stylist_id' => $stylist_id];

// Apply filters
if ($status_filter !== 'all') {
    $query .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if ($date_filter === 'today') {
    $query .= " AND a.appointment_date = CURDATE()";
} elseif ($date_filter === 'upcoming') {
    $query .= " AND a.appointment_date >= CURDATE()";
} elseif ($date_filter === 'past') {
    $query .= " AND a.appointment_date < CURDATE()";
} elseif ($date_filter === 'week') {
    $query .= " AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $query .= " AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
}

if (!empty($search_term)) {
    $query .= " AND (c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR s.name LIKE :search)";
    $params[':search'] = "%{$search_term}%";
}

// Sort order
$query .= " ORDER BY a.appointment_date, a.appointment_time";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get counts for filter badges
$countQueries = [
    'all' => "SELECT COUNT(*) FROM appointments WHERE stylist_id = ?",
    'pending' => "SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND status = 'pending'",
    'confirmed' => "SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND status = 'confirmed'",
    'completed' => "SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND status = 'completed'",
    'cancelled' => "SELECT COUNT(*) FROM appointments WHERE stylist_id = ? AND status = 'cancelled'"
];

$counts = [];
foreach ($countQueries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->execute([$stylist_id]);
    $counts[$key] = $stmt->fetchColumn();
}

// Group appointments by date for display
$groupedAppointments = [];
foreach ($appointments as $appointment) {
    $date = $appointment['appointment_date'];
    if (!isset($groupedAppointments[$date])) {
        $groupedAppointments[$date] = [];
    }
    $groupedAppointments[$date][] = $appointment;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .status-confirmed {
            border-left: 4px solid #10b981;
        }

        .status-pending {
            border-left: 4px solid #f59e0b;
        }

        .status-cancelled {
            border-left: 4px solid #ef4444;
        }

        .status-completed {
            border-left: 4px solid #3b82f6;
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
                    <a href="service-dashboard?stylist_id=<?= $stylist_id ?>"
                        class="block py-2 px-3 rounded bg-white bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="calendar-appt?stylist_id=<?= $stylist_id ?>"
                        class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="all-appointments?stylist_id=<?= $stylist_id ?>"
                        class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-check mr-2"></i> All Appointments
                    </a>
                    <a href="add-services" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-plus-circle mr-2"></i> Add Service
                    </a>
                    <a href="edit-services" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-edit mr-2"></i> Manage Services
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
                        <h2 class="text-lg font-semibold">All Appointments</h2>
                    </div>
                    <div class="flex items-center">
                        <a href="service_provider_dashboard.php?stylist_id=<?= $stylist_id ?>"
                            class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm flex items-center hover:bg-gray-200 mr-2">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                        <a href="service_provider_add_appointment.php?stylist_id=<?= $stylist_id ?>"
                            class="bg-purple-600 text-white px-3 py-1 rounded text-sm flex items-center hover:bg-purple-700">
                            <i class="fas fa-plus mr-1"></i> New Appointment
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <a href="?stylist_id=<?= $stylist_id ?>&status=all&date=<?= $date_filter ?>&search=<?= htmlspecialchars($search_term) ?>"
                                class="px-3 py-1 rounded text-sm <?= $status_filter === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                                All <span
                                    class="ml-1 px-1.5 py-0.5 rounded-full text-xs <?= $status_filter === 'all' ? 'bg-white text-purple-600' : 'bg-gray-200 text-gray-700' ?>"><?= $counts['all'] ?></span>
                            </a>
                            <a href="?stylist_id=<?= $stylist_id ?>&status=pending&date=<?= $date_filter ?>&search=<?= htmlspecialchars($search_term) ?>"
                                class="px-3 py-1 rounded text-sm <?= $status_filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' ?>">
                                Pending <span
                                    class="ml-1 px-1.5 py-0.5 rounded-full text-xs <?= $status_filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-yellow-200 text-yellow-800' ?>"><?= $counts['pending'] ?></span>
                            </a>
                            <a href="?stylist_id=<?= $stylist_id ?>&status=confirmed&date=<?= $date_filter ?>&search=<?= htmlspecialchars($search_term) ?>"
                                class="px-3 py-1 rounded text-sm <?= $status_filter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200' ?>">
                                Confirmed <span
                                    class="ml-1 px-1.5 py-0.5 rounded-full text-xs <?= $status_filter === 'confirmed' ? 'bg-white text-green-600' : 'bg-green-200 text-green-800' ?>"><?= $counts['confirmed'] ?></span>
                            </a>
                            <a href="?stylist_id=<?= $stylist_id ?>&status=completed&date=<?= $date_filter ?>&search=<?= htmlspecialchars($search_term) ?>"
                                class="px-3 py-1 rounded text-sm <?= $status_filter === 'completed' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' ?>">
                                Completed <span
                                    class="ml-1 px-1.5 py-0.5 rounded-full text-xs <?= $status_filter === 'completed' ? 'bg-white text-blue-600' : 'bg-blue-200 text-blue-800' ?>"><?= $counts['completed'] ?></span>
                            </a>
                            <a href="?stylist_id=<?= $stylist_id ?>&status=cancelled&date=<?= $date_filter ?>&search=<?= htmlspecialchars($search_term) ?>"
                                class="px-3 py-1 rounded text-sm <?= $status_filter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200' ?>">
                                Cancelled <span
                                    class="ml-1 px-1.5 py-0.5 rounded-full text-xs <?= $status_filter === 'cancelled' ? 'bg-white text-red-600' : 'bg-red-200 text-red-800' ?>"><?= $counts['cancelled'] ?></span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <select id="date-filter"
                                    class="appearance-none bg-gray-100 border border-gray-300 rounded px-3 py-1 pr-8 text-sm">
                                    <option value="upcoming" <?= $date_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming
                                    </option>
                                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                                    <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week
                                    </option>
                                    <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month
                                    </option>
                                    <option value="past" <?= $date_filter === 'past' ? 'selected' : '' ?>>Past</option>
                                    <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Dates</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-2 top-2 text-xs text-gray-500"></i>
                            </div>

                            <form method="GET" class="flex">
                                <input type="hidden" name="stylist_id" value="<?= $stylist_id ?>">
                                <input type="hidden" name="status" value="<?= $status_filter ?>">
                                <input type="hidden" name="date" value="<?= $date_filter ?>">
                                <div class="relative">
                                    <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>"
                                        placeholder="Search..."
                                        class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm pr-10">
                                    <button type="submit" class="absolute right-0 top-0 h-full px-2 text-gray-500">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Appointments List -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($appointments)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-calendar-day text-gray-300 text-4xl mb-3"></i>
                            <p>No appointments found matching your criteria.</p>
                            <a href="?stylist_id=<?= $stylist_id ?>"
                                class="mt-3 inline-block text-purple-600 hover:text-purple-800 text-sm">
                                <i class="fas fa-redo mr-1"></i> Clear filters
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date & Time</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Client</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Service</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($groupedAppointments as $date => $dateAppointments): ?>
                                        <tr class="bg-gray-50">
                                            <td colspan="6" class="px-6 py-2 text-sm font-medium text-gray-500">
                                                <?= date('l, F j, Y', strtotime($date)) ?>
                                                <?php if ($date === date('Y-m-d')): ?>
                                                    <span
                                                        class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">Today</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php foreach ($dateAppointments as $appointment): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium">
                                                        <?= date('g:i A', strtotime($appointment['appointment_time'])) ?></div>
                                                    <div class="text-xs text-gray-500"><?= $appointment['duration'] ?> min</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium">
                                                        <?= htmlspecialchars($appointment['customer_name']) ?></div>
                                                    <div class="text-xs text-gray-500">
                                                        <?= htmlspecialchars($appointment['customer_phone']) ?></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm"><?= htmlspecialchars($appointment['service_name']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm">$<?= number_format($appointment['price'], 2) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php
                                                    switch ($appointment['status']) {
                                                        case 'pending':
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'confirmed':
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                        case 'completed':
                                                            echo 'bg-blue-100 text-blue-800';
                                                            break;
                                                        default:
                                                            echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                        <?= ucfirst($appointment['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <a href="service_provider_manage_appointment.php?id=<?= $appointment['id'] ?>&stylist_id=<?= $stylist_id ?>"
                                                        class="text-purple-600 hover:text-purple-900">
                                                        <i class="fas fa-edit mr-1"></i> Manage
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Date filter change handler
            document.getElementById('date-filter').addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('date', this.value);
                window.location.href = url.toString();
            });

            // Toggle sidebar
            document.getElementById('sidebar-toggle').addEventListener('click', function () {
                document.querySelector('.sidebar').classList.toggle('hidden');
            });
        });
    </script>
</body>

</html>