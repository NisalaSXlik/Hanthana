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

$availableUniversities = [
    'University of Colombo',
    'University of Peradeniya',
    'University of Moratuwa',
    'University of Sri Jayewardenepura',
    'University of Kelaniya',
    'University of Ruhuna',
    'University of Jaffna',
    'Uva Wellassa University',
    'Rajarata University of Sri Lanka',
    'Sabaragamuwa University of Sri Lanka',
    'South Eastern University of Sri Lanka',
    'Eastern University Sri Lanka',
    'Wayamba University of Sri Lanka',
    'University of Vavuniya'
];

$selectedUniversity = trim((string)($currentUser['university'] ?? ''));

$friendRequests = $friendRequests ?? [];

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
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/settings.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/report.css">
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
                <div class="settings-container">
                    <div class="settings-header">
                        <h1>Settings</h1>
                        <p>Manage your account from one page.</p>
                    </div>

                    <div class="settings-content">
                        <section class="settings-card" id="accountDetailsSection">
                            <div class="card-heading">
                                <h3>Change Account Details</h3>
                                <p>Update your personal and profile information.</p>
                            </div>

                                <form id="profileForm" class="settings-form hf-form"
                                    data-current-username="<?php echo htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES); ?>"
                                    data-current-email="<?php echo htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES); ?>"
                                    data-current-phone="<?php echo htmlspecialchars($currentUser['phone_number'] ?? '', ENT_QUOTES); ?>">
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
                                    <span id="username-status" class="hf-field-status" aria-live="polite"></span>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required
                                           pattern="^[^@\s]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.ac\.lk$"
                                           title="Use university email ending with .ac.lk (e.g., 2023cs140@stu.ucsc.cmb.ac.lk)">
                                    <span id="email-status" class="hf-field-status" aria-live="polite"></span>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone_number" value="<?php echo htmlspecialchars($currentUser['phone_number'] ?? ''); ?>">
                                    <span id="phone-status" class="hf-field-status" aria-live="polite"></span>
                                </div>

                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="university">University</label>
                                        <select id="university" name="university">
                                            <option value="">Select University</option>
                                            <?php if ($selectedUniversity !== '' && !in_array($selectedUniversity, $availableUniversities, true)): ?>
                                                <option value="<?php echo htmlspecialchars($selectedUniversity); ?>" selected>
                                                    <?php echo htmlspecialchars($selectedUniversity); ?> (Current)
                                                </option>
                                            <?php endif; ?>
                                            <?php foreach ($availableUniversities as $universityOption): ?>
                                                <option value="<?php echo htmlspecialchars($universityOption); ?>" <?php echo $selectedUniversity === $universityOption ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($universityOption); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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

                                <button type="submit" class="btn-primary">Save Account Details</button>
                            </form>
                        </section>

                        <section class="settings-card" id="blockedUsersSection">
                            <div class="card-heading">
                                <h3>Blocked Users</h3>
                                <p>Manage people you have blocked.</p>
                            </div>

                            <div id="blockedUsersList">
                                <div class="loading-blocked">
                                    <i class="uil uil-spinner-alt"></i>
                                    <p>Loading blocked users...</p>
                                </div>
                            </div>
                        </section>

                        <section class="settings-card" id="passwordSection">
                            <div class="card-heading">
                                <h3>Change Password</h3>
                                <p>Choose a strong password you have not used before.</p>
                            </div>

                            <form id="passwordForm" class="settings-form hf-form">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" id="currentPassword" name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" id="newPassword" name="new_password" required>
                                    <small>Password must be at least 8 characters long.</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn-primary">Update Password</button>
                            </form>
                        </section>

                        <section class="settings-card settings-card-danger" id="deleteAccountSection">
                            <div class="card-heading">
                                <h3>Delete Account</h3>
                                <p>This action is permanent. Your account will be deactivated immediately.</p>
                            </div>

                            <form id="deleteAccountForm" class="settings-form hf-form">
                                <div class="form-group">
                                    <label for="deleteAccountPassword">Current Password</label>
                                    <input type="password" id="deleteAccountPassword" name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label for="deleteAccountConfirm">Type DELETE to confirm</label>
                                    <input type="text" id="deleteAccountConfirm" name="confirm_text" required>
                                </div>

                                <button type="submit" class="btn-danger">Delete Account</button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit" id="openChatWidget" style="cursor: pointer;"></i>
                    </div>
                    <form class="hf-form hf-inline" onsubmit="return false;">
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages" id="sidebarChatSearch">
                    </div>
                    </form>
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
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/settings.js"></script>
    
    <script>
        (async function loadSidebarMessages() {
            const listContainer = document.getElementById('sidebarMessageList');
            const editIcon = document.getElementById('openChatWidget');

            if (!listContainer) {
                return;
            }

            try {
                const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Chat&action=listConversations');
                const data = await response.json();
                const conversations = Array.isArray(data) ? data : (data.data || []);

                listContainer.innerHTML = '';

                if (!conversations.length) {
                    listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>No messages yet</p></div>';
                    return;
                }

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
                        const chatIcon = document.getElementById('chatIcon');
                        if (chatIcon) {
                            chatIcon.click();
                        }
                    });

                    listContainer.appendChild(messageDiv);
                });
            } catch (error) {
                console.error('Failed to load sidebar messages:', error);
                listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>Failed to load messages</p></div>';
            }

            if (editIcon) {
                editIcon.addEventListener('click', () => {
                    const chatIcon = document.getElementById('chatIcon');
                    if (chatIcon) {
                        chatIcon.click();
                    }
                });
            }
        })();
    </script>
</body>
</html>