<?php
// Updated database connection to use the correct database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'salon_spa';

// Establish the database connection
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get filter criteria
$category = isset($_GET['category']) ? $_GET['category'] : '';
$available = isset($_GET['available']) ? $_GET['available'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query - adjust field names to match the salon_spa database structure
$sql = "SELECT * FROM services WHERE available = 1";

// Use prepared statements to prevent SQL injection
$params = [];
$types = '';

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($available !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $available;
    $types .= 'i';
}

// Debugging info
$debug = false; // Set to true to see query info for debugging

// Execute the query with prepared statement if there are parameters
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($debug) {
            echo "<!-- Prepared query executed: $sql with params: " . implode(', ', $params) . " -->";
        }
        $stmt->close();
    } else {
        if ($debug) {
            echo "<!-- Prepare failed: " . $conn->error . " -->";
        }
        $result = false;
    }
} else {
    $result = $conn->query($sql);
    if ($debug) {
        echo "<!-- Direct query executed: $sql -->";
    }
}

$filtersApplied = $search || $category || $available !== '';

// Use default categories if query fails
$categories = ["Hair", "Facial", "Nail"];
$catResult = $conn->query("SELECT DISTINCT category FROM services WHERE available = 1 ORDER BY category");
if ($catResult && $catResult->num_rows > 0) {
    $categories = [];
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// We don't have locations in the database, so we'll remove that filter
$locations = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salon & Spa Service Filter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Updated to match homepage theme */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding-top: 60px;
            background-color: #FFF5F5;
        }
        
        header {
            background-color: #9F7AEA; /* Primary purple color */
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
            background-color: #8657e0; /* Darker purple */
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header-left a:hover, .header-right a:hover {
            background-color: #7c51cf;
        }

        h1, h2 {
            text-align: center;
            color: #2D3748; /* Dark color from homepage */
        }

        form {
            margin: 20px auto;
            max-width: 600px;
            padding: 10px;
            background-color: #FFFFFF;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        form label {
            width: 100%;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2D3748;
        }

        form select, form button, .search-input {
            padding: 8px;
            margin: 10px 0;
            width: 48%;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #9F7AEA;
            box-shadow: 0 0 0 3px rgba(159, 122, 234, 0.2);
            outline: none;
        }
        
        .search-results {
            text-align: center;
            margin: 20px 0;
            color: #4A5568;
            font-size: 16px;
        }
        
        form button {
            background-color: #9F7AEA;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        form button:hover {
            background-color: #8657e0;
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
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 12px;
            background-color: #FFFFFF;
            width: 250px;
            box-sizing: border-box;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .service:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .service img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .service h3 {
            margin-top: 10px;
            font-size: 18px;
            color: #2D3748;
        }

        .service p {
            font-size: 14px;
            margin: 4px 0;
            color: #4A5568;
        }

        .service button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #9F7AEA;
            color: white;
            border: none;
            border-radius: 30px; /* Rounded button */
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .service button:hover {
            background-color: #8657e0;
        }

        .service .unavailable {
            background-color: #F687B3; /* Secondary color from homepage */
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
        <a href="../../integrated_homepage.php">Home</a>
    </div>
    <div class="header-right">
        <a href="../../integrated_homepage.php">Back</a>
    </div>
</header>

<h1>Glamour Haven Services</h1>

<form method="get" action="">
    <div class="full-width">
        <label for="search">Search Services:</label>
        <input type="text" name="search" id="search" placeholder="Search by name or description" value="<?= htmlspecialchars($search) ?>" class="search-input">
    </div>
    
    <div class="full-width">
        <label for="category">Category:</label>
        <select name="category" id="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="second-row">
        <label for="available">Availability:</label>
        <select name="available" id="available">
            <option value="">All Availability</option>
            <option value="1" <?= ($available === '1') ? 'selected' : '' ?>>Available</option>
            <option value="0" <?= ($available === '0') ? 'selected' : '' ?>>Not Available</option>
        </select>
    </div>

    <div class="full-width">
        <button type="submit">Search & Filter</button>
    </div>
</form>

<?php if ($filtersApplied || ($result && $result->num_rows > 0)): ?>
    <h2>Our Services</h2>

    <?php if ($search): ?>
        <div class="search-results">
            <?php if ($result && $result->num_rows > 0): ?>
                <p>Found <?= $result->num_rows ?> result<?= $result->num_rows > 1 ? 's' : '' ?> for "<strong><?= htmlspecialchars($search) ?></strong>"</p>
            <?php else: ?>
                <p>No results found for "<strong><?= htmlspecialchars($search) ?></strong>". Try different keywords or browse all services.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="services-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="service">
                    <h3><?= htmlspecialchars($row['name'] ?? 'Unnamed Service') ?></h3>

                    <?php
                    // Choose appropriate image based on service name
                    $serviceName = strtolower($row['name'] ?? '');
                    if (strpos($serviceName, 'facial') !== false) {
                        $imagePath = '../../facial.jpg';
                    } elseif (strpos($serviceName, 'hair color') !== false || strpos($serviceName, 'coloring') !== false) {
                        $imagePath = '../../hair_coloring.jpg';
                    } elseif (strpos($serviceName, 'haircut') !== false || strpos($serviceName, 'styling') !== false || strpos($serviceName, 'hair') !== false) {
                        $imagePath = '../../haircut_style.jpg';
                    } elseif (strpos($serviceName, 'manicure') !== false) {
                        $imagePath = '../../manicure.jpg';
                    } elseif (strpos($serviceName, 'pedicure') !== false) {
                        // Using the updated pedicure image
                        $imagePath = '../../pedicure.jpg';
                    } else {
                        $imagePath = '../../spa_service.jpg';
                    }
                    ?>
                    <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['name'] ?? 'Service') ?>">

                    <p><strong>Description:</strong> <?= isset($row['description']) ? htmlspecialchars($row['description']) : 'No description available' ?></p>
                    <p><strong>Duration:</strong> <?= isset($row['duration']) ? $row['duration'] : '0' ?> mins</p>
                    <p><strong>Price:</strong> RM <?= isset($row['price']) ? number_format($row['price'], 2) : '0.00' ?></p>
                    <p><strong>Category:</strong> <?= isset($row['category']) ? htmlspecialchars($row['category']) : 'Uncategorized' ?></p>
                    <p><strong>Availability:</strong> <?= isset($row['is_active']) && $row['is_active'] ? 'Available' : 'Not Available' ?></p>

                    <!-- Book Button (Conditional on Availability) -->
                    <?php if (isset($row['is_active']) && $row['is_active'] == 1): ?>
                        <a href="../../appointment.php?service_id=<?= $row['id'] ?>">
                            <button type="button">Book Now</button>
                        </a>
                    <?php else: ?>
                        <button class="unavailable" disabled>Unavailable</button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; font-family: 'Poppins', sans-serif; color: #4A5568; margin: 50px auto;">No services found matching the selected filters.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
