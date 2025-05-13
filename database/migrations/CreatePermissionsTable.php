<?php

namespace Database\Migrations;

use Core\Migration;
use Core\Database;
use PDO;

class CreatePermissionsTable extends Migration {
    public function up() {
        $pdo = Database::getInstance();
        $sql = "
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "Table 'permissions' created successfully.\n";
    }

    public function down() {
        $pdo = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS permissions;";
        $pdo->exec($sql);
        echo "Table 'permissions' dropped successfully.\n";
    }
}
