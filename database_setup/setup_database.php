<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

try {
    // Create connection without database
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS salon_spa";
    $conn->exec($sql);
    echo "Database created successfully<br>";
    
    // Select the database
    $conn->exec("USE salon_spa");
    
    // Create services table
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Services table created successfully<br>";
    
    // Create stylists table
    $sql = "CREATE TABLE IF NOT EXISTS stylists (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100),
        bio TEXT,
        profile_image VARCHAR(255),
        rating DECIMAL(3,2),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Stylists table created successfully<br>";
    
    // Create appointments table
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
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
    )";
    $conn->exec($sql);
    echo "Appointments table created successfully<br>";
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        related_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Notifications table created successfully<br>";
    
    // Insert sample services if they don't exist
    $stmt = $conn->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO services (name, description, price, duration) VALUES
            ('Haircut & Styling', 'Professional haircut and styling service', 65.00, 60),
            ('Hair Coloring', 'Full hair coloring service with consultation', 120.00, 120),
            ('Manicure', 'Basic manicure service', 35.00, 45),
            ('Pedicure', 'Basic pedicure service', 45.00, 60),
            ('Facial', 'Deep cleansing facial treatment', 80.00, 90)";
        $conn->exec($sql);
        echo "Sample services inserted successfully<br>";
    }
    
    // Insert sample stylists if they don't exist
    $stmt = $conn->query("SELECT COUNT(*) FROM stylists");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO stylists (name, specialization, bio, rating) VALUES
            ('Jessica Parker', 'Hair Styling', 'Expert in modern haircuts and coloring techniques', 4.8),
            ('Michael Chen', 'Nail Care', 'Specialized in nail art and treatments', 4.9),
            ('Sarah Johnson', 'Facial Treatments', 'Certified esthetician with 5 years of experience', 4.7),
            ('David Wilson', 'Hair Coloring', 'Master colorist specializing in balayage and highlights', 4.9)";
        $conn->exec($sql);
        echo "Sample stylists inserted successfully<br>";
    }
    
    echo "Database setup completed successfully!";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> 