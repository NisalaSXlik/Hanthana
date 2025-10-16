<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hanthana Events</title>
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
                    <input type="search" placeholder="Search...">
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
    <main class="profile-container">
        <div class="profile-header">
            <div class="profile-cover">
                <img src="/placeholder.svg?height=200&width=1200&text=University+Campus" alt="Cover Photo">
                <button class="edit-cover-btn">
                    <i class="fas fa-camera"></i> Edit Cover Photo
                </button>
            </div>
            <div class="profile-info">
                <div class="profile-avatar">
                    <img src="/placeholder.svg?height=120&width=120&text=JD" alt="Profile Picture">
                    <button class="edit-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-details">
                    <h1>John Doe</h1>
                    <p class="profile-title">Computer Science Student | Class of 2025</p>
                    <div class="profile-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Hanthana University, Sri Lanka</span>
                    </div>
                    <div class="profile-joined">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Joined: September 2021</span>
                    </div>
                    <div class="profile-actions">
                        <button class="btn btn-primary" onclick="editProfile()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-share-alt"></i> Share Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-nav">
            <button class="profile-nav-btn active" data-tab="overview">
                <i class="fas fa-chart-line"></i> Overview
            </button>
            <button class="profile-nav-btn" data-tab="about">
                <i class="fas fa-info-circle"></i> About
            </button>
            <button class="profile-nav-btn" data-tab="events">
                <i class="fas fa-calendar-alt"></i> My Events
            </button>
            <button class="profile-nav-btn" data-tab="achievements">
                <i class="fas fa-trophy"></i> Achievements
            </button>
            <button class="profile-nav-btn" data-tab="activity">
                <i class="fas fa-history"></i> Activity Log
            </button>
        </div>

        <div class="profile-content">
            <!-- Overview Tab -->
            <div class="profile-tab active" id="overviewTab">
                <div class="profile-grid">
                    <div class="left-column">
                        <div class="profile-section events-overview">
                            <h3>Events Overview</h3>
                            <div class="event-stats-grid">
                                <div class="event-stat-card">
                                    <i class="fas fa-calendar-check"></i>
                                    <h4>Events Attended</h4>
                                    <span class="stat-number">12</span>
                                </div>
                                <div class="event-stat-card">
                                    <i class="fas fa-calendar-plus"></i>
                                    <h4>Events Organized</h4>
                                    <span class="stat-number">2</span>
                                </div>
                                <div class="event-stat-card">
                                    <i class="fas fa-bookmark"></i>
                                    <h4>Bookmarked Events</h4>
                                    <span class="stat-number">5</span>
                                </div>
                                <div class="event-stat-card">
                                    <i class="fas fa-hourglass-half"></i>
                                    <h4>Pending Approvals</h4>
                                    <span class="stat-number">1</span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section upcoming-events">
                            <h3>Upcoming Events</h3>
                            <div class="event-list">
                                <div class="profile-event-item">
                                    <img src="/placeholder.svg?height=60&width=60&text=AI+Symposium" alt="AI Symposium">
                                    <div class="event-info">
                                        <h4>AI in Healthcare Symposium</h4>
                                        <p>March 15, 2024 • Engineering Auditorium</p>
                                        <span class="event-role attendee">Attendee</span>
                                    </div>
                                </div>
                                <div class="profile-event-item">
                                    <img src="/placeholder.svg?height=60&width=60&text=Data+Science" alt="Data Science Workshop">
                                    <div class="event-info">
                                        <h4>Data Science Workshop</h4>
                                        <p>March 20, 2024 • Computer Lab 201</p>
                                        <span class="event-role attendee">Attendee</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-column">
                        <div class="profile-section">
                            <h3>Recent Activity</h3>
                            <div class="activity-feed">
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Registered for <b>"AI in Healthcare Symposium"</b></p>
                                        <small>2 hours ago</small>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Created new event <b>"Startup Pitch Competition"</b></p>
                                        <small>1 day ago</small>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Earned <b>"Early Bird Attendee"</b> badge</p>
                                        <small>3 days ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>Achievements</h3>
                            <div class="achievement-badges">
                                <div class="badge earned">
                                    <i class="fas fa-award"></i>
                                    <div>
                                        <span>Event Enthusiast</span>
                                        <small>Attended 10+ events</small>
                                    </div>
                                </div>
                                <div class="badge locked">
                                    <i class="fas fa-lock"></i>
                                    <div>
                                        <span>Top Organizer</span>
                                        <small>Organize 5+ successful events</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Tab -->
            <div class="profile-tab" id="aboutTab">
                <div class="profile-grid">
                    <div class="left-column">
                        <div class="profile-section about-content">
                            <h3>About Me</h3>
                            <p>I am a passionate Computer Science student at Hanthana University with a keen interest in artificial intelligence, data science, and web development. I enjoy participating in hackathons, workshops, and academic seminars to expand my knowledge and connect with like-minded individuals. My goal is to leverage technology to solve real-world problems and contribute to innovative projects.</p>
                            <p>In my free time, I enjoy hiking, photography, and exploring new programming languages. I believe in continuous learning and actively seek opportunities to collaborate on challenging projects.</p>
                        </div>

                        <div class="profile-section">
                            <h3>Interests</h3>
                            <div class="interests-tags">
                                <span class="interest-tag">Artificial Intelligence</span>
                                <span class="interest-tag">Machine Learning</span>
                                <span class="interest-tag">Web Development</span>
                                <span class="interest-tag">Data Science</span>
                                <span class="interest-tag">Cybersecurity</span>
                                <span class="interest-tag">Robotics</span>
                                <span class="interest-tag">Entrepreneurship</span>
                                <span class="interest-tag">Photography</span>
                                <span class="interest-tag">Hiking</span>
                            </div>
                        </div>
                    </div>

                    <div class="right-column">
                        <div class="profile-section">
                            <h3>Contact Information</h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>john.doe@university.edu</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+94 77 123 4567</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fab fa-linkedin"></i>
                                    <a href="#">linkedin.com/in/johndoe</a>
                                </div>
                                <div class="contact-item">
                                    <i class="fab fa-github"></i>
                                    <a href="#">github.com/johndoe</a>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>Quick Stats</h3>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <div>
                                        <span class="stat-number">12</span>
                                        <span class="stat-label">Events Attended</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <span class="stat-number">3</span>
                                        <span class="stat-label">Friends</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Events Tab -->
            <div class="profile-tab" id="eventsTab">
                <div class="events-header" style="box-shadow: none; padding: 0; margin-bottom: 1.5rem;">
                    <h1>My Events</h1>
                    <div class="filter-controls">
                        <button class="btn btn-secondary filter-btn">
                            <i class="fas fa-filter"></i>
                            <span>Filter</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div class="event-tabs">
                    <button class="tab-btn active" data-tab="upcoming">
                        Upcoming
                        <span class="tab-count">3</span>
                    </button>
                    <button class="tab-btn" data-tab="registered">
                        Registered
                        <span class="tab-count">5</span>
                    </button>
                    <button class="tab-btn" data-tab="organized">
                        Organized
                        <span class="tab-count">2</span>
                    </button>
                    <button class="tab-btn" data-tab="drafts">
                        Drafts
                        <span class="tab-count">1</span>
                    </button>
                </div>

                <div class="tab-content active" id="upcomingEvents">
                    <!-- Upcoming Events -->
                    <div class="my-event-card fade-in">
                        <div class="event-content">
                            <img src="/placeholder.svg?height=150&width=200&text=AI+Symposium" alt="AI in Healthcare Symposium" class="event-image">
                            <div class="event-info">
                                <h3 class="event-title">AI in Healthcare Symposium</h3>
                                <p class="event-description">Explore the latest developments in AI applications in healthcare.</p>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>March 15, 2024</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>2:00 PM - 5:00 PM</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Engineering Auditorium</span>
                                    </div>
                                </div>
                                <div class="event-stats">
                                    <div class="stat">
                                        <i class="fas fa-heart"></i> <span>245 Interested</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-users"></i> <span>89 Going</span>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='event-details.html?id=1'">View Details</button>
                                    <span class="event-status status-registered">Registered</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="my-event-card fade-in">
                        <div class="event-content">
                            <img src="/placeholder.svg?height=150&width=200&text=Data+Science" alt="Data Science Workshop" class="event-image">
                            <div class="event-info">
                                <h3 class="event-title">Data Science Workshop</h3>
                                <p class="event-description">Learn the fundamentals of data science, machine learning, and data visualization techniques.</p>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>March 20, 2024</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>2:00 PM - 5:00 PM</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Computer Lab 201</span>
                                    </div>
                                </div>
                                <div class="event-stats">
                                    <div class="stat">
                                        <i class="fas fa-heart"></i> <span>156 Interested</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-users"></i> <span>78 Going</span>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='event-details.html?id=2'">View Details</button>
                                    <span class="event-status status-registered">Registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="registeredEvents">
                    <!-- Registered Events (example) -->
                    <div class="my-event-card fade-in">
                        <div class="event-content">
                            <img src="/placeholder.svg?height=150&width=200&text=Career+Fair" alt="Career Fair 2024" class="event-image">
                            <div class="event-info">
                                <h3 class="event-title">Career Fair 2024</h3>
                                <p class="event-description">Connect with top employers and explore career opportunities.</p>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>March 25-26, 2024</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>9:00 AM - 4:00 PM</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Student Center</span>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='event-details.html?id=3'">View Details</button>
                                    <span class="event-status status-registered">Registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="organizedEvents">
                    <!-- Organized Events (example) -->
                    <div class="my-event-card fade-in">
                        <div class="event-content">
                            <img src="/placeholder.svg?height=150&width=200&text=Startup+Pitch" alt="Startup Pitch Competition" class="event-image">
                            <div class="event-info">
                                <h3 class="event-title">Startup Pitch Competition</h3>
                                <p class="event-description">Present your innovative startup ideas to a panel of investors and industry experts.</p>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>March 28, 2024</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>1:00 PM - 6:00 PM</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Business Hall</span>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='event-details.html?id=4'">View Details</button>
                                    <button class="btn btn-secondary btn-sm">Edit Event</button>
                                    <span class="event-status status-approved">Approved</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="draftsEvents">
                    <!-- Draft Events (example) -->
                    <div class="my-event-card fade-in">
                        <div class="event-content">
                            <img src="/placeholder.svg?height=150&width=200&text=New+Workshop" alt="New Workshop Idea" class="event-image">
                            <div class="event-info">
                                <h3 class="event-title">New Workshop Idea</h3>
                                <p class="event-description">A draft for an upcoming workshop on advanced web development.</p>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>TBD</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>TBD</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>TBD</span>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm">Continue Editing</button>
                                    <button class="btn btn-danger btn-sm">Delete Draft</button>
                                    <span class="event-status status-draft">Draft</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="right">
                <!-- Calendar Widget -->
                <div class="calendar-widget">
                    <div class="calendar-header">
                        <button class="calendar-nav" onclick="previousMonth()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="calendar-month">March 2024</span>
                        <button class="calendar-nav" onclick="nextMonth()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                        <!-- Days of the month - example -->
                        <div class="calendar-day"></div>
                        <div class="calendar-day"></div>
                        <div class="calendar-day"></div>
                        <div class="calendar-day"></div>
                        <div class="calendar-day"></div>
                        <div class="calendar-day">1</div>
                        <div class="calendar-day">2</div>
                        <div class="calendar-day">3</div>
                        <div class="calendar-day">4</div>
                        <div class="calendar-day">5</div>
                        <div class="calendar-day">6</div>
                        <div class="calendar-day">7</div>
                        <div class="calendar-day">8</div>
                        <div class="calendar-day">9</div>
                        <div class="calendar-day">10</div>
                        <div class="calendar-day">11</div>
                        <div class="calendar-day">12</div>
                        <div class="calendar-day">13</div>
                        <div class="calendar-day">14</div>
                        <div class="calendar-day event-day">15</div> <!-- Event day -->
                        <div class="calendar-day">16</div>
                        <div class="calendar-day">17</div>
                        <div class="calendar-day event-day">18</div> <!-- Event day -->
                        <div class="calendar-day">19</div>
                        <div class="calendar-day event-day">20</div> <!-- Event day -->
                        <div class="calendar-day">21</div>
                        <div class="calendar-day event-day">22</div> <!-- Event day -->
                        <div class="calendar-day">23</div>
                        <div class="calendar-day">24</div>
                        <div class="calendar-day event-day">25</div> <!-- Event day -->
                        <div class="calendar-day event-day">26</div> <!-- Event day -->
                        <div class="calendar-day">27</div>
                        <div class="calendar-day event-day">28</div> <!-- Event day -->
                        <div class="calendar-day">29</div>
                        <div class="calendar-day">30</div>
                        <div class="calendar-day">31</div>
                    </div>
                </div>

                <!-- Trending Events -->
                <div class="trending-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-trending-up trending-icon"></i>
                        Trending This Week
                    </h3>
                    <div class="trending-list">
                        <div class="trending-item">
                            <div class="trending-info">
                                <h4>Study Group: Calculus II</h4>
                                <p>Tomorrow 7PM</p>
                            </div>
                            <div class="trending-count">
                                <i class="fas fa-users"></i>
                                <span>23</span>
                            </div>
                        </div>
                        <div class="trending-item">
                            <div class="trending-info">
                                <h4>Photography Club Meeting</h4>
                                <p>Friday 4PM</p>
                            </div>
                            <div class="trending-count">
                                <i class="fas fa-users"></i>
                                <span>45</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suggested Events -->
                <div class="suggested-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-star star-icon"></i>
                        Suggested for You
                    </h3>
                    <div class="suggested-list">
                        <div class="suggested-item">
                            <h4>Data Science Workshop</h4>
                            <p>Based on your Computer Science major</p>
                            <button class="btn btn-primary">View Details</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/NewHanthaneAfterProperMVC/public/js/script.js"></script>
</body>
</html>
