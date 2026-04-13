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
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/report.css">
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
                        </div>
                    </div>

                    
                    <div class="discover-grid">
                        <?php if (!empty($allPosts)): ?>
                            <?php foreach (array_slice($allPosts, 0, 12) as $post): ?>
                                <?php
                                    $postId = (int)($post['post_id'] ?? 0);
                                    $imageUrl = trim((string)($post['image_url'] ?? ''));
                                    $mediaType = trim((string)($post['media_type'] ?? 'image'));
                                    $caption = trim((string)($post['content'] ?? ''));
                                    $shortText = $caption !== '' ? mb_strimwidth($caption, 0, 110, '...') : 'No preview available';
                                ?>
                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Discover&action=feed&post_id=<?php echo $postId; ?>"
                                class="discover-item <?php echo empty($imageUrl) ? 'discover-item-text' : ''; ?>">

                                    <?php if (!empty($imageUrl)): ?>
                                        <?php if ($mediaType === 'video'): ?>
                                            <video preload="metadata" muted playsinline>
                                                <source src="<?php echo htmlspecialchars($imageUrl); ?>" type="video/mp4">
                                            </video>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Post image">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="discover-text-card">
                                            <div class="discover-text-meta">
                                                <i class="uil uil-align-left" aria-hidden="true"></i>
                                                <span>Text Post</span>
                                            </div>
                                            <p class="discover-text-preview"><?php echo htmlspecialchars($shortText); ?></p>
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
                <div class="suggested-section discover-sidebar-section">
                    <div class="section-header">
                        <h4>Popular Groups</h4>
                    </div>
                    <div class="creator-list">
                        <?php if (!empty($popularGroups)): ?>
                            <?php foreach ($popularGroups as $group): ?>
                                <div class="creator-card" data-group-id="<?php echo (int)$group['group_id']; ?>">
                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>"
                                       class="creator-info" style="text-decoration:none;color:inherit;">
                                        <img src="<?php echo htmlspecialchars($group['display_picture'] ?? BASE_PATH . 'uploads/group_dp/default.png'); ?>"
                                             class="creator-avatar" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                        <div class="creator-details">
                                            <h5><?php echo htmlspecialchars($group['name']); ?></h5>
                                            <p class="creator-bio"><?php echo (int)$group['member_count']; ?> members</p>
                                        </div>
                                    </a>

                                    <?php $isMember = !empty($group['is_member']); ?>
                                    <button
                                        class="follow-btn <?php echo $isMember ? 'followed' : ''; ?>"
                                        data-group-id="<?php echo (int)$group['group_id']; ?>"
                                        data-state="<?php echo $isMember ? 'joined' : 'idle'; ?>"
                                    >
                                        <?php echo $isMember ? 'Joined' : 'Join'; ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No popular groups found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="suggested-section discover-sidebar-section">
                    <div class="section-header">
                        <h4>Recently Joined</h4>
                    </div>
                    <div class="creator-list">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $recentUser): ?>
                                <div class="creator-card">
                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)$recentUser['user_id']; ?>"
                                       class="creator-info" style="text-decoration:none;color:inherit;">
                                        <img src="<?php echo htmlspecialchars($recentUser['profile_picture'] ?? BASE_PATH . 'uploads/user_dp/default.png'); ?>"
                                             class="creator-avatar" alt="<?php echo htmlspecialchars($recentUser['username'] ?? 'User'); ?>">
                                        <div class="creator-details">
                                            <h5><?php echo htmlspecialchars(trim(($recentUser['first_name'] ?? '') . ' ' . ($recentUser['last_name'] ?? '')) ?: ($recentUser['username'] ?? 'User')); ?></h5>
                                        </div>
                                    </a>

                                    <?php if (($recentUser['friend_state'] ?? 'none') !== 'self'): ?>
                                        <button
                                            class="follow-btn add-friend-btn"
                                            data-user-id="<?php echo (int)$recentUser['user_id']; ?>"
                                            data-state="<?php echo htmlspecialchars($recentUser['friend_state'] ?? 'none'); ?>"
                                            data-label-friends="Friend"
                                            type="button"
                                        >
                                            <span>Add Friend</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="follow-btn" type="button" disabled>
                                            You
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No recent users found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>


    <script src="./js/navbar.js"></script>
    <script src="./js/calender.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/discover.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/report.js"></script>
</body>
</html>
