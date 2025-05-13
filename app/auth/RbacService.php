<?php

namespace App\Auth;

use Core\Database;
use PDO;

class RbacService {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Role Management
    public function addRole(string $name, string $description = ''): int|false {
        $sql = "INSERT INTO roles (name, description) VALUES (:name, :description)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute(['name' => $name, 'description' => $description])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    public function getRole(string|int $identifier): array|false {
        $field = is_numeric($identifier) ? 'id' : 'name';
        $sql = "SELECT * FROM roles WHERE $field = :identifier";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Permission Management
    public function addPermission(string $name, string $description = ''): int|false {
        $sql = "INSERT INTO permissions (name, description) VALUES (:name, :description)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute(['name' => $name, 'description' => $description])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    public function getPermission(string|int $identifier): array|false {
        $field = is_numeric($identifier) ? 'id' : 'name';
        $sql = "SELECT * FROM permissions WHERE $field = :identifier";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Assigning Permissions to Roles
    public function assignPermissionToRole(int $roleId, int $permissionId): bool {
        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)
                ON DUPLICATE KEY UPDATE role_id = :role_id"; // Prevents error if already assigned
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    // Assigning Roles to Users
    public function assignRoleToUser(int $userId, int $roleId, string $userType = 'customer'): bool {
        $table = $userType === 'staff' ? 'staff_roles' : 'customer_roles';
        $userIdField = $userType === 'staff' ? 'staff_id' : 'user_id';

        $sql = "INSERT INTO $table ($userIdField, role_id) VALUES (:user_id, :role_id)
                ON DUPLICATE KEY UPDATE $userIdField = :user_id"; // Prevents error
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }

    // Checking User Permissions
    public function userHasPermission(int $userId, string $permissionName, string $userType = 'customer'): bool {
        $roleTable = $userType === 'staff' ? 'staff_roles' : 'customer_roles';
        $userIdField = $userType === 'staff' ? 'staff_id' : 'user_id';

        $sql = "
            SELECT COUNT(*)
            FROM $roleTable ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.$userIdField = :user_id AND p.name = :permission_name;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId, 'permission_name' => $permissionName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // You can add more methods here:
    // - removeRole, removePermission
    // - revokePermissionFromRole, revokeRoleFromUser
    // - getRolesForUser, getPermissionsForRole, etc.
}
