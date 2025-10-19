<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav>
        <div class="container">
            <div class="nav-left">
                <h2 class="logo">Hanthana</h2>
            </div>
            <div class="nav-center">
                <div class="search-bar">
                    <i class="uil uil-search"></i>
                    <input type="search" placeholder="Search...">
                </div>
            </div>
            <div class="nav-right">
                <button class="btn btn-primary">Create</button>
                <div class="calendar-icon">
                    <i class="uil uil-calendar-alt"></i>
                    <div class="calendar-popup" id="calendar-popup">
                        <div class="cal">
                            <div class="calender">
                                <div class="month">
                                    <i class="uil uil-angle-left prev"></i>
                                    <div class="date"></div>
                                    <i class="uil uil-angle-right next"></i>
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
                                <div class="days"></div>
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
                    <!-- Post Creation Modal -->
<div class="post-modal" id="postModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Post</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="post-type-selector">
                <button class="post-type-btn active" data-type="general">General Post</button>
                <button class="post-type-btn" data-type="event">Event Post</button>
            </div>
            
            <div class="post-content">
                <div class="image-upload">
                    <i class="uil uil-image-upload"></i>
                    <p>Drag photos and videos here or click to browse</p>
                    <input type="file" id="postImage" accept="image/*" style="display: none;">
                </div>
                
                <div class="post-details">
                    <div class="form-group">
                        <label for="postCaption">Caption</label>
                        <textarea id="postCaption" placeholder="Write a caption..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="postTags">Tags (minimum 5, separated by commas)</label>
                        <input type="text" id="postTags" placeholder="e.g., travel, srilanka, beach, vacation, sunset">
                        <small class="tag-count">0/5 tags</small>
                    </div>
                    
                    <div class="event-details" id="eventDetails" style="display: none;">
                        <div class="form-group">
                            <label for="eventTitle">Event Title</label>
                            <input type="text" id="eventTitle" placeholder="Event name">
                        </div>
                        <div class="form-group">
                            <label for="eventDate">Date & Time</label>
                            <input type="datetime-local" id="eventDate">
                        </div>
                        <div class="form-group">
                            <label for="eventLocation">Location</label>
                            <input type="text" id="eventLocation" placeholder="Where is the event?">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary cancel-btn">Cancel</button>
            <button class="btn btn-primary share-btn" disabled>Share</button>
        </div>
    </div>
</div>
                </div>
                <div class="notification">
                    <i class="uil uil-bell">
                        <small class="notification-count">9+</small>
                    </i>
                    <div class="notifications-popup">
                        <div>
                            <div class="profile-picture">
                                    <img src="../../public/images/profile-1.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Lahiru Fernando</b> liked your photo
                                <small class="text-muted">JUST NOW</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                    <img src="../../public/images/profile-10.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Minthaka Jayawardena</b> commented: "Great shot!"
                                <small class="text-muted">15 MINUTES AGO</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                    <img src="../../public/images/profile-11.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Alex Silva</b> mentioned you in a post
                                <small class="text-muted">1 HOUR AGO</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="profile-picture" id="profileDropdown">
                    <img src="../../public/images/avatars/<?php echo htmlspecialchars($_SESSION['profile_picture'] ?? 'defaultProfilePic.png'); ?>">
                    <div class="profile-dropdown">
                        <a href="ProfileController.php"><i class="uil uil-user"></i> My Profile</a>
                        <a href="#"><i class="uil uil-cog"></i> Settings</a>
                        <a href="#"><i class="uil uil-bell"></i> Notifications</a>
                        <div class="dropdown-divider"></div>
                        <a href="../controllers/LogoutController.php" class="logout"><i class="uil uil-signout"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>