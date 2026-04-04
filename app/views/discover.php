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
<body class="page-discover">
    
<?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container">
            <?php $activeSidebar = 'discover'; include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="middle-feed">
                    <div class="discover-header">
                        <div class="discover-header-top">
                            <div class="discover-title-block">
                                <h2><i class="uil uil-compass"></i> Discover</h2>
                                <p>Find trending posts, people, and groups across Hanthana</p>
                            </div>
                            <form class="hf-form hf-inline" onsubmit="return false;">
                                <div class="search-bar">
                                    <i class="uil uil-search"></i>
                                    <input type="search" placeholder="Search discover..." id="discoverSearchInput">
                                </div>
                            </form>
                        </div>
                    </div>

                    
                    <div class="discover-grid">
                        <?php if (!empty($allPosts)): ?>
                            <?php foreach (array_slice($allPosts, 0, 12) as $post): ?>
                                <?php
                                    $postId = (int)($post['post_id'] ?? 0);
                                    $imageUrl = trim((string)($post['image_url'] ?? ''));
                                    $caption = trim((string)($post['content'] ?? ''));
                                    $shortText = $caption !== '' ? mb_strimwidth($caption, 0, 110, '...') : 'No preview available';
                                ?>
                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Discover&action=feed&post_id=<?php echo $postId; ?>"
                                class="discover-item <?php echo empty($imageUrl) ? 'discover-item-text' : ''; ?>">

                                    <?php if (!empty($imageUrl)): ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Post image">
                                    <?php else: ?>
                                        <div class="discover-text-card">
                                            <p><?php echo htmlspecialchars($shortText); ?></p>
                                        </div>
                                    <?php endif; ?>

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
                <div class="friend-requests">
                    <div class="heading">
                        <h4>Trending Hashtags</h4>
                    </div>
                    <?php if (!empty($trendingHashtags)): ?>
                        <?php foreach ($trendingHashtags as $tag): ?>
                            <div class="request">
                                <div class="info">
                                    <div>
                                        <h5><?php echo htmlspecialchars($tag['hashtag']); ?></h5>
                                        <p>#<?php echo (int)$tag['rank']; ?> • <?php echo (int)$tag['count']; ?> posts</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="friend-requests-empty">No trending hashtags.</div>
                    <?php endif; ?>
                </div>

                <div class="friend-requests">
                    <div class="heading">
                        <h4>Popular Groups</h4>
                    </div>
                    <?php if (!empty($popularGroups)): ?>
                        <?php foreach ($popularGroups as $group): ?>
                            <div class="request" data-group-id="<?php echo (int)$group['group_id']; ?>">
                                <div class="info">
                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>" class="discover-group-link">
                                        <div class="profile-picture discover-group-avatar-wrap">
                                            <img src="<?php echo htmlspecialchars($group['display_picture'] ?? BASE_PATH . 'images/default_group.png'); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                        </div>
                                    </a>
                                    <div>
                                        <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>" class="discover-group-link">
                                            <h5><?php echo htmlspecialchars($group['name']); ?></h5>
                                        </a>
                                        <p><?php echo (int)$group['member_count']; ?> members</p>
                                    </div>
                                </div>
                                <div class="action">
                                    <?php $isMember = !empty($group['is_member']); ?>
                                    <button
                                        class="btn btn-primary follow-btn <?php echo $isMember ? 'followed' : ''; ?>"
                                        data-group-id="<?php echo (int)$group['group_id']; ?>"
                                        data-state="<?php echo $isMember ? 'joined' : 'idle'; ?>"
                                    >
                                        <?php echo $isMember ? 'Joined' : 'Join'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="friend-requests-empty">No popular groups found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>


    <script src="./js/navbar.js"></script>
    <script src="./js/calender.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/discover.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/post.js"></script>
</body>
</html>
