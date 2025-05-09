<?php
include 'config.php';

try {
    // Create waitlist table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS waitlist (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id INT NOT NULL,
            service_id INT NOT NULL,
            preferred_stylist_id INT,
            service_date DATE NOT NULL,
            preferred_time_start TIME,
            preferred_time_end TIME,
            notes TEXT,
            status ENUM('pending', 'contacted', 'booked', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (service_id) REFERENCES services(id),
            FOREIGN KEY (preferred_stylist_id) REFERENCES stylists(id)
        )
    ");
    
    // Create appointment rescheduling history table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS appointment_reschedule_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            appointment_id INT NOT NULL,
            original_date DATE NOT NULL,
            original_time TIME NOT NULL,
            new_date DATE NOT NULL,
            new_time TIME NOT NULL,
            rescheduled_by ENUM('customer', 'provider') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        )
    ");
    
    echo "Waitlist and rescheduling history tables created successfully.";
    
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
} 