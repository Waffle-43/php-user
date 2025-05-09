<?php
/**
 * Service and Appointment Integration Test
 * 
 * This file demonstrates how the Service Catalog Module (Module2) and the 
 * Appointment Booking Module work together after integration.
 */

require_once __DIR__ . '/../database/db.php'; // Database connection
require_once __DIR__ . '/../utils/service_utils.php'; // Utility functions for service management
require_once __DIR__ . '/../utils/service_integration.php'; // Utility functions for service integration

// Function to display module information in a formatted way
function displayModuleInfo($title, $description) {
    echo "<div class='module-info bg-gray-100 p-4 mb-6 rounded shadow-sm border-l-4 border-blue-500'>";
    echo "<h3 class='text-lg font-bold mb-2'>{$title}</h3>";
    echo "<p class='text-gray-700'>{$description}</p>";
    echo "</div>";
}

// Function to display data in a nice table
function displayTable($data, $title) {
    if (empty($data)) {
        echo "<div class='p-4 bg-yellow-100 text-yellow-800 rounded mb-4'>No data available for {$title}</div>";
        return;
    }
    
    echo "<h3 class='text-xl font-semibold mb-3'>{$title}</h3>";
    echo "<div class='overflow-x-auto mb-8'>";
    echo "<table class='min-w-full bg-white border-collapse'>";
    
    // Table header
    echo "<thead class='bg-gray-50'><tr>";
    foreach (array_keys($data[0]) as $header) {
        echo "<th class='px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border'>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr></thead>";
    
    // Table body
    echo "<tbody>";
    foreach ($data as $row) {
        echo "<tr class='hover:bg-gray-50'>";
        foreach ($row as $key => $value) {
            // Format the value based on the key
            if ($key === 'price' || $key === 'price_after_discount' || $key === 'original_price') {
                $displayValue = 'RM ' . number_format((float)$value, 2);
            } elseif ($key === 'image') {
                if (!empty($value)) {
                    $displayValue = '[Binary Image Data]';
                } else {
                    $displayValue = 'No image';
                }
            } elseif ($key === 'promotion') {
                if ((int)$value > 0) {
                    $displayValue = $value . '%';
                } else {
                    $displayValue = 'None';
                }
            } elseif ($key === 'available' || $key === 'is_active') {
                $displayValue = $value ? 'Yes' : 'No';
            } else {
                $displayValue = $value;
            }
            
            echo "<td class='px-4 py-2 text-sm border'>" . htmlspecialchars($displayValue) . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}

// Get services using different functions to demonstrate the integration
try {
    $allServices = getAllActiveServices($conn);
    $serviceCategories = getAllServiceCategoriesWrapper($conn);
    
    // Get location-based services if available
    $serviceLocations = getAllServiceLocationsWrapper($conn);
    
    // Get any Hair category services if available
    $hairServices = [];
    if (in_array('Hair', $serviceCategories)) {
        $hairServices = getServicesByCategoryWrapper('Hair', $conn);
    } elseif (in_array('Haircut', $serviceCategories)) {
        $hairServices = getServicesByCategoryWrapper('Haircut', $conn);
    }
    
    // Get filtered services
    $filters = [];
    // Check if any location exists
    if (!empty($serviceLocations[0])) {
        $filters['location'] = $serviceLocations[0];
    }
    $filteredServices = getServicesForBookingInterface($conn, $filters);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get the service table version
$tableVersion = detectServiceTableVersion($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service & Appointment Integration Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
        <header class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-3xl font-bold text-center text-purple-800 mb-2">Service & Appointment Integration Test</h1>
            <p class="text-center text-gray-600">This page demonstrates the successful integration between the Service Catalog Module and Appointment Booking Module</p>
        </header>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Error</p>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">System Configuration</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-blue-50 p-4 rounded shadow">
                    <h3 class="font-semibold text-blue-800 mb-2">Database Table Version</h3>
                    <p>Detected structure: <span class="font-mono bg-blue-100 px-2 py-1 rounded"><?= $tableVersion ?></span></p>
                    <p class="text-sm text-gray-700 mt-2">
                        <strong>v1:</strong> Using 'is_active' column (setup_service_provider_module.php structure)<br>
                        <strong>v2:</strong> Using 'available' column (Module2 structure)
                    </p>
                </div>
                
                <div class="bg-green-50 p-4 rounded shadow">
                    <h3 class="font-semibold text-green-800 mb-2">Available Categories</h3>
                    <?php if (!empty($serviceCategories)): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($serviceCategories as $category): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm"><?= htmlspecialchars($category) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-700">No categories found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php displayModuleInfo(
                "Integration Bridge", 
                "The system has successfully integrated both modules using 'service_utils.php' and 'service_integration.php' files. " .
                "These files detect the database structure and provide a unified API to access services regardless of which module they were created with."
            ); ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Available Services</h2>
            
            <?php displayTable($allServices, "All Active Services"); ?>
            
            <?php if (!empty($hairServices)): ?>
                <?php displayTable($hairServices, "Hair Services"); ?>
            <?php endif; ?>
            
            <?php if (!empty($filteredServices) && !empty($serviceLocations[0])): ?>
                <?php displayTable($filteredServices, "Services in " . htmlspecialchars($serviceLocations[0])); ?>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Booking Integration</h2>
            
            <p class="mb-4">Test the integration by booking services from different modules:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($allServices, 0, 6) as $service): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($service['name']) ?></h3>
                        <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($service['description']) ?></p>
                        <p class="mb-1"><span class="font-semibold">Duration:</span> <?= $service['duration'] ?> minutes</p>
                        <p class="mb-3"><span class="font-semibold">Price:</span> RM <?= number_format($service['price'], 2) ?></p>
                        
                        <a href="appointment.php?service_id=<?= $service['id'] ?>" 
                           class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors">
                            Book This Service
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Navigation Options</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="Module2-20250505T195816Z-1-001/Module2/SerCus.php" 
                   class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                    <h3 class="font-semibold text-lg mb-2">Service Catalog (Module 2)</h3>
                    <p class="text-gray-600">Browse and filter services using the Module 2 interface</p>
                </a>
                
                <a href="appointment.php" 
                   class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                    <h3 class="font-semibold text-lg mb-2">Appointment Booking</h3>
                    <p class="text-gray-600">Book appointments using the Appointment Booking module</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
