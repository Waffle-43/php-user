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
        require_once __DIR__ . '/../../views/staff/dashboard.php';
    }
    
    public static function manageStaff()
    {
        // Ensure staff has permission
        StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
        
        $staffModel = new Staff();
        $staff = $staffModel->getAllStaff();
        
        require_once __DIR__ . '/../../views/staff/manage_staff.php';
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
}