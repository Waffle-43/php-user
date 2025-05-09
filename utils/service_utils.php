<?php
/**
 * Utility functions to help with service integration between different table structures
 */

/**
 * Detect which services table structure is being used
 * 
 * @param mixed $conn Database connection (mysqli or PDO)
 * @return string 'v1' for setup_service_provider_module.php structure, 'v2' for groupmate's structure
 */
function detectServiceTableVersion($conn) {
    if ($conn instanceof mysqli) {
        $available_col = $conn->query("SHOW COLUMNS FROM services LIKE 'available'");
        if ($available_col && $available_col->num_rows > 0) {
            return 'v2'; // Groupmate's structure with 'available' column
        } else {
            return 'v1'; // setup_service_provider_module.php structure with 'is_active' column
        }
    } else {
        // PDO connection
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'services' AND column_name = 'available'
        ");
        $stmt->execute();
        $has_available = ($stmt->fetchColumn() > 0);
        
        return $has_available ? 'v2' : 'v1';
    }
}

/**
 * Get all services that are available/active
 * 
 * @param mixed $conn Database connection (mysqli or PDO)
 * @return array Array of services
 */
function getAllActiveServices($conn) {
    $version = detectServiceTableVersion($conn);
    
    if ($conn instanceof mysqli) {
        if ($version === 'v2') {
            // Groupmate's structure - use price_after_discount if promotion applied
            $sql = "SELECT id, name, description, duration, 
                   CASE WHEN promotion > 0 THEN price_after_discount ELSE price END as price, 
                   category, image, location, promotion 
                   FROM services WHERE available = 1 ORDER BY name";
        } else {
            // setup_service_provider_module.php structure
            $sql = "SELECT id, name, description, duration, price, 
                   'General' as category, NULL as image, '' as location, 0 as promotion 
                   FROM services WHERE is_active = 1 ORDER BY name";
        }
        
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Error querying services: " . $conn->error);
        }
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            // Normalize data
            $services[] = normalizeServiceData($row);
        }
        
        return $services;
    } else {
        // PDO connection
        if ($version === 'v2') {
            // Groupmate's structure - use price_after_discount if promotion applied
            $sql = "SELECT id, name, description, duration, 
                   CASE WHEN promotion > 0 THEN price_after_discount ELSE price END as price, 
                   category, image, location, promotion 
                   FROM services WHERE available = 1 ORDER BY name";
        } else {
            // setup_service_provider_module.php structure
            $sql = "SELECT id, name, description, duration, price, 
                   'General' as category, NULL as image, '' as location, 0 as promotion 
                   FROM services WHERE is_active = 1 ORDER BY name";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normalize each service
        foreach ($services as &$service) {
            $service = normalizeServiceData($service);
        }
        
        return $services;
    }
}

/**
 * Normalize service data to ensure it has all required fields
 * 
 * @param array $service Service data from database
 * @return array Normalized service data
 */
function normalizeServiceData($service) {
    // Ensure description is not null
    if (!isset($service['description']) || $service['description'] === null) {
        $service['description'] = '';
    }
    
    // Ensure category is set
    if (!isset($service['category']) || $service['category'] === null) {
        $service['category'] = 'General';
    }
    
    // Ensure price is numeric
    $service['price'] = floatval($service['price']);
    
    // Ensure duration is numeric
    $service['duration'] = intval($service['duration']);
    
    // Ensure promotion is set
    if (!isset($service['promotion'])) {
        $service['promotion'] = 0;
    }
    
    // Ensure location is set
    if (!isset($service['location'])) {
        $service['location'] = '';
    }
    
    // Calculate original price if it's missing but price_after_discount and promotion exist
    if (!isset($service['original_price']) && isset($service['price_after_discount']) && isset($service['promotion']) && $service['promotion'] > 0) {
        $service['original_price'] = $service['price_after_discount'] / (1 - ($service['promotion'] / 100));
    } else if (isset($service['price'])) {
        $service['original_price'] = $service['price'];
    }
    
    return $service;
}

/**
 * Get service details by ID, compatible with both table structures
 * 
 * @param mixed $conn Database connection (mysqli or PDO)
 * @param int $serviceId Service ID
 * @return array|null Service details or null if not found
 */
