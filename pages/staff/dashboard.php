<?php
// Ensure this file is included in the correct context
defined('BASEPATH') or define('BASEPATH', true);

// Check if staff is logged in
if (!isset($_SESSION['staff'])) {
    header('Location: /staff-signin');
    exit;
}

// Get current staff info
$currentStaff = $_SESSION['staff'];
$role = $currentStaff['role'] ?? 'staff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Spa/Salon Booking System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/globals.css">
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-item {
            border-left: 3px solid #2470dc;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">Spa/Salon Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <input class="form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search">
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="/staff-logout">Sign out</a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="/staff-dashboard">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_APPOINTMENTS)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/appointments">
                                <i class="bi bi-calendar-check me-1"></i>
                                Appointments
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_SERVICES)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/services">
                                <i class="bi bi-list-check me-1"></i>
                                Services
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_USERS)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/users">
                                <i class="bi bi-people me-1"></i>
                                Customers
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_STAFF)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/staff-manage">
                                <i class="bi bi-person-badge me-1"></i>
                                Staff Management
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_VIEW_REPORTS)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/reports">
                                <i class="bi bi-graph-up me-1"></i>
                                Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_SETTINGS)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/settings">
                                <i class="bi bi-gear me-1"></i>
                                Settings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Quick Actions</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="/new-appointment">
                                <i class="bi bi-calendar-plus me-1"></i>
                                New Appointment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/new-service">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add Service
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="bi bi-calendar me-1"></i>
                            This week
                        </button>
                    </div>
                </div>

                <!-- Welcome Banner -->
                <div class="welcome-banner mb-4">
                    <h2>Welcome back, <?= htmlspecialchars($currentStaff['name']) ?>!</h2>
                    <p class="mb-0">Here's what's happening with your spa/salon today.</p>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Staff</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $data['staff_count'] ?? 0 ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people stat-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Registered Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $data['user_count'] ?? 0 ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-check stat-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Today's Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">15</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-date stat-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Staff Approvals</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $data['pending_staff'] ?? 0 ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-plus stat-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Chart -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-graph-up me-1"></i>
                                Appointment Statistics
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="appointmentsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-clock-history me-1"></i>
                                Recent Activity
                            </div>
                            <div class="card-body recent-activity">
                                <div class="activity-item">
                                    <p class="mb-1"><strong>New Booking</strong></p>
                                    <p class="mb-1">Jane Smith booked a Swedish Massage</p>
                                    <small class="text-muted">10 minutes ago</small>
                                </div>
                                <div class="activity-item">
                                    <p class="mb-1"><strong>Staff Login</strong></p>
                                    <p class="mb-1">Maria Rodriguez logged in</p>
                                    <small class="text-muted">25 minutes ago</small>
                                </div>
                                <div class="activity-item">
                                    <p class="mb-1"><strong>Service Updated</strong></p>
                                    <p class="mb-1">Facial Treatment price updated</p>
                                    <small class="text-muted">1 hour ago</small>
                                </div>
                                <div class="activity-item">
                                    <p class="mb-1"><strong>New Customer</strong></p>
                                    <p class="mb-1">John Doe registered an account</p>
                                    <small class="text-muted">2 hours ago</small>
                                </div>
                                <div class="activity-item">
                                    <p class="mb-1"><strong>Appointment Completed</strong></p>
                                    <p class="mb-1">Deep Tissue Massage for Alice Johnson</p>
                                    <small class="text-muted">3 hours ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-calendar me-1"></i>
                        Today's Appointments
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>09:00 AM</td>
                                        <td>Sarah Johnson</td>
                                        <td>Swedish Massage (60 min)</td>
                                        <td>Maria Rodriguez</td>
                                        <td><span class="badge bg-success">Confirmed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">View</button>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>10:30 AM</td>
                                        <td>Michael Brown</td>
                                        <td>Haircut & Styling (45 min)</td>
                                        <td>James Wilson</td>
                                        <td><span class="badge bg-success">Confirmed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">View</button>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>11:15 AM</td>
                                        <td>Emily Davis</td>
                                        <td>Facial Treatment (90 min)</td>
                                        <td>Sophia Garcia</td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">View</button>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>01:00 PM</td>
                                        <td>David Wilson</td>
                                        <td>Deep Tissue Massage (90 min)</td>
                                        <td>Maria Rodriguez</td>
                                        <td><span class="badge bg-success">Confirmed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">View</button>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>02:30 PM</td>
                                        <td>Jennifer Taylor</td>
                                        <td>Manicure & Pedicure (75 min)</td>
                                        <td>Lisa Martinez</td>
                                        <td><span class="badge bg-danger">Cancelled</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">View</button>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('appointmentsChart').getContext('2d');
            const appointmentsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    datasets: [{
                        label: 'Appointments',
                        data: [12, 19, 15, 17, 22, 30, 25],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
