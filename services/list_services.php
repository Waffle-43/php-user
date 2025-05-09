<?php
// List all services in database
require_once __DIR__ . '/../utils_files/config.php'; // Config

if (isset($conn)) {
    echo "<h1>All Services</h1>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Duration</th><th>Available</th></tr>";
    
    $stmt = $conn->query('SELECT * FROM services');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>RM " . number_format($row['price'], 2) . "</td>";
        echo "<td>" . $row['duration'] . " min</td>";
        echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Database connection not available.";
} 