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
    <title>Reset Password - Hanthana</title>
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
                <p class="tagline">Enter code and set a new password</p>
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

            <form class="auth-form hf-form" method="post" action="<?php echo BASE_PATH; ?>index.php?controller=Auth&action=resetPassword">
                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-envelope"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                    >
                </div>

                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-key-skeleton"></i>
                    <input
                        type="text"
                        name="code"
                        placeholder="6-digit code"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        required
                    >
                </div>

                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-lock"></i>
                    <input
                        type="password"
                        name="new_password"
                        placeholder="New password"
                        minlength="6"
                        required
                    >
                </div>

                <div class="form-group-l hf-icon-field">
                    <i class="uil uil-lock-access"></i>
                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        minlength="6"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>

            <div class="auth-footer">
                <p>
                    Did not receive a code?
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Auth&action=forgotPassword&email=<?php echo urlencode($email); ?>">Resend code</a>
                </p>
                <p><a href="<?php echo BASE_PATH; ?>index.php?controller=Login&action=index">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
