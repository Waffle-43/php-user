<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'salon_spa';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Restore Database</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; background: #f0fff0; padding: 5px; border-left: 3px solid green; }
        .error { color: red; background: #fff0f0; padding: 5px; border-left: 3px solid red; }
        .warning { color: orange; background: #fffcf0; padding: 5px; border-left: 3px solid orange; }
        h1, h2 { color: #333; }
    </style>
</head>
<body>
    <h1>Restoring Database to Original State</h1>';

// Connect to database
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>Connected to database successfully</p>";
} catch(PDOException $e) {
    die("<p class='error'>Database connection failed: " . $e->getMessage() . "</p>");
}

// Check if stylists table exists
$checkTableSql = "SHOW TABLES LIKE 'stylists'";
$checkTableStmt = $conn->query($checkTableSql);
$tableExists = $checkTableStmt->rowCount() > 0;

if (!$tableExists) {
    echo "<p class='warning'>Stylists table does not exist. No restoration needed.</p>";
} else {
    echo "<p class='success'>Found stylists table</p>";
    
    // Check profile_image column
    $checkColumnSql = "SHOW COLUMNS FROM stylists LIKE 'profile_image'";
    $checkColumnStmt = $conn->query($checkColumnSql);
    
    if ($checkColumnStmt->rowCount() > 0) {
        $columnInfo = $checkColumnStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Current profile_image column type: " . $columnInfo['Type'] . "</p>";
        
        // Check if it's LONGTEXT or BLOB
        if (strtolower($columnInfo['Type']) == 'longtext' || strstr(strtolower($columnInfo['Type']), 'blob')) {
            echo "<p class='warning'>The profile_image column is currently " . $columnInfo['Type'] . ". Reverting to VARCHAR(255)...</p>";
            
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
            echo "<p class='success'>profile_image column is already " . $columnInfo['Type'] . ". No change needed.</p>";
        }
    } else {
        echo "<p class='warning'>profile_image column does not exist.</p>";
    }
}

// Remove the problematic scripts
$filesToDelete = [
    'fix_stylist_images.php',
    'update_appointment_for_images.php',
    'direct_image_test.php',
    'display_stylist_images.php',
    'check_stylist_db.php'
];

echo "<h2>Removing problematic scripts</h2>";
echo "<ul>";
foreach ($filesToDelete as $file) {
    if (file_exists($file)) {
        if (rename($file, $file . '.bak')) {
            echo "<li class='success'>Renamed $file to $file.bak</li>";
        } else {
            echo "<li class='error'>Failed to rename $file</li>";
        }
    } else {
        echo "<li>$file not found</li>";
    }
}
echo "</ul>";

echo '<h2>Restoration Complete</h2>';
echo '<p>Your appointment booking system should now be restored to its original working state.</p>';
echo '<p><a href="appointment.php" style="background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Test Appointment Page</a></p>';
echo '</body></html>';
?> 