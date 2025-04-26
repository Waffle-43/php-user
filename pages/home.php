<?php
include "database/db.php";
include "utils/Helper.php";
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Left search section */
        .search-section {
            display: flex;
            align-items: center;
            margin-right: 30px;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            color: #555;
        }

        .search-input {
            padding: 8px 8px 8px 35px;
            border: none;
            background-color: transparent;
            font-size: 16px;
            color: #555;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            width: 100px;
            transition: width 0.3s;
            outline: none;
        }

        .search-input:focus {
            width: 150px;
        }

        .search-input::placeholder {
            color: #555;
            opacity: 1;
            text-transform: uppercase;
        }

        /* Left navigation menu */
        .left-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .left-nav li {
            margin-right: 40px;
        }

        /* Center logo */
        .logo-section {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .logo {
            height: 40px;
        }

        /* Right navigation menu */
        .right-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .right-nav li {
            margin-left: 40px;
        }

        /* Navigation styling (applies to both left and right nav) */
        .nav-item a {
            text-decoration: none;
            color: #455a64;
            font-weight: 500;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 0;
            transition: color 0.3s;
        }

        .nav-item a:hover {
            color: #4a8b9e;
        }

        .nav-item a.active {
            background-color: #eef2f6;
            padding: 10px 15px;
        }

        /* Right profile section */
        .profile-section {
            display: flex;
            align-items: center;
            margin-left: 30px;
        }

        .profile {
            display: flex;
            align-items: center;
        }

        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f6ff;
        }

        .profile span {
            margin-left: 10px;
            font-weight: 500;
            color: #333;
        }

        /* Main content area */
        .container {
            padding: 20px 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #222;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            margin-top: 0;
        }

        /* Responsive layout */
        @media (max-width: 992px) {
            .navbar {
                flex-wrap: wrap;
                justify-content: center;
                padding: 15px;
            }

            .search-section {
                order: 1;
                margin-right: 0;
                margin-bottom: 15px;
                width: 100%;
                justify-content: center;
            }

            .search-input {
                width: 150px;
                text-align: center;
            }

            .search-input:focus {
                width: 200px;
            }

            .search-icon {
                left: 50%;
                transform: translateX(-80px);
            }

            .left-nav {
                order: 3;
                margin: 15px 0;
                justify-content: center;
            }

            .logo-section {
                order: 2;
                position: relative;
                left: unset;
                transform: none;
                margin: 10px 0;
            }

            .right-nav {
                order: 4;
                margin: 0 0 15px 0;
                justify-content: center;
            }

            .profile-section {
                order: 5;
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .left-nav li,
            .right-nav li {
                margin: 0 15px;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <!-- Left search section with functional search input -->
        <div class="search-section">
            <div class="search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <form action="search.php" method="GET">
                <input type="text" name="query" class="search-input" placeholder="Search">
            </form>
        </div>

        <!-- Left navigation menu -->
        <ul class="left-nav">
            <li class="nav-item"><a href="home" class="active">HOME</a></li>
            <li class="nav-item"><a href="pages">PAGES</a></li>
        </ul>

        <!-- Center logo -->
        <div class="logo-section">
            <img src="./assets/lotus_logo.png" alt="Harmony Spa Logo" class="logo">
        </div>

        <!-- Right navigation menu -->
        <ul class="right-nav">
            <li class="nav-item"><a href="schedule">SCHEDULE</a></li>
            <li class="nav-item"><a href="services">SERVICES</a></li>
        </ul>

        <!-- Right profile section -->
        <div class="profile-section">
            <div class="profile" id="profileLink">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM users WHERE id = '" . $_SESSION['user']['id'] . "'");
                $user = mysqli_fetch_assoc($res);
                $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default_profile.png';
                ?>
                <img src="/<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture" width="150" height="100">
                <span><?= htmlspecialchars($user['username']) ?></span>

                <div class="dropdown">
                    <a href="profile">Profile</a>
                    <a href="logout">Logout</a>
                </div>

            </div>
        </div>
    </div>


    <!-- Profile dropdown shown below navbar as in the mockup -->
    <div class="dropdown"
        style="display: block; position: fixed; top: auto; right: 30px; box-shadow: none; border-top: 1px solid #eee; text-align: right; padding: 5px 0;">
        <a href="profile" style="display: inline-block; margin-right: 15px;">Profile</a>
        <a href="logout">Logout</a>
    </div>
    </div>

    <div class="container">
        <h1>Welcome to PHP Project</h1>
        <p>Your application Homepage.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add any JavaScript needed for interactions
        });
    </script>
</body>

</html>