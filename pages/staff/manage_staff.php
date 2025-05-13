<?php
// Ensure this file is included in the correct context
defined('BASEPATH') or define('BASEPATH', true);

// Check if staff is logged in and has permission
if (!isset($_SESSION['staff'])) {
    header('Location: /staff-signin');
    exit;
}

// Check for permission
$rbacService = new \App\Auth\RbacService();
if (!isset($_SESSION['staff']['id']) || !$rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_staff', 'staff')) {
    $_SESSION['errors']['general'][] = "You don't have permission to access this area";
    header('Location: /staff-dashboard');
    exit;
}

// Get current staff info
$currentStaff = $_SESSION['staff'];
$role = $currentStaff['role'] ?? 'staff';

// Get staff list from the controller
$staff = $staff ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Spa/Salon Booking System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/globals.css">
    <style>
        .staff-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .staff-card:hover {
            transform: translateY(-5px);
        }
        .staff-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #6c757d;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .role-badge {
            font-size: 0.8rem;
        }
        .staff-actions {
            display: flex;
            gap: 5px;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .page-title {
            border-bottom: 2px solid #2470dc;
            padding-bottom: 10px;
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
                            <a class="nav-link" href="/staff-dashboard">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_appointments', 'staff')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/appointments">
                                <i class="bi bi-calendar-check me-1"></i>
                                Appointments
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_services', 'staff')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/services">
                                <i class="bi bi-list-check me-1"></i>
                                Services
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_users', 'staff')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/users">
                                <i class="bi bi-people me-1"></i>
                                Customers
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_staff', 'staff')): ?>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="/staff-manage">
                                <i class="bi bi-person-badge me-1"></i>
                                Staff Management
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'view_reports', 'staff')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/reports">
                                <i class="bi bi-graph-up me-1"></i>
                                Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['staff']['id']) && $rbacService->userHasPermission($_SESSION['staff']['id'], 'manage_settings', 'staff')): ?>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2 page-title">Staff Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                            <i class="bi bi-person-plus me-1"></i> Add New Staff
                        </button>
                    </div>
                </div>

                <!-- Display success/error messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['general'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($_SESSION['errors']['general'] as $error): ?>
                            <p class="mb-0"><?= $error ?></p>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['errors']['general']); ?>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Filter by Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="all">All Statuses</option>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="roleFilter" class="form-label">Filter by Role</label>
                            <select class="form-select" id="roleFilter">
                                <option value="all">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                                <option value="service_provider">Service Provider</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchStaff" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchStaff" placeholder="Search by name, email...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" id="applyFilters">Apply Filters</button>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Section -->
                <?php 
                $pendingStaff = array_filter($staff, function($member) {
                    return $member['status'] === 'pending';
                });
                
                if (!empty($pendingStaff)): 
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-white">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Pending Staff Approvals (<?= count($pendingStaff) ?>)
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Requested On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingStaff as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['name']) ?></td>
                                        <td><?= htmlspecialchars($member['email']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($member['role']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($member['created_at'])) ?></td>
                                        <td>
                                            <form action="/staff-confirmation" method="POST" class="d-inline">
                                                <input type="hidden" name="staff_id" value="<?= $member['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <form action="/staff-confirmation" method="POST" class="d-inline">
                                                <input type="hidden" name="staff_id" value="<?= $member['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Staff List -->
                <div class="row" id="staffList">
                    <?php foreach ($staff as $member): ?>
                    <div class="col-md-4 staff-item" 
                         data-status="<?= htmlspecialchars($member['status']) ?>" 
                         data-role="<?= htmlspecialchars($member['role']) ?>"
                         data-name="<?= htmlspecialchars($member['name']) ?>"
                         data-email="<?= htmlspecialchars($member['email']) ?>">
                        <div class="card staff-card">
                            <?php 
                            $statusClass = 'bg-secondary';
                            if ($member['status'] === 'approved') $statusClass = 'bg-success';
                            if ($member['status'] === 'rejected') $statusClass = 'bg-danger';
                            if ($member['status'] === 'pending') $statusClass = 'bg-warning';
                            ?>
                            <span class="badge <?= $statusClass ?> status-badge"><?= ucfirst(htmlspecialchars($member['status'])) ?></span>
                            
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="staff-avatar">
                                        <i class="bi bi-person"></i>
                                    </div>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($member['name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($member['email']) ?></p>
                                <p class="card-text">
                                    <span class="badge bg-info role-badge"><?= ucfirst(htmlspecialchars($member['role'])) ?></span>
                                </p>
                                <p class="card-text small">
                                    <strong>Joined:</strong> <?= date('M d, Y', strtotime($member['created_at'])) ?>
                                </p>
                                
                                <div class="staff-actions">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewStaffModal" 
                                            data-id="<?= $member['id'] ?>"
                                            data-name="<?= htmlspecialchars($member['name']) ?>"
                                            data-email="<?= htmlspecialchars($member['email']) ?>"
                                            data-username="<?= htmlspecialchars($member['username']) ?>"
                                            data-role="<?= htmlspecialchars($member['role']) ?>"
                                            data-status="<?= htmlspecialchars($member['status']) ?>"
                                            data-created="<?= date('M d, Y', strtotime($member['created_at'])) ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStaffModal"
                                            data-id="<?= $member['id'] ?>"
                                            data-name="<?= htmlspecialchars($member['name']) ?>"
                                            data-email="<?= htmlspecialchars($member['email']) ?>"
                                            data-username="<?= htmlspecialchars($member['username']) ?>"
                                            data-role="<?= htmlspecialchars($member['role']) ?>"
                                            data-status="<?= htmlspecialchars($member['status']) ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <?php if ($member['id'] != $currentStaff['id']): ?>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteStaffModal"
                                            data-id="<?= $member['id'] ?>"
                                            data-name="<?= htmlspecialchars($member['name']) ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- No Staff Found Message -->
                <div id="noStaffMessage" class="alert alert-info text-center" style="display: none;">
                    <i class="bi bi-info-circle me-2"></i> No staff members found matching your filters.
                </div>
            </main>
        </div>
    </div>

    <!-- View Staff Modal -->
    <div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStaffModalLabel">Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="staff-avatar mx-auto">
                            <i class="bi bi-person"></i>
                        </div>
                        <h4 id="viewStaffName" class="mt-2"></h4>
                        <span id="viewStaffRole" class="badge bg-info"></span>
                        <span id="viewStaffStatus" class="badge bg-success ms-2"></span>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Username:</div>
                        <div class="col-md-8" id="viewStaffUsername"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8" id="viewStaffEmail"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Joined:</div>
                        <div class="col-md-8" id="viewStaffCreated"></div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Permissions</h5>
                        <ul class="list-group" id="viewStaffPermissions">
                            <!-- Permissions will be populated by JavaScript -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editStaffForm" action="/staff-update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="editStaffId" name="staff_id">
                        
                        <div class="mb-3">
                            <label for="editStaffName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editStaffName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStaffUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editStaffUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStaffEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editStaffEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStaffRole" class="form-label">Role</label>
                            <select class="form-select" id="editStaffRole" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                                <option value="service_provider">Service Provider</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStaffStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStaffStatus" name="status" required>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reset Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="editStaffPassword" name="password" placeholder="Leave blank to keep current password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Leave blank if you don't want to change the password</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addStaffForm" action="/staff-add" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addStaffName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addStaffName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="addStaffUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="addStaffEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffRole" class="form-label">Role</label>
                            <select class="form-select" id="addStaffRole" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                                <option value="service_provider">Service Provider</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffStatus" class="form-label">Status</label>
                            <select class="form-select" id="addStaffStatus" name="status" required>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="addStaffPassword" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleAddPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addStaffConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="addStaffConfirmPassword" name="confirm_password" required>
                            <div class="invalid-feedback">Passwords do not match</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Staff Modal -->
    <div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStaffModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="deleteStaffName" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteStaffForm" action="/staff-delete" method="POST">
                        <input type="hidden" id="deleteStaffId" name="staff_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/pages/staff/staff_management.js"></script>
</body>
</html>
