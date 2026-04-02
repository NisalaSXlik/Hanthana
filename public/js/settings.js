document.addEventListener('DOMContentLoaded', function() {
    // Settings Navigation
    const navItems = document.querySelectorAll('.settings-nav .menu-item');
    const sections = document.querySelectorAll('.settings-section');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            this.classList.add('active');
            const sectionId = this.getAttribute('data-section') + '-section';
            document.getElementById(sectionId).classList.add('active');
        });
    });
    
    // Profile Form Submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitForm(this, 'updateProfile');
        });
    }
    
    // Password Form Submission
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showMessage('Password must be at least 8 characters long', 'error');
                return;
            }
            
            await submitForm(this, 'updatePassword');
        });
    }
    
    // Privacy Form Submission
    const privacyForm = document.getElementById('privacyForm');
    if (privacyForm) {
        privacyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitForm(this, 'updatePrivacy');
        });
    }
    
    // Notification Form Submission
    const notificationForm = document.getElementById('notificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitForm(this, 'updateNotifications');
        });
    }
    
    // Push Form Submission
    const pushForm = document.getElementById('pushForm');
    if (pushForm) {
        pushForm.addEventListener('change', async function(e) {
            const formData = new FormData();
            formData.append('push_enabled', e.target.checked ? '1' : '0');
            await submitFormData(formData, 'updateNotifications');
        });
    }
    
    // Appearance Form Submission
    const appearanceForm = document.getElementById('appearanceForm');
    if (appearanceForm) {
        appearanceForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitForm(this, 'updateAppearance');
        });
    }
    
    // Theme Selection with real-time preview
    const themeSelect = document.getElementById('theme-select');
    if (themeSelect) {
        const currentTheme = localStorage.getItem('theme') || 'light';
        themeSelect.value = currentTheme;
        applyTheme(currentTheme);
        
        themeSelect.addEventListener('change', function() {
            const selectedTheme = this.value;
            applyTheme(selectedTheme);
            localStorage.setItem('theme', selectedTheme);
            
            // Auto-save theme preference
            const formData = new FormData();
            formData.append('theme', selectedTheme);
            formData.append('font_size', document.getElementById('font-size').value);
            submitFormData(formData, 'updateAppearance');
        });
    }
    
    // Font Size Selection with real-time preview
    const fontSizeSelect = document.getElementById('font-size');
    if (fontSizeSelect) {
        const currentFontSize = localStorage.getItem('fontSize') || 'medium';
        fontSizeSelect.value = currentFontSize;
        applyFontSize(currentFontSize);
        
        fontSizeSelect.addEventListener('change', function() {
            const selectedSize = this.value;
            applyFontSize(selectedSize);
            localStorage.setItem('fontSize', selectedSize);
            
            // Auto-save font size preference
            const formData = new FormData();
            formData.append('theme', document.getElementById('theme-select').value);
            formData.append('font_size', selectedSize);
            submitFormData(formData, 'updateAppearance');
        });
    }
    
    // Password Visibility Toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const parent = input.parentElement;
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '<i class="uil uil-eye"></i>';
        toggleBtn.className = 'password-toggle';
        toggleBtn.style.cssText = 'background: none; border: none; cursor: pointer; color: var(--color-gray); position: absolute; right: 10px; top: 50%; transform: translateY(-50%);';
        
        parent.style.position = 'relative';
        parent.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="uil uil-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="uil uil-eye"></i>';
            }
        });
    });
    
    // Password Strength Indicator
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });
    }
    
    // Load blocked users
    loadBlockedUsers();
    
    // Real-time form validation
    initializeFormValidation();
    initializePrivacyExplanations();

});

// Helper function to submit forms
async function submitForm(form, action) {
    const formData = new FormData(form);
    await submitFormData(formData, action);
}

// Helper function to submit form data
async function submitFormData(formData, action) {
    const submitButton = document.querySelector(`#${action.replace('update', '').toLowerCase()}-section button[type="submit"]`);
    
    if (submitButton) {
        const originalText = submitButton.textContent;
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
            showMessage(data.message, 'success');
            if (action === 'updatePassword') {
                document.getElementById('passwordForm').reset();
            }
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Failed to save changes', 'error');
    } finally {
        if (submitButton) {
            submitButton.textContent = submitButton.getAttribute('data-original-text') || 'Save Changes';
            submitButton.disabled = false;
        }
    }
}

