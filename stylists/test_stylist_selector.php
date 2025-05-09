<?php
require_once __DIR__ . '/utils/config.php'; // Database connection

// Get stylist ID from query string
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;
echo "<p>Selected stylist_id: $stylist_id (type: " . gettype($stylist_id) . ")</p>";

// Get stylist info
$stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
$stmt->execute([$stylist_id]);
$stylist = $stmt->fetch();

if (!$stylist) {
    die("Stylist not found");
}

echo "<p>Stylist info:</p>";
echo "<pre>";
print_r($stylist);
echo "</pre>";

// Get all stylists for comparison
$stylistsStmt = $conn->prepare("SELECT id, name FROM stylists WHERE is_active = 1");
$stylistsStmt->execute();
$allStylists = $stylistsStmt->fetchAll();

echo "<p>All stylists:</p>";
echo "<pre>";
print_r($allStylists);
echo "</pre>";

echo "<p>Testing selection logic:</p>";
foreach($allStylists as $s) {
    echo "<p>Testing: " . $s['id'] . " (type: " . gettype($s['id']) . ") == $stylist_id (type: " . gettype($stylist_id) . "): ";
    echo ($s['id'] == $stylist_id) ? "TRUE" : "FALSE";
    echo "</p>";
    
    echo "<p>Testing with intval: " . intval($s['id']) . " == $stylist_id: ";
    echo (intval($s['id']) == $stylist_id) ? "TRUE" : "FALSE";
    echo "</p>";
}
?> 