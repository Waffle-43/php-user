<?php
include "database/db.php";
use Utils\Helper;
if (isset($_POST['upload-pfp'])) {
    $filename = $_FILES['profile-picture']['name'];
    $tempname = $_FILES['profile-picture']['tmp_name'];
    $folder = "./uploads" . $filename;
    // Ensure the uploads directory exists
    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true); // Create the directory with write permissions
    }

    $query = "UPDATE users SET profile_picture='$folder' WHERE id='" . $_SESSION['user']['id'] . "'";
    if (move_uploaded_file($tempname, $folder) && mysqli_query($conn, $query)) {
        $_SESSION['success']['profile-picture'] = "Profile picture updated successfully!";
        Helper::redirect('profile');
    } else {
        $_SESSION['error']['profile-picture'] = "Failed to upload profile picture.";
        Helper::redirect('profile');
    }
}

?>