// Helper function to show messages
function showMessage(message, type) {
    if (window.showToast) {
        window.showToast(message, type);
    } else {
        // Fallback alert
        alert(message);
    }
}

// Apply theme
function applyTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark-theme');
    } else {
        document.body.classList.remove('dark-theme');
    }
}

// Apply font size
function applyFontSize(size) {
    const sizes = {
        'small': '14px',
        'medium': '16px',
        'large': '18px'
    };
    document.documentElement.style.fontSize = sizes[size] || '16px';
}

// Calculate password strength
function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    if (strength <= 2) return 'weak';
    if (strength <= 4) return 'medium';
    return 'strong';
}

// Update password strength indicator
function updatePasswordStrengthIndicator(strength) {
    const input = document.getElementById('newPassword');
    if (!input) return;
    
    // Remove existing strength classes
    input.classList.remove('password-weak', 'password-medium', 'password-strong');
    
    // Remove existing strength indicator
    const existingIndicator = input.parentNode.querySelector('.password-strength');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    // Add appropriate class and indicator
    if (input.value.length > 0) {
        input.classList.add('password-' + strength);
        
        const strengthText = {
            'weak': 'Weak',
            'medium': 'Medium', 
            'strong': 'Strong'
        };
        
        const strengthColors = {
            'weak': 'var(--color-danger)',
            'medium': 'orange',
            'strong': 'var(--color-success)'
        };
        
        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        indicator.style.cssText = `
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: ${strengthColors[strength]};
            font-weight: 500;
        `;
        indicator.textContent = `Password strength: ${strengthText[strength]}`;
        
        input.parentNode.appendChild(indicator);
    }
}

// Load blocked users
async function loadBlockedUsers() {
    const container = document.getElementById('blockedUsersList');
    if (!container) return;
    
    try {
        const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=getBlockedUsers');
        const data = await response.json();
        
        if (data.success && data.users && data.users.length > 0) {
            container.innerHTML = data.users.map(user => `
                <div class="blocked-user">
                    <div class="user-info">
                        <img src="${user.avatar || BASE_PATH + 'uploads/user_dp/default_user_dp.jpg'}" alt="${user.first_name}" onerror="this.src='${BASE_PATH + 'uploads/user_dp/default_user_dp.jpg'}'">
                        <div>
                            <h4>${user.first_name} ${user.last_name}</h4>
                            <p>@${user.username}</p>
                        </div>
                    </div>
                    <button class="btn-secondary unblock-btn" data-user-id="${user.id}">Unblock</button>
                </div>
            `).join('');
            
            // Add unblock event listeners
            container.querySelectorAll('.unblock-btn').forEach(btn => {
                btn.addEventListener('click', unblockUser);
            });
        } else {
            container.innerHTML = '<p style="color: var(--color-gray); text-align: center; padding: 2rem;">No blocked users</p>';
        }
    } catch (error) {
        console.error('Failed to load blocked users:', error);
        container.innerHTML = '<p style="color: var(--color-danger); text-align: center; padding: 2rem;">Failed to load blocked users</p>';
    }
}

// Unblock user
async function unblockUser(e) {
    const userId = e.target.getAttribute('data-user-id');
    
    if (!confirm('Are you sure you want to unblock this user?')) {
        return;
    }
    
    const button = e.target;
    const originalText = button.textContent;
    button.textContent = 'Unblocking...';
    button.disabled = true;
    
    try {
        const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=unblockUser', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('User unblocked successfully', 'success');
            loadBlockedUsers(); // Reload the list
        } else {
            showMessage(data.message, 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Failed to unblock user:', error);
        showMessage('Failed to unblock user', 'error');
        button.textContent = originalText;
        button.disabled = false;
    }
}

// Initialize form validation
function initializeFormValidation() {
    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value;
            if (email && !isValidEmail(email)) {
                showFieldError(this, 'Please enter a valid email address');
            } else {
                clearFieldError(this);
            }
        });
    }
    
    // Phone validation
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            const phone = this.value;
            if (phone && !isValidPhone(phone)) {
                showFieldError(this, 'Please enter a valid phone number');
            } else {
                clearFieldError(this);
            }
        });
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation helper
function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Show field error
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = 'color: var(--color-danger); font-size: 0.8rem; margin-top: 0.25rem;';
    
    field.parentNode.appendChild(errorDiv);
}

