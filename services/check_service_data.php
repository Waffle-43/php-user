<?php
// Simple script to check service data
require_once __DIR__ . '/../utils_files/connect.php'; // Database connection
require_once __DIR__ . '/../utils/service_utils.php'; // Utility functions for service management


$conn = require_once '/../utils_files/connect.php';

// Get all categories
echo "<h2>Available Categories:</h2>";
$categories = getAllServiceCategories($conn);
echo "<pre>";
print_r($categories);
echo "</pre>";

// Get all locations
echo "<h2>Available Locations:</h2>";
$locations = getAllServiceLocations($conn);
echo "<pre>";
print_r($locations);
echo "</pre>";

// Get all services
echo "<h2>All Services:</h2>";
$services = getAllActiveServices($conn);
echo "<pre>";
print_r($services);
echo "</pre>"; 