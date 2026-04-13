<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $authController->register($_POST);
    
    if ($result['success']) {
        header('Location: ' . BASE_PATH .'index.php?controller=Login&action=index');
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
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card signup-card">
            <form method="POST" action="/?controller=Signup&action=index" class="auth-form hf-form" id="signup-form" data-form="signup">
                <div class="auth-header">
                    <div class="logo">Hanthana</div>
                    <div class="tagline">Connect with your community</div>
                </div>

                <!-- Error Messages Only (success redirects) -->
                <?php if (isset($_SESSION['signup_errors'])): ?>
                    <div class="error-messages" style="color: red; padding: 10px; margin-bottom: 15px;">
                        <?php foreach ($_SESSION['signup_errors'] as $error): ?>
                            <div>❌ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                        <?php unset($_SESSION['signup_errors']); ?>
                    </div>
                <?php endif; ?>

                <div class="signup-name-row">
                    <div class="form-group-l signup-form-group hf-icon-field">
                        <i class="uil uil-user"></i>
                        <input type="text" name="first_name" placeholder="First Name" required 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div class="form-group-l signup-form-group hf-icon-field">
                        <i class="uil uil-user"></i>
                        <input type="text" name="last_name" placeholder="Last Name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group-l signup-form-group hf-icon-field">
                    <i class="uil uil-user"></i>
                    <input type="text" id="username" name="username" placeholder="Username" required autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <span id="username-status" class="hf-field-status" aria-live="polite"></span>
                </div>
                
                <div class="form-group-l signup-form-group hf-icon-field">
                    <i class="uil uil-envelope"></i>
                      <input type="email" id="email" name="email" placeholder="University Email" required autocomplete="email"
                          pattern="^[^@\s]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.ac\.lk$"
                          title="Use university email ending with .ac.lk (e.g., 2023cs140@stu.ucsc.cmb.ac.lk)"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <span id="email-status" class="hf-field-status" aria-live="polite"></span>
                </div>
                
                <div class="form-group-l signup-form-group hf-icon-field">
                    <i class="uil uil-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="Phone Number" pattern="[0-9]{10}" required
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    <span id="phone-status" class="hf-field-status" aria-live="polite"></span>
                </div>

                <div class="form-group-l signup-form-group hf-icon-field has-dropdown">
                    <i class="uil uil-graduation-cap"></i>
                    <select id="university" name="university" required>
                        <option value="" disabled <?php echo empty($_POST['university']) ? 'selected' : ''; ?>>Select University</option>
                        <option value="University of Colombo" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Colombo') ? 'selected' : ''; ?>>University of Colombo</option>
                        <option value="University of Peradeniya" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Peradeniya') ? 'selected' : ''; ?>>University of Peradeniya</option>
                        <option value="University of Moratuwa" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Moratuwa') ? 'selected' : ''; ?>>University of Moratuwa</option>
                        <option value="University of Sri Jayewardenepura" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Sri Jayewardenepura') ? 'selected' : ''; ?>>University of Sri Jayewardenepura</option>
                        <option value="University of Kelaniya" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Kelaniya') ? 'selected' : ''; ?>>University of Kelaniya</option>
                        <option value="University of Ruhuna" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Ruhuna') ? 'selected' : ''; ?>>University of Ruhuna</option>
                        <option value="University of Jaffna" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Jaffna') ? 'selected' : ''; ?>>University of Jaffna</option>
                        <option value="Uva Wellassa University" <?php echo (isset($_POST['university']) && $_POST['university'] === 'Uva Wellassa University') ? 'selected' : ''; ?>>Uva Wellassa University</option>
                        <option value="Rajarata University of Sri Lanka" <?php echo (isset($_POST['university']) && $_POST['university'] === 'Rajarata University of Sri Lanka') ? 'selected' : ''; ?>>Rajarata University of Sri Lanka</option>
                        <option value="Sabaragamuwa University of Sri Lanka" <?php echo (isset($_POST['university']) && $_POST['university'] === 'Sabaragamuwa University of Sri Lanka') ? 'selected' : ''; ?>>Sabaragamuwa University of Sri Lanka</option>
                        <option value="South Eastern University of Sri Lanka" <?php echo (isset($_POST['university']) && $_POST['university'] === 'South Eastern University of Sri Lanka') ? 'selected' : ''; ?>>South Eastern University of Sri Lanka</option>
                        <option value="Eastern University Sri Lanka" <?php echo (isset($_POST['university']) && $_POST['university'] === 'Eastern University Sri Lanka') ? 'selected' : ''; ?>>Eastern University Sri Lanka</option>
                        <option value="Wayamba University of Sri Lanka" <?php echo (isset($_POST['university']) && $_POST['university'] === 'Wayamba University of Sri Lanka') ? 'selected' : ''; ?>>Wayamba University of Sri Lanka</option>
                        <option value="University of Vavuniya" <?php echo (isset($_POST['university']) && $_POST['university'] === 'University of Vavuniya') ? 'selected' : ''; ?>>University of Vavuniya</option>
                    </select>
                </div>
                
                <div class="form-group-l signup-form-group hf-icon-field">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                    <span id="password-status" class="hf-field-status" aria-live="polite"></span>
                </div>
                
                <div class="form-group-l signup-form-group hf-icon-field">
                    <i class="uil uil-lock"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                
                <p class="auth-footer">Already have an account? <a href="<?php echo BASE_PATH; ?>index.php?controller=Login&action=index">Login</a></p>
            </form>
        </div>
    </div>

    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
    <script src="./js/signup.js"></script>
</body>
</html>