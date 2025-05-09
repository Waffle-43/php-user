<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'salon_spa';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Simple Stylist Images</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; padding: 5px; }
        .error { color: red; padding: 5px; }
        .warning { color: orange; padding: 5px; }
        .stylist-card { 
            border: 1px solid #ddd; 
            margin: 20px 0; 
            padding: 15px; 
            border-radius: 5px; 
            display: flex;
            align-items: center;
        }
        .stylist-image { 
            width: 100px;
            height: 100px; 
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        h1, h2 { color: #333; }
        .explanation { 
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Simple Stylist Images Solution</h1>';

// Connect to database
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>Connected to database successfully</p>";
} catch(PDOException $e) {
    die("<p class='error'>Database connection failed: " . $e->getMessage() . "</p>");
}

// Check profile_image column type
$checkColumnSql = "SHOW COLUMNS FROM stylists LIKE 'profile_image'";
$checkColumnStmt = $conn->query($checkColumnSql);

if ($checkColumnStmt->rowCount() > 0) {
    $columnInfo = $checkColumnStmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current profile_image column type: " . $columnInfo['Type'] . "</p>";
    
    // Check if it's LONGTEXT or BLOB
    if (strtolower($columnInfo['Type']) == 'longtext' || strstr(strtolower($columnInfo['Type']), 'blob')) {
        echo "<p class='warning'>The profile_image column is currently " . $columnInfo['Type'] . ". This can cause issues with large images. Reverting to VARCHAR(255)...</p>";
        
        try {
            // First clear the profile_image data
            $clearDataSql = "UPDATE stylists SET profile_image = NULL";
            $conn->exec($clearDataSql);
            echo "<p class='success'>Cleared profile_image data</p>";
            
            // Then alter the column type back to VARCHAR(255)
            $alterSql = "ALTER TABLE stylists MODIFY profile_image VARCHAR(255)";
            $conn->exec($alterSql);
            echo "<p class='success'>Changed profile_image column back to VARCHAR(255)</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error reverting profile_image column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>profile_image column is already " . $columnInfo['Type'] . ". This is the correct type for storing file paths.</p>";
    }
}

// Map stylists to their image files
$stylistImages = [
    'Jessica Parker' => '../images/jessica parker.jpg',
    'Michael Chen' => '../images/micheal chen.jpg',  // Note the typo in the image name
    'Sarah Johnson' => '../images/sarah johnson.jpg',
    'David Wilson' => '../images/david wilson.jpg'
];

// Get all stylists from database
$stmt = $conn->query("SELECT id, name, specialization, profile_image FROM stylists");
$stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<div class='explanation'>
    <h3>Why This Solution Works</h3>
    <p>Instead of storing binary image data directly in the database (which causes the 'packet too big' errors), 
    we store the file path to the image. This is what the database was originally designed for.</p>
    <p>The errors you were seeing ('MySQL server has gone away' and 'Got a packet bigger than max_allowed_packet') 
    happen because large binary data exceeds MySQL's limits. This approach solves that problem.</p>
</div>";

echo "<h2>Update Stylist Images</h2>";

foreach ($stylists as $stylist) {
    $stylistId = $stylist['id'];
    $stylistName = $stylist['name'];
    $specialization = $stylist['specialization'];
    
    echo "<div class='stylist-card'>";
    
    // Find matching image file
    $imageFile = isset($stylistImages[$stylistName]) ? $stylistImages[$stylistName] : null;
    
    if ($imageFile && file_exists($imageFile)) {
        // Display the image directly
        echo "<img src='$imageFile' alt='$stylistName' class='stylist-image'>";
        
        // Update database with the image file path
        $updateSql = "UPDATE stylists SET profile_image = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$imageFile, $stylistId]);
        
        echo "<div>
            <h3>$stylistName ($specialization)</h3>
            <p class='success'>Updated profile image to use file: $imageFile</p>
        </div>";
    } else {
        echo "<div style='width: 100px; height: 100px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;'>
            <span>No image</span>
        </div>";
        echo "<div>
            <h3>$stylistName ($specialization)</h3>
            <p class='error'>No matching image file found for: $stylistName</p>";
        
        // Check if the file exists in the current directory
        echo "<p>Checking for image files:</p><ul>";
        foreach (glob("*.jpg") as $filename) {
            echo "<li>Found: $filename " . (stripos($filename, strtolower(str_replace(' ', '', $stylistName))) !== false ? "(possible match)" : "") . "</li>";
        }
        echo "</ul></div>";
    }
    
    echo "</div>";
}

echo "<h2>How It Works</h2>";
echo "<p>This approach stores file paths in the database instead of binary data. This is compatible with your existing code and simpler to implement.</p>";
echo "<ol>
    <li>In book_appointment.php, when retrieving stylists, the profile_image field will contain the file path</li>
    <li>In the appointment.php page, you can display the image using: &lt;img src='[profile_image]'&gt;</li>
    <li>This avoids the MySQL packet size limitations</li>
</ol>";

echo "<p><a href='appointments' style='background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 20px;'>Test Appointment Page</a></p>";
echo '</body></html>';
?> 