<?php
                <?php
                    $friendRequests = $friendRequests ?? [];
                    include __DIR__ . '/templates/friend-requests.php';
                ?>
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
                                        <div class="password-input-container">
                                            <input type="password" class="setting-input" placeholder="Enter your current password">
                                            <button type="button" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>New Password</h4>
                                        <div class="password-input-container">
                                            <input type="password" class="setting-input" placeholder="Enter new password">
                                            <button type="button" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-item">
                                    <div class="setting-label">
                                        <h4>Confirm New Password</h4>
                                        <div class="password-input-container">
                                            <input type="password" class="setting-input" placeholder="Confirm new password">
                                            <button type="button" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
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
                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit"></i>
                    </div>
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages">
                    </div>
                    <div class="message-list">
                        <div class="message">
                            <div class="profile-picture">
                                    <img src="../../public/images/2.jpg">
                                <div class="active"></div>
                            </div>
                            <div class="message-body">
                                <h5>Minthaka J.</h5>
                                <p>Are we still meeting tomorrow?</p>
                            </div>
                        </div>
                        <div class="message">
                            <div class="profile-picture">
                                    <img src="../../public/images/6.jpg">
                            </div>
                            <div class="message-body">
                                <h5>Lahiru F.</h5>
                                <p>Sent you the event details</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="friend-requests">
        <h4>Friend Requests <span class="badge">(5)</span></h4>
        <div class="request">
            <div class="info">
                <div class="profile-picture">
                        <img src="../../public/images/4.jpg">
                </div>
                <div>
                    <h5>Emma Watson</h5>
                    <p>8 mutual friends</p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn">Accept</button>
                <button class="btn decline-btn">Decline</button>
            </div>
        </div>
        <div class="request">
            <div class="info">
                <div class="profile-picture">
                        <img src="../../public/images/2.jpg">
                </div>
                <div>
                    <h5>Minthaka</h5>
                    <p>28 mutual friends</p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn">Accept</button>
                <button class="btn decline-btn">Decline</button>
            </div>
        </div>
        <div class="request">
            <div class="info">
                <div class="profile-picture">
                        <img src="../../public/images/5.jpg">
                </div>
                <div>
                    <h5>Lahiru</h5>
                    <p>85 mutual friends</p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn">Accept</button>
                <button class="btn decline-btn">Decline</button>
            </div>
        </div>
        <div class="request">
            <div class="info">
                <div class="profile-picture">
                        <img src="../../public/images/1.jpg">
                </div>
                <div>
                    <h5>Tharusha</h5>
                    <p>82 mutual friends</p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn">Accept</button>
                <button class="btn decline-btn">Decline</button>
            </div>
        </div>
        <div class="request">
            <div class="info">
                <div class="profile-picture">
                        <img src="../../public/images/5.jpg">
                </div>
                <div>
                    <h5>Nisal</h5>
                    <p>85 mutual friends</p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn">Accept</button>
                <button class="btn decline-btn">Decline</button>
            </div>
        </div>
    </div>
    <div class="toast-container" id="toastContainer"></div>
            </div>
        </div>

</main>
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <!-- Include existing JavaScript files -->
    <script src="../../public/js/navbar.js"></script>
    <script src="../../public/js/calender.js"></script>
    <script src="../../public/js/notificationpopup.js"></script>
    <script src="../../public/js/general.js"></script>
    <script src="../../public/js/post.js"></script>
    <script src="../../public/js/friends.js"></script>
    
    <!-- Settings specific JavaScript -->
    <script src="../../public/js/settings.js"></script>
</body>
</html>