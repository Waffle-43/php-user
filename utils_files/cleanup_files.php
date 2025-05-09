<?php
// Cleanup utility for appointment and scheduling module
echo '<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Files</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        h1, h2 { color: #333; }
        .file-list { margin-bottom: 30px; }
        .file-item { 
            display: flex;
            align-items: center;
            padding: 5px;
            margin: 5px 0;
        }
        .file-item:nth-child(odd) { background-color: #f9f9f9; }
        .file-item.deleted { 
            background-color: #ffecec;
            text-decoration: line-through;
            color: #999;
        }
        .file-item input[type="checkbox"] { margin-right: 10px; }
        .file-size { color: #666; margin-left: 10px; font-size: 0.8em; }
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .button.danger {
            background-color: #f44336;
        }
        .button.secondary {
            background-color: #008CBA;
        }
        .categories { 
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .category {
            flex: 1;
            min-width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .success { color: green; padding: 5px; }
        .error { color: red; padding: 5px; }
    </style>
</head>
<body>
    <h1>Cleanup Files</h1>
    <p>This utility helps you identify and delete unnecessary files from your appointment scheduling module.</p>';

// Define files that can be safely deleted
$filesToDelete = [
    'Test and Debug Files' => [
        'appointment_debug.log' => 'Debug log file',
        'direct_image_test.php' => 'Test file for images',
        'check_stylist_db.php' => 'Database check utility',
        'fix_stylist_images.php' => 'Temporary fix script',
        'update_appointment_for_images.php' => 'Temporary script for image updates',
        'display_stylist_images.php' => 'Test display file',
        'update_stylist_images_direct.php' => 'Temporary script',
        'update_stylist_images.php' => 'Replaced by simple_stylist_images.php',
        'show_stylists.php' => 'Simple display utility',
        'fix_appointments_table.php' => 'One-time fix script',
        'check_notifications_schema.php' => 'Database check utility',
        'verify_appointments.php' => 'Validation script',
        'fix_appointments.php' => 'One-time fix script',
        'test_stylist_images.php' => 'Test utility for stylist images'
    ],
    'Backup Files' => [
        'book_appointment.php.bak' => 'Backup file',
        'appointment.php.bak' => 'Backup file',
        'restore_database.php' => 'One-time database restore script'
    ]
];

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $deletedFiles = [];
    $errors = [];

    foreach ($_POST['files'] as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $deletedFiles[] = $file;
            } else {
                $errors[] = "Failed to delete $file";
            }
        } else {
            $errors[] = "$file does not exist";
        }
    }

    if (!empty($deletedFiles)) {
        echo '<div class="success">Successfully deleted: ' . implode(', ', $deletedFiles) . '</div>';
    }
    
    if (!empty($errors)) {
        echo '<div class="error">Errors: ' . implode(', ', $errors) . '</div>';
    }
}

// Check which files exist
echo '<form method="post" action="">';
echo '<div class="categories">';

foreach ($filesToDelete as $category => $files) {
    echo '<div class="category">';
    echo "<h2>$category</h2>";
    echo '<div class="file-list">';
    
    $existingFiles = false;
    
    foreach ($files as $file => $description) {
        if (file_exists($file)) {
            $existingFiles = true;
            $fileSize = filesize($file);
            $fileSizeFormatted = $fileSize < 1024 
                ? "$fileSize B" 
                : ($fileSize < 1048576 
                    ? round($fileSize / 1024, 2) . " KB" 
                    : round($fileSize / 1048576, 2) . " MB");
            
            echo "<div class='file-item'>";
            echo "<input type='checkbox' name='files[]' value='$file' id='$file' checked>";
            echo "<label for='$file'>$file</label>";
            echo "<span class='file-size'>($fileSizeFormatted)</span>";
            echo "</div>";
            echo "<div style='margin-left: 25px; margin-bottom: 10px; color: #666;'>$description</div>";
        }
    }
    
    if (!$existingFiles) {
        echo "<p>No files found in this category.</p>";
    }
    
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '<div style="margin-top: 20px;">
    <button type="submit" name="delete" class="button danger" onclick="return confirm(\'Are you sure you want to delete the selected files? This action cannot be undone.\')">Delete Selected Files</button>
    <a href="appointment.php" class="button secondary" style="text-decoration: none;">Go to Appointment Page</a>
</div>';

echo '</form>';

echo '<h2>Files to Keep</h2>
<p>The following files are essential for your appointment scheduling system and should be kept:</p>
<ul>
    <li><strong>appointment.php</strong> - Main appointment booking page</li>
    <li><strong>book_appointment.php</strong> - Backend for processing appointments</li>
    <li><strong>appointments.php</strong> - User\'s appointment list view</li>
    <li><strong>service_utils.php</strong> - Utilities for services</li>
    <li><strong>notification_utils.php</strong> - Notification system</li>
    <li><strong>simple_stylist_images.php</strong> - Image handling solution</li>
    <li><strong>connect.php</strong> - Database connection</li>
    <li><strong>config.php</strong> - Configuration</li>
    <li><strong>All image files</strong> - Stylist and service images</li>
</ul>';

echo '</body>
</html>';
?> 