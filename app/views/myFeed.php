<?php
// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch posts from DB if not provided by a controller
if (!isset($posts)) {
    require_once __DIR__ . '/../models/PostModel.php';
    $posts = (new PostModel())->getFeedPosts();
}

$currentUserId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hanthana</title>
    <link rel="stylesheet" href="../../public/css/myfeed.css">
    <link rel="stylesheet" href="../../public/css/general.css">
    <link rel="stylesheet" href="../../public/css/navbar.css"> 
    <link rel="stylesheet" href="../../public/css/mediaquery.css">
    <link rel="stylesheet" href="../../public/css/calender.css">
    <link rel="stylesheet" href="../../public/css/notificationpopup.css">
    <link rel="stylesheet" href="../../public/css/post.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="feeds">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <?php
                                $rawAvatar = trim($post['profile_picture'] ?? '');
                                if ($rawAvatar === '' || $rawAvatar === null) {
                                    $avatarUrl = '../../public/images/avatars/default.png';
                                } else {
                                    if (stripos($rawAvatar, 'http://') === 0 || stripos($rawAvatar, 'https://') === 0) {
                                        $avatarUrl = $rawAvatar;
                                    } elseif ($rawAvatar[0] === '/') {
                                        // Root-relative path provided; keep as-is
                                        $avatarUrl = $rawAvatar;
                                    } else {
                                        // Filename only; map to avatars folder relative to this view
                                        $avatarUrl = '../../public/images/avatars/' . $rawAvatar;
                                    }
                                }

                                // Prefer username at the top. If absent, fall back to full name, then to 'Unknown'.
                                $fullName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
                                $displayName = $post['username'] ?? '';
                                if ($displayName === '' || $displayName === null) {
                                    $displayName = $fullName !== '' ? $fullName : 'Unknown';
                                }
                            ?>
                            <div class="feed" data-post-id="<?php echo (int)$post['post_id']; ?>" data-post-content="<?php echo htmlspecialchars($post['content'] ?? '', ENT_QUOTES); ?>">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-picture">
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
                                        </div>
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($displayName); ?></h3>
                                            <small><?php echo htmlspecialchars($post['created_at'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <?php 
                                    $isOwner = isset($currentUserId) && (int)$currentUserId === (int)($post['author_id'] ?? $post['user_id'] ?? 0);
                                    ?>
                                    <?php if ($isOwner): ?>
                                        <div class="post-menu">
                                            <button class="menu-trigger" aria-label="Post menu"><i class="uil uil-ellipsis-h"></i></button>
                                            <div class="menu">
                                                <button class="menu-item edit-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                    <i class="uil uil-edit"></i> Edit
                                                </button>
                                                <button class="menu-item delete-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                    <i class="uil uil-trash-alt"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <i class="uil uil-ellipsis-h"></i>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($post['image_url'])): ?>
                                <?php
                                    $rawPostImg = trim($post['image_url']);
                                    if (stripos($rawPostImg, 'http://') === 0 || stripos($rawPostImg, 'https://') === 0) {
                                        $postImgUrl = $rawPostImg;
                                    } else {
                                        // Map any non-absolute (including root-relative) path to our posts folder using filename only
                                        $postImgUrl = '../../public/images/posts/' . basename($rawPostImg);
                                    }
                                ?>
                                <div class="photo">
                                    <img src="<?php echo htmlspecialchars($postImgUrl); ?>" alt="Post Image">
                                </div>
                                <?php endif; ?>

                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <i class="uil uil-heart"></i>
                                        <i class="uil uil-comment load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>"></i>
                                        <i class="uil uil-share-alt"></i>
                                    </div>
                                    <i class="uil uil-bookmark"></i>
                                </div>

                                   <?php if (!empty($post['content'])): ?>
                                <div class="caption">
                                 <p><b><?php echo htmlspecialchars($post['username'] ?? ''); ?></b> <?php echo htmlspecialchars($post['content']); ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="comments load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                    View all comments
                                </div>

                                <div class="comment-section" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                    <div class="comment-header">
                                        <h3>Comments</h3>
                                        <button class="close-comments">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="comments-container" id="comments-container-<?php echo (int)$post['post_id']; ?>">
                                        <div class="comments-loading">Click to load comments</div>
                                    </div>
                                    
                                    <div class="add-comment-form">
                                        <div class="comment-input-container">
                                            <img src="../../public/images/profile-1.jpg" alt="Your Avatar" class="current-user-avatar">
                                            <div class="comment-input-wrapper">
                                                <textarea class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo (int)$post['post_id']; ?>"></textarea>
                                                <button class="comment-submit-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">Post Comment</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="feed">
                            <div class="caption"><p>No posts yet. Create one to get started.</p></div>
                        </div>
                    <?php endif; ?>
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
                                <img src="../../public/images/2.jpg">
                                <div class="active"></div>
                            </div>
                            <div class="message-body">
                                <h5>Minthaka J.</h5>
                                <p>Are we still meeting tomorrow?</p>
                            </div>
                        </div>
                        <div class="message">
                            <div class="profile-picture">
                                <img src="../../public/images/6.jpg">
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
                                <img src="../../public/images/4.jpg">
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
                                <img src="../../public/images/2.jpg">
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
                                <img src="../../public/images/5.jpg">
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
                                <img src="../../public/images/1.jpg">
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
                                <img src="../../public/images/5.jpg">
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
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <!-- Edit Post Modal -->
    <div id="editPostModal" class="post-modal" role="dialog" aria-modal="true" aria-labelledby="editPostTitle" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editPostTitle">Edit Post</h3>
                <button class="close-modal edit-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="editPostContent">Content</label>
                    <textarea id="editPostContent" rows="5" placeholder="Update your post..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary cancel-edit">Cancel</button>
                <button class="btn btn-primary save-edit" disabled>Save</button>
            </div>
        </div>
    </div>

    <script src="../../public/js/calender.js"></script>
    <script src="../../public/js/feed.js"></script>
    <script src="../../public/js/friends.js"></script>
    <script src="../../public/js/general.js"></script>
    <script src="../../public/js/notificationpopup.js"></script>
    <script src="../../public/js/navbar.js"></script>
    <script src="../../public/js/post.js"></script>
    <script src="../../public/js/comment.js"></script>
    
</body>
</html>