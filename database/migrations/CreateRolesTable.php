<?php

namespace Database\Migrations;

use Core\Migration;
use Core\Database;
use PDO;

class CreateRolesTable extends Migration {
    public function up() {
        $pdo = Database::getInstance();
        $sql = "
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "Table 'roles' created successfully.\n";
    }

    public function down() {
        $pdo = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS roles;";
        $pdo->exec($sql);
        echo "Table 'roles' dropped successfully.\n";
    }
}
