document.addEventListener('DOMContentLoaded', () => {
    initializeToast();
    bindProfileForm();
    bindPasswordForm();
    bindDeleteAccountForm();
    initializePasswordToggles();
    initializePasswordStrength();
    initializeFormValidation();
    loadBlockedUsers();
});

function bindProfileForm() {
    const profileForm = document.getElementById('profileForm');
    if (!profileForm) {
        return;
    }

    const availabilityValidator = createProfileAvailabilityValidator(profileForm);

    profileForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!profileForm.checkValidity()) {
            profileForm.reportValidity();
            return;
        }

        const isValid = await availabilityValidator.validateBeforeSubmit();
        if (!isValid) {
            return;
        }

        await submitForm(profileForm, 'updateProfile');
    });
}

function createProfileAvailabilityValidator(profileForm) {
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');

    const statusElements = {
        username: document.getElementById('username-status'),
        email: document.getElementById('email-status'),
        phone: document.getElementById('phone-status')
    };

    const inputs = {
        username: usernameInput,
        email: emailInput,
        phone: phoneInput
    };

    const currentValues = {
        username: normalizeValue(profileForm.dataset.currentUsername || ''),
        email: normalizeValue(profileForm.dataset.currentEmail || ''),
        phone: normalizeValue(profileForm.dataset.currentPhone || '')
    };

    const availabilityState = {
        username: { value: currentValues.username, available: currentValues.username !== '', pending: false },
        email: { value: currentValues.email, available: currentValues.email !== '', pending: false },
        phone: { value: currentValues.phone, available: true, pending: false }
    };

    const timers = {
        username: null,
        email: null,
        phone: null
    };

    function normalizeValue(value) {
        return String(value || '').trim();
    }

    function isUnchanged(field, value) {
        return normalizeValue(value) === currentValues[field];
    }

    function setStatus(field, message, isError, icon) {
        const target = statusElements[field];
        if (!target) {
            return;
        }

        target.textContent = message ? `${icon || ''} ${message}`.trim() : '';
        target.classList.toggle('is-error', Boolean(message) && Boolean(isError));
        target.classList.toggle('is-success', Boolean(message) && !Boolean(isError));
    }

    function setFieldValidity(field, message) {
        const input = inputs[field];
        if (!input) {
            return;
        }

        input.setCustomValidity(message || '');
        input.classList.toggle('error', Boolean(message));
    }

    function updateState(field, value, available, pending) {
        availabilityState[field] = {
            value,
            available,
            pending
        };
    }

    async function checkAvailability(field, rawValue, options = {}) {
        const input = inputs[field];
        if (!input) {
            return true;
        }

        const skipUnchanged = Boolean(options.skipUnchanged);
        const trimmedValue = normalizeValue(rawValue);
        const optionalField = field === 'phone';

        if (trimmedValue === '') {
            setStatus(field, '', false);
            setFieldValidity(field, '');
            updateState(field, '', optionalField, false);
            return optionalField;
        }

        if (skipUnchanged && isUnchanged(field, trimmedValue)) {
            setStatus(field, `${field.charAt(0).toUpperCase() + field.slice(1)} unchanged.`, false, '✓');
            setFieldValidity(field, '');
            updateState(field, trimmedValue, true, false);
            return true;
        }

        if (field === 'username' && !/^[a-zA-Z0-9_]+$/.test(trimmedValue)) {
            const message = 'Username can only contain letters, numbers, and underscores.';
            setStatus(field, message, true, '!');
            setFieldValidity(field, message);
            updateState(field, trimmedValue, false, false);
            return false;
        }

        if (field === 'email' && !isValidUniversityEmail(trimmedValue)) {
            const message = 'Use university email ending with .ac.lk';
            setStatus(field, message, true, '!');
            setFieldValidity(field, message);
            updateState(field, trimmedValue, false, false);
            return false;
        }

        if (field === 'phone' && !isValidPhone(trimmedValue)) {
            const message = 'Phone number must be exactly 10 digits.';
            setStatus(field, message, true, '!');
            setFieldValidity(field, message);
            updateState(field, trimmedValue, false, false);
            return false;
        }

        if (availabilityState[field].value === trimmedValue && !availabilityState[field].pending) {
            return availabilityState[field].available;
        }

        updateState(field, trimmedValue, false, true);
        setStatus(field, 'Checking availability...', false);
        setFieldValidity(field, '');

        try {
            const url = `${BASE_PATH}index.php?controller=Auth&action=checkAvailability&exclude_current=1&field=${encodeURIComponent(field)}&value=${encodeURIComponent(trimmedValue)}`;
            const response = await fetch(url);
            const data = await response.json();
            const latestValue = normalizeValue(input.value);

            if (latestValue !== trimmedValue) {
                return availabilityState[field].available;
            }

            const isAvailable = Boolean(data.available);
            updateState(field, trimmedValue, isAvailable, false);

            if (isAvailable) {
                setStatus(field, data.message || `${field} is available.`, false, '✓');
                setFieldValidity(field, '');
                return true;
            }

            const errorMessage = data.message || `${field} is not available.`;
            setStatus(field, errorMessage, true, '!');
            setFieldValidity(field, errorMessage);
            return false;
        } catch (error) {
            console.error(`Availability check failed for ${field}:`, error);
            const message = 'Unable to verify availability right now.';
            setStatus(field, message, true, '!');
            setFieldValidity(field, message);
            updateState(field, trimmedValue, false, false);
            return false;
        }
    }

    function scheduleCheck(field, value) {
        if (timers[field]) {
            clearTimeout(timers[field]);
        }

        timers[field] = setTimeout(() => {
            checkAvailability(field, value, { skipUnchanged: true });
        }, 350);
    }

    if (usernameInput) {
        usernameInput.addEventListener('input', function onUsernameInput() {
            const value = normalizeValue(this.value);
            setFieldValidity('username', '');

            if (!value) {
                setStatus('username', '', false);
                updateState('username', '', false, false);
                return;
            }

            scheduleCheck('username', value);
        });

        usernameInput.addEventListener('blur', function onUsernameBlur() {
            checkAvailability('username', this.value, { skipUnchanged: true });
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function onEmailInput() {
            const value = normalizeValue(this.value);
            setFieldValidity('email', '');

            if (!value) {
                setStatus('email', '', false);
                updateState('email', '', false, false);
                return;
            }

            scheduleCheck('email', value);
        });

        emailInput.addEventListener('blur', function onEmailBlur() {
            checkAvailability('email', this.value, { skipUnchanged: true });
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function onPhoneInput() {
            const value = normalizeValue(this.value);
            setFieldValidity('phone', '');

            if (!value) {
                setStatus('phone', '', false);
                updateState('phone', '', true, false);
                return;
            }

            scheduleCheck('phone', value);
        });

        phoneInput.addEventListener('blur', function onPhoneBlur() {
            checkAvailability('phone', this.value, { skipUnchanged: true });
        });
    }

    return {
        validateBeforeSubmit: async function validateBeforeSubmit() {
            const username = normalizeValue(usernameInput ? usernameInput.value : '');
            const email = normalizeValue(emailInput ? emailInput.value : '');
            const phone = normalizeValue(phoneInput ? phoneInput.value : '');

            const usernameReady = await checkAvailability('username', username, { skipUnchanged: true });
            const emailReady = await checkAvailability('email', email, { skipUnchanged: true });
            const phoneReady = await checkAvailability('phone', phone, { skipUnchanged: true });

            if (!usernameReady && usernameInput) {
                usernameInput.reportValidity();
            } else if (!emailReady && emailInput) {
                emailInput.reportValidity();
            } else if (!phoneReady && phoneInput) {
                phoneInput.reportValidity();
            }

            return usernameReady && emailReady && phoneReady;
        }
    };
}

function bindPasswordForm() {
    const passwordForm = document.getElementById('passwordForm');
    if (!passwordForm) {
        return;
    }

    passwordForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const newPassword = document.getElementById('newPassword')?.value || '';
        const confirmPassword = document.getElementById('confirmPassword')?.value || '';

        if (newPassword !== confirmPassword) {
            showMessage('Passwords do not match', 'error');
            return;
        }

        if (newPassword.length < 8) {
            showMessage('Password must be at least 8 characters long', 'error');
            return;
        }

        const result = await submitForm(passwordForm, 'updatePassword');
        if (result && result.success) {
            passwordForm.reset();
            updatePasswordStrengthIndicator('empty');
        }
    });
}

