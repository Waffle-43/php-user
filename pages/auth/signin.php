<?php

use Utils\Helper;

$csrfToken = Helper::generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="assets/css/auth_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .staff-sign-in-button {
            display: block;
            width: 120px;
            /* Fixed small size */
            padding: 8px 0;
            /* Less padding to make it neat */
            margin: 15px auto;
            /* <<< auto centers it horizontally */

            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
            /* Smaller text */
            font-weight: 500;
            color: #2a6d62;
            background-color: transparent;
            border: 2px solid #2a6d62;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .staff-sign-in-button:hover {
            background-color: rgba(42, 109, 98, 0.1);
            color: #3a8f7f;
            border-color: #3a8f7f;
        }

        .staff-sign-in-button:active {
            transform: translateY(1px);
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="logo"></div>
        <div class="left">
            <img src="./assets/lotus_logo.png" alt="lotus logo">
            <h2>Hello again, we've missed you!</h2>
            <p>Sign in to access our exclusive features</p>
        </div>
        <div class="right">
            <h2>Sign In</h2>
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="error-messages">
                    <?php Helper::showError("general") ?>
                </div>
            <?php endif; ?>

            <form action="sign-in" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="input-group">
                    <input type="text" name="username" <?= Helper::oldValue("username", "Username") ?> required>
                    <?php Helper::showError("username") ?>
                </div>

                <div class="input-group">
                    <input type="text" name="email" <?= Helper::oldValue("email", "Enter your email") ?> required>
                    <?php Helper::showError("email") ?>

                </div>
                <div class="input-group">
                    <input type="password" name="password" <?= Helper::oldValue('password', 'Password') ?> required>
                    <?php Helper::showError("password") ?>

                    <i class="fa fa-eye"></i>
                </div>
                <div class="options">
                    <label>
                        <input name="remember_me" type="checkbox"> Remember me</label>
                    <a href="email-confirmation">Forgot password?</a>
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
            <div class="register">
                Want to be a member? <a href="sign-up">Create an Account</a>
            </div>
            <a href="staff-signin" class="staff-sign-in-button">Staff</a>
        </div>
    </div>
    <script src="assets/javascript/main.js"></script>
</body>

</html>
<?php
unset($_SESSION['errors']);
unset($_SESSION['old']);
?>