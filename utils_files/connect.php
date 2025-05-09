<?php
// Database connection details
$host = 'localhost';  // Database host
$user = 'root';       // Database username
$pass = '';           // Database password
$dbname = 'salon_spa';// Database name

// Create a connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check if connection was successful
if ($conn->connect_error) {
    // If connection fails, display the error message
    die('Connection failed: ' . $conn->connect_error);
}

// Uncomment the following line if you'd like to output a success message when connected
// echo "Connected successfully to the database.";

// Always return the connection for use in other files
return $conn;
?>