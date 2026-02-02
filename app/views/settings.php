<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$currentUserId = $_SESSION['user_id'];

require_once __DIR__ . '/../models/UserModel.php';
$userModel = new UserModel();
$currentUser = $userModel->findById($currentUserId);

$friendRequests = $friendRequests ?? [];

// Get user settings
require_once __DIR__ . '/../models/SettingsModel.php';
$settingsModel = new SettingsModel();
$userSettings = $settingsModel->getUserSettings($currentUserId);

// Get friend requests for sidebar
$friendRequests = [];
try {
    if (file_exists(__DIR__ . '/../models/FriendModel.php')) {
        require_once __DIR__ . '/../models/FriendModel.php';
        $friendModel = new FriendModel();
        $friendRequests = $friendModel->getIncomingRequests($currentUserId, 5);
    }
} catch (Exception $e) {
    error_log("FriendModel error: " . $e->getMessage());
    $friendRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Hanthana</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/settings.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    
    <script> 
        const BASE_PATH = '<?php echo BASE_PATH; ?>'; 
        const USER_ID = <?php echo $currentUserId; ?>;
    </script>
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php $activeSidebar = 'settings'; include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <!-- Settings Navigation -->
                <div class="settings-nav-container">
                    <div class="settings-nav">
                        <a href="#" class="menu-item active" data-section="account">
                            <i class="uil uil-user"></i>
                            <h3>Account</h3>
                        </a>
                        <a href="#" class="menu-item" data-section="privacy">
                            <i class="uil uil-lock"></i>
                            <h3>Privacy</h3>
                        </a>
                        <a href="#" class="menu-item" data-section="notifications">
                            <i class="uil uil-bell"></i>
                            <h3>Notifications</h3>
                        </a>
                        <a href="#" class="menu-item" data-section="appearance">
                            <i class="uil uil-palette"></i>
                            <h3>Appearance</h3>
                        </a>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="settings-container">
                    <!-- Account Settings -->
                    <div id="account-section" class="settings-section active">
                        <div class="settings-header">
                            <h1>Account Settings</h1>
                            <p>Manage your account information</p>
                        </div>

                        <div class="settings-content">
                            <!-- Profile Information -->
                            <div class="settings-card">
                                <h3>Profile Information</h3>
                                <form id="profileForm" class="settings-form">
                                    <div id="profileFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="firstName">First Name</label>
                                            <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastName">Last Name</label>
                                            <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone_number" value="<?php echo htmlspecialchars($currentUser['phone_number'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="bio">Bio</label>
                                        <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="university">University</label>
                                            <input type="text" id="university" name="university" value="<?php echo htmlspecialchars($currentUser['university'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="location">Location</label>
                                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($currentUser['location'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="dateOfBirth">Date of Birth</label>
                                        <input type="date" id="dateOfBirth" name="date_of_birth" value="<?php echo htmlspecialchars($currentUser['date_of_birth'] ?? ''); ?>">
                                    </div>

                                    <button type="submit" class="btn-primary">Save Changes</button>
                                </form>
                            </div>

                            <!-- Change Password -->
                            <div class="settings-card">
                                <h3>Change Password</h3>
                                <form id="passwordForm" class="settings-form">
                                    <div id="passwordFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-group">
                                        <label for="currentPassword">Current Password</label>
                                        <input type="password" id="currentPassword" name="current_password" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="newPassword">New Password</label>
                                        <input type="password" id="newPassword" name="new_password" required>
                                        <small>Password must be at least 8 characters long</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm New Password</label>
                                        <input type="password" id="confirmPassword" name="confirm_password" required>
                                    </div>

                                    <button type="submit" class="btn-primary">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings -->
                    <div id="privacy-section" class="settings-section">
                        <div class="settings-header">
                            <h1>Privacy Settings</h1>
                            <p>Control who can see your information and interact with you</p>
                        </div>

                        <div class="settings-content">
                            <div class="settings-card">
                                <h3>Profile & Content Privacy</h3>
                                <form id="privacyForm" class="settings-form">
                                    <div id="privacyFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-group">
                                        <label for="profile_visibility">Who can see your profile?</label>
                                        <select id="profile_visibility" name="profile_visibility">
                                            <option value="everyone" <?php echo (($userSettings['profile_visibility'] ?? 'everyone') === 'everyone') ? 'selected' : ''; ?>>Everyone</option>
                                            <option value="friends_only" <?php echo (($userSettings['profile_visibility'] ?? 'everyone') === 'friends_only') ? 'selected' : ''; ?>>Friends Only</option>
                                            <option value="only_me" <?php echo (($userSettings['profile_visibility'] ?? 'everyone') === 'only_me') ? 'selected' : ''; ?>>Only Me</option>
                                        </select>
                                        <small>Controls who can view your profile page and basic information</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="post_visibility">Who can see your posts?</label>
                                        <select id="post_visibility" name="post_visibility">
                                            <option value="everyone" <?php echo (($userSettings['post_visibility'] ?? 'everyone') === 'everyone') ? 'selected' : ''; ?>>Everyone</option>
                                            <option value="friends_only" <?php echo (($userSettings['post_visibility'] ?? 'everyone') === 'friends_only') ? 'selected' : ''; ?>>Friends Only</option>
                                            <option value="only_me" <?php echo (($userSettings['post_visibility'] ?? 'everyone') === 'only_me') ? 'selected' : ''; ?>>Only Me</option>
                                        </select>
                                        <small>Controls who can see your posts in their feed and on your profile</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="friend_request_visibility">Who can send you friend requests?</label>
                                        <select id="friend_request_visibility" name="friend_request_visibility">
                                            <option value="everyone" <?php echo (($userSettings['friend_request_visibility'] ?? 'everyone') === 'everyone') ? 'selected' : ''; ?>>Everyone</option>
                                            <option value="friends_of_friends" <?php echo (($userSettings['friend_request_visibility'] ?? 'everyone') === 'friends_of_friends') ? 'selected' : ''; ?>>Friends of Friends</option>
                                            <option value="no_one" <?php echo (($userSettings['friend_request_visibility'] ?? 'everyone') === 'no_one') ? 'selected' : ''; ?>>No One</option>
                                        </select>
                                        <small>Controls who can send you friend requests</small>
                                    </div>

                                    <div class="privacy-options">
                                        <h4>Contact Information</h4>
                                        <div class="form-group checkbox-group">
                                            <label>
                                                <input type="checkbox" name="show_email" <?php echo (!empty($userSettings['show_email'])) ? 'checked' : ''; ?> >
                                                <span>Show email on profile</span>
                                                <small>Your email will be visible to people who can view your profile</small>
                                            </label>
                                        </div>

                                        <div class="form-group checkbox-group">
                                            <label>
                                                <input type="checkbox" name="show_phone" <?php echo (!empty($userSettings['show_phone'])) ? 'checked' : ''; ?> >
                                                <span>Show phone number on profile</span>
                                                <small>Your phone number will be visible to people who can view your profile</small>
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-primary">Save Privacy Settings</button>
                                </form>
                            </div>

                            <div class="settings-card">
                                <h3>Blocked Users</h3>
                                <div id="blockedUsersList">
                                    <div class="loading-blocked" style="text-align: center; padding: 1rem; color: var(--color-gray);">
                                        <i class="uil uil-spinner-alt" style="animation: spin 1s linear infinite;"></i>
                                        <p>Loading blocked users...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div id="notifications-section" class="settings-section">
                        <div class="settings-header">
                            <h1>Notification Settings</h1>
                            <p>Manage how you receive notifications</p>
                        </div>

                        <div class="settings-content">
                            <div class="settings-card">
                                <h3>Email Notifications</h3>
                                <form id="notificationForm" class="settings-form">
                                    <div id="notificationFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="email_comments" <?php echo (!empty($userSettings['email_comments'])) ? 'checked' : ''; ?>>
                                            <span>Comments on your posts</span>
                                            <small>Get notified when someone comments on your posts</small>
                                        </label>
                                    </div>

                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="email_likes" <?php echo (!empty($userSettings['email_likes'])) ? 'checked' : ''; ?>>
                                            <span>Likes on your posts</span>
                                            <small>Get notified when someone likes your posts</small>
                                        </label>
                                    </div>

                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="email_friend_requests" <?php echo (!empty($userSettings['email_friend_requests'])) ? 'checked' : ''; ?>>
                                            <span>Friend requests</span>
                                            <small>Get notified when someone sends you a friend request</small>
                                        </label>
                                    </div>

                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="email_messages" <?php echo (!empty($userSettings['email_messages'])) ? 'checked' : ''; ?>>
                                            <span>New messages</span>
                                            <small>Get notified when you receive new messages</small>
                                        </label>
                                    </div>

                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="email_group_activity" <?php echo (!empty($userSettings['email_group_activity'])) ? 'checked' : ''; ?>>
                                            <span>Group activity</span>
                                            <small>Get notified about activity in your groups</small>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn-primary">Save Notification Settings</button>
                                </form>
                            </div>

                            <div class="settings-card">
                                <h3>Push Notifications</h3>
                                <form id="pushForm" class="settings-form">
                                    <div id="pushFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-group checkbox-group">
                                        <label>
                                            <input type="checkbox" name="push_enabled" <?php echo (!empty($userSettings['push_enabled'])) ? 'checked' : ''; ?>>
                                            <span>Enable push notifications</span>
                                            <small>Receive browser notifications for new activity</small>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn-primary">Save Push Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Settings -->
                    <div id="appearance-section" class="settings-section">
                        <div class="settings-header">
                            <h1>Appearance Settings</h1>
                            <p>Customize how Hanthana looks</p>
                        </div>

                        <div class="settings-content">
                            <div class="settings-card">
                                <h3>Theme & Display</h3>
                                <form id="appearanceForm" class="settings-form">
                                    <div id="appearanceFormMessage" class="form-message" style="display:none;"></div>
                                    
                                    <div class="form-group">
                                        <label for="theme-select">Choose Theme</label>
                                        <select id="theme-select" name="theme">
                                            <option value="light" <?php echo (($userSettings['theme'] ?? 'light') === 'light') ? 'selected' : ''; ?>>Light</option>
                                            <option value="dark" <?php echo (($userSettings['theme'] ?? 'light') === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                            <option value="auto" <?php echo (($userSettings['theme'] ?? 'light') === 'auto') ? 'selected' : ''; ?>>Auto (System Default)</option>
                                        </select>
                                        <small>Choose your preferred color theme</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="font-size">Font Size</label>
                                        <select id="font-size" name="font_size">
                                            <option value="small" <?php echo (($userSettings['font_size'] ?? 'medium') === 'small') ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo (($userSettings['font_size'] ?? 'medium') === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo (($userSettings['font_size'] ?? 'medium') === 'large') ? 'selected' : ''; ?>>Large</option>
                                        </select>
                                        <small>Adjust the text size across the application</small>
                                    </div>

                                    <button type="submit" class="btn-primary">Save Appearance Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit" id="openChatWidget" style="cursor: pointer;"></i>
                    </div>
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages" id="sidebarChatSearch">
                    </div>
                    <div class="message-list" id="sidebarMessageList">
                        <div class="loading-messages" style="text-align: center; padding: 1rem; color: #888;">
                            <i class="uil uil-spinner-alt" style="animation: spin 1s linear infinite;"></i>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Loading messages...</p>
                        </div>
                    </div>
                </div>

                <?php include __DIR__ . '/templates/friend-requests.php'; ?>

                <div class="toast-container" id="toastContainer"></div>
            </div>
        </div>

    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-popup-header">
            <h4>Events</h4>
            <span id="popup-date">--</span>
        </div>
        <div class="calendar-popup-body" id="calendarEvents">
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script src="./js/calender.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/settings.js"></script>
    
    <script>
        // Load blocked users
        async function loadBlockedUsers() {
            try {
                const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Settings&action=getBlockedUsers');
                const data = await response.json();
                
                const blockedList = document.getElementById('blockedUsersList');
                
                if (data.success && data.users.length > 0) {
                    let html = '';
                    data.users.forEach(user => {
                        html += `
                            <div class="blocked-user-item" style="display: flex; justify-content: between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--color-light);">
                                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                                    <img src="${BASE_PATH + (user.profile_picture || 'uploads/user_dp/default_user_dp.jpg')}" 
                                         alt="${user.username}" 
                                         style="width: 40px; height: 40px; border-radius: 50%;">
                                    <div>
                                        <h5 style="margin: 0; color: var(--color-dark);">${user.first_name || ''} ${user.last_name || ''}</h5>
                                        <p style="margin: 0; color: var(--color-gray); font-size: 0.9rem;">@${user.username}</p>
                                    </div>
                                </div>
                                <button class="btn-unblock" 
                                        data-user-id="${user.user_id}" 
                                        style="background: var(--color-primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">
                                    Unblock
                                </button>
                            </div>
                        `;
                    });
                    blockedList.innerHTML = html;
                    
                    // Add unblock event listeners
                    document.querySelectorAll('.btn-unblock').forEach(btn => {
                        btn.addEventListener('click', async function() {
                            const userId = this.getAttribute('data-user-id');
                            await unblockUser(userId);
                        });
                    });
                } else {
                    blockedList.innerHTML = '<p style="color: var(--color-gray); text-align: center; padding: 2rem;">No blocked users</p>';
                }
            } catch (error) {
                console.error('Failed to load blocked users:', error);
                document.getElementById('blockedUsersList').innerHTML = '<p style="color: var(--color-danger); text-align: center;">Failed to load blocked users</p>';
            }
        }
        
        // Unblock user function
        async function unblockUser(userId) {
            if (!confirm('Are you sure you want to unblock this user?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Settings&action=unblockUser', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('User unblocked successfully', 'success');
                    loadBlockedUsers(); // Reload the list
                } else {
                    showToast(data.message || 'Failed to unblock user', 'error');
                }
            } catch (error) {
                console.error('Unblock error:', error);
                showToast('Failed to unblock user', 'error');
            }
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="uil uil-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="toast-close">
                    <i class="uil uil-times"></i>
                </button>
            `;
            
            toastContainer.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
            
            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.remove();
            });
        }
        
        // Load blocked users when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadBlockedUsers();
        });
        
        // Load top 3 conversations for sidebar
        (async function loadSidebarMessages() {
            const listContainer = document.getElementById('sidebarMessageList');
            const searchInput = document.getElementById('sidebarChatSearch');
            const editIcon = document.getElementById('openChatWidget');
            
            if (!listContainer) return;
            
            try {
                const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Chat&action=listConversations');
                const data = await response.json();
                const conversations = Array.isArray(data) ? data : (data.data || []);
                
                listContainer.innerHTML = '';
                
                if (!conversations.length) {
                    listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>No messages yet</p></div>';
                    return;
                }
                
                // Show only top 3
                const top3 = conversations.slice(0, 3);
                
                top3.forEach(conv => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message';
                    messageDiv.style.cursor = 'pointer';
                    
                    const avatarPath = conv.avatar || 'uploads/user_dp/default_user_dp.jpg';
                    const fullAvatar = avatarPath.startsWith('http') ? avatarPath : BASE_PATH + avatarPath;
                    
                    messageDiv.innerHTML = `
                        <div class="profile-picture">
                            <img src="${fullAvatar}" alt="${conv.display_name || 'User'}">
                            ${conv.is_online ? '<div class="active"></div>' : ''}
                        </div>
                        <div class="message-body">
                            <h5>${conv.display_name || 'Unknown'}</h5>
                            <p>${conv.last_message_preview || 'No messages yet'}</p>
                        </div>
                    `;
                    
                    messageDiv.addEventListener('click', () => {
                        // Open chat widget
                        const chatIcon = document.getElementById('chatIcon');
                        if (chatIcon) chatIcon.click();
                    });
                    
                    listContainer.appendChild(messageDiv);
                });
                
            } catch (error) {
                console.error('Failed to load sidebar messages:', error);
                listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>Failed to load messages</p></div>';
            }
            
            // Edit icon opens chat widget
            if (editIcon) {
                editIcon.addEventListener('click', () => {
                    const chatIcon = document.getElementById('chatIcon');
                    if (chatIcon) chatIcon.click();
                });
            }
        })();
    </script>
</body>
</html>
