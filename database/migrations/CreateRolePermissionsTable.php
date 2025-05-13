<?php

namespace Database\Migrations;

use Core\Migration;
use Core\Database;
use PDO;

class CreateRolePermissionsTable extends Migration {
    public function up() {
        $pdo = Database::getInstance();
        // Ensure roles and permissions tables exist before creating foreign keys
        $sql = "
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "Table 'role_permissions' created successfully.\n";
    }

    public function down() {
        $pdo = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS role_permissions;";
        $pdo->exec($sql);
        echo "Table 'role_permissions' dropped successfully.\n";
    }
}
