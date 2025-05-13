<?php
require_once __DIR__ . '/../utils_files/config.php'; // Config

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
    $categories = ["Hair", "Skincare", "Massage"];
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
    <title>Service Provider - Manage Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 60px;
        }
        
        .navbar {
            background-color: #9F7AEA;
        }
        
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 250px;
            background-color: #6B46C1;
            color: white;
            min-height: 100%;
        }
        
        .sidebar-menu a {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            display: block;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .service-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .services-list {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }
        
        .service-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .service-card .card-img-container {
            height: 160px;
            overflow: hidden;
        }
        
        .service-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .service-card .card-body {
            flex: 1;
        }
        
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.7);
        }
        
        .service-price {
            font-weight: 600;
            color: #2D3748;
        }
        
        .service-discount {
            color: #E53E3E;
            font-weight: 500;
        }
        
        .filter-section {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-spa mr-2"></i>
                Harmony Heaven Spa
            </a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge badge-danger">3</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#">New appointment at 3:00 PM</a>
                            <a class="dropdown-item" href="reschedule-services">Client rescheduled to tomorrow</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="all-notifications">View all notifications</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-user-circle mr-1"></i>
                            <?= htmlspecialchars($stylist['name']) ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile"><i class="fas fa-user mr-2"></i>Profile</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-cog mr-2"></i>Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="enhanced-dashboard?stylist_id=<?= $stylist_id ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="all-appointments?stylist_id=<?= $stylist_id ?>">
                    <i class="far fa-calendar-alt"></i> All Appointments
                </a>
                <a href="add-appointments?stylist_id=<?= $stylist_id ?>">
                    <i class="fas fa-plus-circle"></i> Add Appointment
                </a>
                <a href="manage-appointment?stylist_id=<?= $stylist_id ?>">
                    <i class="fas fa-edit"></i> Manage Appointments
                </a>
                <a href="calendar-appt?stylist_id=<?= $stylist_id ?>">
                    <i class="fas fa-calendar-check"></i> Calendar View
                </a>
                <a href="serviceprovider-services?stylist_id=<?= $stylist_id ?>" class="active">
                    <i class="fas fa-spa"></i> Manage Services
                </a>
                <a href="reschedule-services?stylist_id=<?= $stylist_id ?>">
                    <i class="fas fa-sync-alt"></i> Reschedule
                </a>
                <a href="#">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-spa text-primary mr-2"></i>
                    <?= $editing ? 'Edit Service' : 'Manage Services' ?>
                </h2>
                
                <?php if (!$editing): ?>
                    <button class="btn btn-primary" data-toggle="collapse" data-target="#addServiceForm">
                        <i class="fas fa-plus mr-2"></i>Add New Service
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $success_message ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= $error_message ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Service Form -->
            <div id="addServiceForm" class="service-form <?= $editing ? '' : 'collapse' ?>">
                <h4 class="mb-4"><?= $editing ? 'Edit Service' : 'Add New Service' ?></h4>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editing): ?>
                        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="image">Service Image</label>
                                <input type="file" class="form-control-file" id="image" name="image" accept="image/*" <?= $editing ? '' : 'required' ?>>
                                
                                <?php if ($editing && !empty($service['image'])): ?>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="data:image/jpeg;base64,<?= base64_encode($service['image']) ?>" 
                                             alt="Current Image" class="img-thumbnail" style="max-height: 150px;">
                                        <p class="small text-muted mt-1">Current image will be kept if no new image is selected</p>
                                    </div>
                                <?php else: ?>
                                    <img id="imagePreview" src="" alt="Preview" class="img-thumbnail mt-2" style="max-height: 150px; display: none;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Service Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= $editing ? htmlspecialchars($service['name']) : '' ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category">Category</label>
                                        <select class="form-control" id="category" name="category" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= htmlspecialchars($cat) ?>" 
                                                        <?= ($editing && $service['category'] === $cat) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">Price (RM)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" 
                                               value="<?= $editing ? htmlspecialchars($service['price']) : '' ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="promotion">Promotion (%)</label>
                                        <input type="number" class="form-control" id="promotion" name="promotion" min="0" max="100" 
                                               value="<?= $editing ? htmlspecialchars($service['promotion']) : '0' ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="duration">Duration (minutes)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" min="5" step="5" 
                                               value="<?= $editing ? htmlspecialchars($service['duration']) : '60' ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <select class="form-control" id="location" name="location" required>
                                            <?php foreach ($states as $state): ?>
                                                <option value="<?= htmlspecialchars($state) ?>" 
                                                        <?= ($editing && $service['location'] === $state) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($state) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required><?= $editing ? htmlspecialchars($service['description']) : '' ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="available" name="available" 
                                               <?= (!$editing || $service['available']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="available">Available</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mt-3">
                                    <div class="form-group mb-0">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i><?= $editing ? 'Update Service' : 'Add Service' ?>
                                        </button>
                                        
                                        <?php if ($editing): ?>
                                            <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times mr-2"></i>Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Filter Section (only show when not editing) -->
            <?php if (!$editing): ?>
                <div class="filter-section">
                    <div class="card">
                        <div class="card-body p-3">
                            <form method="GET" action="" class="form-inline">
                                <input type="hidden" name="stylist_id" value="<?= $stylist_id ?>">
                                
                                <div class="form-group mr-3">
                                    <label for="filter_category" class="mr-2">Category:</label>
                                    <select class="form-control form-control-sm" id="filter_category" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" 
                                                    <?= (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-3">
                                    <label for="filter_available" class="mr-2">Status:</label>
                                    <select class="form-control form-control-sm" id="filter_available" name="available">
                                        <option value="">All</option>
                                        <option value="1" <?= (isset($_GET['available']) && $_GET['available'] === '1') ? 'selected' : '' ?>>Available</option>
                                        <option value="0" <?= (isset($_GET['available']) && $_GET['available'] === '0') ? 'selected' : '' ?>>Unavailable</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                
                                <?php if (isset($_GET['category']) || isset($_GET['available'])): ?>
                                    <a href="service_provider_services.php?stylist_id=<?= $stylist_id ?>" class="btn btn-sm btn-secondary ml-2">
                                        Clear Filters
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Services List -->
            <?php if (!$editing): ?>
                <div class="services-list">
                    <h4 class="mb-4">Your Services</h4>
                    
                    <?php if (empty($services)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No services found. Add your first service using the form above.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($services as $service_item): ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card service-card">
                                        <div class="card-img-container">
                                            <?php if (!empty($service_item['image'])): ?>
                                                <img src="data:image/jpeg;base64,<?= base64_encode($service_item['image']) ?>" alt="<?= htmlspecialchars($service_item['name']) ?>">
                                            <?php else: ?>
                                                <img src="../images/spa_service.jpg" alt="Default Service Image">
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($service_item['name']) ?></h5>
                                            <p class="card-text small text-muted"><?= htmlspecialchars($service_item['category']) ?></p>
                                            
                                            <?php if ((int)$service_item['promotion'] > 0): ?>
                                                <p class="mb-1">
                                                    <span class="text-muted text-decoration-line-through">RM<?= number_format($service_item['price'], 2) ?></span>
                                                    <span class="service-discount ml-2">
                                                        <i class="fas fa-tag mr-1"></i><?= (int)$service_item['promotion'] ?>% OFF
                                                    </span>
                                                </p>
                                                <p class="service-price mb-2">RM<?= number_format($service_item['price_after_discount'], 2) ?></p>
                                            <?php else: ?>
                                                <p class="service-price mb-2">RM<?= number_format($service_item['price'], 2) ?></p>
                                            <?php endif; ?>
                                            
                                            <p class="card-text small">
                                                <i class="far fa-clock mr-1"></i><?= (int)$service_item['duration'] ?> minutes
                                            </p>
                                            
                                            <?php if ((int)$service_item['available']): ?>
                                                <p class="mb-2"><span class="badge badge-success">Available</span></p>
                                            <?php else: ?>
                                                <p class="mb-2"><span class="badge badge-secondary">Unavailable</span></p>
                                            <?php endif; ?>
                                            
                                            <div class="text-center mt-3">
                                                <a href="?stylist_id=<?= $stylist_id ?>&edit=<?= $service_item['id'] ?>" class="btn btn-sm btn-primary mr-1">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </a>
                                                <a href="?stylist_id=<?= $stylist_id ?>&delete=<?= $service_item['id'] ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this service?');">
                                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('image').addEventListener('change', function (event) {
            const reader = new FileReader();
            reader.onload = function () {
                const img = document.getElementById('imagePreview');
                img.src = reader.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        });

        // Calculate discounted price automatically
        document.getElementById('promotion').addEventListener('input', function () {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const promotion = parseFloat(this.value) || 0;
            
            if (price > 0 && promotion > 0) {
                const discountedPrice = price - (price * (promotion / 100));
                console.log(`Original: ${price}, Promotion: ${promotion}%, Discounted: ${discountedPrice.toFixed(2)}`);
            }
        });
        
        // Show success message then fade out
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert-success').fadeOut('slow');
            }, 3000);
        });
    </script>
</body>
</html> 