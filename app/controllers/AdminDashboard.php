<?php

namespace App\Controllers;

use App\Auth\RolePermission;
use App\Models\Staff;
use App\Models\User;
use Utils\Helper;

class AdminDashboard
{
    public static function index()
    {
        // Ensure staff is logged in
        StaffAuth::requireStaffLogin();
        
        // Get stats for dashboard
        $staffModel = new Staff();
        $userModel = new User();
        
        $data = [
            'staff_count' => count($staffModel->getAllStaff()),
            'user_count' => count($userModel->getAllUsers()),
            'pending_staff' => count($staffModel->getAllPending()),
            'current_staff' => $_SESSION['staff']
        ];
        
        // Load the view with data
        require_once __DIR__ . '/../../pages/staff/dashboard.php';
    }
    
    public static function manageStaff()
    {
        // Ensure staff has permission
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        $staffModel = new Staff();
        $staff = $staffModel->getAllStaff();
        
        require_once __DIR__ . '/../../pages/staff/manage_staff.php';
    }
    
    public static function approveStaff()
    {
        // Check permissions
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        
        $staffId = $_POST['staff_id'] ?? null;
        $action = $_POST['action'] ?? null;
        
        if (!$staffId || !in_array($action, ['approve', 'reject'])) {
            $_SESSION['errors']['general'][] = "Invalid request";
            Helper::redirect('staff-manage');
            exit;
        }
        
        $staffModel = new Staff();
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        if ($staffModel->updateStatus($staffId, $status)) {
            $_SESSION['success'] = "Staff status updated successfully";
        } else {
            $_SESSION['errors']['general'][] = "Could not update staff status";
        }
        
        Helper::redirect('staff-manage');
    }
    
    public static function updateStaff()
    {
        // Check permissions
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        
        $staffId = $_POST['staff_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $role = $_POST['role'] ?? null;
        $status = $_POST['status'] ?? null;
        $password = $_POST['password'] ?? null;
        
        if (!$staffId || !$name || !$username || !$email || !$role || !$status) {
            $_SESSION['errors']['general'][] = "All fields are required except password";
            Helper::redirect('staff-manage');
            exit;
        }
        
        $staffModel = new Staff();
        
        // Prepare data for update
        $data = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'status' => $status
        ];
        
        // Only include password if it's provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($staffModel->updateStaff($staffId, $data)) {
            $_SESSION['success'] = "Staff information updated successfully";
        } else {
            $_SESSION['errors']['general'][] = "Could not update staff information";
        }
        
        Helper::redirect('staff-manage');
    }
    
    public static function addStaff()
    {
        // Check permissions
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        
        $name = $_POST['name'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $role = $_POST['role'] ?? null;
        $status = $_POST['status'] ?? null;
        $password = $_POST['password'] ?? null;
        $confirmPassword = $_POST['confirm_password'] ?? null;
        
        if (!$name || !$username || !$email || !$role || !$status || !$password || !$confirmPassword) {
            $_SESSION['errors']['general'][] = "All fields are required";
            Helper::redirect('staff-manage');
            exit;
        }
        
        if ($password !== $confirmPassword) {
            $_SESSION['errors']['general'][] = "Passwords do not match";
            Helper::redirect('staff-manage');
            exit;
        }
        
        $staffModel = new Staff();
        
        // Check if username or email already exists
        if ($staffModel->findByUsername($username)) {
            $_SESSION['errors']['general'][] = "Username already exists";
            Helper::redirect('staff-manage');
            exit;
        }
        
        if ($staffModel->findByEmail($email)) {
            $_SESSION['errors']['general'][] = "Email already exists";
            Helper::redirect('staff-manage');
            exit;
        }
        
        // Prepare data for insertion
        $data = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        if ($staffModel->createStaff($data)) {
            $_SESSION['success'] = "New staff added successfully";
        } else {
            $_SESSION['errors']['general'][] = "Could not add new staff";
        }
        
        Helper::redirect('staff-manage');
    }
    
    public static function deleteStaff()
    {
        // Check permissions
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        
        $staffId = $_POST['staff_id'] ?? null;
        
        if (!$staffId) {
            $_SESSION['errors']['general'][] = "Invalid request";
            Helper::redirect('staff-manage');
            exit;
        }
        
        // Prevent deleting yourself
        if ($staffId == $_SESSION['staff']['id']) {
            $_SESSION['errors']['general'][] = "You cannot delete your own account";
            Helper::redirect('staff-manage');
            exit;
        }
        
        $staffModel = new Staff();
        
        if ($staffModel->deleteStaff($staffId)) {
            $_SESSION['success'] = "Staff deleted successfully";
        } else {
            $_SESSION['errors']['general'][] = "Could not delete staff";
        }
        
        Helper::redirect('staff-manage');
    }
}
