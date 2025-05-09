<?php
require_once 'connect.php';

echo "<h1>Database Tables Check</h1>";

try {
    // Use the salon_spa database
    if ($conn instanceof mysqli) {
        $conn->query("USE salon_spa");
    } else {
        $conn->exec("USE salon_spa");
    }
    
    echo "<p>Connected to database successfully</p>";
    
    // Detect which tables exist
    $tables = [];
    
    if ($conn instanceof mysqli) {
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
    } else {
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
    }
    
    echo "<h2>Tables in Database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Total tables: " . count($tables) . "</p>";
    
    // Check for the stylists table specifically
    if (!in_array('stylists', $tables)) {
        echo "<p style='color: red;'><strong>stylists table is missing!</strong></p>";
    } else {
        echo "<p style='color: green;'><strong>stylists table exists!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 