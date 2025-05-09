<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../app/models/Staff.php';
require_once __DIR__ . '/../core/Database.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the script is accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Access database directly
        $pdo = null;
        
        // Create a direct database connection to bypass the private constructor issue
        try {
            // You might need to adjust these connection details to match your configuration
            $host = 'localhost';
            $dbname = 'salon_spa';  // Change to your database name
            $username = 'root';       // Change if different
            $password = '';           // Add password if set
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Admin account details
            $adminData = [
                'name' => 'Alex Admin',
                'username' => 'alexadmin',
                'email' => 'alex@gmail.com',
                'password' => password_hash('alex123', PASSWORD_DEFAULT),
                'status' => 'approved',
                'role' => 'staff',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert admin directly using PDO
            $sql = "INSERT INTO staff (name, username, email, password, status, role, created_at) 
                    VALUES (:name, :username, :email, :password, :status, :role, :created_at)";
                    
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($adminData);
            
            if ($result) {
                echo '<div style="color: green; font-weight: bold;">Admin account created successfully!</div>';
                echo '<p>Username: admin</p>';
                echo '<p>Password: admin123</p>';
                echo '<p>IMPORTANT: Delete this file immediately for security reasons!</p>';
                
                // Let's also check if the roles table exists and create the admin role if needed
                $checkRolesTable = $pdo->query("SHOW TABLES LIKE 'roles'");
                if ($checkRolesTable->rowCount() > 0) {
                    // Check if admin role exists
                    $checkAdminRole = $pdo->query("SELECT * FROM roles WHERE name = 'admin'");
                    if ($checkAdminRole->rowCount() == 0) {
                        // Create admin role
                        $permissions = json_encode([
                            "PERMISSION_MANAGE_APPOINTMENTS",
                            "PERMISSION_MANAGE_SERVICES",
                            "PERMISSION_MANAGE_USERS",
                            "PERMISSION_MANAGE_STAFF",
                            "PERMISSION_VIEW_REPORTS",
                            "PERMISSION_MANAGE_SETTINGS"
                        ]);
                        
                        $roleStmt = $pdo->prepare("INSERT INTO roles (name, permissions, description) 
                                                  VALUES ('admin', :permissions, 'Administrator with full access')");
                        $roleStmt->execute(['permissions' => $permissions]);
                        
                        echo '<div style="color: green; font-weight: bold;">Admin role also created successfully!</div>';
                    } else {
                        echo '<div style="color: blue;">Admin role already exists.</div>';
                    }
                } else {
                    echo '<div style="color: orange;">Roles table not found. You may need to create it manually.</div>';
                    
                    // Suggest SQL to create roles table
                    echo '<div style="background-color: #f5f5f5; padding: 10px; border-radius: 5px; margin-top: 10px;">
                        <p>SQL to create roles table:</p>
                        <pre>
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `permissions` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
);

INSERT INTO `roles` (`name`, `permissions`, `description`) VALUES
(\'admin\', \'["PERMISSION_MANAGE_APPOINTMENTS","PERMISSION_MANAGE_SERVICES","PERMISSION_MANAGE_USERS","PERMISSION_MANAGE_STAFF","PERMISSION_VIEW_REPORTS","PERMISSION_MANAGE_SETTINGS"]\', \'Administrator with full access\');
                        </pre>
                    </div>';
                }
            } else {
                echo '<div style="color: red; font-weight: bold;">Failed to create admin account.</div>';
            }
            
        } catch (PDOException $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        echo '<div style="color: red; font-weight: bold;">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1 {
            color: #333;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0069d9;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Admin Account Setup</h1>
    
    <div class="warning">
        <strong>Warning:</strong> This script creates an administrator account for your system.
        For security reasons, delete this file immediately after use!
    </div>
    
    <p>Click the button below to create an admin account with the following credentials:</p>
    <ul>
        <li><strong>Username:</strong> admin</li>
        <li><strong>Password:</strong> admin123</li>
        <li><strong>Email:</strong> mrv@gmail.com</li>
        <li><strong>Role:</strong> admin</li>
    </ul>
    
    <form method="post">
        <button type="submit">Create Admin Account</button>
    </form>
</body>
</html>