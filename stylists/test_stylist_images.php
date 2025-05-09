<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'salon_spa';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Test Stylist Images</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; padding: 5px; }
        .error { color: red; padding: 5px; }
        .warning { color: orange; padding: 5px; }
        .info { color: blue; padding: 5px; }
        .stylist-card { 
            border: 1px solid #ddd; 
            margin: 20px 0; 
            padding: 15px; 
            border-radius: 5px; 
        }
        .stylist-image { 
            width: 100px;
            height: 100px; 
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            float: left;
        }
        h1, h2 { color: #333; }
        .explanation { 
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 10px;
            margin: 20px 0;
        }
        .actions {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>Test Stylist Images</h1>';

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
    echo "<p class='info'>Current profile_image column type: <strong>" . $columnInfo['Type'] . "</strong></p>";
    
    if (strtolower($columnInfo['Type']) != 'varchar(255)') {
        echo "<p class='warning'>For best results, the profile_image column should be VARCHAR(255). Run simple_stylist_images.php to fix this.</p>";
    } else {
        echo "<p class='success'>Profile image column is correctly set to VARCHAR(255).</p>";
    }
}

// Get all stylists from database
$stmt = $conn->query("SELECT id, name, specialization, profile_image FROM stylists");
$stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Stylist Images Test</h2>";
echo "<p>This page shows how the stylist images will appear in the appointment page.</p>";

if (empty($stylists)) {
    echo "<p class='error'>No stylists found in database!</p>";
} else {
    echo "<p class='info'>Found " . count($stylists) . " stylists.</p>";
    
    echo "<h3>Available Image Files</h3>";
    echo "<ul>";
    $imageFiles = glob("*.jpg");
    foreach ($imageFiles as $file) {
        echo "<li>" . htmlspecialchars($file) . " (" . number_format(filesize($file)) . " bytes)</li>";
    }
    echo "</ul>";
    
    echo "<h3>Stylist Data</h3>";
    
    foreach ($stylists as $stylist) {
        $stylistId = $stylist['id'];
        $stylistName = $stylist['name'];
        $specialization = $stylist['specialization'];
        $profileImage = $stylist['profile_image'];
        
        echo "<div class='stylist-card'>";
        
        // Display how it would appear in the appointment.php file
        echo "<h3>Stylist ID: $stylistId - $stylistName ($specialization)</h3>";
        
        if (!empty($profileImage)) {
            // Determine if it's a file path or base64 data
            if (strpos($profileImage, '.jpg') !== false || 
                strpos($profileImage, '.png') !== false || 
                strpos($profileImage, '.jpeg') !== false || 
                strpos($profileImage, '.gif') !== false) {
                
                echo "<p class='info'>Image stored as file path: " . htmlspecialchars($profileImage) . "</p>";
                
                if (file_exists($profileImage)) {
                    echo "<img src='" . htmlspecialchars($profileImage) . "' alt='$stylistName' class='stylist-image'>";
                    echo "<p class='success'>Image file exists and is displayed correctly.</p>";
                } else {
                    echo "<p class='error'>Image file does not exist on the server! Path: " . htmlspecialchars($profileImage) . "</p>";
                }
            } else if (strpos($profileImage, 'data:image') === 0) {
                echo "<p class='info'>Image stored as data URL (base64)</p>";
                echo "<img src='" . htmlspecialchars($profileImage) . "' alt='$stylistName' class='stylist-image'>";
            } else {
                // Binary data - this shouldn't happen with a VARCHAR column
                echo "<p class='warning'>Image appears to be stored as binary data. This may cause issues.</p>";
                echo "<img src='data:image/jpeg;base64," . base64_encode($profileImage) . "' alt='$stylistName' class='stylist-image'>";
            }
        } else {
            echo "<p class='error'>No profile image set for this stylist.</p>";
        }
        
        echo "<div style='clear:both'></div>";
        echo "</div>";
    }
}

// Show how the image will render in JavaScript (appointment.php)
echo "<h2>JavaScript Rendering Test</h2>";
echo "<p>This simulates how the JavaScript in appointment.php will render the stylist images:</p>";

echo "<div id='js-test'></div>";

echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const stylists = " . json_encode($stylists) . ";
    const testDiv = document.getElementById('js-test');
    
    stylists.forEach(stylist => {
        const card = document.createElement('div');
        card.className = 'stylist-card';
        
        let html = '<h3>' + stylist.name + ' (' + stylist.specialization + ')</h3>';
        
        html += `<div class='flex items-center mb-4'>`;
        if (stylist.profile_image) {
            html += `<img src='${stylist.profile_image}' alt='${stylist.name}' class='stylist-image'>`;
        } else {
            html += `<div style='width: 100px; height: 100px; border-radius: 50%; background-color: #eee; display: flex; align-items: center; justify-content: center;'>
                <span>No image</span>
            </div>`;
        }
        html += `</div>`;
        
        card.innerHTML = html;
        testDiv.appendChild(card);
    });
});
</script>";

echo "<div class='actions'>
    <h3>Next Steps</h3>
    <a href='simple_stylist_images.php' class='btn'>Run Simple Stylist Images Fix</a>
    <a href='appointment.php' class='btn'>Test Appointment Page</a>
</div>";

echo "</body></html>";
?>