function getServiceById($conn, $serviceId) {
    $version = detectServiceTableVersion($conn);
    
    if ($conn instanceof mysqli) {
        if ($version === 'v2') {
            // Groupmate's structure
            $sql = "SELECT id, name, description, duration, price,
                   price_after_discount, promotion,
                   category, image, location, available 
                   FROM services WHERE id = ? AND available = 1";
        } else {
            // setup_service_provider_module.php structure
            $sql = "SELECT id, name, description, duration, price, 
                   price as price_after_discount, 0 as promotion,
                   'General' as category, NULL as image, '' as location, is_active as available 
                   FROM services WHERE id = ? AND is_active = 1";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $serviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $service = $result->fetch_assoc();
        
        if ($service) {
            return normalizeServiceData($service);
        }
        
        return null;
    } else {
        // PDO connection
        if ($version === 'v2') {
            // Groupmate's structure
            $sql = "SELECT id, name, description, duration, price,
                   price_after_discount, promotion,
                   category, image, location, available 
                   FROM services WHERE id = :id AND available = 1";
        } else {
            // setup_service_provider_module.php structure
            $sql = "SELECT id, name, description, duration, price, 
                   price as price_after_discount, 0 as promotion,
                   'General' as category, NULL as image, '' as location, is_active as available 
                   FROM services WHERE id = :id AND is_active = 1";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $serviceId, PDO::PARAM_INT);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service) {
            return normalizeServiceData($service);
        }
        
        return null;
    }
}

/**
 * Get services by category, compatible with both table structures
 *
 * @param mixed $conn Database connection (mysqli or PDO)
 * @param string $category Category name
 * @return array Array of services in the specified category
 */
function getServicesByCategory($conn, $category) {
    $version = detectServiceTableVersion($conn);
    
    if ($conn instanceof mysqli) {
        if ($version === 'v2') {
            // Groupmate's structure
            $sql = "SELECT id, name, description, duration, 
                  CASE WHEN promotion > 0 THEN price_after_discount ELSE price END as price,
                  category, image, location, promotion 
                  FROM services WHERE category = ? AND available = 1 ORDER BY name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $category);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = normalizeServiceData($row);
            }
        } else {
            // setup_service_provider_module.php structure - no categories, return all
            $services = getAllActiveServices($conn);
        }
        
        return $services;
    } else {
        // PDO connection
        if ($version === 'v2') {
            // Groupmate's structure
            $sql = "SELECT id, name, description, duration, 
                  CASE WHEN promotion > 0 THEN price_after_discount ELSE price END as price,
                  category, image, location, promotion 
                  FROM services WHERE category = :category AND available = 1 ORDER BY name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Normalize each service
            foreach ($services as &$service) {
                $service = normalizeServiceData($service);
            }
        } else {
            // setup_service_provider_module.php structure - no categories, return all
            $services = getAllActiveServices($conn);
        }
        
        return $services;
    }
}

/**
 * Get all available service categories
 *
 * @param mixed $conn Database connection (mysqli or PDO)
 * @return array Array of unique categories
 */
function getAllServiceCategories($conn) {
    $version = detectServiceTableVersion($conn);
    
    if ($version === 'v1') {
        // setup_service_provider_module.php has no categories
        return ['General'];
    }
    
    if ($conn instanceof mysqli) {
        $sql = "SELECT DISTINCT category FROM services WHERE available = 1 ORDER BY category";
        $result = $conn->query($sql);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    } else {
        // PDO connection
        $sql = "SELECT DISTINCT category FROM services WHERE available = 1 ORDER BY category";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

/**
 * Get all service locations
 *
 * @param mixed $conn Database connection (mysqli or PDO)
 * @return array Array of unique locations
 */
function getAllServiceLocations($conn) {
    $version = detectServiceTableVersion($conn);
    
    if ($version === 'v1') {
        // setup_service_provider_module.php has no locations
        return [];
    }
    
    if ($conn instanceof mysqli) {
        $sql = "SELECT DISTINCT location FROM services WHERE available = 1 ORDER BY location";
        $result = $conn->query($sql);
        
        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['location'];
        }
        
        return $locations;
    } else {
        // PDO connection
        $sql = "SELECT DISTINCT location FROM services WHERE available = 1 ORDER BY location";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?> 