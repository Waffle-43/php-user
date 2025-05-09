<?php
/**
 * Service Integration Helper File
 * 
 * This file provides utility functions to bridge between the service management module 
 * (addService.php, editService.php, SerCus.php) and the appointment booking module.
 */

// Include database connection
require_once __DIR__ . '/../utils_files/connect.php'; // Database connection
require_once __DIR__ . '/../utils/service_utils.php'; // Utility functions for service management

/**
 * Get all active services formatted for appointment booking
 * 
 * @param mixed $conn Database connection
 * @return array Array of service data formatted for appointment booking
 */
function getActiveServicesForBooking($conn = null) {
    if ($conn === null) {
        global $conn;
    }
    
    // Use the utility function from service_utils.php which handles both module versions
    return getAllActiveServices($conn);
}

/**
 * Get service details by ID
 * 
 * @param int $serviceId The service ID
 * @param mixed $conn Database connection
 * @return array|null Service details or null if not found
 */
function getServiceDetails($serviceId, $conn = null) {
    if ($conn === null) {
        global $conn;
    }
    
    // Use the utility function from service_utils.php which handles both module versions
    return getServiceById($conn, $serviceId);
}

/**
 * Wraps the original getServicesByCategory function to handle cases where connection is null
 * 
 * @param string $category The service category
 * @param mixed $conn Database connection
 * @return array Array of services in the specified category
 */
function getServicesByCategoryWrapper($category, $conn = null) {
    if ($conn === null) {
        global $conn;
    }
    
    // Use the utility function from service_utils.php which handles both module versions
    return getServicesByCategory($conn, $category);
}

/**
 * Wraps the original getAllServiceCategories function to handle cases where connection is null
 * 
 * @param mixed $conn Database connection
 * @return array Array of unique service categories
 */
function getAllServiceCategoriesWrapper($conn = null) {
    if ($conn === null) {
        global $conn;
    }
    
    // Use the utility function from service_utils.php which handles both module versions
    return getAllServiceCategories($conn);
}

/**
 * Wraps the original getAllServiceLocations function to handle cases where connection is null
 * 
 * @param mixed $conn Database connection
 * @return array Array of unique service locations
 */
function getAllServiceLocationsWrapper($conn = null) {
    if ($conn === null) {
        global $conn;
    }
    
    // Use the utility function from service_utils.php which handles both module versions
    return getAllServiceLocations($conn);
}

/**
 * Create an integrated function to handle booking service selection
 * Works with both Module2 and Appointment Booking system
 * 
 * @param mixed $conn Database connection
 * @param array $filters Optional filters (category, location)
 * @return array Services formatted for the booking interface
 */
function getServicesForBookingInterface($conn = null, $filters = []) {
    if ($conn === null) {
        global $conn;
    }
    
    $services = getAllActiveServices($conn);
    
    // Apply filters if provided
    if (!empty($filters)) {
        $filteredServices = [];
        
        foreach ($services as $service) {
            $include = true;
            
            // Apply category filter
            if (isset($filters['category']) && !empty($filters['category']) && $filters['category'] !== 'all') {
                if ($service['category'] !== $filters['category']) {
                    $include = false;
                }
            }
            
            // Apply location filter
            if (isset($filters['location']) && !empty($filters['location'])) {
                if (!isset($service['location']) || $service['location'] !== $filters['location']) {
                    $include = false;
                }
            }
            
            if ($include) {
                $filteredServices[] = $service;
            }
        }
        
        return $filteredServices;
    }
    
    return $services;
}

/**
 * Create an integrated booking function
 * This allows creating appointments using services from either module
 * 
 * @param mixed $conn Database connection
 * @param int $serviceId Service ID
 * @param string $date Appointment date
 * @param string $time Appointment time
 * @param int $stylistId Stylist ID
 * @param int $userId User ID
 * @return bool|string True if successful, error message if failed
 */
function createServiceAppointment($conn, $serviceId, $date, $time, $stylistId, $userId) {
    try {
        // First verify the service exists and is active
        $service = getServiceById($conn, $serviceId);
        
        if (!$service) {
            return "Selected service not found or is not available";
        }
        
        // Format the appointment datetime
        $appointmentDateTime = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        // Insert the appointment
        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare("
                INSERT INTO appointments 
                (user_id, service_id, stylist_id, appointment_datetime, status, created_at)
                VALUES (?, ?, ?, ?, 'booked', NOW())
            ");
            $stmt->bind_param('iiis', $userId, $serviceId, $stylistId, $appointmentDateTime);
            $success = $stmt->execute();
            
            if (!$success) {
                return "Database error: " . $stmt->error;
            }
            
            return true;
        } else {
            // PDO implementation
            $stmt = $conn->prepare("
                INSERT INTO appointments 
                (user_id, service_id, stylist_id, appointment_datetime, status, created_at)
                VALUES (:user_id, :service_id, :stylist_id, :appointment_datetime, 'booked', NOW())
            ");
            
            $params = [
                ':user_id' => $userId,
                ':service_id' => $serviceId,
                ':stylist_id' => $stylistId,
                ':appointment_datetime' => $appointmentDateTime
            ];
            
            $success = $stmt->execute($params);
            
            if (!$success) {
                return "Database error: " . implode(' ', $stmt->errorInfo());
            }
            
            return true;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}
