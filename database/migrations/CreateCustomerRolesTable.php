<?php

namespace Database\Migrations;

use Core\Migration;
use Core\Database;
use PDO;

class CreateCustomerRolesTable extends Migration {
    public function up() {
        $pdo = Database::getInstance();
        // Assumes 'users' table with 'id' primary key exists for customers
        $sql = "
            CREATE TABLE IF NOT EXISTS customer_roles (
                user_id INT NOT NULL, 
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, 
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "Table 'customer_roles' created successfully.\n";
    }

    public function down() {
        $pdo = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS customer_roles;";
        $pdo->exec($sql);
        echo "Table 'customer_roles' dropped successfully.\n";
    }
}
