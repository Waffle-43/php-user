<?php
/**
 * Integration Setup Script
 * 
 * This script ensures all necessary tables and seed data are available
 * for the integrated homepage to function properly.
 */

require_once __DIR__ . '/../utils_files/connect.php';

echo "<h1>Setting Up Integration Environment</h1>";

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
    
    echo "<p>Detected " . count($tables) . " existing tables</p>";
    
    // Create customers table if it doesn't exist
    if (!in_array('customers', $tables)) {
        $sql = "CREATE TABLE customers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            profile_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn instanceof mysqli) {
            $conn->query($sql);
        } else {
            $conn->exec($sql);
        }
        
        echo "<p>Created customers table</p>";
        
        // Insert sample customers
        $sql = "INSERT INTO customers (name, email, phone, profile_image) VALUES
            ('Jessica Thompson', 'jessica@example.com', '123-456-7890', 'https://randomuser.me/api/portraits/women/12.jpg'),
            ('Michael Rodriguez', 'michael@example.com', '234-567-8901', 'https://randomuser.me/api/portraits/men/45.jpg'),
            ('Amanda Smith', 'amanda@example.com', '345-678-9012', 'https://randomuser.me/api/portraits/women/28.jpg')";
        
        if ($conn instanceof mysqli) {
            $conn->query($sql);
        } else {
            $conn->exec($sql);
        }
        
        echo "<p>Added sample customers</p>";
    }
    
    // Create stylists table if it doesn't exist
    if (!in_array('stylists', $tables)) {
        $sql = "CREATE TABLE stylists (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            specialization VARCHAR(100),
            bio TEXT,
            profile_image VARCHAR(255),
            rating DECIMAL(3,1) DEFAULT 5.0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn instanceof mysqli) {
            $conn->query($sql);
        } else {
            $conn->exec($sql);
        }
        
        echo "<p>Created stylists table</p>";
        
        // Insert sample stylists
        $sql = "INSERT INTO stylists (name, specialization, bio, profile_image, rating) VALUES
            ('Emma Wilson', 'Hair Specialist', 'With 8 years of experience in hair styling and coloring, Emma brings the latest trends from Paris and New York to our salon.', 'https://randomuser.me/api/portraits/women/32.jpg', 4.9),
            ('Sophia Chen', 'Esthetician', 'Certified in advanced skincare techniques, Sophia customizes treatments based on your unique skin needs for optimal results.', 'https://randomuser.me/api/portraits/women/44.jpg', 4.8),
            ('David Rodriguez', 'Barber', 'David combines traditional barbering skills with modern styling techniques to give you the perfect cut and shave.', 'https://randomuser.me/api/portraits/men/32.jpg', 4.9),
            ('Mia Johnson', 'Nail Technician', 'Specializing in nail art and gel applications, Mia creates stunning designs that last.', 'https://randomuser.me/api/portraits/women/68.jpg', 4.7)";
        
        if ($conn instanceof mysqli) {
            $conn->query($sql);
        } else {
            $conn->exec($sql);
        }
        
        echo "<p>Added sample stylists</p>";
    }
    
    // Create reviews table if it doesn't exist
    if (!in_array('reviews', $tables)) {
        $sql = "CREATE TABLE reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id INT,
            service_id INT,
            stylist_id INT,
            rating INT NOT NULL,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (service_id) REFERENCES services(id),
            FOREIGN KEY (stylist_id) REFERENCES stylists(id)
        )";
        
        if ($conn instanceof mysqli) {
            $conn->query($sql);
        } else {
            $conn->exec($sql);
        }
        
        echo "<p>Created reviews table</p>";
        
        // Add sample reviews if there are services and stylists
        if (in_array('services', $tables) && in_array('stylists', $tables)) {
            // Get service IDs
            if ($conn instanceof mysqli) {
                $serviceResult = $conn->query("SELECT id FROM services LIMIT 3");
                $serviceIds = [];
                while ($row = $serviceResult->fetch_assoc()) {
                    $serviceIds[] = $row['id'];
                }
                
                $stylistResult = $conn->query("SELECT id FROM stylists LIMIT 3");
                $stylistIds = [];
                while ($row = $stylistResult->fetch_assoc()) {
                    $stylistIds[] = $row['id'];
                }
            } else {
                $serviceResult = $conn->query("SELECT id FROM services LIMIT 3");
                $serviceIds = $serviceResult->fetchAll(PDO::FETCH_COLUMN);
                
                $stylistResult = $conn->query("SELECT id FROM stylists LIMIT 3");
                $stylistIds = $stylistResult->fetchAll(PDO::FETCH_COLUMN);
            }
            
            if (!empty($serviceIds) && !empty($stylistIds)) {
                $reviews = [
                    [
                        'customer_id' => 1,
                        'service_id' => $serviceIds[0] ?? 1,
                        'stylist_id' => $stylistIds[0] ?? 1,
                        'rating' => 5,
                        'comment' => "Emma transformed my hair! I've never received so many compliments. The online booking was so convenient and the salon atmosphere was relaxing.",
                        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                    ],
                    [
                        'customer_id' => 2,
                        'service_id' => $serviceIds[1] ?? 2,
                        'stylist_id' => $stylistIds[1] ?? 2,
                        'rating' => 5,
                        'comment' => "David gives the best haircut I've ever had. The online system made it easy to book exactly when I wanted. Will definitely be coming back regularly!",
                        'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
                    ],
                    [
                        'customer_id' => 3,
                        'service_id' => $serviceIds[2] ?? 3,
                        'stylist_id' => $stylistIds[2] ?? 3,
                        'rating' => 5,
                        'comment' => "Sophia's facial treatments have completely transformed my skin. The ability to book online and see her availability in real-time is a game changer.",
                        'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks'))
                    ]
                ];
                
                foreach ($reviews as $review) {
                    if ($conn instanceof mysqli) {
                        $stmt = $conn->prepare("INSERT INTO reviews (customer_id, service_id, stylist_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iiiiss", $review['customer_id'], $review['service_id'], $review['stylist_id'], $review['rating'], $review['comment'], $review['created_at']);
                        $stmt->execute();
                    } else {
                        $sql = "INSERT INTO reviews (customer_id, service_id, stylist_id, rating, comment, created_at) VALUES (:customer_id, :service_id, :stylist_id, :rating, :comment, :created_at)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($review);
                    }
                }
                
                echo "<p>Added sample reviews</p>";
            }
        }
    }
    
    // Ensure services and stylists tables have proper columns for integration
    if (in_array('services', $tables)) {
        // Check if category column exists in services table
        $hasCategoryColumn = false;
        
        if ($conn instanceof mysqli) {
            $result = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
            $hasCategoryColumn = $result->num_rows > 0;
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'services' AND column_name = 'category'");
            $stmt->execute();
            $hasCategoryColumn = $stmt->fetchColumn() > 0;
        }
        
        if (!$hasCategoryColumn) {
            if ($conn instanceof mysqli) {
                $conn->query("ALTER TABLE services ADD COLUMN category VARCHAR(50) DEFAULT 'General'");
            } else {
                $conn->exec("ALTER TABLE services ADD COLUMN category VARCHAR(50) DEFAULT 'General'");
            }
            
            echo "<p>Added category column to services table</p>";
            
            // Update services with categories
            $categories = ['Hair', 'Facial', 'Nail', 'Massage', 'Makeup'];
            
            foreach ($categories as $index => $category) {
                $limit = ($index + 1) * 2; // Distribute services among categories
                
                if ($conn instanceof mysqli) {
                    $sql = "UPDATE services SET category = '{$category}' WHERE id <= {$limit} AND id > " . ($index * 2);
                    $conn->query($sql);
                } else {
                    $sql = "UPDATE services SET category = :category WHERE id <= :upper AND id > :lower";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':category' => $category,
                        ':upper' => $limit,
                        ':lower' => $index * 2
                    ]);
                }
            }
            
            echo "<p>Updated services with categories</p>";
        }
    }
    
    // Success message
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; color: #155724; border-radius: 4px;'>";
    echo "<h2>Integration Setup Complete</h2>";
    echo "<p>Your database has been prepared for the integrated homepage.</p>";
    echo "<p><a href='integrated_homepage.php' style='color: #155724; font-weight: bold;'>Go to Integrated Homepage</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
