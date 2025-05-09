<?php
require_once __DIR__ . '/utils_files/connect.php'; // Database connection

echo "<h1>Stylists Table Structure</h1>";

try {
    // Get column information for the stylists table
    if ($conn instanceof mysqli) {
        $result = $conn->query("DESCRIBE stylists");
        
        echo "<h2>Columns in stylists table:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        // PDO implementation
        $result = $conn->query("DESCRIBE stylists");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Columns in stylists table:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Check for the is_active column specifically
    $hasIsActiveColumn = false;
    
    if ($conn instanceof mysqli) {
        $result = $conn->query("SHOW COLUMNS FROM stylists LIKE 'is_active'");
        $hasIsActiveColumn = $result->num_rows > 0;
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'stylists' AND column_name = 'is_active'");
        $stmt->execute();
        $hasIsActiveColumn = $stmt->fetchColumn() > 0;
    }
    
    if (!$hasIsActiveColumn) {
        echo "<p style='color: red;'><strong>is_active column is missing from the stylists table!</strong></p>";
        
        // Add the column
        echo "<p>Adding is_active column to stylists table...</p>";
        
        if ($conn instanceof mysqli) {
            $conn->query("ALTER TABLE stylists ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        } else {
            $conn->exec("ALTER TABLE stylists ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        }
        
        echo "<p style='color: green;'><strong>is_active column added successfully!</strong></p>";
    } else {
        echo "<p style='color: green;'><strong>is_active column exists in the stylists table!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 