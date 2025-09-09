<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover | Hanthana</title>
    <link rel="stylesheet" href="public/css/general.css">
    <link rel="stylesheet" href="public/css/discover.css">
    <link rel="stylesheet" href="public/css/navbar.css"> 
    <link rel="stylesheet" href="public/css/mediaquery.css">
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
                <a href="login.php" class="btn btn-secondary" id="loginBtn">Log In</a>
                <a href="signup.php" class="btn btn-primary" id="signupBtn">Sign Up</a>
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
                <a href="login.php" class="btn btn-primary btn-login" style="min-width: 90px; text-align: center; border-radius: var(--border-radius); background: var(--color-primary); color: var(--color-white); box-shadow: 0 2px 8px rgba(14, 165, 233, 0.15); font-weight: 500; padding: 0.5rem 1.5rem; border: none; font-size: 0.9rem; transition: all 0.3s ease;">Log In</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="left">
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

                <div class="popular-groups">
                    <h4>Popular Groups</h4>
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
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-book-alt"></i>
                            </div>
                            <div class="group-info">
                                <h5>Book Club LK</h5>
                                <p>3.2k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-music"></i>
                            </div>
                            <div class="group-info">
                                <h5>Sri Lankan Musicians</h5>
                                <p>7.8k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-graduation-cap"></i>
                            </div>
                            <div class="group-info">
                                <h5>University Students LK</h5>
                                <p>15.3k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-car"></i>
                            </div>
                            <div class="group-info">
                                <h5>Car Enthusiasts SL</h5>
                                <p>4.6k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-palette"></i>
                            </div>
                            <div class="group-info">
                                <h5>Artists of Sri Lanka</h5>
                                <p>6.1k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-plane"></i>
                            </div>
                            <div class="group-info">
                                <h5>Travel Sri Lanka</h5>
                                <p>9.2k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-dumbbell"></i>
                            </div>
                            <div class="group-info">
                                <h5>Fitness & Health LK</h5>
                                <p>6.8k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-processor"></i>
                            </div>
                            <div class="group-info">
                                <h5>Tech Community SL</h5>
                                <p>11.5k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-basketball"></i>
                            </div>
                            <div class="group-info">
                                <h5>Sports Sri Lanka</h5>
                                <p>8.4k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-shopping-bag"></i>
                            </div>
                            <div class="group-info">
                                <h5>Entrepreneurs LK</h5>
                                <p>5.9k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-flower"></i>
                            </div>
                            <div class="group-info">
                                <h5>Gardening Sri Lanka</h5>
                                <p>4.3k members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">Explore All Groups</button>
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
                            <img src="public/images/1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.2k</span>
                                <span><i class="uil uil-comment"></i> 243</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/2.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 890</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/3.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 820</span>
                                <span><i class="uil uil-comment"></i> 256</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/4.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.3k</span>
                                <span><i class="uil uil-comment"></i> 250</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/5.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 8.9k</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/6.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 750</span>
                                <span><i class="uil uil-comment"></i> 56</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/story-1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 2.5k</span>
                                <span><i class="uil uil-comment"></i> 216</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="public/images/story-3.jpg">
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
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('public/images/1.jpg')">
                            <div class="trending-content">
                                <span class="trending-rank">1</span>
                                <div class="trending-details">
                                    <h5>#ColomboVibes</h5>
                                    <p class="post-count">5.2K posts today</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trend 2 -->
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('public/images/2.jpg')">
                            <div class="trending-content">
                                <span class="trending-rank">2</span>
                                <div class="trending-details">
                                    <h5>#SLTech2024</h5>
                                    <p class="post-count">3.8K posts today</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trend 3 -->
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('public/images/3.jpg')">
                            <div class="trending-content">
                                <span class="trending-rank">3</span>
                                <div class="trending-details">
                                    <h5>#BeachLife</h5>
                                    <p class="post-count">2.9K posts today</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trend 4 -->
                        <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('public/images/4.jpg')">
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
                                <img src="public/images/profile-1.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TravelWithNisal</h5>
                                    <p class="creator-bio">Travel Photographer</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="public/images/profile-10.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>ChefAnoma</h5>
                                    <p class="creator-bio">Food Blogger</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="public/images/profile-11.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TechSagara</h5>
                                    <p class="creator-bio">Tech Enthusiast</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="public/images/profile-13.jpg" class="creator-avatar">
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

    <script src="public/js/landingpage.js"></script>
    <script src="public/js/discover.js"></script>
</body>
</html>