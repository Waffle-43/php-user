<?php

namespace App\Auth;

class RolePermission
{
    // Define permission constants
    const PERMISSION_VIEW_DASHBOARD = 'view_dashboard';
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_MANAGE_STAFF = 'manage_staff';
    const PERMISSION_MANAGE_CONTENT = 'manage_content';
    const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
    
    // Define role-based permissions
    private static $rolePermissions = [
        'admin' => [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_MANAGE_USERS,
            self::PERMISSION_MANAGE_STAFF,
            self::PERMISSION_MANAGE_CONTENT,
            self::PERMISSION_MANAGE_SETTINGS
        ],
        'manager' => [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_MANAGE_USERS,
            self::PERMISSION_MANAGE_CONTENT
        ],
        'staff' => [
            self::PERMISSION_VIEW_DASHBOARD
        ]
    ];
    
    // Check if staff has specific permission
    public static function hasPermission($permission)
    {
        if (!isset($_SESSION['staff']) || !isset($_SESSION['staff']['role'])) {
            return false;
        }
        
        $role = $_SESSION['staff']['role'];
        
        // Check role-based permissions
        if (isset(self::$rolePermissions[$role]) && in_array($permission, self::$rolePermissions[$role])) {
            return true;
        }
        
        // Check additional custom permissions
        if (isset($_SESSION['staff']['permissions'])) {
            $customPermissions = json_decode($_SESSION['staff']['permissions'], true);
            if (is_array($customPermissions) && in_array($permission, $customPermissions)) {
                return true;
            }
        }
        
        return false;
    }
}