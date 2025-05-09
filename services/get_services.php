<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep errors off to prevent HTML in JSON response

// Set header before any output
header('Content-Type: application/json');

try {
    // Try to include config.php (PDO connection)
    if (file_exists('config.php')) {
        include_once 'config.php';
    } else {
        // Fallback to connect.php (mysqli connection)
        $conn = require_once 'connect.php';
    }
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Include our utility functions
    require_once 'service_utils.php';
    
    // Check if the services table exists
    if ($conn instanceof mysqli) {
        $result = $conn->query("SHOW TABLES LIKE 'services'");
        if (!$result || $result->num_rows == 0) {
            throw new Exception("Services table does not exist");
        }
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = 'services'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            throw new Exception("Services table does not exist");
        }
    }
    
    // Get services using our utility function
    $services = getAllActiveServices($conn);
    
    // If no services found, return an empty array with a message
    if (empty($services)) {
        echo json_encode([
            'services' => [],
            'message' => 'No services found'
        ]);
        exit;
    }
    
    // Return services as JSON
    echo json_encode($services);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load services',
        'message' => $e->getMessage()
    ]);
}
?> 