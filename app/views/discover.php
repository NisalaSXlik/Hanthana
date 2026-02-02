<?php
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../models/UserModel.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
        exit();
    }

    $currentUserId = $_SESSION['user_id'];
    $userModel = new UserModel;
    $currentUser = $userModel->findById($_SESSION['user_id']);

    require_once __DIR__ . '/../models/FriendModel.php';
    $friendModel = new FriendModel();
    $incomingFriendRequests = $friendModel->getIncomingRequests($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover | Hanthana</title>
    <?php if (!defined('BASE_PATH')) require_once __DIR__ . '/../../config/config.php'; ?>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/discover.css">
    <link rel="stylesheet" href="./css/navbar.css"> 
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    
<?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container">
            <?php $activeSidebar = 'discover'; include __DIR__ . '/templates/left-sidebar.php'; ?>
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
                            <img src="<?php echo BASE_PATH; ?>images/1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.2k</span>
                                <span><i class="uil uil-comment"></i> 243</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/2.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 890</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/3.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 820</span>
                                <span><i class="uil uil-comment"></i> 256</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/4.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 1.3k</span>
                                <span><i class="uil uil-comment"></i> 250</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/5.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 8.9k</span>
                                <span><i class="uil uil-comment"></i> 156</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/6.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 750</span>
                                <span><i class="uil uil-comment"></i> 56</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/story-1.jpg">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> 2.5k</span>
                                <span><i class="uil uil-comment"></i> 216</span>
                            </div>
                        </div>
                        <div class="discover-item">
                            <img src="<?php echo BASE_PATH; ?>images/story-3.jpg">
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
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('<?php echo BASE_PATH; ?>images/1.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">1</span>
                        <div class="trending-details">
                            <h5>#ColomboVibes</h5>
                            <p class="post-count">5.2K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 2 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('<?php echo BASE_PATH; ?>images/2.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">2</span>
                        <div class="trending-details">
                            <h5>#SLTech2024</h5>
                            <p class="post-count">3.8K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 3 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('<?php echo BASE_PATH; ?>images/3.jpg')">
                    <div class="trending-content">
                        <span class="trending-rank">3</span>
                        <div class="trending-details">
                            <h5>#BeachLife</h5>
                            <p class="post-count">2.9K posts today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trend 4 -->
                <div class="trending-item" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('<?php echo BASE_PATH; ?>images/4.jpg')">
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
                                <img src="<?php echo BASE_PATH; ?>images/profile-1.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TravelWithNisal</h5>
                                    <p class="creator-bio">Travel Photographer</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="<?php echo BASE_PATH; ?>images/profile-10.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>ChefAnoma</h5>
                                    <p class="creator-bio">Food Blogger</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="<?php echo BASE_PATH; ?>images/profile-11.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>TechSagara</h5>
                                    <p class="creator-bio">Tech Enthusiast</p>
                                </div>
                            </div>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="<?php echo BASE_PATH; ?>images/profile-13.jpg" class="creator-avatar">
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

    <?php include __DIR__ . '../templates/chat-clean.php'; ?>

    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
    <script src="./js/all.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/calender.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/discover.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/post.js"></script>
</body>
</html>