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
    <link rel="stylesheet" href="assets/css/staff_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    

</head>

<body>
    <div class="container">
        <div class="logo"></div>
        <div class="left">
            <h2 >Sign In</h2>
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
                    <input type="email" name="staff-email" <?= Helper::oldValue("staff-email", "Work Email") ?> required>
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
            Need staff access? <a href="staff-signup">Request Account</a>
            </div>

        </div>
        <div class="right">
            <img src="./assets/lotus_logo.png" alt="lotus logo">
            <h2 class="message-text" id="messageTitle">Staff Portal Access</h2>
            <p class="message-subtext" id="messageSubtext" >Sign in to access administrative features and staff resources</p>

        </div>
    </div>
    <script src="assets/javascript/main.js"></script>
</body>

</html>
<?php
unset($_SESSION['errors']);
unset($_SESSION['old']);
?>