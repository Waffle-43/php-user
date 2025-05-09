<?php
require_once __DIR__ . '/utils_files/connect.php'; // Database connection

echo "<h1>Testing Stylists Query</h1>";

try {
    $query = "
        SELECT id, name, specialization, bio, profile_image, rating 
        FROM stylists 
        WHERE is_active = 1 
        ORDER BY rating DESC 
        LIMIT 4
    ";
    
    echo "<p>Executing query: " . htmlspecialchars($query) . "</p>";
    
    // Test the query directly without prepare
    if ($conn instanceof mysqli) {
        $result = $conn->query($query);
        
        if ($result === false) {
            echo "<p style='color: red;'>Direct query failed: " . $conn->error . "</p>";
        } else {
            echo "<p style='color: green;'>Direct query successful!</p>";
            
            echo "<h2>Results:</h2>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Name</th><th>Specialization</th><th>Rating</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
                echo "<td>" . htmlspecialchars($row['rating']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        // PDO implementation
        $result = $conn->query($query);
        
        echo "<p style='color: green;'>Direct query successful!</p>";
        
        echo "<h2>Results:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Specialization</th><th>Rating</th></tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
            echo "<td>" . htmlspecialchars($row['rating']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Now test with prepare
    echo "<h2>Testing with prepare():</h2>";
    
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            echo "<p style='color: red;'>Prepare failed: " . $conn->error . "</p>";
        } else {
            echo "<p style='color: green;'>Prepare successful!</p>";
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo "<p>Data retrieved!</p>";
        }
    } else {
        // PDO implementation
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            echo "<p style='color: red;'>Prepare failed</p>";
        } else {
            echo "<p style='color: green;'>Prepare successful!</p>";
            
            $stmt->execute();
            $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Data retrieved: " . count($stylists) . " records</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 