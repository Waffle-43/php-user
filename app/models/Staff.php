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
        $query = "SELECT * FROM staff WHERE email = :email LIMIT 1";
        $result = $this->db->query($query, ['email' => $email]);
        
        return $result ? $result[0] : null;
    }

    public function findById(int $id)
    {
        $query = "SELECT * FROM staff WHERE id = :id LIMIT 1";
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
}