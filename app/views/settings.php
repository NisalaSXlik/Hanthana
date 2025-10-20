<?php
// Simple session check example
session_start();
if (!isset($_SESSION['user_id'])) {
    // In a real app, you would redirect to login
    // header('Location: login.php');
    // exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Social Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Include existing CSS files -->
    <link rel="stylesheet" href="../../public/css/general.css">
    <link rel="stylesheet" href="../../public/css/navbar.css"> 
    <link rel="stylesheet" href="../../public/css/mediaquery.css">
    <link rel="stylesheet" href="../../public/css/calender.css">
    <link rel="stylesheet" href="../../public/css/notificationpopup.css">
    
    <!-- Settings specific CSS -->
    <link rel="stylesheet" href="../../public/css/settings.css">
</head>
<body>
    <!-- Navbar -->
    <nav>
        <div class="container">
            <div class="nav-left">
                <a href="#" class="logo">SocialApp</a>
            </div>
            
            <div class="nav-center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Search for creators, inspirations, and projects">
                </div>
            </div>
            
            <div class="nav-right">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                    <div class="notifications-popup">
                        <div>
                            <div class="profile-picture">
                                <img src="https://ui-avatars.com/api/?name=John+Doe&background=0ea5e9&color=fff" alt="User">
                            </div>
                            <div class="notification-body">
                                <b>John Doe</b> liked your post
                                <small>10 MINUTES AGO</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                <img src="https://ui-avatars.com/api/?name=Jane+Smith&background=10b981&color=fff" alt="User">
                            </div>
                            <div class="notification-body">
                                <b>Jane Smith</b> commented on your photo
                                <small>1 HOUR AGO</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                <img src="https://ui-avatars.com/api/?name=Mike+Johnson&background=ef4444&color=fff" alt="User">
                            </div>
                            <div class="notification-body">
                                <b>Mike Johnson</b> shared your story
                                <small>3 HOURS AGO</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-icon">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="calendar-popup">
                        <div class="calender">
                            <div class="month">
                                <i class="fas fa-angle-left prev"></i>
                                <div class="date">December 2023</div>
                                <i class="fas fa-angle-right next"></i>
                            </div>
                            <div class="weekdays">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>
                            <div class="days">
                                <!-- Calendar days would be populated by JavaScript -->
                            </div>
                            <div class="goto-today">
                                <div class="goto">
                                    <input type="text" placeholder="mm/yyyy" class="date-input">
                                    <button class="goto-btn">Go</button>
                                </div>
                                <button class="today-btn">Today</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-picture" id="profileDropdownTrigger">
                    <img src="https://ui-avatars.com/api/?name=Lithmal+Perera&background=0ea5e9&color=fff" alt="Profile">
                </div>
                
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="#">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Left Sidebar -->
            <div class="left">
                <div class="profile">
                    <div class="profile-picture">
                        <img src="https://ui-avatars.com/api/?name=Lithmal+Perera&background=0ea5e9&color=fff" alt="Profile">
                    </div>
                    <div class="handle">
                        <h4>Lithmal Perera</h4>
                        <p>@lithmal</p>
                    </div>
                </div>
                
                <!-- Settings Navigation -->
                <div class="side-bar">
                    <div class="settings-nav">
                        <a href="#personal-info" class="menu-item active" data-section="personal-info">
                            <i class="fas fa-user-circle"></i>
                            <h3>Personal Information</h3>
                        </a>
                        <a href="#change-password" class="menu-item" data-section="change-password">
                            <i class="fas fa-lock"></i>
                            <h3>Change Password</h3>
                        </a>
                        <a href="#privacy" class="menu-item" data-section="privacy">
                            <i class="fas fa-shield-alt"></i>
                            <h3>Privacy Settings</h3>
                        </a>
                        <a href="#preferences" class="menu-item" data-section="preferences">
                            <i class="fas fa-sliders-h"></i>
                            <h3>Preferences</h3>
                        </a>
                    </div>
                </div>
                
                <!-- Groups Section -->
                <div class="joined-groups">
                    <h4>Groups You've Joined</h4>
                    <div class="group-list">
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="group-info">
                                <h5>Colombo Foodies</h5>
                                <p>12.5K members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="group-info">
                                <h5>SL Photography Club</h5>
                                <p>8.2K members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-hiking"></i>
                            </div>
                            <div class="group-info">
                                <h5>Hiking Sri Lanka</h5>
                                <p>5.7K members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">See All Groups</button>
                </div>
            </div>
            
            <!-- Middle Section - Settings Content -->
            <div class="middle">
                <div class="settings-container">
                    <div class="settings-header">
                        <h1>Settings</h1>
                        <p>Manage your account settings and preferences</p>
                    </div>
                    
                    <div class="settings-content">
                        <!-- Personal Information Section -->
                        <div class="settings-section active" id="personal-info-section">
                            <h2>Personal Information</h2>
                            <div class="settings-card">
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Name</h4>
                                        <p>Lithmal Perera</p>
                                    </div>
                                    <button class="btn btn-primary">Change</button>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Email</h4>
                                        <p>lithmal@example.com</p>
                                    </div>
                                    <button class="btn btn-primary">Change</button>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Phone Number</h4>
                                        <p>+94 77 123 4567</p>
                                    </div>
                                    <button class="btn btn-primary">Change</button>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Date of Birth</h4>
                                        <p>January 15, 1990</p>
                                    </div>
                                    <button class="btn btn-primary">Change</button>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Location</h4>
                                        <p>Colombo, Sri Lanka</p>
                                    </div>
                                    <button class="btn btn-primary">Change</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Change Password Section -->
                        <div class="settings-section" id="change-password-section">
                            <h2>Change Password</h2>
                            <div class="settings-card">
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Current Password</h4>
                                        <input type="password" class="setting-input" placeholder="Enter your current password">
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>New Password</h4>
                                        <input type="password" class="setting-input" placeholder="Enter new password">
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Confirm New Password</h4>
                                        <input type="password" class="setting-input" placeholder="Confirm new password">
                                    </div>
                                </div>
                                
                                <button class="btn btn-primary" style="margin-top: 1rem;">Update Password</button>
                            </div>
                        </div>
                        
                        <!-- Privacy Settings Section -->
                        <div class="settings-section" id="privacy-section">
                            <h2>Privacy Settings</h2>
                            <div class="settings-card">
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Profile Visibility</h4>
                                        <p>Who can see your profile</p>
                                    </div>
                                    <select class="setting-select">
                                        <option>Public</option>
                                        <option>Friends Only</option>
                                        <option>Private</option>
                                    </select>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Activity Status</h4>
                                        <p>Show when you're active</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="activity-toggle" class="toggle-input" checked>
                                        <label for="activity-toggle" class="toggle-label"></label>
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Data Sharing</h4>
                                        <p>Allow data for personalization</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="data-sharing-toggle" class="toggle-input">
                                        <label for="data-sharing-toggle" class="toggle-label"></label>
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Search Visibility</h4>
                                        <p>Allow search engines to link to your profile</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="search-toggle" class="toggle-input" checked>
                                        <label for="search-toggle" class="toggle-label"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preferences Section -->
                        <div class="settings-section" id="preferences-section">
                            <h2>Preferences</h2>
                            <div class="settings-card">
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Theme</h4>
                                        <p>Choose your preferred theme</p>
                                    </div>
                                    <select class="setting-select" id="theme-select">
                                        <option value="light">Light Mode</option>
                                        <option value="dark">Dark Mode</option>
                                        <option value="auto">Auto (System)</option>
                                    </select>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Language</h4>
                                        <p>Select your preferred language</p>
                                    </div>
                                    <select class="setting-select">
                                        <option>English</option>
                                        <option>Sinhala</option>
                                        <option>Tamil</option>
                                    </select>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Timezone</h4>
                                        <p>Set your local timezone</p>
                                    </div>
                                    <select class="setting-select">
                                        <option>Asia/Colombo (UTC+5:30)</option>
                                        <option>UTC-8:00 Pacific Time</option>
                                        <option>UTC-5:00 Eastern Time</option>
                                        <option>UTC+0:00 GMT</option>
                                        <option>UTC+1:00 Central European Time</option>
                                    </select>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Email Notifications</h4>
                                        <p>Receive email updates</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="email-toggle" class="toggle-input" checked>
                                        <label for="email-toggle" class="toggle-label"></label>
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Push Notifications</h4>
                                        <p>Receive push notifications</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="push-toggle" class="toggle-input" checked>
                                        <label for="push-toggle" class="toggle-label"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
                       <!-- Right Sidebar -->
            <div class="right">
                <!-- Messages -->
                <div class="messages-card">
                    <h4>Messages</h4>
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="search" placeholder="Search messages">
                    </div>
                    <div class="message-list">
                        <div class="message-item">
                            <div class="profile-picture">
                                <img src="https://ui-avatars.com/api/?name=Minthaka+J&background=0ea5e9&color=fff" alt="Minthaka J">
                            </div>
                            <div class="message-info">
                                <h5>Minthaka J.</h5>
                                <p>Are we still meeting tomorrow?</p>
                            </div>
                        </div>
                        <div class="message-item">
                            <div class="profile-picture">
                                <img src="https://ui-avatars.com/api/?name=Lahiru+F&background=10b981&color=fff" alt="Lahiru F">
                            </div>
                            <div class="message-info">
                                <h5>Lahiru F.</h5>
                                <p>Sent you the event details</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Friend Requests -->
                <div class="friend-requests">
                    <h4>Friend Requests (5)</h4>
                    <div class="request-list">
                        <div class="request-item">
                            <div class="request-profile">
                                <div class="profile-picture">
                                    <img src="https://ui-avatars.com/api/?name=Emma+Watson&background=10b981&color=fff" alt="Emma Watson">
                                </div>
                                <div class="request-info">
                                    <h5>Emma Watson</h5>
                                    <p>8 mutual friends</p>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn btn-primary btn-sm">Accept</button>
                                <button class="btn btn-secondary btn-sm">Decline</button>
                            </div>
                        </div>
                        
                        <div class="request-item">
                            <div class="request-profile">
                                <div class="profile-picture">
                                    <img src="https://ui-avatars.com/api/?name=Minthaka&background=0ea5e9&color=fff" alt="Minthaka">
                                </div>
                                <div class="request-info">
                                    <h5>Minthaka</h5>
                                    <p>28 mutual friends</p>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn btn-primary btn-sm">Accept</button>
                                <button class="btn btn-secondary btn-sm">Decline</button>
                            </div>
                        </div>
                        
                        <div class="request-item">
                            <div class="request-profile">
                                <div class="profile-picture">
                                    <img src="https://ui-avatars.com/api/?name=Lahiru&background=ef4444&color=fff" alt="Lahiru">
                                </div>
                                <div class="request-info">
                                    <h5>Lahiru</h5>
                                    <p>85 mutual friends</p>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn btn-primary btn-sm">Accept</button>
                                <button class="btn btn-secondary btn-sm">Decline</button>
                            </div>
                        </div>
                        
                        <!-- Tharusha without buttons as shown in image -->
                        <div class="request-item">
                            <div class="request-profile">
                                <div class="profile-picture">
                                    <img src="https://ui-avatars.com/api/?name=Tharusha&background=64748b&color=fff" alt="Tharusha">
                                </div>
                                <div class="request-info">
                                    <h5>Tharusha</h5>
                                    <p>82 mutual friends</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">See All Requests</button>
                </div>
                
                <!-- Popular Groups -->
                <div class="popular-groups">
                    <h4>Popular Groups</h4>
                    <div class="group-list">
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-music"></i>
                            </div>
                            <div class="group-info">
                                <h5>Music Lovers</h5>
                                <p>2105 members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="group-info">
                                <h5>Photography</h5>
                                <p>1823 members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="group-info">
                                <h5>Gaming</h5>
                                <p>3150 members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">Explore More</button>
                </div>
            </div>
    </main>

    <!-- Include existing JavaScript files -->
    <script src="../../public/js/navbar.js"></script>
    <script src="../../public/js/calender.js"></script>
    <script src="../../public/js/notificationpopup.js"></script>
    <script src="../../public/js/general.js"></script>
    
    <!-- Settings specific JavaScript -->
    <script src="../../public/js/settings.js"></script>
</body>
</html>