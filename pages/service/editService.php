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

// Retrieve the service id from the URL
if (!isset($_GET['id'])) {
    echo "No service ID specified.";
    exit();
}
$service_id = (int)$_GET['id'];

// Fetch service details from the database
$result = $conn->query("SELECT * FROM services WHERE id = $service_id");

if ($result->num_rows === 0) {
    echo "Service not found.";
    exit();
}

$service = $result->fetch_assoc();

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
        $price_after_discount = $price - ($price * ($promotion / 100));
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
        echo "<script>alert('Service updated successfully!'); window.location.href = 'addService.php';</script>"; // Redirect after update
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Service</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding-top: 80px;
            margin: 0;
        }

        header {
            background-color: #28a745;
            color: white;
            padding: 15px;
            position: fixed;
            width: 100%;
            top: 0;
            display: flex;
            justify-content: space-between;
            z-index: 1000;
        }

        header a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            background-color: #45a049;
            padding: 10px 20px;
            border-radius: 5px;
        }

        header a:hover {
            background-color: #388e3c;
        }

        .container {
            padding: 30px;
            max-width: 1200px;
            margin: auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .image-box {
            flex: 1;
            text-align: center;
            margin-right: 20px;
        }

        .image-box img {
            max-width: 200px;
            margin-top: 15px;
            display: block;
            border-radius: 8px;
        }

        .form-box {
            flex: 2;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        input[type="checkbox"] {
            transform: scale(1.2);
            margin-top: 5px;
        }

        button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }
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
    <h2>Edit Service</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="image-box">
            <label for="image">Service Image:</label>
            <input type="file" id="image" name="image" accept="image/*">
            <?php if (!empty($service['image'])): ?>
                <img id="imagePreview" src="data:image/jpeg;base64,<?= base64_encode($service['image']) ?>" alt="Service Image" style="display:block;">
            <?php else: ?>
                <img id="imagePreview" alt="Preview" style="display:none;">
            <?php endif; ?>
        </div>

        <div class="form-box">
            <label for="name">Service Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($service['name']) ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($service['description']) ?></textarea>

            <label for="price">Price (RM):</label>
            <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($service['price']) ?>" required>

            <label for="duration">Duration (minutes):</label>
            <input type="number" id="duration" name="duration" value="<?= htmlspecialchars($service['duration']) ?>" required>

            <label for="location">Location:</label>
            <select id="location" name="location" required>
                <option value="<?= htmlspecialchars($service['location']) ?>" selected><?= htmlspecialchars($service['location']) ?></option>
                <option value="Johor">Johor</option>
                <option value="Kedah">Kedah</option>
                <option value="Kelantan">Kelantan</option>
                <option value="Melaka">Melaka</option>
                <option value="Negeri Sembilan">Negeri Sembilan</option>
                <option value="Pahang">Pahang</option>
                <option value="Perak">Perak</option>
                <option value="Perlis">Perlis</option>
                <option value="Pulau Pinang">Pulau Pinang</option>
                <option value="Sabah">Sabah</option>
                <option value="Sarawak">Sarawak</option>
                <option value="Selangor">Selangor</option>
                <option value="Terengganu">Terengganu</option>
            </select>

            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="Hair" <?= $service['category'] == 'Hair' ? 'selected' : '' ?>>Hair</option>
                <option value="Skincare" <?= $service['category'] == 'Skincare' ? 'selected' : '' ?>>Skincare</option>
                <option value="Massage" <?= $service['category'] == 'Massage' ? 'selected' : '' ?>>Massage</option>
            </select>

            <label for="available">Available:</label>
            <input type="checkbox" id="available" name="available" <?= $service['available'] ? 'checked' : '' ?>>

            <label for="promotion">Promotion Percentage:</label>
            <input type="number" id="promotion" name="promotion" value="<?= htmlspecialchars($service['promotion']) ?>" step="0.01">

            <label for="price_after_discount">Price After Discount (RM):</label>
            <input type="number" id="price_after_discount" name="price_after_discount" value="<?= htmlspecialchars($service['price_after_discount']) ?>" step="0.01" disabled>

            <br><br>
            <button type="submit" name="update_service">Update Service</button>
        </div>
    </form>
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

    document.getElementById('promotion').addEventListener('input', function () {
        const price = parseFloat(document.getElementById('price').value);
        const promotion = parseFloat(this.value);
        const price_after_discount = price - (price * (promotion / 100));
        document.getElementById('price_after_discount').value = price_after_discount.toFixed(2);
    });
</script>

</body>
</html>

<?php
$conn->close();
?>
