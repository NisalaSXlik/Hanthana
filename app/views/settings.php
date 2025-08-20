<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Hanthana Events</title>
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <a href="event.html">Hanthana</a>
                </div>
            </div>
            
            <div class="nav-center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Search settings...">
                </div>
            </div>
            
            <div class="nav-right">
                <div class="calendar-icon" onclick="toggleCalendarPopup()">
                    <i class="fas fa-calendar-alt" style="font-size: 1.2rem; color: var(--color-primary); cursor: pointer;"></i>
                    <div class="calendar-popup" id="calendarPopup">
                        <div class="calendar-popup-header">
                            <span>Events for <span id="selectedDate"></span></span>
                            <button class="close-popup" onclick="closeCalendarPopup()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="calendar-popup-body" id="calendarEvents">
                            <div class="calendar-event-item">
                                <h5>AI in Healthcare Symposium</h5>
                                <p>March 15, 2024 • 2:00 PM</p>
                            </div>
                            <div class="calendar-event-item">
                                <h5>Data Science Workshop</h5>
                                <p>March 20, 2024 • 2:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="notification" onclick="toggleNotificationsPopup()">
                    <i class="fas fa-bell" style="font-size: 1.2rem; color: var(--color-primary);"></i>
                    <div class="notification-count">3</div>
                    <div class="notifications-popup" id="notificationsPopup">
                        <div>
                            <div class="profile-picture">
                                <img src="/placeholder.svg?height=40&width=40&text=Admin" alt="Admin">
                            </div>
                            <div class="notification-body">
                                <b>Event Approved</b> Your AI Healthcare Symposium has been approved.
                                <small>2 minutes ago</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="profile-picture" onclick="toggleProfileDropdown()">
                    <img src="/placeholder.svg?height=40&width=40&text=JD" alt="Profile">
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="profile.html"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.html"><i class="fas fa-cog"></i> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="settings-container">
        <div class="settings-header">
            <h1>Settings</h1>
            <p>Manage your account preferences, privacy, and security.</p>
        </div>

        <div class="settings-nav">
            <button class="settings-nav-btn active" data-tab="general">
                <i class="fas fa-cog"></i> General
            </button>
            <button class="settings-nav-btn" data-tab="notifications">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <button class="settings-nav-btn" data-tab="privacy">
                <i class="fas fa-lock"></i> Privacy
            </button>
            <button class="settings-nav-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i> Security
            </button>
            <button class="settings-nav-btn" data-tab="data">
                <i class="fas fa-database"></i> Data & Export
            </button>
        </div>

        <div class="settings-content">
            <!-- General Settings Tab -->
            <div class="settings-tab active" id="generalTab">
                <div class="settings-section">
                    <h3>Profile Information</h3>
                    <form class="settings-form">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" value="John Doe">
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Email Address</label>
                            <input type="email" id="emailAddress" value="john.doe@university.edu" disabled>
                            <small>Email cannot be changed.</small>
                        </div>
                        <div class="form-group">
                            <label for="studentId">Student ID</label>
                            <input type="text" id="studentId" value="U1234567">
                        </div>
                        <div class="form-group">
                            <label for="department">Department/Faculty</label>
                            <input type="text" id="department" value="Computer Science">
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" rows="4" placeholder="Tell us about yourself...">Passionate CS student interested in AI, data science, and web development.</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>

                <div class="settings-section">
                    <h3>Preferences</h3>
                    <form class="settings-form">
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone">
                                <option value="GMT+5:30">GMT+5:30 (Colombo)</option>
                                <option value="GMT+0:00">GMT+0:00 (London)</option>
                                <option value="GMT-5:00">GMT-5:00 (New York)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language">
                                <option value="en">English</option>
                                <option value="si">Sinhala</option>
                                <option value="ta">Tamil</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Preferences</button>
                    </form>
                </div>
            </div>

            <!-- Notifications Settings Tab -->
            <div class="settings-tab" id="notificationsTab">
                <div class="settings-section">
                    <h3>Email Notifications</h3>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                            <span>Event Reminders <small>Receive reminders for upcoming events you're interested in or going to.</small></span>
                        </label>
                        <label class="toggle-option">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                            <span>New Event Alerts <small>Get notified about new events matching your interests.</small></span>
                        </label>
                        <label class="toggle-option">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                            <span>Organizer Updates <small>Receive important announcements from event organizers.</small></span>
                        </label>
                        <label class="toggle-option">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                            <span>Approval Status <small>Updates on the approval status of your submitted events.</small></span>
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>In-App Notifications</h3>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                            <span>Activity Feed <small>Notifications for comments, likes, and shares on your events.</small></span>
                        </label>
                        <label class="toggle-option">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                            <span>Direct Messages <small>Receive alerts for new direct messages.</small></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Privacy Settings Tab -->
            <div class="settings-tab" id="privacyTab">
                <div class="settings-section">
                    <h3>Profile Visibility</h3>
                    <div class="form-group">
                        <label for="profileVisibility">Who can see your profile?</label>
                        <select id="profileVisibility">
                            <option value="public">Everyone</option>
                            <option value="friends">Friends Only</option>
                            <option value="private">Only Me</option>
                        </select>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>Event Participation Visibility</h3>
                    <div class="form-group">
                        <label for="participationVisibility">Who can see events you're attending?</label>
                        <select id="participationVisibility">
                            <option value="public">Everyone</option>
                            <option value="friends">Friends Only</option>
                            <option value="private">Only Me</option>
                        </select>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>Data Sharing</h3>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                            <span>Share anonymized data for research <small>Help improve university services by sharing anonymized usage data.</small></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Security Settings Tab -->
            <div class="settings-tab" id="securityTab">
                <div class="settings-section">
                    <h3>Password</h3>
                    <form class="settings-form">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword">
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword">
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword">
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <div class="settings-section">
                    <h3>Two-Factor Authentication (2FA)</h3>
                    <div class="security-status">
                        <div class="status-indicator disabled">
                            <i class="fas fa-times-circle"></i>
                            <span>2FA is Disabled</span>
                        </div>
                        <button class="btn btn-primary" onclick="enableTwoFactor()">Enable 2FA</button>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Add an extra layer of security to your account by enabling two-factor authentication.</p>
                </div>

                <div class="settings-section">
                    <h3>Login Sessions</h3>
                    <div class="login-sessions">
                        <div class="session-item current">
                            <div class="session-info">
                                <h4>Current Session</h4>
                                <p>Browser: Chrome on Windows</p>
                                <p>Location: Kandy, Sri Lanka</p>
                                <small>Last Active: Just now</small>
                            </div>
                            <span class="session-badge current">Active</span>
                        </div>
                        <div class="session-item">
                            <div class="session-info">
                                <h4>Previous Session</h4>
                                <p>Browser: Firefox on macOS</p>
                                <p>Location: Colombo, Sri Lanka</p>
                                <small>Last Active: March 1, 2024, 10:00 AM</small>
                            </div>
                            <button class="btn btn-secondary btn-sm">Revoke</button>
                        </div>
                    </div>
                    <button class="btn btn-danger btn-sm">Revoke All Other Sessions</button>
                </div>
            </div>

            <!-- Data & Export Settings Tab -->
            <div class="settings-tab" id="dataTab">
                <div class="settings-section">
                    <h3>Export Data</h3>
                    <p class="text-sm text-gray-600 mb-4">Download a copy of your account data, including profile information, event history, and preferences.</p>
                    <div class="data-actions">
                        <button class="btn btn-primary" onclick="downloadData()">
                            <i class="fas fa-download"></i> Download My Data
                        </button>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>Account Deletion</h3>
                    <p class="text-sm text-gray-600 mb-4">Permanently delete your Hanthana Events account and all associated data. This action cannot be undone.</p>
                    <div class="data-actions">
                        <button class="btn btn-danger" onclick="requestDataDeletion()">
                            <i class="fas fa-trash-alt"></i> Request Account Deletion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/NewHanthaneAfterProperMVC/public/js/script.js"></script>
</body>
</html>
