<?php
include 'config.php';

// Check if stylist_id is set (for testing purposes)
$stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 1;

// Get stylist information
$stmt = $conn->prepare("SELECT * FROM stylists WHERE id = ?");
$stmt->execute([$stylist_id]);
$stylist = $stmt->fetch();

if (!$stylist) {
    die("Stylist not found");
}

// Handle delete request
if (isset($_GET['delete'])) {
    $idToDelete = (int)$_GET['delete'];
    $deleteStmt = $conn->prepare("UPDATE services SET available = 0 WHERE id = ?");
    $deleteStmt->execute([$idToDelete]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?stylist_id=" . $stylist_id);
    exit();
}

// Handle form submission for adding/updating service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $location = $_POST['location'];
    $category = $_POST['category'];
    $available = isset($_POST['available']) ? 1 : 0;
    $promotion = isset($_POST['promotion']) ? intval($_POST['promotion']) : 0;

    // Calculate discounted price
    $price_after_discount = $price;
    if ($promotion > 0 && $promotion <= 100) {
        $price_after_discount = $price - ($price * $promotion / 100);
    }

    $imageData = null;
    if ($_FILES['image']['error'] === 0) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
    }

    if (isset($_POST['service_id']) && $_POST['service_id'] > 0) {
        // Update existing service
        $service_id = (int)$_POST['service_id'];
        
        // Check if image is being updated
        if ($imageData !== null) {
            $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, 
                                    location = ?, category = ?, available = ?, image = ?, promotion = ?, 
                                    price_after_discount = ? WHERE id = ?");
            $null = NULL;
            $stmt->bindParam(1, $name);
            $stmt->bindParam(2, $description);
            $stmt->bindParam(3, $price);
            $stmt->bindParam(4, $duration);
            $stmt->bindParam(5, $location);
            $stmt->bindParam(6, $category);
            $stmt->bindParam(7, $available, PDO::PARAM_INT);
            $stmt->bindParam(8, $imageData, PDO::PARAM_LOB);
            $stmt->bindParam(9, $promotion, PDO::PARAM_INT);
            $stmt->bindParam(10, $price_after_discount);
            $stmt->bindParam(11, $service_id, PDO::PARAM_INT);
        } else {
            // Skip image update if no new image
            $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, 
                                   location = ?, category = ?, available = ?, promotion = ?, 
                                   price_after_discount = ? WHERE id = ?");
            $stmt->bindParam(1, $name);
            $stmt->bindParam(2, $description);
            $stmt->bindParam(3, $price);
            $stmt->bindParam(4, $duration);
            $stmt->bindParam(5, $location);
            $stmt->bindParam(6, $category);
            $stmt->bindParam(7, $available, PDO::PARAM_INT);
            $stmt->bindParam(8, $promotion, PDO::PARAM_INT);
            $stmt->bindParam(9, $price_after_discount);
            $stmt->bindParam(10, $service_id, PDO::PARAM_INT);
        }
        
        if ($stmt->execute()) {
            $success_message = "Service updated successfully!";
        } else {
            $error_message = "Error updating service: " . implode(" ", $stmt->errorInfo());
        }
    } else {
        // Add new service
        $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, location, category, 
                               available, image, promotion, price_after_discount) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $price);
        $stmt->bindParam(4, $duration);
        $stmt->bindParam(5, $location);
        $stmt->bindParam(6, $category);
        $stmt->bindParam(7, $available, PDO::PARAM_INT);
        $stmt->bindParam(8, $imageData, PDO::PARAM_LOB);
        $stmt->bindParam(9, $promotion, PDO::PARAM_INT);
        $stmt->bindParam(10, $price_after_discount);
        
        if ($stmt->execute()) {
            $success_message = "Service added successfully!";
        } else {
            $error_message = "Error adding service: " . implode(" ", $stmt->errorInfo());
        }
    }
}

// Check if we're editing an existing service
$editing = false;
$service = null;
if (isset($_GET['edit'])) {
    $service_id = (int)$_GET['edit'];
    $serviceStmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $serviceStmt->execute([$service_id]);
    $service = $serviceStmt->fetch();
    if ($service) {
        $editing = true;
    }
}

// Get all services
$servicesStmt = $conn->prepare("SELECT * FROM services WHERE available = 1 ORDER BY id DESC");
$servicesStmt->execute();
$services = $servicesStmt->fetchAll();

// Get categories
$categoriesStmt = $conn->prepare("SELECT DISTINCT category FROM services WHERE available = 1 ORDER BY category");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($categories)) {
    $categories = ["Hair", "Skincare", "Massage", "Spa"];
}

// List of Malaysian states for location dropdown
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 
    'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'
];

