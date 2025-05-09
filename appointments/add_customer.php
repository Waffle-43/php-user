<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

// Set header to return JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get customer data from POST
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Validate required fields
if (empty($name)) {
    echo json_encode([
        'success' => false,
        'error' => 'Customer name is required'
    ]);
    exit;
}

try {
    // Check if customer with this email or phone already exists
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
    $checkStmt->execute([$email, $phone]);
    $existingCustomer = $checkStmt->fetch();
    
    if ($existingCustomer) {
        echo json_encode([
            'success' => false,
            'error' => 'A customer with this email or phone already exists'
        ]);
        exit;
    }
    
    // Insert new customer
    $insertStmt = $conn->prepare("
        INSERT INTO customers (name, email, phone, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    $insertStmt->execute([$name, $email, $phone]);
    $customerId = $conn->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'id' => $customerId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 