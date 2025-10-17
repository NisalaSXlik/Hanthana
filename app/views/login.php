<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController();
$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $authController->login($identifier, $password);
    
    if ($result['success']) {
        header('Location: myfeed.php');
        exit;
    } else {
        $error = $result['errors'][0] ?? 'Login failed';
    }
}

// Show success message if redirected from registration
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success = "Registration successful! Please login.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to Hanthana</title>
    <link rel="stylesheet" href="../../public/css/general.css">
    <link rel="stylesheet" href="../../public/css/login.css">
    <link rel="stylesheet" href="../../public/css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Hanthana</h1>
                <p class="tagline">Connect with your community</p>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="success-message" style="color: green; padding: 10px; text-align: center; margin-bottom: 15px; background: #d4ffd4; border-radius: 5px;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-message" style="color: red; padding: 10px; text-align: center; margin-bottom: 15px; background: #ffd4d4; border-radius: 5px;">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form class="auth-form" method="post" action="">
                <div class="form-group-l">
                    <i class="uil uil-envelope"></i>
                    <input type="text" name="identifier" placeholder="Email or Phone Number" required
                           value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                </div>
                <div class="form-group-l">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Register</a></p>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>
        </div>
    </div>

    <!-- Optional: Keep JS for enhanced UX (not required) -->
    <script src="../../public/js/login.js"></script>
</body>
</html>