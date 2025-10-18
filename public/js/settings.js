// Settings Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Settings Navigation
    const navItems = document.querySelectorAll('.settings-nav .menu-item');
    const sections = document.querySelectorAll('.settings-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Show corresponding section
            const sectionId = this.getAttribute('data-section') + '-section';
            document.getElementById(sectionId).classList.add('active');
        });
    });
    
    // Theme Selection
    const themeSelect = document.getElementById('theme-select');
    
    // Check for saved theme preference
    const currentTheme = localStorage.getItem('theme') || 'light';
    themeSelect.value = currentTheme;
    
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
    themeSelect.addEventListener('change', function() {
        const selectedTheme = this.value;
        
        if (selectedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
        
        localStorage.setItem('theme', selectedTheme);
    });
    
    // Password Visibility Toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const parent = input.parentElement;
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.className = 'password-toggle';
        toggleBtn.style.background = 'none';
        toggleBtn.style.border = 'none';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.color = 'var(--color-gray)';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '10px';
        toggleBtn.style.top = '50%';
        toggleBtn.style.transform = 'translateY(-50%)';
        
        parent.style.position = 'relative';
        parent.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Form Validation for Password Change
    const passwordForm = document.querySelector('#change-password-section .settings-card');
    const newPasswordInput = passwordForm.querySelectorAll('input[type="password"]')[1];
    const confirmPasswordInput = passwordForm.querySelectorAll('input[type="password"]')[2];
    
    function validatePasswords() {
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.style.borderColor = 'var(--color-danger)';
            return false;
        } else {
            confirmPasswordInput.style.borderColor = 'var(--color-success)';
            return true;
        }
    }
    
    if (newPasswordInput && confirmPasswordInput) {
        newPasswordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
    }
    
    // Friend Request Actions
    const acceptButtons = document.querySelectorAll('.request-actions .btn-primary');
    const declineButtons = document.querySelectorAll('.request-actions .btn-secondary');
    
    acceptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestItem = this.closest('.request-item');
            const userName = requestItem.querySelector('h5').textContent;
            
            // Show acceptance message
            showNotification(`Friend request from ${userName} accepted!`, 'success');
            
            // Remove the request item
            requestItem.style.opacity = '0';
            setTimeout(() => {
                requestItem.remove();
                
                // Update friend requests count
                updateFriendRequestsCount();
            }, 300);
        });
    });
    
    declineButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestItem = this.closest('.request-item');
            const userName = requestItem.querySelector('h5').textContent;
            
            // Show decline message
            showNotification(`Friend request from ${userName} declined`, 'info');
            
            // Remove the request item
            requestItem.style.opacity = '0';
            setTimeout(() => {
                requestItem.remove();
                
                // Update friend requests count
                updateFriendRequestsCount();
            }, 300);
        });
    });
    
    function updateFriendRequestsCount() {
        const requestCount = document.querySelectorAll('.request-item').length;
        const requestsTitle = document.querySelector('.friend-requests h4');
        
        if (requestsTitle) {
            requestsTitle.textContent = `Friend Requests (${requestCount})`;
        }
    }
    
    // Settings Save Notification
    const updateButtons = document.querySelectorAll('.btn-primary');
    
    updateButtons.forEach(button => {
        if (button.textContent.includes('Update') || button.textContent.includes('Change')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show success message
                showNotification('Settings updated successfully!', 'success');
            });
        }
    });
    
    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'var(--color-success)' : 'var(--color-primary)';
        
        notification.className = 'save-notification';
        notification.innerHTML = `
            <div style="position: fixed; top: 100px; right: 20px; background: ${bgColor}; color: white; padding: 1rem; border-radius: var(--card-border-radius); box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i> 
                ${message}
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Initialize any existing JS functionality from other files
    if (typeof initNavbar === 'function') {
        initNavbar();
    }
    
    if (typeof initNotifications === 'function') {
        initNotifications();
    }
    
    if (typeof initCalendar === 'function') {
        initCalendar();
    }
});


document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('.settings-nav .menu-item');
  const sections = document.querySelectorAll('.settings-section');

  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      // Remove active from all links
      navLinks.forEach(l => l.classList.remove('active'));
      this.classList.add('active');
      // Hide all sections
      sections.forEach(sec => sec.classList.remove('active'));
      // Show the selected section
      const target = this.getAttribute('href');
      const section = document.querySelector(target);
      if (section) section.classList.add('active');
    });
  });
});

// Dark theme styles
const darkThemeStyles = `
    <style>
        .dark-theme {
            --color-dark: #f1f5f9;
            --color-light: #1e293b;
            --color-white: #334155;
            --color-gray: #94a3b8;
        }
        
        .dark-theme body {
            background: #0f172a;
            color: #f1f5f9;
        }
        
        .dark-theme .settings-card {
            background: #334155;
        }
        
        .dark-theme .setting-input,
        .dark-theme .setting-select {
            background: #475569;
            color: #f1f5f9;
            border-color: #475569;
        }
        
        .dark-theme .setting-input::placeholder {
            color: #94a3b8;
        }
        
        .dark-theme .friend-requests,
        .dark-theme .messages-card,
        .dark-theme .popular-groups {
            background: #334155;
        }
    </style>
`;

// Add dark theme styles to head
document.head.insertAdjacentHTML('beforeend', darkThemeStyles);