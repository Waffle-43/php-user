<!-- views/staff/sidebar.php -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>" href="staff-dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_USERS)): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>" href="staff-users">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_STAFF)): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'staff' ? 'active' : '' ?>" href="staff-manage">
                    <i class="fas fa-user-tie"></i> Staff
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_CONTENT)): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'content' ? 'active' : '' ?>" href="staff-content">
                    <i class="fas fa-file-alt"></i> Content
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (\App\Auth\RolePermission::hasPermission(\App\Auth\RolePermission::PERMISSION_MANAGE_SETTINGS)): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'settings' ? 'active' : '' ?>" href="staff-settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Account</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>" href="staff-profile">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="staff-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>