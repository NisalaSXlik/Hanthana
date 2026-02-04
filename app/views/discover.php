<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
require_once __DIR__ . '/../models/FriendModel.php';

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']))  {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit(); 
}

$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById((int)$currentUserId);
$friendModel = new FriendModel();
$incomingFriendRequests = $friendModel->getIncomingRequests($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover | Hanthana</title>
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
                        <?php if (!empty($allPosts)): ?>
                            <?php foreach (array_slice($allPosts, 0, 12) as $post): ?>  
                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Discover&action=feed&post_id=<?php echo $post['post_id']; ?>" class="discover-item">
                                    <img src="<?php echo htmlspecialchars($post['image_url'] ?? BASE_PATH . 'images/default_post.png'); ?>" 
                                        alt="Post image">
                                    <div class="item-overlay">
                                        <span><i class="uil uil-heart"></i> <?php echo $post['upvote_count'] ?? 0; ?></span>
                                        <span><i class="uil uil-comment"></i> <?php echo $post['comment_count'] ?? 0; ?></span>
                                        <?php if (!empty($post['is_group_post'])): ?>
                                            <div class="group-badge">
                                                <i class="uil uil-users-alt"></i> <?php echo htmlspecialchars($post['group_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No posts found.</p>
                        <?php endif; ?>
                    </div>
                     
                </div>
            </div>

            <div class="right">
            <!-- Trending Hashtags Section -->
                <div class="trending-section">
                    <div class="section-header">
                        <h4>Trending Hashtags</h4>
                    </div>
                    <div class="trending-list">
                        <?php if (!empty($trendingHashtags)): ?>
                            <?php foreach ($trendingHashtags as $tag): ?>
                                <div class="trending-item">
                                    <div class="trending-content">
                                        <span class="trending-rank"><?php echo $tag['rank']; ?></span>
                                        <div class="trending-details">
                                            <h5><?php echo htmlspecialchars($tag['hashtag']); ?></h5>
                                            <p class="post-count"><?php echo $tag['count']; ?> posts</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No trending hashtags.</p>
                        <?php endif; ?>
                    </div>
                </div>
        
                
                <!-- Popular Groups Section -->
                <div class="suggested-section">
                    <div class="section-header">
                        <h4>Popular Groups</h4>
                    </div>
                    <div class="creator-list">
                        <?php if (!empty($popularGroups)): ?>
                            <?php foreach ($popularGroups as $group): ?>
                                <div class="creator-card">
                                    <div class="creator-info">
                                        <img src="<?php echo htmlspecialchars($group['display_picture'] ?? BASE_PATH . 'images/default_group.png'); ?>" 
                                             class="creator-avatar" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                        <div class="creator-details">
                                            <h5><?php echo htmlspecialchars($group['name']); ?></h5>
                                            <p class="creator-bio"><?php echo $group['member_count']; ?> members</p>
                                        </div>
                                    </div>
                                    <?php if ($group['is_member']): ?>
                                        <button class="follow-btn followed" disabled>Joined</button>
                                    <?php else: ?>
                                        <button class="follow-btn" data-group-id="<?php echo $group['group_id']; ?>">Join</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No popular groups found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script src="./js/all.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/calender.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/discover.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/post.js"></script>
</body>
</html>
