<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to Hanthana</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Hanthana</h1>
                <p class="tagline">Connect with your community</p>
            </div>

            <div class="success-message" id="success-message"></div>
            <div class="error-message" id="error-message"></div>
            
            <form class="auth-form" id="login-form" data-form="login">
                <div class="form-group-l">
                    <i class="uil uil-envelope"></i>
                    <input type="text" name="identifier" placeholder="Email or Phone Number" required>
                </div>
                <div class="form-group-l">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?php echo BASE_PATH; ?>index.php?controller=Signup&action=index">Register</a></p>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>
        </div>
    </div>

    <script type="module" src="./js/login.js"></script>
</body>
</html>