// Clear field error
function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
}

function initializePrivacyExplanations() {
    const privacyForm = document.getElementById('privacyForm');
    if (!privacyForm) return;

    const explanations = {
        profile_visibility: {
            'everyone': 'Your profile is visible to everyone on Hanthana',
            'friends': 'Only your friends can see your profile',
            'private': 'Only you can see your profile'
        },
        post_visibility: {
            'everyone': 'Your posts are visible to everyone',
            'friends': 'Only your friends can see your posts', 
            'private': 'Only you can see your posts'
        },
        friend_request_visibility: {
            'everyone': 'Anyone can send you friend requests',
            'friends_of_friends': 'Only friends of your friends can send requests',
            'none': 'No one can send you friend requests'
        }
    };

    // Create explanation container
    const explanationContainer = document.createElement('div');
    explanationContainer.id = 'privacyExplanations';
    explanationContainer.style.cssText = `
        background: var(--color-light);
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
        border-left: 4px solid var(--color-primary);
    `;
    
    privacyForm.appendChild(explanationContainer);

    // Update explanations when settings change
    const updateExplanations = () => {
        const profileVis = document.querySelector('select[name="profile_visibility"]').value;
        const postVis = document.querySelector('select[name="post_visibility"]').value;
        const friendReqVis = document.querySelector('select[name="friend_request_visibility"]').value;

        explanationContainer.innerHTML = `
            <h4 style="margin-top: 0; color: var(--color-dark);">Current Privacy Settings:</h4>
            <div style="display: grid; gap: 0.5rem;">
                <div>
                    <strong>Profile:</strong> ${explanations.profile_visibility[profileVis]}
                </div>
                <div>
                    <strong>Posts:</strong> ${explanations.post_visibility[postVis]}
                </div>
                <div>
                    <strong>Friend Requests:</strong> ${explanations.friend_request_visibility[friendReqVis]}
                </div>
            </div>
            <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(79, 70, 229, 0.1); border-radius: 0.25rem;">
                <small style="color: var(--color-primary);">
                    <i class="uil uil-info-circle"></i>
                    These settings take effect immediately and apply to all new content.
                </small>
            </div>
        `;
    };

    // Listen for changes
    const privacySelects = privacyForm.querySelectorAll('select');
    privacySelects.forEach(select => {
        select.addEventListener('change', updateExplanations);
    });

    // Initial update
    updateExplanations();
}

// Enhanced form submission with privacy feedback
async function submitFormData(formData, action) {
    const submitButton = document.querySelector(`#${action.replace('update', '').toLowerCase()}-section button[type="submit"]`);
    
    if (submitButton) {
        const originalText = submitButton.textContent;
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
            showMessage(data.message, 'success');
            
            // Special handling for privacy settings
            if (action === 'updatePrivacy') {
                showMessage('Privacy settings updated successfully! These changes are now active.', 'success');
                
                // Update any visible UI elements if needed
                updatePrivacyUI();
            }
            
            if (action === 'updatePassword') {
                document.getElementById('passwordForm').reset();
            }
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Failed to save changes', 'error');
    } finally {
        if (submitButton) {
            submitButton.textContent = submitButton.getAttribute('data-original-text') || 'Save Changes';
            submitButton.disabled = false;
        }
    }
}

function updatePrivacyUI() {
    // This function can update any real-time UI elements based on privacy changes
    console.log('Privacy settings updated - UI refresh may be needed');
    
    // Example: Update friend request button states if visible
    const friendButtons = document.querySelectorAll('.friend-request-btn');
    friendButtons.forEach(btn => {
        // You might want to refresh friend request buttons
        btn.disabled = false; // Reset any disabled states
    });
}
