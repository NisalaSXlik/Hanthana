document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signup-form');
    if (!signupForm) return;

    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(signupForm);
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');
        const phone = formData.get('phone');
        if (!phone.match(/^\d{10}$/)) {
            alert('Please enter a valid 10-digit phone number.');
            return;
        }
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 500));
        alert('Registration successful! You can now log in.');
        window.location.href = 'login.php';
    });
});
