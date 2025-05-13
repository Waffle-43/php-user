<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'salon_spa';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if stylist_id is set (for therapist portal integration)
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

// Get stylist information if stylist_id is provided
$stylist = null;
if ($stylist_id) {
    $stylistStmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
    $stylistStmt->bind_param('i', $stylist_id);
    $stylistStmt->execute();
    $result = $stylistStmt->get_result();
    $stylist = $result->fetch_assoc();
    $stylistStmt->close();
}

// Check if a service ID is provided
$editing = false;
$service = null;

if (isset($_GET['id'])) {
$service_id = (int)$_GET['id'];

// Fetch service details from the database
$result = $conn->query("SELECT * FROM services WHERE id = $service_id");

if ($result->num_rows === 0) {
    echo "Service not found.";
    exit();
}

$service = $result->fetch_assoc();
    $editing = true;

// Handle form submission (updating service)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $location = $_POST['location'];
    $category = $_POST['category'];
    $available = isset($_POST['available']) ? 1 : 0;
    $promotion = isset($_POST['promotion']) ? (int)$_POST['promotion'] : 0; // New field for promotion
    $price_after_discount = $price;

    // If there is a promotion, calculate the price after discount
    if ($promotion > 0) {
        $price_after_discount = $price * (1 - ($promotion / 100));
    }

    // Handle image upload
    $imageData = $service['image']; // Keep existing image if no new image is uploaded
    if ($_FILES['image']['error'] === 0) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Prepare the SQL statement with the correct types
    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, location = ?, category = ?, available = ?, image = ?, promotion = ?, price_after_discount = ? WHERE id = ?");
    $null = NULL;
    $stmt->bind_param("ssdissibidi", $name, $description, $price, $duration, $location, $category, $available, $null, $promotion, $price_after_discount, $service_id);

    if ($imageData !== null) {
        $stmt->send_long_data(7, $imageData);
    }

    // Execute the statement
    if ($stmt->execute()) {
            $success_message = "Service updated successfully!";
            // Refresh service data
            $result = $conn->query("SELECT * FROM services WHERE id = $service_id");
            $service = $result->fetch_assoc();
    } else {
            $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
    }
}

// Get all services if no specific service is being edited
$services = null;
if (!$editing) {
    $services = $conn->query("SELECT * FROM services WHERE available = 1 ORDER BY id DESC");
}

// List of Malaysian states for location dropdown
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 
    'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editing ? 'Edit Service' : 'Services List' ?> - Therapist Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar {
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 10px;
        }
        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar text-white w-64 flex-shrink-0">
            <div class="p-4 border-b border-indigo-400 border-opacity-20">
                <h1 class="text-xl font-bold flex items-center">
                    <i class="fas fa-spa mr-2"></i>
                    Harmony Heaven Spa
                </h1>
                <p class="text-xs text-indigo-100 mt-1">Therapist Portal</p>
            </div>
            <nav class="p-4">
                <div class="mb-6">
                    <p class="text-xs uppercase text-indigo-200 mb-2">Navigation</p>
                    <a href="home" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-home mr-2"></i> Back to Homepage
                    </a>
                    <a href="service-dashboard?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="calendar-appt?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="all-appointments?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-check mr-2"></i> All Appointments
                    </a>
                    <a href="add-services?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-plus-circle mr-2"></i> Add Service
                    </a>
                    <a href="edit-services.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded bg-white bg-opacity-10 mb-1">
                        <i class="fas fa-edit mr-2"></i> Manage Services
                    </a>
    </div>
    <div>
                    <a href="logout" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center p-4">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-600">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="text-lg font-semibold"><?= $editing ? 'Edit Service' : 'Services List' ?></h2>
                    </div>
                    <?php if ($stylist): ?>
                    <div class="flex items-center">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($stylist['name']) ?>&background=random" 
                             class="h-8 w-8 rounded-full mr-2" alt="<?= htmlspecialchars($stylist['name']) ?>">
                        <span class="text-sm font-medium"><?= htmlspecialchars($stylist['name']) ?></span>
                    </div>
                    <?php endif; ?>
    </div>
