<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'salon_spa';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all services with promotions
$result = $conn->query("SELECT id, price, promotion FROM services WHERE promotion > 0");

if ($result->num_rows > 0) {
    echo "<h2>Updating discounted prices for services:</h2>";
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $price = (float)$row['price'];
        $promotion = (int)$row['promotion'];
        
        // Calculate correctly discounted price
        $price_after_discount = $price * (1 - ($promotion / 100));
        
        // Update the database
        $stmt = $conn->prepare("UPDATE services SET price_after_discount = ? WHERE id = ?");
        $stmt->bind_param("di", $price_after_discount, $id);
        
        if ($stmt->execute()) {
            echo "<p>Service ID {$id}: Price RM{$price}, Promotion {$promotion}%, New discounted price: RM" . number_format($price_after_discount, 2) . "</p>";
        } else {
            echo "<p>Error updating Service ID {$id}: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    }
    
    echo "<p>All discounted prices have been updated!</p>";
} else {
    echo "<p>No services with promotions found.</p>";
}

echo "<p><a href='Module2-20250505T195816Z-1-001/Module2/editService.php'>Return to Services</a></p>";

$conn->close();
?> 