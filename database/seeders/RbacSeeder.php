<?php

// Adjust the path to your vendor/autoload.php if necessary
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth\RbacService;

$rbacService = new RbacService();

echo "Starting RBAC Seeding...\n";

// Define Roles
$roles = [
    ['name' => 'Admin', 'description' => 'Administrator with full system access'],
    ['name' => 'Staff', 'description' => 'Staff member with operational access'],
    ['name' => 'Customer', 'description' => 'Customer with access to booking and personal appointments'],
];

$roleIds = [];
foreach ($roles as $roleData) {
    $existingRole = $rbacService->getRole($roleData['name']);
    if ($existingRole) {
        $roleIds[$roleData['name']] = $existingRole['id'];
        echo "Role '{$roleData['name']}' already exists. ID: {$existingRole['id']}\n";
    } else {
        $roleId = $rbacService->addRole($roleData['name'], $roleData['description']);
        if ($roleId) {
            $roleIds[$roleData['name']] = $roleId;
            echo "Created role '{$roleData['name']}' with ID: $roleId\n";
        } else {
            echo "Failed to create role '{$roleData['name']}'\n";
        }
    }
}

// Define Permissions
$permissions = [
    // Admin Permissions
    ['name' => 'manage_system_settings', 'description' => 'Manage overall system settings'],
    ['name' => 'manage_roles_permissions', 'description' => 'Manage roles and permissions'],
    ['name' => 'manage_all_users', 'description' => 'Manage all user accounts (customers and staff)'],
    
    // Staff Permissions
    ['name' => 'manage_appointments', 'description' => 'Manage appointments (create, edit, cancel for any customer)'],
    ['name' => 'view_staff_dashboard', 'description' => 'Access the staff dashboard'],
    ['name' => 'manage_services', 'description' => 'Manage available services'],
    ['name' => 'manage_own_availability', 'description' => 'Manage own working hours/availability'],

    // Customer Permissions
    ['name' => 'book_appointments', 'description' => 'Book new appointments'],
    ['name' => 'view_own_appointments', 'description' => 'View own past and upcoming appointments'],
    ['name' => 'cancel_own_appointments', 'description' => 'Cancel own appointments'],
    ['name' => 'update_own_profile', 'description' => 'Update own user profile'],
];

$permissionIds = [];
foreach ($permissions as $permissionData) {
    $existingPermission = $rbacService->getPermission($permissionData['name']);
    if ($existingPermission) {
        $permissionIds[$permissionData['name']] = $existingPermission['id'];
        echo "Permission '{$permissionData['name']}' already exists. ID: {$existingPermission['id']}\n";
    } else {
        $permissionId = $rbacService->addPermission($permissionData['name'], $permissionData['description']);
        if ($permissionId) {
            $permissionIds[$permissionData['name']] = $permissionId;
            echo "Created permission '{$permissionData['name']}' with ID: $permissionId\n";
        } else {
            echo "Failed to create permission '{$permissionData['name']}'\n";
        }
    }
}

// Assign Permissions to Roles
$assignments = [
    'Admin' => [
        'manage_system_settings', 'manage_roles_permissions', 'manage_all_users', 
        'manage_appointments', 'view_staff_dashboard', 'manage_services', 'manage_own_availability', // Admin can do staff things too
        'book_appointments', 'view_own_appointments', 'cancel_own_appointments', 'update_own_profile' // And customer things
    ],
    'Staff' => [
        'manage_appointments', 'view_staff_dashboard', 'manage_services', 'manage_own_availability',
        'update_own_profile' // Staff might update their staff profile details
    ],
    'Customer' => [
        'book_appointments', 'view_own_appointments', 'cancel_own_appointments', 'update_own_profile'
    ],
];

foreach ($assignments as $roleName => $permissionNames) {
    if (!isset($roleIds[$roleName])) {
        echo "Role '$roleName' not found for assignments. Skipping.\n";
        continue;
    }
    $roleId = $roleIds[$roleName];
    foreach ($permissionNames as $permissionName) {
        if (!isset($permissionIds[$permissionName])) {
            echo "Permission '$permissionName' not found for assignment to '$roleName'. Skipping.\n";
            continue;
        }
        $permissionId = $permissionIds[$permissionName];
        if ($rbacService->assignPermissionToRole($roleId, $permissionId)) {
            echo "Assigned permission '$permissionName' to role '$roleName'\n";
        } else {
            echo "Failed to assign permission '$permissionName' to role '$roleName'\n";
        }
    }
}

echo "RBAC Seeding completed.\n";
