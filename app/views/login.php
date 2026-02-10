<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
    exit();
}

$authController = new AuthController();
$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $authController->login($identifier, $password);
    
    if ($result['success']) {
        header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
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
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
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
                <div class="auth-alert auth-alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form class="auth-form hf-form" method="post" action="">
                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-envelope"></i>
                    <input type="text" name="identifier" placeholder="Email or Phone Number" required
                           value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                </div>
                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?php echo BASE_PATH; ?>index.php?controller=Signup&action=index">Register</a></p>
                <a href="<?php echo BASE_PATH; ?>index.php?controller=Auth&action=forgotPassword" class="forgot-password">Forgot password?</a>
            </div>
        </div>
    </div>

    <!-- Optional: Keep JS for enhanced UX (not required) -->
    <script src="./js/login.js"></script>
</body>
</html>
