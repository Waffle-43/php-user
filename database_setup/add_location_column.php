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

// Check if location column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'location'");
if ($result->num_rows == 0) {
    // Add location column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN location VARCHAR(255) AFTER duration")) {
        echo "Location column added successfully!";
    } else {
        echo "Error adding location column: " . $conn->error;
    }
} else {
    echo "Location column already exists.";
}

// Check if category column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
if ($result->num_rows == 0) {
    // Add category column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN category VARCHAR(255) AFTER location")) {
        echo "<br>Category column added successfully!";
    } else {
        echo "<br>Error adding category column: " . $conn->error;
    }
} else {
    echo "<br>Category column already exists.";
}

// Check if available column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'available'");
if ($result->num_rows == 0) {
    // Add available column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN available TINYINT(1) DEFAULT 1 AFTER category")) {
        echo "<br>Available column added successfully!";
    } else {
        echo "<br>Error adding available column: " . $conn->error;
    }
} else {
    echo "<br>Available column already exists.";
}

// Check if image column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'image'");
if ($result->num_rows == 0) {
    // Add image column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN image BLOB AFTER available")) {
        echo "<br>Image column added successfully!";
    } else {
        echo "<br>Error adding image column: " . $conn->error;
    }
} else {
    echo "<br>Image column already exists.";
}

// Check if promotion column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'promotion'");
if ($result->num_rows == 0) {
    // Add promotion column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN promotion INT DEFAULT 0 AFTER image")) {
        echo "<br>Promotion column added successfully!";
    } else {
        echo "<br>Error adding promotion column: " . $conn->error;
    }
} else {
    echo "<br>Promotion column already exists.";
}

// Check if price_after_discount column exists
$result = $conn->query("SHOW COLUMNS FROM services LIKE 'price_after_discount'");
if ($result->num_rows == 0) {
    // Add price_after_discount column if it doesn't exist
    if ($conn->query("ALTER TABLE services ADD COLUMN price_after_discount DECIMAL(10,2) AFTER promotion")) {
        echo "<br>Price after discount column added successfully!";
    } else {
        echo "<br>Error adding price after discount column: " . $conn->error;
    }
} else {
    echo "<br>Price after discount column already exists.";
}

$conn->close();
echo "<br><br>Database update completed. <a href='Module2-20250505T195816Z-1-001/Module2/addService.php'>Go back to Add Service</a>";
?> 