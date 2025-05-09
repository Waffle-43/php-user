<?php
/**
 * Integrated Homepage for Harmony Heaven Spa
 * 
 * This homepage integrates both the Service Catalog Module (Module 2) and 
 * Appointment Booking Module (Module 3) into a single unified interface.
 */

require_once __DIR__ . '/../utils_files/connect.php'; // Database connection
require_once __DIR__ . '/../utils/service_utils.php'; // Utility functions for service management
require_once __DIR__ . '/../utils/service_integration.php'; // Utility functions for service integration

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your connection settings.");
}

// Get services data from both modules
try {
    // Get all active services using the integration function
    $allServices = getAllActiveServices($conn);
    
    // Get featured services (first 6 services for display on homepage)
    $featuredServices = array_slice($allServices, 0, 6);
    
    // Get service categories using the wrapper to avoid function conflicts
    $serviceCategories = getAllServiceCategoriesWrapper($conn);
    
    // Try to get stylists, but handle the case where the table might not exist
    $stylists = [];
    try {
        $query = "
            SELECT id, name, specialization, bio, profile_image, rating
            FROM stylists
            WHERE is_active = 1
            ORDER BY rating DESC
            LIMIT 4
        ";
        
        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare stylists query: " . $conn->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $stylists[] = $row;
            }
        } else {
            // PDO implementation
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare stylists query");
            }
            
            $stmt->execute();
            $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Use sample stylists data if query fails
        error_log("Stylists query failed: " . $e->getMessage());
        $stylists = [
            [
                'id' => 1,
                'name' => 'Emma Wilson',
                'specialization' => 'Hair Specialist',
                'bio' => 'With 8 years of experience in hair styling and coloring.',
                'profile_image' => 'https://randomuser.me/api/portraits/women/32.jpg',
                'rating' => 4.9
            ],
            [
                'id' => 2,
                'name' => 'Sophia Chen',
                'specialization' => 'Esthetician',
                'bio' => 'Certified in advanced skincare techniques.',
                'profile_image' => 'https://randomuser.me/api/portraits/women/44.jpg',
                'rating' => 4.8
            ],
            [
                'id' => 3,
                'name' => 'David Rodriguez',
                'specialization' => 'Barber',
                'bio' => 'Traditional barbering skills with modern styling techniques.',
                'profile_image' => 'https://randomuser.me/api/portraits/men/32.jpg',
                'rating' => 4.9
            ],
            [
                'id' => 4,
                'name' => 'Mia Johnson',
                'specialization' => 'Nail Technician',
                'bio' => 'Specializing in nail art and gel applications.',
                'profile_image' => 'https://randomuser.me/api/portraits/women/68.jpg',
                'rating' => 4.7
            ]
        ];
    }
    
    // Get testimonials
    $testimonials = [];
    try {
        $query = "
            SELECT r.id, r.rating, r.comment, r.created_at, 
                   c.name AS customer_name, c.profile_image,
                   s.name AS service_name
            FROM reviews r
            JOIN customers c ON r.customer_id = c.id
            JOIN services s ON r.service_id = s.id
            WHERE r.rating >= 4
            ORDER BY r.created_at DESC
            LIMIT 3
        ";
        
        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare reviews query: " . $conn->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $testimonials[] = $row;
            }
        } else {
            // PDO implementation
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare reviews query");
            }
            
            $stmt->execute();
            $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Use sample testimonials if query fails
        error_log("Testimonials query failed: " . $e->getMessage());
        $testimonials = [
            [
                'rating' => 5,
                'comment' => "Emma transformed my hair! I've never received so many compliments. The online booking was so convenient and the salon atmosphere was relaxing.",
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'customer_name' => 'Jessica T.',
                'profile_image' => 'https://randomuser.me/api/portraits/women/12.jpg'
            ],
            [
                'rating' => 5,
                'comment' => "David gives the best haircut I've ever had. The online system made it easy to book exactly when I wanted. Will definitely be coming back regularly!",
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'customer_name' => 'Michael R.',
                'profile_image' => 'https://randomuser.me/api/portraits/men/45.jpg'
            ],
            [
                'rating' => 5,
                'comment' => "Sophia's facial treatments have completely transformed my skin. The ability to book online and see her availability in real-time is a game changer.",
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'customer_name' => 'Amanda S.',
                'profile_image' => 'https://randomuser.me/api/portraits/women/28.jpg'
            ]
        ];
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Helper function to format time elapsed since date
function timeElapsed($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        if ($diff->d == 1) {
            return 'yesterday';
        }
        if ($diff->d < 7) {
            return $diff->d . ' days ago';
        }
        return floor($diff->d / 7) . ' week' . (floor($diff->d / 7) > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

// Function to display service card
function displayServiceCard($service) {
    $serviceId = $service['id'];
    $serviceName = htmlspecialchars($service['name']);
    $serviceDescription = htmlspecialchars($service['description']);
    $servicePrice = number_format($service['price'], 2);
    $serviceDuration = $service['duration'];
    
    // Determine the appropriate image based on service name
    $serviceLowerName = strtolower($serviceName);
    if (strpos($serviceLowerName, 'facial') !== false) {
        $serviceImage = "../images/facial.jpg";
    } elseif (strpos($serviceLowerName, 'hair color') !== false || strpos($serviceLowerName, 'coloring') !== false) {
        $serviceImage = "../images/hair_coloring.jpg";
    } elseif (strpos($serviceLowerName, 'haircut') !== false || strpos($serviceLowerName, 'styling') !== false || strpos($serviceLowerName, 'hair') !== false) {
        $serviceImage = "../images/haircut_style.jpg";
    } elseif (strpos($serviceLowerName, 'manicure') !== false) {
        $serviceImage = "../images/manicure.jpg";
    } elseif (strpos($serviceLowerName, 'pedicure') !== false) {
        $serviceImage = "../images/pedicure.jpg";
    } else {
        $serviceImage = "../images/spa_service.jpg";
    }
    
    $category = isset($service['category']) ? $service['category'] : 'Service';
    $rating = isset($service['rating']) ? $service['rating'] : rand(45, 49) / 10;
    $reviewCount = isset($service['review_count']) ? $service['review_count'] : rand(50, 200);
    
    // Determine the duration badge color based on category or duration
    $badgeClass = 'bg-primary';
    if (stripos($category, 'hair') !== false) {
        $badgeClass = 'bg-primary';
    } elseif (stripos($category, 'facial') !== false || stripos($category, 'face') !== false) {
        $badgeClass = 'bg-secondary';
    } elseif (stripos($category, 'nail') !== false || stripos($category, 'mani') !== false || stripos($category, 'pedi') !== false) {
        $badgeClass = 'bg-accent';
    }
    
    echo <<<HTML
    <div class="service-card bg-white rounded-xl overflow-hidden shadow-md transition">
        <div class="relative h-48 overflow-hidden">
            <img src="{$serviceImage}" 
                 alt="{$serviceName}" class="w-full h-full object-cover">
            <div class="absolute bottom-0 left-0 {$badgeClass} text-white px-3 py-1 text-sm font-medium">
                <i class="fas fa-clock mr-1"></i> {$serviceDuration} mins
            </div>
        </div>
        <div class="p-6">
            <div class="flex justify-between items-start mb-3">
                <h3 class="text-xl font-bold">{$serviceName}</h3>
                <span class="text-lg font-bold text-primary">RM {$servicePrice}</span>
            </div>
            <p class="text-gray-600 mb-4">{$serviceDescription}</p>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-star text-yellow-400 mr-1"></i>
                    <span class="font-medium">{$rating}</span>
                    <span class="text-gray-500 text-sm ml-1">({$reviewCount})</span>
                </div>
                <a href="appointment.php?service_id={$serviceId}" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition">
                    Book Now
                </a>
            </div>
        </div>
    </div>
    HTML;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harmony Heaven Spa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#9F7AEA',
                        secondary: '#F687B3',
                        dark: '#2D3748',
                        light: '#F7FAFC',
                        accent: '#F6AD55'
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FFF5F5;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.4)), url('../images/spa_service.jpg');
            background-size: cover;
            background-position: center;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .nav-item.active {
            border-bottom: 3px solid #9F7AEA;
        }
        
        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .mobile-menu.open {
            max-height: 500px;
        }
        
        .stylist-card:hover img {
            transform: scale(1.05);
        }
        
        .stylist-card img {
            transition: transform 0.3s ease;
        }
        
        .booking-step {
            position: relative;
        }
        
        .booking-step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -2rem;
            width: 4rem;
            height: 2px;
            background-color: #9F7AEA;
        }
        
        /* Dropdown Menu Improvements */
        .dropdown-menu {
            display: block;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s linear 0.2s;
        }
        
        .group:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition-delay: 0s;
        }
        
        .group .dropdown-menu:hover {
            opacity: 1;
            visibility: visible;
            transition-delay: 0s;
        }
        
        /* Add transition delay when leaving the dropdown */
        .group:hover .dropdown-menu,
        .dropdown-menu:hover {
            transition-delay: 0s;
        }
        
        .dropdown-menu {
            transition-delay: 0.5s; /* This creates the lingering effect */
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <i class="fas fa-spa text-primary text-2xl"></i>
                <span class="text-xl font-bold text-dark">Glamour Haven</span>
            </div>
            
            <!-- Desktop Navigation - Including all modules -->
            <nav class="hidden md:flex space-x-6">
                <a href="integrated_homepage.php" class="nav-item active text-primary font-medium py-2">Home</a>
                <a href="#services" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Services</a>
                <a href="#stylists" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Our Stylists</a>
                <a href="#booking" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Book Now</a>
                
                <!-- Module Navigation -->
                <div class="relative group ml-4">
                    <button class="flex items-center space-x-1 text-gray-600 hover:text-primary font-medium py-2">
                        <span>Modules</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 dropdown-menu">
                        <a href="Module2-20250505T195816Z-1-001/Module2/SerCus.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-concierge-bell mr-2 text-accent"></i> Service Catalogue
                        </a>
                        <a href="appointment.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-calendar-check mr-2 text-primary"></i> Appointment Booking
                        </a>
                        <a href="service_provider_dashboard.php?stylist_id=1" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tachometer-alt mr-2 text-green-500"></i> Service Provider Dashboard
                        </a>
                    </div>
                </div>
            </nav>
            
            <!-- User Actions -->
            <div class="flex items-center space-x-4">
                <button class="md:hidden text-gray-600" id="mobile-menu-button">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden md:flex items-center space-x-2">
                    <button class="text-gray-600 hover:text-primary relative">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">2</span>
                    </button>
                    <div class="relative group">
                        <button class="flex items-center space-x-2">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User" class="w-8 h-8 rounded-full">
                            <span class="text-gray-700 font-medium">Guest</span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                         <img src="/<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture" width="150" height="100">
                          <span><?= htmlspecialchars($user['username']) ?></span>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 dropdown-menu">
                            <a href="profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Profile</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Appointments</a>
                            <a href="logout" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
                <a href="#booking" class="hidden md:block bg-primary text-white px-4 py-2 rounded-md hover:bg-purple-700 transition">
                    <i class="fas fa-calendar-day mr-2"></i> Book Now
                </a>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu bg-white md:hidden" id="mobile-menu">
            <div class="container mx-auto px-4 py-2 flex flex-col space-y-2">
                <a href="integrated_homepage.php" class="nav-item active text-primary font-medium py-2">Home</a>
                <a href="#services" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Services</a>
                <a href="#stylists" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Our Stylists</a>
                <a href="#booking" class="nav-item text-gray-600 hover:text-primary font-medium py-2">Book Now</a>
                
                <!-- Module Navigation Mobile -->
                <div class="pt-2 border-t border-gray-200">
                    <p class="text-gray-500 text-sm font-medium py-2">MODULES</p>
                    <a href="services" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-concierge-bell mr-2 text-accent"></i> Service Catalogue
                    </a>
                    <a href="appointments" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-calendar-check mr-2 text-primary"></i> Appointment Booking
                    </a>
                    <a href="service-dashboard" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-tachometer-alt mr-2 text-green-500"></i> Service Provider Dashboard
                    </a>
                </div>
                
                <div class="pt-2 border-t border-gray-200">
                    <a href="#booking" class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-purple-700 transition mb-2 block text-center">
                        <i class="fas fa-calendar-day mr-2"></i> Book Now
                    </a>
                    <div class="flex items-center space-x-3 py-2">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User" class="w-8 h-8 rounded-full">
                        <div>
                            <p class="font-medium">Guest</p>
                            <p class="text-xs text-gray-500">Login to book</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 py-2">
                        <a href="sign-in" class="text-gray-600 hover:text-primary text-sm"><i class="fas fa-user mr-2"></i> Login</a>
                        <a href="sign-up" class="text-gray-600 hover:text-primary text-sm"><i class="fas fa-user-plus mr-2"></i> Register</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section text-white py-20 md:py-32">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Discover Your Perfect Glow</h1>
            <p class="text-xl md:text-2xl mb-8 max-w-2xl mx-auto">Premium salon & spa services tailored just for you. Book instantly online.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#booking" class="bg-primary hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-full transition">
                    Book Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="#services" class="bg-white hover:bg-gray-100 text-primary font-bold py-3 px-8 rounded-full transition">
                    Explore Services
                </a>
            </div>
        </div>
    </section>

    <!-- Booking Steps -->
    <section class="bg-white py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-dark mb-12">How It Works</h2>
            <div class="flex flex-col md:flex-row justify-center items-center gap-8 md:gap-16">
                <div class="booking-step flex flex-col items-center text-center max-w-xs">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-full mb-4">
                        <i class="fas fa-search text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">1. Choose Your Service</h3>
                    <p class="text-gray-600">Browse our extensive menu of salon and spa treatments</p>
                </div>
                
                <div class="booking-step flex flex-col items-center text-center max-w-xs">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-full mb-4">
                        <i class="fas fa-user-friends text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">2. Select Your Stylist</h3>
                    <p class="text-gray-600">Pick from our team of certified beauty professionals</p>
                </div>
                
                <div class="booking-step flex flex-col items-center text-center max-w-xs">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-full mb-4">
                        <i class="fas fa-calendar-check text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">3. Book Your Slot</h3>
                    <p class="text-gray-600">Reserve your preferred date and time instantly</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-16 bg-light">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-dark">Our Services</h2>
                <a href="Module2-20250505T195816Z-1-001/Module2/SerCus.php" class="text-primary font-medium flex items-center">
                    View All Services <i class="fas fa-chevron-right ml-2"></i>
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Service Categories Filter -->
            <div class="mb-8">
                <div class="flex flex-wrap gap-3">
                    <a href="#services" class="bg-primary text-white px-4 py-2 rounded-full text-sm transition">
                        All Services
                    </a>
                    <?php if (isset($serviceCategories) && !empty($serviceCategories)): ?>
                        <?php foreach($serviceCategories as $category): ?>
                            <a href="?category=<?= urlencode($category) ?>#services" 
                               class="bg-white text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-full text-sm transition">
                                <?= htmlspecialchars($category) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (isset($featuredServices) && !empty($featuredServices)): ?>
                    <?php foreach ($featuredServices as $service): ?>
                        <?php displayServiceCard($service); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-8">
                        <p class="text-gray-600">No services available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="Module2-20250505T195816Z-1-001/Module2/SerCus.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-3 px-8 rounded-full transition">
                    Browse All Services
                </a>
            </div>
        </div>
    </section>

    <!-- Stylists Section -->
    <section id="stylists" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-dark mb-4">Meet Our Stylists</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Our team of certified professionals are ready to help you look and feel your best.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php if (isset($stylists) && !empty($stylists)): ?>
                    <?php foreach ($stylists as $stylist): ?>
                        <?php
                            $stylistId = $stylist['id'];
                            $stylistName = htmlspecialchars($stylist['name']);
                            $stylistTitle = isset($stylist['specialization']) ? htmlspecialchars($stylist['specialization']) : 'Style Professional';
                            $stylistImage = !empty($stylist['profile_image']) ? $stylist['profile_image'] : 'https://randomuser.me/api/portraits/women/32.jpg';
                            $stylistRating = isset($stylist['rating']) ? number_format($stylist['rating'], 1) : '4.8';
                            $reviewCount = rand(150, 350);
                            
                            // Get specialist services
                            $specialties = ['Hair', 'Styling', 'Color'];
                            if (stripos($stylistTitle, 'hair') !== false) {
                                $specialties = ['Hair', 'Color', 'Extensions'];
                            } elseif (stripos($stylistTitle, 'esth') !== false || stripos($stylistTitle, 'facial') !== false) {
                                $specialties = ['Facials', 'Peels', 'Microderm'];
                            } elseif (stripos($stylistTitle, 'barber') !== false) {
                                $specialties = ['Haircuts', 'Shaves', 'Beard'];
                            } elseif (stripos($stylistTitle, 'nail') !== false || stripos($stylistTitle, 'mani') !== false) {
                                $specialties = ['Manicure', 'Pedicure', 'Gel'];
                            }
                        ?>
                        <!-- Stylist Card -->
                        <div class="stylist-card bg-light rounded-xl overflow-hidden shadow-md text-center transition hover:shadow-lg">
                            <div class="relative h-64 overflow-hidden">
                                <img src="<?= $stylistImage ?>" 
                                     alt="<?= $stylistName ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold mb-1"><?= $stylistName ?></h3>
                                <p class="text-secondary mb-3"><?= $stylistTitle ?></p>
                                <div class="flex justify-center space-x-2 mb-4">
                                    <?php foreach ($specialties as $specialty): ?>
                                        <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded"><?= $specialty ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex justify-center items-center mb-4">
                                    <i class="fas fa-star text-yellow-400 mr-1"></i>
                                    <span class="font-medium mr-1"><?= $stylistRating ?></span>
                                    <span class="text-gray-500 text-sm">(<?= $reviewCount ?> reviews)</span>
                                </div>
                                <a href="appointment.php?stylist_id=<?= $stylistId ?>" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition w-full inline-block">
                                    Book with <?= explode(' ', $stylistName)[0] ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback stylists if none are available in database -->
                    <!-- Stylist Card 1 -->
                    <div class="stylist-card bg-light rounded-xl overflow-hidden shadow-md text-center transition hover:shadow-lg">
                        <div class="relative h-64 overflow-hidden">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" 
                                 alt="Emma Wilson" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-1">Emma Wilson</h3>
                            <p class="text-secondary mb-3">Master Stylist</p>
                            <div class="flex justify-center space-x-2 mb-4">
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Hair</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Color</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Extensions</span>
                            </div>
                            <div class="flex justify-center items-center mb-4">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span class="font-medium mr-1">4.9</span>
                                <span class="text-gray-500 text-sm">(247 reviews)</span>
                            </div>
                            <a href="appointment.php" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition w-full inline-block">
                                Book with Emma
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stylist Card 2 -->
                    <div class="stylist-card bg-light rounded-xl overflow-hidden shadow-md text-center transition hover:shadow-lg">
                        <div class="relative h-64 overflow-hidden">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" 
                                 alt="Sophia Chen" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-1">Sophia Chen</h3>
                            <p class="text-secondary mb-3">Esthetician</p>
                            <div class="flex justify-center space-x-2 mb-4">
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Facials</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Peels</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Microderm</span>
                            </div>
                            <div class="flex justify-center items-center mb-4">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span class="font-medium mr-1">4.8</span>
                                <span class="text-gray-500 text-sm">(189 reviews)</span>
                            </div>
                            <a href="appointment.php" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition w-full inline-block">
                                Book with Sophia
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stylist Card 3 -->
                    <div class="stylist-card bg-light rounded-xl overflow-hidden shadow-md text-center transition hover:shadow-lg">
                        <div class="relative h-64 overflow-hidden">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" 
                                 alt="David Rodriguez" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-1">David Rodriguez</h3>
                            <p class="text-secondary mb-3">Barber</p>
                            <div class="flex justify-center space-x-2 mb-4">
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Haircuts</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Shaves</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Beard</span>
                            </div>
                            <div class="flex justify-center items-center mb-4">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span class="font-medium mr-1">4.9</span>
                                <span class="text-gray-500 text-sm">(312 reviews)</span>
                            </div>
                            <a href="appointment.php" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition w-full inline-block">
                                Book with David
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stylist Card 4 -->
                    <div class="stylist-card bg-light rounded-xl overflow-hidden shadow-md text-center transition hover:shadow-lg">
                        <div class="relative h-64 overflow-hidden">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" 
                                 alt="Mia Johnson" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-1">Mia Johnson</h3>
                            <p class="text-secondary mb-3">Nail Technician</p>
                            <div class="flex justify-center space-x-2 mb-4">
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Manicure</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Pedicure</span>
                                <span class="bg-purple-100 text-primary text-xs px-2 py-1 rounded">Gel</span>
                            </div>
                            <div class="flex justify-center items-center mb-4">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span class="font-medium mr-1">4.7</span>
                                <span class="text-gray-500 text-sm">(176 reviews)</span>
                            </div>
                            <a href="appointment.php" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-full text-sm transition w-full inline-block">
                                Book with Mia
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="appointment.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-3 px-8 rounded-full transition">
                    View All Stylists
                </a>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking" class="py-16 bg-gradient-to-r from-purple-50 to-pink-50">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="md:flex">
                    <div class="md:w-1/2 bg-primary text-white p-8 md:p-12">
                        <h2 class="text-3xl font-bold mb-4">Ready for Your Transformation?</h2>
                        <p class="mb-6">Book your appointment in just a few clicks. Our flexible scheduling makes it easy to find the perfect time.</p>
                        
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="bg-white bg-opacity-20 p-2 rounded-full mr-4">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold">Instant Confirmation</h4>
                                    <p class="text-sm opacity-90">Get your appointment secured immediately</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="bg-white bg-opacity-20 p-2 rounded-full mr-4">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold">Flexible Rescheduling</h4>
                                    <p class="text-sm opacity-90">Change your booking up to 24 hours before</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="bg-white bg-opacity-20 p-2 rounded-full mr-4">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold">Loyalty Rewards</h4>
                                    <p class="text-sm opacity-90">Earn points with every visit</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:w-1/2 p-8 md:p-12">
                        <h3 class="text-2xl font-bold text-dark mb-6">Book Your Appointment</h3>
                        
                        <form action="appointment.php" method="get">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="service">Select Service</label>
                                <select id="service" name="service_id" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Select a Service</option>
                                    <?php if (isset($allServices) && !empty($allServices)): ?>
                                        <?php foreach ($allServices as $service): ?>
                                            <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?> (RM <?= number_format($service['price'], 2) ?>)</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option>Haircut & Styling</option>
                                        <option>Color Treatment</option>
                                        <option>Facial Treatment</option>
                                        <option>Manicure & Pedicure</option>
                                        <option>Waxing Services</option>
                                        <option>Makeup Application</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="stylist">Preferred Stylist</label>
                                <select id="stylist" name="stylist_id" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Any Available Stylist</option>
                                    <?php if (isset($stylists) && !empty($stylists)): ?>
                                        <?php foreach ($stylists as $stylist): ?>
                                            <option value="<?= $stylist['id'] ?>"><?= htmlspecialchars($stylist['name']) ?> (<?= htmlspecialchars($stylist['specialization']) ?>)</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option>Emma Wilson (Hair Specialist)</option>
                                        <option>Sophia Chen (Esthetician)</option>
                                        <option>David Rodriguez (Barber)</option>
                                        <option>Mia Johnson (Nail Technician)</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="date">Preferred Date</label>
                                <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <button type="submit" class="w-full bg-primary hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                Continue to Booking
                            </button>
                        </form>
                        
                        <div class="mt-6 text-center">
                            <p class="text-gray-600 text-sm">Need help? Call us at <a href="tel:+60123456789" class="text-primary font-medium">(60) 123-456-789</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-dark mb-4">What Our Clients Say</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Don't just take our word for it - hear from our satisfied customers</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (isset($testimonials) && !empty($testimonials)): ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                        <!-- Testimonial Card -->
                        <div class="bg-light p-8 rounded-xl">
                            <div class="flex items-center mb-4">
                                <div class="text-yellow-400 mr-2">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?php if ($i < $testimonial['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?= isset($testimonial['created_at']) ? timeElapsed($testimonial['created_at']) : 'Recently' ?>
                                </span>
                            </div>
                            <p class="text-gray-700 mb-6">"<?= htmlspecialchars($testimonial['comment']) ?>"</p>
                            <div class="flex items-center">
                                <img src="<?= isset($testimonial['profile_image']) ? $testimonial['profile_image'] : 'https://randomuser.me/api/portraits/women/12.jpg' ?>" 
                                     alt="<?= htmlspecialchars($testimonial['customer_name']) ?>" class="w-10 h-10 rounded-full mr-3">
                                <div>
                                    <h4 class="font-bold"><?= htmlspecialchars($testimonial['customer_name']) ?></h4>
                                    <p class="text-sm text-gray-500">
                                        <?= isset($testimonial['service_name']) ? 'For ' . htmlspecialchars($testimonial['service_name']) : 'Satisfied Client' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-16 bg-primary text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Experience Glamour Haven?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">Join thousands of satisfied clients who trust us with their beauty needs.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#booking" class="bg-white hover:bg-gray-100 text-primary font-bold py-3 px-8 rounded-full transition">
                    Book Your Appointment
                </a>
                <a href="tel:+60123456789" class="border-2 border-white hover:bg-white hover:bg-opacity-10 font-bold py-3 px-8 rounded-full transition">
                    <i class="fas fa-phone mr-2"></i> Call Us Now
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-spa text-primary text-2xl"></i>
                        <span class="text-xl font-bold">Glamour Haven</span>
                    </div>
                    <p class="text-gray-400 mb-4">Your premier destination for salon and spa services, offering luxury treatments with convenience.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="integrated_homepage.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white">Services</a></li>
                        <li><a href="#stylists" class="text-gray-400 hover:text-white">Our Stylists</a></li>
                        <li><a href="#booking" class="text-gray-400 hover:text-white">Book Now</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Our Modules</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="Module2-20250505T195816Z-1-001/Module2/SerCus.php" class="text-gray-400 hover:text-white">
                                <i class="fas fa-concierge-bell mr-2 text-accent"></i> Service Catalogue
                            </a>
                        </li>
                        <li>
                            <a href="appointment.php" class="text-gray-400 hover:text-white">
                                <i class="fas fa-calendar-check mr-2 text-primary"></i> Appointment Booking
                            </a>
                        </li>
                        <li>
                            <a href="service_provider_dashboard.php?stylist_id=1" class="text-gray-400 hover:text-white">
                                <i class="fas fa-tachometer-alt mr-2 text-green-400"></i> Service Provider Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary mr-3 mt-1"></i>
                            <span class="text-gray-400">123 Beauty Ave, Kuala Lumpur, Malaysia</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone text-primary mr-3"></i>
                            <a href="tel:+60123456789" class="text-gray-400 hover:text-white">(60) 123-456-789</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-primary mr-3"></i>
                            <a href="mailto:hello@glamourhaven.com" class="text-gray-400 hover:text-white">hello@glamourhaven.com</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock text-primary mr-3"></i>
                            <span class="text-gray-400">Mon-Sat: 9AM-8PM<br>Sun: 10AM-6PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© <?= date('Y') ?> Glamour Haven. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white text-sm">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm">Terms of Service</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Initialize booking features
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Initializing integrated salon booking system...');
            
            // Service selection affects stylist options
            const serviceSelect = document.getElementById('service');
            const stylistSelect = document.getElementById('stylist');
            
            if (serviceSelect && stylistSelect) {
                serviceSelect.addEventListener('change', function() {
                    const serviceId = this.value;
                    if (serviceId) {
                        // In a real implementation, you would fetch the relevant stylists
                        // based on the selected service using AJAX
                        console.log('Service selected:', serviceId);
                    }
                });
            }
        });
    </script>
</body>
</html> 