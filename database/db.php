<?php
// filepath: c:\xampp\htdocs\php-user\db.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "salon_spa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>