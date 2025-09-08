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
    <div class="auth-container" style="max-width: 400px; margin: 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 8px 32px rgba(60,60,120,0.08); padding: 40px 32px;">
        <form id="signup-form" class="auth-form" data-form="signup" style="display: flex; flex-direction: column; gap: 18px;">
            <h2 style="text-align:center; color:#2196f3; font-size:2.2rem; font-weight:700; margin-bottom:0;">Hanthana</h2>
            <p style="text-align:center; color:#4a5568; margin-bottom:10px;">Create your account</p>
            <div class="form-group-l" style="position:relative;">
                <i class="uil uil-envelope" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#2196f3;"></i>
                <input type="email" name="email" placeholder="Email" required style="width:100%; padding:12px 12px 12px 44px; border-radius:10px; border:1px solid #e2e8f0; font-size:1rem;">
            </div>
            <div class="form-group-l" style="position:relative;">
                <i class="uil uil-phone" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#2196f3;"></i>
                <input type="tel" name="phone" placeholder="Phone Number" pattern="[0-9]{10}" required style="width:100%; padding:12px 12px 12px 44px; border-radius:10px; border:1px solid #e2e8f0; font-size:1rem;">
            </div>
            <div class="form-group-l" style="position:relative;">
                <i class="uil uil-lock" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#2196f3;"></i>
                <input type="password" name="password" placeholder="Password" required style="width:100%; padding:12px 12px 12px 44px; border-radius:10px; border:1px solid #e2e8f0; font-size:1rem;">
            </div>
            <div class="form-group-l" style="position:relative;">
                <i class="uil uil-lock" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#2196f3;"></i>
                <input type="password" name="confirmPassword" placeholder="Confirm Password" required style="width:100%; padding:12px 12px 12px 44px; border-radius:10px; border:1px solid #e2e8f0; font-size:1rem;">
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="background:#2196f3; color:#fff; border:none; border-radius:12px; font-size:1.1rem; padding:12px 0; margin-top:10px; cursor:pointer;">Sign Up</button>
            <p style="text-align:center; margin-top:10px; color:#4a5568;">Already have an account? <a href="login.php" style="color:#2196f3; font-weight:500; text-decoration:none;">Login</a></p>
        </form>
    </div>
    <script src="../../public/js/signup.js"></script>
</body>
</html>
