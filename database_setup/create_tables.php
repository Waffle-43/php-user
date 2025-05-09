<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Database Tables</h2>";

// Database configuration
$db_host = 'localhost';
$db_name = 'salon_spa';
$db_user = 'root';
$db_pass = '';

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "<p style='color:green'>Database '$db_name' created or already exists!</p>";
    
    // Use the database
    $conn->exec("USE $db_name");
    
    // Create services table
    $conn->exec("CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green'>Services table created!</p>";
    
    // Create stylists table
    $conn->exec("CREATE TABLE IF NOT EXISTS stylists (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100),
        bio TEXT,
        profile_image VARCHAR(255),
        rating DECIMAL(3,2),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green'>Stylists table created!</p>";
    
    // Create appointments table
    $conn->exec("DROP TABLE IF EXISTS appointments");
    $conn->exec("CREATE TABLE appointments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        service_id INT NOT NULL,
        stylist_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        duration INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        notes TEXT,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id),
        FOREIGN KEY (stylist_id) REFERENCES stylists(id)
    )");
    echo "<p style='color:green'>Appointments table created!</p>";
    
    // Create notifications table
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        related_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green'>Notifications table created!</p>";
    
    // Check if services has any data
    $stmt = $conn->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        // Insert sample services
        $conn->exec("INSERT INTO services (name, description, price, duration) VALUES
            ('Haircut & Styling', 'Professional haircut and styling service', 65.00, 60),
            ('Hair Coloring', 'Full hair coloring service with consultation', 120.00, 120),
            ('Manicure', 'Basic manicure service', 35.00, 45),
            ('Pedicure', 'Basic pedicure service', 45.00, 60),
            ('Facial', 'Deep cleansing facial treatment', 80.00, 90)
        ");
        echo "<p style='color:green'>Sample services added!</p>";
    }
    
    // Check if stylists has any data
    $stmt = $conn->query("SELECT COUNT(*) FROM stylists");
    if ($stmt->fetchColumn() == 0) {
        // Insert sample stylists
        $conn->exec("INSERT INTO stylists (name, specialization, bio, rating) VALUES
            ('Jessica Parker', 'Hair Styling', 'Expert in modern haircuts and coloring techniques', 4.8),
            ('Michael Chen', 'Nail Care', 'Specialized in nail art and treatments', 4.9),
            ('Sarah Johnson', 'Facial Treatments', 'Certified esthetician with 5 years of experience', 4.7),
            ('David Wilson', 'Hair Coloring', 'Master colorist specializing in balayage and highlights', 4.9)
        ");
        echo "<p style='color:green'>Sample stylists added!</p>";
    }
    
    echo "<p style='color:green'><strong>All tables and sample data created successfully!</strong></p>";
    echo "<p><a href='appointment_ui.php' style='display: inline-block; background-color: #ec4899; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Appointment UI</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 