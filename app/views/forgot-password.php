<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = isset($email) ? (string)$email : '';
$error = isset($error) ? (string)$error : '';
$success = isset($success) ? (string)$success : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hanthana</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="logo">Hanthana</h1>
                <p class="tagline">Forgot your password?</p>
            </div>

            <?php if ($success): ?>
                <div class="auth-alert auth-alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="auth-form hf-form" method="post" action="<?php echo BASE_PATH; ?>index.php?controller=Auth&action=sendPasswordResetCode">
                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-envelope"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your account email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Send 6-Digit Code</button>
            </form>

            <div class="auth-footer">
                <p><a href="<?php echo BASE_PATH; ?>index.php?controller=Login&action=index">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