function bindDeleteAccountForm() {
    const deleteForm = document.getElementById('deleteAccountForm');
    if (!deleteForm) {
        return;
    }

    deleteForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const confirmText = (document.getElementById('deleteAccountConfirm')?.value || '').trim().toUpperCase();
        if (confirmText !== 'DELETE') {
            showMessage('Please type DELETE to confirm account deletion', 'error');
            return;
        }

        const confirmed = confirm('This will permanently deactivate your account. Do you want to continue?');
        if (!confirmed) {
            return;
        }

        const result = await submitForm(deleteForm, 'deleteAccount');
        if (result && result.success) {
            const redirectUrl = result.redirect || (BASE_PATH + 'index.php?controller=Login&action=index');
            window.location.href = redirectUrl;
        }
    });
}

async function submitForm(form, action) {
    const formData = new FormData(form);
    return submitFormData(formData, action, form);
}

async function submitFormData(formData, action, form) {
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;
    const originalText = submitButton ? submitButton.textContent : '';

    if (submitButton) {
        submitButton.textContent = 'Saving...';
        submitButton.disabled = true;
    }

    try {
        const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=' + action, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage(data.message || 'Saved successfully', 'success');
        } else {
            showMessage(data.message || 'Failed to save changes', 'error');
        }

        return data;
    } catch (error) {
        console.error('Settings request failed:', error);
        showMessage('Failed to save changes', 'error');
        return { success: false };
    } finally {
        if (submitButton) {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    }
}

