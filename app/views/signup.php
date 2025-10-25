<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $authController->register($_POST);
    
    if ($result['success']) {
        // Redirect to login page immediately
        header('Location: login.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Hanthana</title>
    <link rel="stylesheet" href="../../public/css/general.css">
    <link rel="stylesheet" href="../../public/css/login.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card signup-card">
            <form method="POST" action="" class="auth-form" id="signup-form" data-form="signup">
                <div class="auth-header">
                    <div class="logo">Hanthana</div>
                    <div class="tagline">Connect with your community</div>
                </div>

                <!-- Error Messages Only (success redirects) -->
                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="color: red; padding: 10px; margin-bottom: 15px;">
                        <?php foreach ($errors as $error): ?>
                            <div>❌ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="signup-name-row">
                    <div class="form-group-l signup-form-group">
                        <i class="uil uil-user"></i>
                        <input type="text" name="first_name" placeholder="First Name" required 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div class="form-group-l signup-form-group">
                        <i class="uil uil-user"></i>
                        <input type="text" name="last_name" placeholder="Last Name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-user"></i>
                    <input type="text" name="username" placeholder="Username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-phone"></i>
                    <input type="tel" name="phone" placeholder="Phone Number" pattern="[0-9]{10}" required
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="confirmPassword" placeholder="Confirm Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                
                <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>

    <script src="../../public/js/signup.js"></script>
</body>
</html>