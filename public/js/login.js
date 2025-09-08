// DOM Elements
const authForm = document.getElementById('auth-form');
const authSwitchLink = document.getElementById('auth-switch-link');
const authSwitchText = document.getElementById('auth-switch-text');

// Initialize with login form
renderLoginForm();

// Switch between forms
authSwitchLink.addEventListener('click', (e) => {
    e.preventDefault();
    if (authForm.dataset.form === 'login') {
        renderRegisterForm();
    } else {
        renderLoginForm();
    }
});

// Form submission
authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(authForm);
    
    if (authForm.dataset.form === 'login') {
        // Handle login
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 500));
            window.location.href = 'myFeed.html'; // Redirect to feed after login
        } catch (error) {
            console.error('Login error:', error);
        }
    } else {
        // Handle registration
        try {
            // Check if passwords match
            const password = formData.get('password');
            const confirmPassword = formData.get('confirmPassword');
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // After successful registration
            renderLoginForm(); // Switch back to login form
            // You could add a visual indicator that registration was successful
            const emailField = authForm.querySelector('input[type="email"]');
            if (emailField) emailField.value = formData.get('email');
        } catch (error) {
            console.error('Registration error:', error);
        }
    }
});

function renderLoginForm() {
    authForm.dataset.form = 'login';
    authForm.innerHTML = `
        <div class="form-group-l">
            <i class="uil uil-envelope"></i>
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group-l">
            <i class="uil uil-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    `;
    authSwitchText.innerHTML = 'Don\'t have an account? <a href="#" id="auth-switch-link">Register</a>';
    // Re-bind the switch link event
    document.getElementById('auth-switch-link').addEventListener('click', (e) => {
        e.preventDefault();
        renderRegisterForm();
    });
}

function renderRegisterForm() {
    authForm.dataset.form = 'register';
    authForm.innerHTML = `
        <div class="form-group-l minimal">
            <i class="uil uil-user"></i>
            <input type="text" name="username" placeholder="Username" required>
        </div>
        <div class="form-group-l minimal">
            <i class="uil uil-envelope"></i>
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group-l minimal">
            <i class="uil uil-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-group-l minimal">
            <i class="uil uil-lock-access"></i>
            <input type="password" name="confirmPassword" placeholder="Confirm Password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    `;
    // ... rest of the function

    authSwitchText.innerHTML = 'Already have an account? <a href="#" id="auth-switch-link">Login</a>';
    // Re-bind the switch link event
    document.getElementById('auth-switch-link').addEventListener('click', (e) => {
        e.preventDefault();
        renderLoginForm();
    });
}