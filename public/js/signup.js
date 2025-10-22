document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signup-form');
    if (!signupForm) return;

    signupForm.addEventListener('submit', async function(e) {
        const formData = new FormData(signupForm);
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');
        const phone = formData.get('phone');
        if (!phone.match(/^\d{10}$/)) {
            e.preventDefault();
            alert('Please enter a valid 10-digit phone number.');
            return;
        }
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }
        // Validation passed — allow normal form submission to server (no need to preventDefault)
        // If you prefer AJAX, we can re-enable fetch-based submit; for now we submit the form normally so
        // the server-side controller receives the POST and inserts into the database.
    });
});
