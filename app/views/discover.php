<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover | Hanthana</title>
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/general.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/discover.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/navbar.css"> 
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/mediaquery.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/calender.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/post.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/myfeed.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
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
                <div class="notification">
                    <i class="uil uil-bell">
                        <small class="notification-count">9+</small>
                    </i>
                    <div class="notifications-popup">
                        <div>
                            <div class="profile-picture">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-1.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Lahiru Fernando</b> liked your photo
                                <small class="text-muted">JUST NOW</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-10.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Minthaka Jayawardena</b> commented: "Great shot!"
                                <small class="text-muted">15 MINUTES AGO</small>
                            </div>
                        </div>
                        <div>
                            <div class="profile-picture">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-11.jpg" />
                            </div>
                            <div class="notification-body">
                                <b>Alex Silva</b> mentioned you in a post
                                <small class="text-muted">1 HOUR AGO</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="profile-picture">
                    <img src="/NewHanthaneAfterProperMVC/public/images/profile-12.jpg">
                        <div class="profile-dropdown">
                            <a href="#"><i class="uil uil-user"></i> My Profile</a>
                            <a href="#"><i class="uil uil-cog"></i> Settings</a>
                            <a href="#"><i class="uil uil-signout"></i> notifications</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="logout"><i class="uil uil-signout"></i> Logout</a>
                        </div>
                </div>
            </div>
        </div>
          <div class="calendar-popup" id="calendarPopup">
    <div class="arrow-up"></div>
    <div class="calendar-popup-header">
        Events on <span id="popup-date">--</span>
    </div>
    <div class="calendar-popup-body" id="calendarEvents">
        <p>No events found for this day.</p>
    </div>
</div>
    </nav>

    <main>
        <div class="container">
            <div class="left">
                <div class="profile">
                    <div class="profile-picture">
                        <img src="/NewHanthaneAfterProperMVC/public/images/4.jpg">
                    </div>
                    <div class="handle">
                        <h4>Lithmal Perera</h4>
                        <p>@lithmal</p>
                    </div>
                </div>

                <div class="side-bar">
                    <a class="menu-item">
                        <i class="uil uil-home"></i>
                        <h3>My Feed</h3>
                    </a>
                    <a class="menu-item active">
                        <i class="uil uil-compass"></i>
                        <h3>Discover</h3>
                    </a>
                    <a class="menu-item">
                        <i class="uil uil-calendar-alt"></i>
                        <h3>Events</h3>
                    </a>
                    <a class="menu-item">
                        <i class="uil uil-users-alt"></i>
                        <h3>popular</h3>
                    </a>
                </div>

                <div class="joined-groups">
                    <h4>Groups You've Joined</h4>
                    <div class="group-list">
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-users-alt"></i>
                            </div>
                            <div class="group-info">
                                <h5>Colombo Foodies</h5>
                                <p>12.5k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-camera"></i>
                            </div>
                            <div class="group-info">
                                <h5>SL Photography Club</h5>
                                <p>8.2k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-mountains"></i>
                            </div>
                            <div class="group-info">
                                <h5>Hiking Sri Lanka</h5>
                                <p>5.7k members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">See All Groups</button>
                </div>
            </div>

            <div class="middle">
                <div class="middle-feed">
                    <div class="discover-header">
                        <h2>Discover</h2>
                        <div class="search-bar">
                            <i class="uil uil-search"></i>
                            <input type="search" placeholder="Search...">
                        </div>
                    </div>

                    <div class="discover-grid">
                        <!-- 9 Discover Items -->
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.2k</span>
                                <span><i class="uil uil-comment"></i> 243</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/2.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 890</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/3.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 820</span>
                                <span><i class="uil uil-comment"></i> 256</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/4.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.3k</span>
                                <span><i class="uil uil-comment"></i> 250</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/5.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 8.9k</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/6.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 750</span>
                                <span><i class="uil uil-comment"></i> 56</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/story-1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 2.5k</span>
                                <span><i class="uil uil-comment"></i> 216</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="/NewHanthaneAfterProperMVC/public/images/story-3.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 8.5k</span>
                                <span><i class="uil uil-comment"></i> 256</span>
                            </div>
                        </div>
                        <!-- 7 more discover items -->
                    </div>
                </div>
            </div>

            <div class="right">
                <!-- Trending Now Section -->
                        <div class="trending-section">
            <div class="section-header">
                <h4>Trending Now</h4>
                <a href="#" class="see-all">See all</a>
            </div>
            <div class="trending-list">
                <!-- Trend 1 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/1.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">1</span>
                        <div class="trending-details">
                            <h5>#ColomboVibes</h5>
                            <p class="post-count">5.2K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 2 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/2.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">2</span>
                        <div class="trending-details">
                            <h5>#SLTech2024</h5>
                            <p class="post-count">3.8K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 3 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/3.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">3</span>
                        <div class="trending-details">
                            <h5>#BeachLife</h5>
                            <p class="post-count">2.9K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 4 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/4.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">4</span>
                        <div class="trending-details">
                            <h5>#FoodieLK</h5>
                            <p class="post-count">4.1K posts today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                
                <!-- Suggested Creators Section -->
                <div class="suggested-section">
                    <div class="section-header">
                        <h4>Suggested Creators</h4>
                        <a href="#" class="see-all">See all</a>
                    </div>
                    <div class="creator-list">
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-1.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TravelWithNisal</h5>
                                    <p class="creator-bio">Travel Photographer</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-10.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>ChefAnoma</h5>
                                    <p class="creator-bio">Food Blogger</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-11.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TechSagara</h5>
                                    <p class="creator-bio">Tech Enthusiast</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="/NewHanthaneAfterProperMVC/public/images/profile-13.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>ArtBySachini</h5>
                                    <p class="creator-bio">Digital Artist</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    

    <script src="/NewHanthaneAfterProperMVC/public/js/navbar.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/calender.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/notificationpopup.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/discover.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/genral.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/feed.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/post.js"></script>
</body>
</html>