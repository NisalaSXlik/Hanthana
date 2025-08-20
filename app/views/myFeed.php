<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hanthana</title>
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/myfeed.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/general.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/navbar.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/mediaquery.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/calender.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/notificationpopup.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/post.css">
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
                <div class="profile-picture" id="profileDropdown">
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
                    <a class="menu-item active" data-target="feed">
                        <i class="uil uil-home"></i>
                        <h3>My Feed</h3>
                    </a>
                    <a href="discover.html" class="menu-item" data-target="discover">
                        <i class="uil uil-compass"></i>
                        <h3>Discover</h3>
                    </a>
                    <a class="menu-item" data-target="events">
                        <i class="uil uil-calendar-alt"></i>
                        <h3>Events</h3>
                    </a>
                    <a class="menu-item" data-target="groups">
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

                <button class="btn btn-primary">Create Post</button>
            </div>

            <div class="middle">
                <div class="feeds">
                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-picture">
                                    <img src="/NewHanthaneAfterProperMVC/public/images/profile-1.jpg">
                                </div>
                                <div class="info">
                                    <h3>Nisal Gamage</h3>
                                    <small>Colombo, 15 mins ago</small>
                                </div>
                            </div>
                            <i class="uil uil-ellipsis-h"></i>
                        </div>
                        <div class="photo">
                            <img src="/NewHanthaneAfterProperMVC/public/images/1.jpg">
                        </div>
                        <div class="action-buttons">
                            <div class="interaction-buttons">
                                <i class="uil uil-heart"></i>
                                <i class="uil uil-comment"></i>
                                <i class="uil uil-share-alt"></i>
                                <button class="add-to-calendar-btn" data-event='{"title":"Temple Visit","date":"2023-11-15T09:00:00","location":"Temple of the Tooth, Kandy","image":"/NewHanthaneAfterProperMVC/public/images/2.jpg"}'>
                <i class="uil uil-calendar-alt"></i> Add to Calendar</button>
                            </div>
                            <i class="uil uil-bookmark"></i>
                        </div>
                        <div class="liked-by">
                            <div class="liked-users">
                                <img src="/NewHanthaneAfterProperMVC/public/images/6.jpg">
                                <img src="/NewHanthaneAfterProperMVC/public/images/1.jpg">
                                <img src="/NewHanthaneAfterProperMVC/public/images/2.jpg">
                            </div>
                            <p>Liked by <b>tharusha</b> and <b>187 others</b></p>
                        </div>
                        <div class="caption">
                            <p><b>Nisal Gamage</b> Weekend getaway to Unawatuna Beach! The perfect escape from city life 🏖️ #beachvibes #srilanka #weekend</p>
                        </div>
                        <div class="comments">View all 42 comments</div>
                    </div>

                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-picture">
                                    <img src="/NewHanthaneAfterProperMVC/public/images/profile-14.jpg">
                                </div>
                                <div class="info">
                                    <h3>Tharusha Perera</h3>
                                    <small>Kandy, 1 hour ago</small>
                                </div>
                            </div>
                            <i class="uil uil-ellipsis-h"></i>
                        </div>
                        <div class="photo">
                            <img src="/NewHanthaneAfterProperMVC/public/images/2.jpg">
                        </div>
                        <div class="action-buttons">
                            <div class="interaction-buttons">
                                <i class="uil uil-heart"></i>
                                <i class="uil uil-comment"></i>
                                <i class="uil uil-share-alt"></i>
                            </div>
                            <i class="uil uil-bookmark"></i>
                        </div>
                        <div class="liked-by">
                            <div class="liked-users">
                                <img src="/NewHanthaneAfterProperMVC/public/images/3.jpg">
                                <img src="/NewHanthaneAfterProperMVC/public/images/4.jpg">
                                <img src="/NewHanthaneAfterProperMVC/public/images/5.jpg">
                            </div>
                            <p>Liked by <b>lithmal</b> and <b>243 others</b></p>
                        </div>
                        <div class="caption">
                            <p><b>Tharusha Perera</b> Morning blessings at the Temple of the Tooth. There's such peaceful energy here 🙏 #kandy #srilanka #culture</p>
                        </div>
                        <div class="comments">View all 56 comments</div>
                    </div>
                </div>
            </div>

            <div class="right">
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
                                <img src="/NewHanthaneAfterProperMVC/public/images/2.jpg">
                                <div class="active"></div>
                            </div>
                            <div class="message-body">
                                <h5>Minthaka J.</h5>
                                <p>Are we still meeting tomorrow?</p>
                            </div>
                        </div>
                        <div class="message">
                            <div class="profile-picture">
                                <img src="/NewHanthaneAfterProperMVC/public/images/6.jpg">
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
                    <img src="/NewHanthaneAfterProperMVC/public/images/4.jpg">
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
                    <img src="/NewHanthaneAfterProperMVC/public/images/2.jpg">
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
                    <img src="/NewHanthaneAfterProperMVC/public/images/5.jpg">
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
                    <img src="/NewHanthaneAfterProperMVC/public/images/1.jpg">
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
                    <img src="/NewHanthaneAfterProperMVC/public/images/5.jpg">
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

    <script src="/NewHanthaneAfterProperMVC/public/js/calender.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/feed.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/friends.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/genral.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/notificationpopup.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/navbar.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/post.js"></script>
    
</body>
</html>