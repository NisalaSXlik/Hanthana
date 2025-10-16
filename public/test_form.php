<?php
require_once '../app/controllers/AuthController.php';

$authController = new AuthController();
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $authController->register($_POST);
    
    if ($result['success']) {
        $message = "<div style='color: green; padding: 10px;'>✅ " . $result['message'] . "</div>";
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Registration Form</title>
    <style>
        .error { color: red; margin: 5px 0; }
        .success { color: green; margin: 10px 0; }
        .form-group { margin: 10px 0; }
        input { padding: 8px; margin: 5px 0; width: 300px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Test Registration Form</h1>
    
    <?php echo $message; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <div>❌ <?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <input type="text" name="first_name" placeholder="First Name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <input type="text" name="last_name" placeholder="Last Name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <input type="text" name="username" placeholder="Username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <input type="tel" name="phone" placeholder="Phone (10 digits)" pattern="[0-9]{10}" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="form-group">
            <input type="text" name="bio" placeholder="Bio (optional)" value="<?php echo $_POST['bio'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="text" name="university" placeholder="University (optional)" value="<?php echo $_POST['university'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="text" name="location" placeholder="Location (optional)" value="<?php echo $_POST['location'] ?? ''; ?>">
        </div>
        
        <button type="submit">Register</button>
    </form>
    
    <hr>
    <p><a href="test_auth.php">Run Complete Auth Test</a></p>
    <p><a href="test_database.php">Check Database</a></p>
</body>
</html>