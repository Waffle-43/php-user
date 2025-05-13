<?php

namespace Database\Migrations;

use Core\Migration;
use Core\Database;
use PDO;

class CreateStaffRolesTable extends Migration {
    public function up() {
        $pdo = Database::getInstance();
        // Assumes 'staff' table with 'id' primary key exists
        $sql = "
            CREATE TABLE IF NOT EXISTS staff_roles (
                staff_id INT NOT NULL, 
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (staff_id, role_id),
                FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE, 
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "Table 'staff_roles' created successfully.\n";
    }

    public function down() {
        $pdo = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS staff_roles;";
        $pdo->exec($sql);
        echo "Table 'staff_roles' dropped successfully.\n";
    }
}
