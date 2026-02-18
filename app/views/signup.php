<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Hanthana</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card signup-card">
            <form class="auth-form" id="signup-form">
                <div class="auth-header">
                    <div class="logo">Hanthana</div>
                    <div class="tagline">Connect with your community</div>
                </div>

                <div class="success-message" id="success-message">edee</div>
                <div class="error-message" id="error-message">eeff</div>

                <div class="signup-name-row">
                    <div class="form-group-l signup-form-group">
                        <i class="uil uil-user"></i>
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="form-group-l signup-form-group">
                        <i class="uil uil-user"></i>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-user"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-phone"></i>
                    <input type="tel" name="phone_number" placeholder="Phone Number" required>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <div class="form-group-l signup-form-group">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                
                <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>

    <script type="module" src="./js/signup.js"></script>
</body>
</html>