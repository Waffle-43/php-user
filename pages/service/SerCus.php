<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'php-starter';

// Establish the database connection
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get filter criteria
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$available = isset($_GET['available']) ? $_GET['available'] : '';

// Base query
$sql = "SELECT * FROM services WHERE 1";

if ($category) {
    $sql .= " AND category = '$category'";
}
if ($location) {
    $sql .= " AND location = '$location'";
}
if ($available !== '') {
    $sql .= " AND available = '$available'";
}

$result = $conn->query($sql);
$filtersApplied = $category || $location || $available !== '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salon & Spa Service Filter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        header {
            background-color: #4CAF50;
            color: white;
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header-left, .header-right {
            display: flex;
            align-items: center;
        }

        .header-left a, .header-right a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: 16px;
            padding: 10px 20px;
            background-color: #388e3c;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header-left a:hover, .header-right a:hover {
            background-color: #45a049;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 60px;
        }

        h1, h2 {
            text-align: center;
        }

        form {
            margin: 20px auto;
            max-width: 600px;
            padding: 10px;
            background-color: #f4f4f4;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        form label {
            width: 100%;
            margin-bottom: 5px;
        }

        form select, form button {
            padding: 5px;
            margin: 10px 0;
            width: 48%;
        }

        .full-width {
            width: 100%;
        }

        .second-row {
            width: 100%;
            margin-top: 10px;
        }

        .services-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin: 30px;
        }

        .service {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 8px;
            background-color: #f9f9f9;
            width: 250px;
            box-sizing: border-box;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .service img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
        }

        .service h3 {
            margin-top: 10px;
            font-size: 18px;
        }

        .service p {
            font-size: 14px;
            margin: 4px 0;
        }

        .service button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .service button:hover {
            background-color: #45a049;
        }

        .service .unavailable {
            background-color: #d32f2f;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .service {
                width: 45%;
            }
        }

        @media (max-width: 480px) {
            .service {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <a href="#">Search</a>
        <a href="#">Home</a>
    </div>
    <div class="header-right">
        <a href="#">User</a>
        <a href="#">Logout</a>
    </div>
</header>

<h1>Salon & Spa Services</h1>

<form method="get" action="">
    <div class="full-width">
        <label for="category">Category:</label>
        <select name="category" id="category">
            <option value="">All</option>
            <option value="Hair" <?= $category === 'Hair' ? 'selected' : '' ?>>Hair</option>
            <option value="Skincare" <?= $category === 'Skincare' ? 'selected' : '' ?>>Skincare</option>
            <option value="Massage" <?= $category === 'Massage' ? 'selected' : '' ?>>Massage</option>
        </select>
    </div>

    <div class="full-width">
        <label for="location">Location:</label>
        <select name="location" id="location">
            <option value="">All</option>
            <?php
            $states = ["Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang", "Perak", "Perlis", "Penang", "Sabah", "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur", "Putrajaya"];
            foreach ($states as $state) {
                echo "<option value=\"$state\"" . ($location === $state ? ' selected' : '') . ">$state</option>";
            }
            ?>
        </select>
    </div>

    <div class="second-row">
        <label for="available">Availability:</label>
        <select name="available" id="available">
            <option value="">All</option>
            <option value="1" <?= $available === '1' ? 'selected' : '' ?>>Available</option>
            <option value="0" <?= $available === '0' ? 'selected' : '' ?>>Not Available</option>
        </select>
    </div>

    <div class="full-width">
        <button type="submit">Filter</button>
    </div>
</form>

<?php if ($filtersApplied || $result->num_rows > 0): ?>
    <h2>Our Services</h2>

    <div class="services-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="service">
                    <h3><?= htmlspecialchars($row['name']) ?></h3>

                    <?php if (!empty($row['image'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($row['image']) ?>" alt="Service Image">
                    <?php endif; ?>

                    <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                    <p><strong>Duration:</strong> <?= $row['duration'] ?> mins</p>
                    <p><strong>Price:</strong> RM <?= number_format($row['price_after_discount'], 2) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                    <p><strong>Availability:</strong> <?= $row['available'] ? 'Available' : 'Not Available' ?></p>

                    <!-- Book Button (Conditional on Availability) -->
                    <?php if ($row['available'] == 1): ?>
                        <form method="GET" action="bookService.php">
                            <input type="hidden" name="service_id" value="<?= $row['id'] ?>">
                            <button type="submit">Book</button>
                        </form>
                    <?php else: ?>
                        <button class="unavailable" disabled>Unavailable</button>
                    <?php endif; ?>
                </div>
            <?php } ?>
        <?php else: ?>
            <p style="text-align:center;">No services found matching the selected filters.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
