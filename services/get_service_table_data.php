<?php
// Get service table data
require_once __DIR__ . '/../utils_files/config.php'; // Config

echo "<h2>Service Table Data</h2>";

// Check if connection exists
if (isset($conn)) {
    try {
        // Check which service table version is being used (v1 or v2)
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'services' AND column_name = 'available'
        ");
        $stmt->execute();
        $has_available = ($stmt->fetchColumn() > 0);
        $version = $has_available ? 'v2' : 'v1';
        
        echo "Service Table Version: " . $version . "<br>";
        
        // Get all services from table
        $query = "SELECT * FROM services";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Total services: " . count($services) . "<br>";
        
        if ($version === 'v2') {
            // Get unique categories and locations
            $query = "SELECT DISTINCT category FROM services ORDER BY category";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h3>Categories:</h3>";
            echo "<pre>";
            print_r($categories);
            echo "</pre>";
            
            $query = "SELECT DISTINCT location FROM services ORDER BY location";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h3>Locations:</h3>";
            echo "<pre>";
            print_r($locations);
            echo "</pre>";
        }
        
        // Display first 10 services
        echo "<h3>First 10 Services:</h3>";
        echo "<table border='1' cellpadding='5'>";
        
        // Display table headers based on first service's columns
        if (count($services) > 0) {
            echo "<tr>";
            foreach (array_keys($services[0]) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            
            // Display service data
            $count = 0;
            foreach ($services as $service) {
                echo "<tr>";
                foreach ($service as $key => $value) {
                    if ($key === 'image' && $value) {
                        echo "<td>[IMAGE DATA]</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                    }
                }
                echo "</tr>";
                
                $count++;
                if ($count >= 10) break;
            }
        }
        
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Database connection not available.";
} 