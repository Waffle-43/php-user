<!-- views/staff/dashboard.php -->
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Users</h5>
                            <h2 class="card-text"><?= $data['user_count'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Staff</h5>
                            <h2 class="card-text"><?= $data['staff_count'] ?></h2>
                        </div>
                    </div>
                </div>
                <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_STAFF)): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pending Staff</h5>
                            <h2 class="card-text"><?= $data['pending_staff'] ?></h2>
                            <a href="staff-manage" class="btn btn-sm btn-primary">Manage</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Add more dashboard content here -->
            
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>