async function loadBlockedUsers() {
    const container = document.getElementById('blockedUsersList');
    if (!container) {
        return;
    }

    try {
        const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=getBlockedUsers');
        const data = await response.json();

        if (!data.success || !Array.isArray(data.users) || data.users.length === 0) {
            container.innerHTML = '<p class="settings-blocked-empty">No blocked users</p>';
            return;
        }

        const rows = data.users.map((user) => {
            const userId = user.user_id || user.id;
            const avatarPath = user.profile_picture || user.avatar || 'uploads/user_dp/default_user_dp.jpg';
            const avatar = avatarPath.startsWith('http') ? avatarPath : BASE_PATH + avatarPath;
            const firstName = escapeHtml(user.first_name || '');
            const lastName = escapeHtml(user.last_name || '');
            const username = escapeHtml(user.username || 'unknown');

            return `
                <div class="settings-blocked-row">
                    <div class="settings-blocked-meta">
                        <img class="settings-blocked-avatar" src="${avatar}" alt="${username}" onerror="this.src='${BASE_PATH + 'uploads/user_dp/default.png'}'">
                        <div class="settings-blocked-text">
                            <h4 class="settings-blocked-name">${firstName} ${lastName}</h4>
                            <p class="settings-blocked-username">@${username}</p>
                        </div>
                    </div>
                    <button class="settings-unblock-btn" type="button" data-user-id="${userId}">Unblock</button>
                </div>
            `;
        });

        container.innerHTML = rows.join('');

        container.querySelectorAll('.settings-unblock-btn').forEach((button) => {
            button.addEventListener('click', async () => {
                const userId = button.getAttribute('data-user-id');
                await unblockUser(userId, button);
            });
        });
    } catch (error) {
        console.error('Failed to load blocked users:', error);
        container.innerHTML = '<p class="settings-blocked-empty settings-blocked-empty-error">Failed to load blocked users</p>';
    }
}

async function unblockUser(userId, button) {
    if (!userId) {
        return;
    }

    const confirmed = confirm('Are you sure you want to unblock this user?');
    if (!confirmed) {
        return;
    }

    const originalText = button ? button.textContent : '';
    if (button) {
        button.textContent = 'Unblocking...';
        button.disabled = true;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);

        const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=unblockUser', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage('User unblocked successfully', 'success');
            await loadBlockedUsers();
        } else {
            showMessage(data.message || 'Failed to unblock user', 'error');
            if (button) {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    } catch (error) {
        console.error('Failed to unblock user:', error);
        showMessage('Failed to unblock user', 'error');
        if (button) {
            button.textContent = originalText;
            button.disabled = false;
        }
    }
}

function initializePasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    passwordInputs.forEach((input) => {
        if (input.dataset.toggleReady === '1') {
            return;
        }

        const parent = input.parentElement;
        if (!parent) {
            return;
        }

        let wrapper = input.closest('.password-input-wrap');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'password-input-wrap';
            parent.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '<i class="uil uil-eye"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle password visibility');

        toggleButton.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            toggleButton.innerHTML = isHidden ? '<i class="uil uil-eye-slash"></i>' : '<i class="uil uil-eye"></i>';
        });

        wrapper.appendChild(toggleButton);
        input.dataset.toggleReady = '1';
    });
}

function initializePasswordStrength() {
    const input = document.getElementById('newPassword');
    if (!input) {
        return;
    }

    input.addEventListener('input', () => {
        const strength = evaluatePasswordStrength(input.value);
        updatePasswordStrengthIndicator(strength);
    });
}

function evaluatePasswordStrength(password) {
    if (!password) {
        return 'empty';
    }

    let score = 0;

    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^a-zA-Z\d]/.test(password)) score += 1;

    if (score <= 2) return 'weak';
    if (score <= 4) return 'medium';
    return 'strong';
}

function updatePasswordStrengthIndicator(strength) {
    const input = document.getElementById('newPassword');
    if (!input) {
        return;
    }

    input.classList.remove('password-weak', 'password-medium', 'password-strong');

    const formGroup = input.closest('.form-group') || input.parentElement;
    const existing = formGroup ? formGroup.querySelector('.password-strength') : null;
    if (existing) {
        existing.remove();
    }

    if (strength === 'empty') {
        return;
    }

    const labels = {
        weak: 'Weak',
        medium: 'Medium',
        strong: 'Strong'
    };

    input.classList.add('password-' + strength);

    const indicator = document.createElement('div');
    indicator.className = 'password-strength strength-' + strength;
    indicator.textContent = 'Password strength: ' + labels[strength];

    if (formGroup) {
        formGroup.appendChild(indicator);
    }
}

function initializeFormValidation() {
    const universitySelect = document.getElementById('university');
    if (universitySelect) {
        universitySelect.addEventListener('change', function onUniversityChange() {
            this.setCustomValidity('');
            clearFieldError(this);
        });
    }

    const dateInput = document.getElementById('dateOfBirth');
    if (dateInput) {
        dateInput.addEventListener('input', function onDateInput() {
            this.setCustomValidity('');
            clearFieldError(this);
        });
    }
}

function isValidUniversityEmail(email) {
    const regex = /^[^@\s]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*\.ac\.lk$/i;
    return regex.test(email);
}

function isValidPhone(phone) {
    const sanitized = String(phone || '').replace(/\D/g, '');
    return /^\d{10}$/.test(sanitized);
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    if (field.parentElement) {
        field.parentElement.appendChild(errorDiv);
    }
}

function clearFieldError(field) {
    field.classList.remove('error');

    const parent = field.parentElement;
    if (!parent) {
        return;
    }

    const errorDiv = parent.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function initializeToast() {
    if (typeof window.showToast === 'function') {
        return;
    }

    window.showToast = (message, type = 'info') => {
        const container = document.getElementById('toastContainer');
        if (!container) {
            alert(message);
            return;
        }

        const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle');
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="uil uil-${icon}"></i>
                <span>${escapeHtml(message)}</span>
            </div>
            <button class="toast-close" type="button">
                <i class="uil uil-times"></i>
            </button>
        `;

        container.appendChild(toast);

        const removeToast = () => {
            if (toast.parentElement) {
                toast.remove();
            }
        };

        const closeButton = toast.querySelector('.toast-close');
        if (closeButton) {
            closeButton.addEventListener('click', removeToast);
        }

        setTimeout(removeToast, 5000);
    };
}

function showMessage(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    alert(message);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
