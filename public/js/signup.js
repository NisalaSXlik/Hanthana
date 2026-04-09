document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signup-form');
    if (!signupForm) return;

    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = signupForm.querySelector('input[name="password"]');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const universitySelect = document.getElementById('university');
    const usernameStatus = document.getElementById('username-status');
    const emailStatus = document.getElementById('email-status');
    const phoneStatus = document.getElementById('phone-status');
    const passwordStatus = document.getElementById('password-status');

    const availabilityState = {
        username: { value: '', available: false, pending: false },
        email: { value: '', available: false, pending: false },
        phone: { value: '', available: false, pending: false }
    };

    let usernameTimer = null;
    let emailTimer = null;
    let phoneTimer = null;

    function validatePasswordMatch() {
        if (!passwordInput || !confirmPasswordInput) return true;

        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (!confirmPassword) {
            setFeedback(passwordStatus, '', false);
            confirmPasswordInput.setCustomValidity('');
            return true;
        }

        if (password !== confirmPassword) {
            setFeedback(passwordStatus, 'Passwords do not match.', true, '!');
            confirmPasswordInput.setCustomValidity('Passwords do not match.');
            return false;
        }

        setFeedback(passwordStatus, 'Passwords match.', false, '✓');
        confirmPasswordInput.setCustomValidity('');
        return true;
    }

    function setFeedback(target, message, isError, icon) {
        if (!target) return;
        target.textContent = message ? `${icon || ''} ${message}`.trim() : '';
        target.classList.toggle('is-error', !!isError);
        target.classList.toggle('is-success', !isError && !!message);
    }

    function updateAvailabilityState(field, value, available) {
        availabilityState[field] = {
            value,
            available,
            pending: false
        };
    }

    async function checkAvailability(field, value) {
        const targetInput = field === 'username' ? usernameInput : field === 'email' ? emailInput : phoneInput;
        const targetFeedback = field === 'username' ? usernameStatus : field === 'email' ? emailStatus : phoneStatus;

        if (!targetInput || !targetFeedback) return false;

        const trimmedValue = value.trim();
        if (!trimmedValue) {
            setFeedback(targetFeedback, '', false);
            updateAvailabilityState(field, '', false);
            targetInput.setCustomValidity('');
            return false;
        }

        availabilityState[field].pending = true;
        setFeedback(targetFeedback, 'Checking availability...', false);

        try {
            const response = await fetch(`${BASE_PATH}index.php?controller=Auth&action=checkAvailability&field=${encodeURIComponent(field)}&value=${encodeURIComponent(trimmedValue)}`);
            const data = await response.json();

            const available = Boolean(data.available);
            updateAvailabilityState(field, trimmedValue, available);

            if (available) {
                setFeedback(targetFeedback, data.message || `${field} is available.`, false, '✓');
                targetInput.setCustomValidity('');
            } else {
                setFeedback(targetFeedback, data.message || `${field} is not available.`, true, '!');
                targetInput.setCustomValidity(data.message || `${field} is not available.`);
            }

            return available;
        } catch (error) {
            console.error(`Availability check failed for ${field}:`, error);
            setFeedback(targetFeedback, 'Unable to verify right now.', true, '!');
            targetInput.setCustomValidity('Unable to verify availability.');
            updateAvailabilityState(field, trimmedValue, false);
            return false;
        }
    }

    function scheduleAvailabilityCheck(field, value) {
        clearTimeout(field === 'username' ? usernameTimer : emailTimer);
        clearTimeout(field === 'phone' ? phoneTimer : 0);

        const timer = setTimeout(() => {
            checkAvailability(field, value);
        }, 350);

        if (field === 'username') {
            usernameTimer = timer;
        } else {
            if (field === 'email') {
                emailTimer = timer;
            } else {
                phoneTimer = timer;
            }
        }
    }

    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            scheduleAvailabilityCheck('username', this.value);
        });
        usernameInput.addEventListener('input', function() {
            const currentValue = this.value.trim();
            availabilityState.username.available = false;
            if (currentValue !== availabilityState.username.value) {
                setFeedback(usernameStatus, '', false);
                this.setCustomValidity('');
            }
            if (currentValue) {
                scheduleAvailabilityCheck('username', currentValue);
            }
        });
    }

    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            scheduleAvailabilityCheck('email', this.value);
        });
        emailInput.addEventListener('input', function() {
            const currentValue = this.value.trim();
            availabilityState.email.available = false;
            if (currentValue !== availabilityState.email.value) {
                setFeedback(emailStatus, '', false);
                this.setCustomValidity('');
            }
            if (currentValue) {
                scheduleAvailabilityCheck('email', currentValue);
            }
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            scheduleAvailabilityCheck('phone', this.value);
        });
        phoneInput.addEventListener('input', function() {
            const currentValue = this.value.trim();
            availabilityState.phone.available = false;
            if (currentValue !== availabilityState.phone.value) {
                setFeedback(phoneStatus, '', false);
                this.setCustomValidity('');
            }
            if (currentValue) {
                scheduleAvailabilityCheck('phone', currentValue);
            }
        });
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validatePasswordMatch);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        confirmPasswordInput.addEventListener('blur', validatePasswordMatch);
    }

    if (universitySelect) {
        const validateUniversity = () => {
            if (!universitySelect.value) {
                universitySelect.setCustomValidity('Please select a university.');
            } else {
                universitySelect.setCustomValidity('');
            }
        };

        universitySelect.addEventListener('change', validateUniversity);
        universitySelect.addEventListener('invalid', function() {
            this.setCustomValidity('Please select a university.');
        });
        validateUniversity();
    }

    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(signupForm);
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');
        const phone = formData.get('phone');

        if (!signupForm.checkValidity()) {
            signupForm.reportValidity();
            return;
        }

        if (!validatePasswordMatch()) {
            confirmPasswordInput?.reportValidity();
            return;
        }

        const username = (usernameInput?.value || '').trim();
        const email = (emailInput?.value || '').trim();
        const phoneValue = (phoneInput?.value || '').trim();

        const usernameReady = !username || (availabilityState.username.value === username && availabilityState.username.available) || await checkAvailability('username', username);
        const emailReady = !email || (availabilityState.email.value === email && availabilityState.email.available) || await checkAvailability('email', email);
        const phoneReady = !phoneValue || (availabilityState.phone.value === phoneValue && availabilityState.phone.available) || await checkAvailability('phone', phoneValue);

        if (!phoneValue.match(/^\d{10}$/)) {
            phoneInput?.setCustomValidity('Please enter a valid 10-digit phone number.');
            phoneInput?.reportValidity();
            return;
        }

        if (!usernameReady || !emailReady || !phoneReady) {
            return;
        }

        signupForm.submit();
    });
});
