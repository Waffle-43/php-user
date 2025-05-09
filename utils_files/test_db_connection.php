<?php
// Simple db connection test
require_once 'config.php';

echo "<h2>Testing Database Connection</h2>";

// Check if $conn is set
if (isset($conn)) {
    echo "Connection established!<br>";
    
    // Check service table
    try {
        $query = "SHOW TABLES LIKE 'services'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            echo "Services table exists!<br>";
            
            // Show service table structure
            $query = "DESCRIBE services";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Service Table Structure:</h3>";
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
            
            // Show some sample service data
            $query = "SELECT * FROM services LIMIT 5";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Service Data:</h3>";
            echo "<pre>";
            print_r($services);
            echo "</pre>";
            
        } else {
            echo "Services table does not exist!<br>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Failed to connect to database!";
} 