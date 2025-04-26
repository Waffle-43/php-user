<?php

namespace App\Controllers;

use App\Models\Staff;
use Valitron\Validator;
use Utils\Helper;
use App\Auth\RolePermission;

class StaffAuth
{
    public static function checkStaffSession()
    {
        // Check if staff is logged in and session is valid
        if (!isset($_SESSION['staff']) || !isset($_SESSION['staff']['last_activity'])) {
            return false;
        }
        
        // Check for session timeout (e.g., 30 minutes)
        $timeout = 1800; // 30 minutes
        if (time() - $_SESSION['staff']['last_activity'] > $timeout) {
            self::logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['staff']['last_activity'] = time();
        return true;
    }
    public static function requirePermission($permission)
{
    self::requireStaffLogin();
    
    if (!\App\Auth\RolePermission::hasPermission($permission)) {
        $_SESSION['errors']['general'][] = "You don't have permission to access this area";
        Helper::redirect('staff-dashboard');
        exit;
    }
}
    
    public static function requireStaffLogin()
    {
        // Use this method to protect staff-only pages
        if (!self::checkStaffSession()) {
            $_SESSION['errors']['general'][] = "Please log in to access the staff area";
            Helper::redirect('staff-signin');
            exit;
        }
    }
    
    public static function logout()
    {
        // Clear staff session and cookie
        unset($_SESSION['staff']);
        if (isset($_COOKIE['staff_remember'])) {
            setcookie("staff_remember", "", time() - 3600, "/", "", true, true);
        }
        
        $_SESSION['success'] = "You have been logged out successfully";
        Helper::redirect('staff-signin');
        exit;
    }
    
    public static function handleRememberMe()
    {
        // Check for "remember me" cookie and auto-login staff if valid
        if (!isset($_SESSION['staff']) && isset($_COOKIE['staff_remember'])) {
            $staffId = base64_decode($_COOKIE['staff_remember']);
            
            $staff = new Staff();
            $staffMember = $staff->findById($staffId);
            
            if ($staffMember && $staffMember->status === 'approved') {
                $_SESSION['staff'] = [
                    'id' => $staffMember->id,
                    'username' => $staffMember->username,
                    'email' => $staffMember->email,
                    'email_encrypted' => Helper::encryptEmail($staffMember->email),
                    'name' => $staffMember->name,
                    'role' => $staffMember->role,
                    'last_activity' => time(),
                ];
                
                // Refresh the cookie
                setcookie("staff_remember", base64_encode($staffMember->id), time() + (30 * 24 * 60 * 60), "/", "", true, true);
            }
        }
    }
}