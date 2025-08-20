<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover | Hanthana</title>
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/general.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/navbar.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/discover.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/mediaquery.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body onload="protectedContentLoad()">
    <div class="modal-overlay" id="signupModal">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <i class="uil uil-lock-alt modal-icon"></i>
            <h3 class="modal-title">Join Hanthana to continue</h3>
            <p class="modal-text">Sign up to like, comment, save posts, and access all features.</p>
            <div class="modal-actions">
                <a href="login.html" class="btn btn-secondary" id="loginBtn">Log In</a>
                <a href="login.html" class="btn btn-primary" id="signupBtn">Sign Up</a>
            </div>
        </div>
    </div>
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
                    
                </div>
                <div class="notification">
                    <i class="uil uil-bell">
                        <small class="notification-count">9+</small>
                    </i>
                    
                </div>
                <a href="/project/login.html" class="btn btn-login">Log In</a>
                <a href="/project/login.html" class="btn btn-primary">Sign Up</a>
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
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/2.jpg')">
                            <div class="trending-content">
                                <span class="trending-rank">2</span>
                                <div class="trending-details">
                                    <h5>#SLTech2024</h5>
                                    <p class="post-count">3.8K posts today</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trend 3 -->
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/3.jpg')">
                            <div class="trending-content">
                                <span class="trending-rank">3</span>
                                <div class="trending-details">
                                    <h5>#BeachLife</h5>
                                    <p class="post-count">2.9K posts today</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trend 4 -->
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/NewHanthaneAfterProperMVC/public/images/4.jpg')">
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

    <script src="/NewHanthaneAfterProperMVC/public/js/landingpage.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/discover.js"></script>
</body>
</html>