// Get notifications for this stylist
$notifStmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 10
");
$notifStmt->execute([$stylist_id]);
$notifications = $notifStmt->fetchAll();
$notificationCount = count($notifications);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider - Manage Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #6B46C1;
            --primary-light: #9F7AEA;
            --secondary-color: #4A5568;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7fafc;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .flex {
            display: flex;
        }
        
        .flex-1 {
            flex: 1;
        }
        
        .flex-col {
            flex-direction: column;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .h-screen {
            height: 100vh;
        }
        
        .overflow-hidden {
            overflow: hidden;
        }
        
        .overflow-auto {
            overflow: auto;
        }
        
        .text-white {
            color: white;
        }
        
        .text-gray-600 {
            color: #4a5568;
        }
        
        .bg-white {
            background-color: white;
        }
        
        .shadow-sm {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .p-4 {
            padding: 1rem;
        }
        
        .p-6 {
            padding: 1.5rem;
        }
        
        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .px-3 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        .mb-1 {
            margin-bottom: 0.25rem;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        
        .mb-4 {
            margin-bottom: 1rem;
        }
        
        .mb-6 {
            margin-bottom: 1.5rem;
        }
        
        .mr-2 {
            margin-right: 0.5rem;
        }
        
        .mt-1 {
            margin-top: 0.25rem;
        }
        
        .mt-4 {
            margin-top: 1rem;
        }
        
        .w-64 {
            width: 16rem;
        }
        
        .text-xl {
            font-size: 1.25rem;
        }
        
        .text-xs {
            font-size: 0.75rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .rounded {
            border-radius: 0.25rem;
        }
        
        .rounded-lg {
            border-radius: 0.5rem;
        }
        
        .border-b {
            border-bottom-width: 1px;
        }
        
        .block {
            display: block;
        }
        
        .hidden {
            display: none;
        }
        
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        @media (min-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            
            .md\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar a {
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .border-indigo-400 {
            border-color: #7f9cf5;
        }
        
        .border-opacity-20 {
            border-opacity: 0.2;
        }
        
        .text-indigo-100 {
            color: #c3dafe;
        }
        
        .text-indigo-200 {
            color: #a3bffa;
        }
        
        /* Service Cards */
        .service-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            background-color: white;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .service-img {
            height: 160px;
            overflow: hidden;
        }
        
        .service-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .service-body {
            padding: 1rem;
        }
        
        .service-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .service-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .service-price .original {
            color: #4a5568;
            font-weight: 500;
        }
        
        .service-price .discounted {
            color: #e53e3e;
            font-weight: 700;
        }
        
        .service-details {
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #718096;
        }
        
        .service-details p {
            margin-bottom: 0.25rem;
        }
        
        .service-footer {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            outline: none;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #553c9a;
        }
        
        .btn-danger {
            background-color: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c53030;
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.5rem;
        }
        
        .form-group {
            padding: 0 0.5rem;
            margin-bottom: 1rem;
            flex: 1 0 100%;
        }
        
        @media (min-width: 768px) {
            .form-group.half {
                flex: 0 0 50%;
            }
            
            .form-group.third {
                flex: 0 0 33.333333%;
            }
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e0;
            border-radius: 0.375rem;
            font-size: 1rem;
            line-height: 1.5;
            transition: border-color 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(159, 122, 234, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .form-check-input {
            margin-right: 0.5rem;
            transform: scale(1.2);
        }
        
        .image-preview {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            margin-top: 0.5rem;
            border-radius: 0.375rem;
            display: none;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
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
                    <a href="service_provider_dashboard.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-spa mr-2"></i> Dashboard
                    </a>
                    <a href="service_provider_calendar.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i> Calendar
                    </a>
                    <a href="service_provider_all_appointments.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded hover:bg-white hover:bg-opacity-10 mb-1">
                        <i class="fas fa-calendar-check mr-2"></i> All Appointments
                    </a>
                    <a href="service_provider_manage_services.php?stylist_id=<?= $stylist_id ?>" class="block py-2 px-3 rounded bg-white bg-opacity-10 mb-1">
                        <i class="fas fa-concierge-bell mr-2"></i> Manage Services
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
                        <h2 class="text-lg font-semibold">Manage Services</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button id="notifications-btn" class="text-gray-600 hover:text-purple-600 relative">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if ($notificationCount > 0): ?>
                                <span class="notification-badge absolute -top-2 -right-2 bg-red-500 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs"><?= $notificationCount ?></span>
                                <?php endif; ?>
                            </button>
                        </div>
                        <div class="relative">
                            <div class="flex items-center">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($stylist['name']) ?>&background=random" 
                                     class="h-8 w-8 rounded-full mr-2" alt="<?= htmlspecialchars($stylist['name']) ?>">
                                <span class="text-sm font-medium"><?= htmlspecialchars($stylist['name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Service Management Content -->
            <div class="p-6">
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?= $error_message ?>
                </div>
                <?php endif; ?>

                <!-- Service Form -->
                <div class="form-container">
                    <h3 class="form-title"><?= $editing ? 'Edit Service' : 'Add New Service' ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($editing): ?>
                        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label class="form-label" for="name">Service Name</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?= $editing ? htmlspecialchars($service['name']) : '' ?>">
                            </div>
                            
                            <div class="form-group half">
                                <label class="form-label" for="category">Category</label>
                                <select class="form-control" id="category" name="category" required>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $editing && $service['category'] == $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" required><?= $editing ? htmlspecialchars($service['description']) : '' ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group third">
                                <label class="form-label" for="price">Price (RM)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required
                                       value="<?= $editing ? $service['price'] : '' ?>">
                            </div>
                            
                            <div class="form-group third">
                                <label class="form-label" for="promotion">Promotion (%)</label>
                                <input type="number" class="form-control" id="promotion" name="promotion" min="0" max="100"
                                       value="<?= $editing ? $service['promotion'] : '0' ?>">
                            </div>
                            
                            <div class="form-group third">
                                <label class="form-label" for="duration">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" required
                                       value="<?= $editing ? $service['duration'] : '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label class="form-label" for="location">Location</label>
                                <select class="form-control" id="location" name="location" required>
                                    <?php foreach ($states as $state): ?>
                                    <option value="<?= htmlspecialchars($state) ?>" <?= $editing && $service['location'] == $state ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($state) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group half">
                                <label class="form-label" for="image">Service Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" <?= $editing ? '' : 'required' ?>>
                                <?php if ($editing && !empty($service['image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($service['image']) ?>" alt="Current Service Image" id="currentImage" class="image-preview" style="display: block;">
                                <?php endif; ?>
                                <img id="imagePreview" class="image-preview" alt="Image Preview">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="available" name="available" <?= (!$editing || $service['available']) ? 'checked' : '' ?>>
                                    <label class="form-label" for="available">Service Available</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <?= $editing ? 'Update Service' : 'Add Service' ?>
                                </button>
                                <?php if ($editing): ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>?stylist_id=<?= $stylist_id ?>" class="btn btn-danger" style="margin-left: 10px;">
                                    Cancel
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Services List -->
                <h3 class="text-lg font-semibold text-gray-700 mb-4">All Services</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($services as $srv): ?>
                    <div class="service-card">
                        <div class="service-img">
                            <?php if (!empty($srv['image'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($srv['image']) ?>" alt="<?= htmlspecialchars($srv['name']) ?>">
                            <?php else: ?>
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; background-color: #f7fafc;">
                                <i class="fas fa-spa" style="font-size: 3rem; color: #cbd5e0;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="service-body">
                            <h3 class="service-title"><?= htmlspecialchars($srv['name']) ?></h3>
                            <div class="service-price">
                                <span class="original">RM <?= number_format($srv['price'], 2) ?></span>
                                <?php if ($srv['promotion'] > 0): ?>
                                <span class="discounted">RM <?= number_format($srv['price_after_discount'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($srv['promotion'] > 0): ?>
                            <div class="text-sm" style="color: #e53e3e; margin-bottom: 0.5rem;">
                                <i class="fas fa-tags mr-1"></i> <?= $srv['promotion'] ?>% Off
                            </div>
                            <?php endif; ?>
                            <div class="service-details">
                                <p><strong>Duration:</strong> <?= $srv['duration'] ?> minutes</p>
                                <p><strong>Category:</strong> <?= htmlspecialchars($srv['category']) ?></p>
                                <p><strong>Location:</strong> <?= htmlspecialchars($srv['location']) ?></p>
                                <p><strong>Available:</strong> <?= $srv['available'] ? 'Yes' : 'No' ?></p>
                                <p class="mt-2"><strong>Description:</strong></p>
                                <p><?= nl2br(htmlspecialchars(substr($srv['description'], 0, 100))) ?>...</p>
                            </div>
                        </div>
                        <div class="service-footer">
                            <a href="?stylist_id=<?= $stylist_id ?>&edit=<?= $srv['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <a href="?stylist_id=<?= $stylist_id ?>&delete=<?= $srv['id'] ?>" class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this service?');">
                                <i class="fas fa-trash-alt mr-1"></i> Delete
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('hidden');
        });
        
        // Image preview
        document.getElementById('image').addEventListener('change', function(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const img = document.getElementById('imagePreview');
                img.src = reader.result;
                img.style.display = 'block';
                
                // Hide current image if it exists
                const currentImg = document.getElementById('currentImage');
                if (currentImg) {
                    currentImg.style.display = 'none';
                }
            };
            reader.readAsDataURL(event.target.files[0]);
        });
    </script>
</body>
</html> 