</header>

            <!-- Service Management Content -->
            <div class="p-6">
                <?php if (isset($success_message)): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= $error_message ?>
        </div>
                <?php endif; ?>

                <?php if ($editing): ?>
                <!-- Edit Service Form -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b">Edit Service</h3>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($service['name']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select id="category" name="category" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="Hair" <?= $service['category'] == 'Hair' ? 'selected' : '' ?>>Hair</option>
                <option value="Skincare" <?= $service['category'] == 'Skincare' ? 'selected' : '' ?>>Skincare</option>
                <option value="Massage" <?= $service['category'] == 'Massage' ? 'selected' : '' ?>>Massage</option>
                                    <option value="Spa" <?= $service['category'] == 'Spa' ? 'selected' : '' ?>>Spa</option>
            </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" required rows="4"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($service['description']) ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (RM)</label>
                                <input type="number" id="price" name="price" step="0.01" required value="<?= $service['price'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="promotion" class="block text-sm font-medium text-gray-700 mb-1">Promotion (%)</label>
                                <input type="number" id="promotion" name="promotion" min="0" max="100" value="<?= $service['promotion'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                                <input type="number" id="duration" name="duration" required value="<?= $service['duration'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select id="location" name="location" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <?php foreach ($states as $state): ?>
                                    <option value="<?= htmlspecialchars($state) ?>" <?= $service['location'] == $state ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($state) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Service Image</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <?php if (!empty($service['image'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Current image:</p>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($service['image']) ?>" alt="Current Image" 
                                         class="mt-1 max-h-32 rounded-md" id="currentImage">
                                </div>
                                <?php endif; ?>
                                <img id="imagePreview" class="mt-2 hidden rounded-md max-h-32" alt="New Image Preview">
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="available" name="available" <?= $service['available'] ? 'checked' : '' ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="available" class="ml-2 block text-sm text-gray-700">Service Available</label>
                        </div>
                        
                        <div class="pt-4 flex space-x-3">
                            <button type="submit" name="update_service" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Service
                            </button>
                            <a href="edit-services?stylist_id=<?= $stylist_id ?>" 
                               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Cancel
                            </a>
        </div>
    </form>
                </div>
                <?php else: ?>
                <!-- Services List -->
                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-700">All Services</h3>
                    <a href="add-services?stylist_id=<?= $stylist_id ?>" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        <i class="fas fa-plus mr-2"></i> Add New Service
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php while ($services && $row = $services->fetch_assoc()): ?>
                    <div class="service-card bg-white rounded-lg shadow overflow-hidden">
                        <div class="h-40 overflow-hidden">
                            <?php if (!empty($row['image'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($row['image']) ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($row['name']) ?>">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                <i class="fas fa-spa text-gray-300 text-5xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($row['name']) ?></h3>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-gray-600">RM <?= number_format($row['price'], 2) ?></span>
                                <?php if (isset($row['promotion']) && $row['promotion'] > 0): ?>
                                <span class="text-red-600 font-medium">
                                    RM <?= number_format($row['price_after_discount'], 2) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($row['promotion']) && $row['promotion'] > 0): ?>
                            <div class="mt-1 text-sm text-red-500">
                                <i class="fas fa-tags mr-1"></i> <?= $row['promotion'] ?>% Off
                            </div>
                            <?php endif; ?>
                            <div class="mt-3 text-sm text-gray-500">
                                <p><span class="font-medium">Duration:</span> <?= $row['duration'] ?> mins</p>
                                <p><span class="font-medium">Category:</span> <?= htmlspecialchars($row['category']) ?></p>
                                <p><span class="font-medium">Location:</span> <?= htmlspecialchars($row['location']) ?></p>
                                <p><span class="font-medium">Available:</span> <?= $row['available'] ? 'Yes' : 'No' ?></p>
                                <p class="mt-2 line-clamp-2"><?= htmlspecialchars($row['description']) ?></p>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 flex justify-between">
                            <a href="?id=<?= $row['id'] ?>&stylist_id=<?= $stylist_id ?>" 
                               class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <a href="?delete=<?= $row['id'] ?>&stylist_id=<?= $stylist_id ?>" 
                               class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700"
                               onclick="return confirm('Are you sure you want to delete this service?');">
                                <i class="fas fa-trash-alt mr-1"></i> Delete
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
</div>

<script>
        // Toggle sidebar on mobile
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('-translate-x-full');
        });

        // Image preview
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function(event) {
        const reader = new FileReader();
                reader.onload = function() {
            const img = document.getElementById('imagePreview');
            img.src = reader.result;
                    img.classList.remove('hidden');
                    
                    // Hide current image if it exists
                    const currentImg = document.getElementById('currentImage');
                    if (currentImg) {
                        currentImg.style.display = 'none';
                    }
        };
        reader.readAsDataURL(event.target.files[0]);
    });
        }
</script>
</body>
</html>
