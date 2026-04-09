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
$oauthError = '';

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

if (isset($_GET['oauth_error'])) {
    $oauthErrorMap = [
        'google_not_configured' => 'Google login is not configured yet. Please try email login for now.',
        'oauth_state_failed' => 'Unable to start Google login. Please try again.',
        'google_access_denied' => 'Google sign-in was cancelled.',
        'google_invalid_state' => 'Google sign-in validation failed. Please try again.',
        'google_missing_code' => 'Google sign-in failed to return an authorization code.',
        'google_token_exchange_failed' => 'Google sign-in token exchange failed.',
        'google_profile_fetch_failed' => 'Unable to fetch your Google profile.',
        'google_email_not_verified' => 'Your Google email must be verified to continue.',
        'google_account_create_failed' => 'Could not create your account from Google profile.',
        'account_banned' => 'Your account is currently banned. Contact support for assistance.',
    ];

    $code = (string)$_GET['oauth_error'];
    $oauthError = $oauthErrorMap[$code] ?? 'Google sign-in failed. Please try again.';
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
            <?php if ($error || $oauthError): ?>
                <div class="auth-alert auth-alert-error">
                    ❌ <?php echo htmlspecialchars($error ?: $oauthError); ?>
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

            <div class="auth-divider"><span>or</span></div>
            <a class="btn btn-google btn-block" href="<?php echo BASE_PATH; ?>index.php?controller=Auth&action=googleLogin" aria-label="Continue with Google">
                <span class="google-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" focusable="false" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.2-1.4 3.6-5.4 3.6-3.2 0-5.8-2.7-5.8-6s2.6-6 5.8-6c1.8 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.2 14.6 2.3 12 2.3 6.8 2.3 2.6 6.6 2.6 12s4.2 9.7 9.4 9.7c5.4 0 9-3.8 9-9.1 0-.6-.1-1.1-.2-1.5H12z"/>
                        <path fill="#34A853" d="M3.7 7.4l3.2 2.3C7.8 7.9 9.7 6.6 12 6.6c1.8 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.2 14.6 2.3 12 2.3c-3.6 0-6.7 2-8.3 5.1z"/>
                        <path fill="#FBBC05" d="M12 21.7c2.5 0 4.7-.8 6.3-2.3l-3-2.4c-.8.6-1.8 1-3.3 1-2.4 0-4.4-1.6-5.1-3.8l-3.2 2.5c1.6 3.1 4.7 5 8.3 5z"/>
                        <path fill="#4285F4" d="M21 12.6c0-.6-.1-1.1-.2-1.5H12v3.9h5.4c-.3 1.4-1.1 2.4-2.1 3.1l3 2.4c1.8-1.7 2.7-4.2 2.7-7.9z"/>
                    </svg>
                </span>
                <span class="google-text">Continue with Google</span>
            </a>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?php echo BASE_PATH; ?>index.php?controller=Signup&action=index">Register</a></p>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>
        </div>
    </div>

    <!-- Optional: Keep JS for enhanced UX (not required) -->
    <script src="./js/login.js"></script>
</body>
</html>
