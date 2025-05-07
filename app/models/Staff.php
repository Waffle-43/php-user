<?php

namespace App\Models;

use Core\Database;
use Core\BaseModel;
use PDO;

class Staff
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function createStaff(string $name, string $username, string $email, string $password, string $status = 'pending', string $role = 'staff')
    {
        $query = "INSERT INTO staff (name, username, email, password, status, role, created_at) 
                  VALUES (:name, :username, :email, :password, :status, :role, NOW())";
        
        $params = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'status' => $status,
            'role' => $role
        ];
        
        return $this->db->query($query, $params);
    }

    public function findByEmail(string $email)
    {
        $query = "SELECT s.*, r.permissions 
                 FROM staff s
                 LEFT JOIN roles r ON s.role = r.name
                 WHERE s.email = :email LIMIT 1";
        $result = $this->db->query($query, ['email' => $email]);
        
        return $result ? $result[0] : null;
    }

    public function findById(int $id)
    {
        $query = "SELECT s.*, r.permissions 
                 FROM staff s
                 LEFT JOIN roles r ON s.role = r.name
                 WHERE s.id = :id LIMIT 1";
        $result = $this->db->query($query, ['id' => $id]);
        
        return $result ? $result[0] : null;
    }

    public function emailExists(string $email): bool
    {
        $query = "SELECT COUNT(*) as count FROM staff WHERE email = :email";
        $result = $this->db->query($query, ['email' => $email]);
        
        return $result[0]->count > 0;
    }

    public function updateStatus(int $id, string $status)
    {
        $query = "UPDATE staff SET status = :status, updated_at = NOW() WHERE id = :id";
        return $this->db->query($query, ['id' => $id, 'status' => $status]);
    }
    
    public function updatePassword(int $id, string $password)
    {
        $query = "UPDATE staff SET password = :password, updated_at = NOW() WHERE id = :id";
        return $this->db->query($query, ['id' => $id, 'password' => $password]);
    }
    
    public function getAllPending()
    {
        $query = "SELECT id, name, username, email, created_at FROM staff WHERE status = 'pending' ORDER BY created_at DESC";
        return $this->db->query($query);
    }
    
    public function getAllStaff()
    {
        $query = "SELECT id, name, username, email, status, role, created_at, updated_at FROM staff ORDER BY name ASC";
        return $this->db->query($query);
    }
    
    // New methods to add:
    
    public function getStaffPermissions(int $id)
    {
        $query = "SELECT r.permissions 
                 FROM staff s
                 JOIN roles r ON s.role = r.name
                 WHERE s.id = :id";
        $result = $this->db->query($query, ['id' => $id]);
        
        return $result ? $result[0]->permissions : null;
    }
    
    public function updateRole(int $id, string $role)
    {
        $query = "UPDATE staff SET role = :role, updated_at = NOW() WHERE id = :id";
        return $this->db->query($query, ['id' => $id, 'role' => $role]);
    }
    
    public function getAvailableRoles()
    {
        $query = "SELECT name, permissions, description FROM roles ORDER BY name";
        return $this->db->query($query);
    }
    
    public function assignPermissions(int $id, array $permissions)
    {
        // Convert permissions array to JSON string for storage
        $permissionsJson = json_encode($permissions);
        
        $query = "UPDATE staff SET custom_permissions = :permissions, updated_at = NOW() WHERE id = :id";
        return $this->db->query($query, ['id' => $id, 'permissions' => $permissionsJson]);
    }
    
    public function updateStaffProfile(int $id, array $data)
    {
        $allowedFields = ['name', 'username', 'email'];
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updateStr = implode(', ', $updates);
        $query = "UPDATE staff SET $updateStr, updated_at = NOW() WHERE id = :id";
        
        return $this->db->query($query, $params);
    }
}