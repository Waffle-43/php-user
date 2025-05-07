<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'php-starter';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_GET['delete'])) {
    $idToDelete = (int)$_GET['delete'];
    $conn->query("DELETE FROM services WHERE id = $idToDelete");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
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

    $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, location, category, available, image, promotion, price_after_discount)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $null = NULL;
    $stmt->bind_param("ssdissibid", $name, $description, $price, $duration, $location, $category, $available, $null, $promotion, $price_after_discount);
    
    if ($imageData !== null) {
        $stmt->send_long_data(7, $imageData);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Service added successfully!');</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$services = $conn->query("SELECT * FROM services");
?>

<!-- HTML Starts -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Service</title>
    <style>
        /* [keep your existing styles here] */
        body { font-family: Arial, sans-serif; padding-top: 80px; margin: 0; }
        header { background-color: #28a745; color: white; padding: 15px; position: fixed; width: 100%; top: 0; display: flex; justify-content: space-between; z-index: 1000; }
        header a { color: white; text-decoration: none; margin: 0 15px; background-color: #45a049; padding: 10px 20px; border-radius: 5px; }
        .container { padding: 30px; max-width: 1200px; margin: auto; }
        form { display: flex; flex-wrap: wrap; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .image-box { flex: 1; text-align: center; margin-right: 20px; }
        .image-box img { max-width: 200px; margin-top: 15px; display: none; border-radius: 8px; }
        .form-box { flex: 2; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="checkbox"] { transform: scale(1.2); margin-top: 5px; }
        button { padding: 10px 20px; background-color: #28a745; color: white; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .services-list { margin-top: 50px; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; }
        .service-card { width: 220px; background: #f4f4f4; border-radius: 10px; padding: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; }
        .service-card img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; }
        .service-card h3 { margin: 10px 0; font-size: 16px; text-align: center; }
        .service-card p { font-size: 14px; margin: 4px 0; }
        .actions { display: flex; justify-content: space-around; margin-top: 10px; }
        .actions a, .actions button { font-size: 12px; padding: 6px 10px; border: none; border-radius: 5px; text-decoration: none; }
        .actions .edit-btn { background-color: #ffc107; color: white; }
        .actions .delete-btn { background-color: #dc3545; color: white; }
    </style>
</head>
<body>

<header>
    <div>
        <a href="#">Search</a>
        <a href="#">Home</a>
    </div>
    <div>
        <a href="#">User</a>
        <a href="#">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Add New Service</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="image-box">
            <label for="image">Service Image:</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <img id="imagePreview" alt="Preview">
        </div>
        <div class="form-box">
            <label for="name">Service Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="price">Price (RM):</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <label for="promotion">Promotion (%):</label>
            <input type="number" id="promotion" name="promotion" min="0" max="100" value="0">

            <label for="duration">Duration (minutes):</label>
            <input type="number" id="duration" name="duration" required>

            <label for="location">Location:</label>
            <select id="location" name="location" required>
                <?php
                $states = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
                foreach ($states as $state) {
                    echo "<option value=\"$state\">$state</option>";
                }
                ?>
            </select>

            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="Hair">Hair</option>
                <option value="Skincare">Skincare</option>
                <option value="Massage">Massage</option>
            </select>

            <label for="available">Available:</label>
            <input type="checkbox" id="available" name="available" checked>
            <br><br>
            <button type="submit" name="add_service">Add Service</button>
        </div>
    </form>

    <h2>Existing Services</h2>
    <div class="services-list">
        <?php while ($row = $services->fetch_assoc()): ?>
            <div class="service-card">
                <?php if (!empty($row['image'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($row['image']) ?>" alt="Service Image">
                <?php endif; ?>
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p><strong>Original:</strong> RM <?= number_format($row['price'], 2) ?></p>
                <?php if ($row['promotion'] > 0): ?>
                    <p><strong>Promotion:</strong> <?= $row['promotion'] ?>% off</p>
                    <p><strong>Now:</strong> <span style="color:red;">RM <?= number_format($row['price_after_discount'], 2) ?></span></p>
                <?php endif; ?>
                <p><strong>Duration:</strong> <?= $row['duration'] ?> mins</p>
                <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                <p><strong>Available:</strong> <?= $row['available'] ? 'Yes' : 'No' ?></p>
                <div class="actions">
                    <a href="editService.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

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
</script>

</body>
</html>

<?php $conn->close(); ?>
