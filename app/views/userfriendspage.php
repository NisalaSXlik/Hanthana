<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

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
$isOwner = (int)$currentUserId === (int)$profileUserId;
$friendModel = new FriendModel();
$pendingRequests = $isOwner ? $friendModel->getIncomingRequests($currentUserId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isOwner ? 'My Friends' : htmlspecialchars($displayName) . ' Friends'; ?> - Profile</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/events-page.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/userfriends.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>
        .profile-page nav {
            background: var(--color-white) !important;
        }

        .profile-page main .container {
            align-items: start;
        }

        .profile-page .right {
            align-self: start;
        }
    </style>
</head>
<body class="profile-page user-friends-page">
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="friends-page-header">
                    <div class="friends-header-top">
                        <div class="friends-title-block">
                            <h2>
                                <i class="uil uil-users-alt"></i>
                                <?php echo $isOwner ? 'My Friends' : htmlspecialchars($displayName) . '\'s Friends'; ?>
                            </h2>
                            <p><?php echo $friendsCount; ?> friend<?php echo $friendsCount !== 1 ? 's' : ''; ?></p>
                        </div>
                    </div>
                </div>

                <div class="friends-grid">
                    <?php if (!empty($friendList)): ?>
                        <?php foreach ($friendList as $friend): ?>
                            <?php
                                $friendUserId = (int)($friend['friend_user_id'] ?? 0);
                                $friendName = trim(($friend['first_name'] ?? '') . ' ' . ($friend['last_name'] ?? ''));
                                $friendName = $friendName === '' ? ($friend['username'] ?? 'Unknown User') : $friendName;
                                $friendHandle = !empty($friend['username']) ? '@' . $friend['username'] : '';
                                $friendAvatar = MediaHelper::resolveMediaPath($friend['profile_picture'] ?? '', 'uploads/user_dp/default.png');
                                $friendProfileUrl = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $friendUserId;
                            ?>
                            <div class="friend-card" data-friend-user-id="<?php echo $friendUserId; ?>">
                                <a href="<?php echo htmlspecialchars($friendProfileUrl); ?>" class="friend-card-link">
                                    <img src="<?php echo htmlspecialchars($friendAvatar); ?>" alt="<?php echo htmlspecialchars($friendName); ?>" class="friend-avatar">
                                    <div class="friend-info">
                                        <h4><?php echo htmlspecialchars($friendName); ?></h4>
                                        <?php if ($friendHandle !== ''): ?>
                                            <p><?php echo htmlspecialchars($friendHandle); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php if ($isOwner): ?>
                                <button
                                    type="button"
                                    class="friend-action-btn unfriend-btn"
                                    data-user-id="<?php echo $friendUserId; ?>"
                                    title="Unfriend"
                                >
                                    <i class="uil uil-user-minus"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="friends-empty">
                            <i class="uil uil-users-alt"></i>
                            <p><?php echo $isOwner ? 'No friends yet. Start connecting!' : 'This user has no friends yet.'; ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isOwner && !empty($pendingRequests)): ?>
                <div class="friend-requests-section">
                    <h3><i class="uil uil-user-check"></i> Friend Requests</h3>
                    <div class="friend-requests-list">
                        <?php foreach ($pendingRequests as $request): ?>
                            <?php
                                $reqUserId = (int)($request['sender_id'] ?? 0);
                                $reqName = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
                                $reqName = $reqName === '' ? ($request['username'] ?? 'Unknown User') : $reqName;
                                $reqHandle = !empty($request['username']) ? '@' . $request['username'] : '';
                                $reqAvatar = MediaHelper::resolveMediaPath($request['profile_picture'] ?? '', 'uploads/user_dp/default.png');
                                $reqProfileUrl = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $reqUserId;
                            ?>
                            <div class="friend-request-item" data-request-user-id="<?php echo $reqUserId; ?>">
                                <a href="<?php echo htmlspecialchars($reqProfileUrl); ?>" class="friend-request-link">
                                    <img src="<?php echo htmlspecialchars($reqAvatar); ?>" alt="<?php echo htmlspecialchars($reqName); ?>" class="friend-request-avatar">
                                    <div class="friend-request-info">
                                        <h5><?php echo htmlspecialchars($reqName); ?></h5>
                                        <?php if ($reqHandle !== ''): ?>
                                            <small><?php echo htmlspecialchars($reqHandle); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="friend-request-actions">
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm accept-friend-btn"
                                        data-user-id="<?php echo $reqUserId; ?>"
                                    >
                                        <i class="uil uil-check"></i> Accept
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm reject-friend-btn"
                                        data-user-id="<?php echo $reqUserId; ?>"
                                    >
                                        <i class="uil uil-times"></i> Decline
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <div class="friends-modal" id="friendListModal" aria-hidden="true">
        <div class="friends-modal__content" role="dialog" aria-modal="true" aria-labelledby="friendListTitle">
            <div class="friends-modal__header">
                <h2 id="friendListTitle">Friends <span data-friend-list-count>(<?php echo $friendsCount; ?>)</span></h2>
                <button type="button" class="friends-modal__close" data-close-friends-modal aria-label="Close friend list">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <div class="friends-modal__body">
                <ul class="friends-modal__list" data-friends-list>
                    <?php foreach ($friendList as $friend): ?>
                        <?php
                            $friendUserId = (int)($friend['friend_user_id'] ?? 0);
                            $friendName = trim(($friend['first_name'] ?? '') . ' ' . ($friend['last_name'] ?? ''));
                            if ($friendName === '') {
                                $friendName = $friend['username'] ?? 'Unknown User';
                            }
                            $friendHandle = !empty($friend['username']) ? '@' . $friend['username'] : '';
                            $friendAvatar = MediaHelper::resolveMediaPath($friend['profile_picture'] ?? '', 'uploads/user_dp/default.png');
                            $friendProfileUrl = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $friendUserId;
                        ?>
                        <li class="friends-modal__item" data-friend-user-id="<?php echo $friendUserId; ?>">
                            <a class="friends-modal__link" href="<?php echo htmlspecialchars($friendProfileUrl); ?>">
                                <img class="friends-modal__avatar" src="<?php echo htmlspecialchars($friendAvatar); ?>" alt="<?php echo htmlspecialchars($friendName); ?>">
                                <div class="friends-modal__info">
                                    <span class="friends-modal__name"><?php echo htmlspecialchars($friendName); ?></span>
                                    <?php if ($friendHandle !== ''): ?>
                                        <span class="friends-modal__handle"><?php echo htmlspecialchars($friendHandle); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="friends-modal__empty" data-friends-empty style="<?php echo empty($friendList) ? '' : 'display:none;'; ?>">No friends to show yet.</p>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const CURRENT_USER_ID = <?php echo $currentUserId; ?>;
        const PROFILE_USER_ID = <?php echo $profileUserId; ?>;
        const IS_OWNER = <?php echo $isOwner ? 'true' : 'false'; ?>;
    </script>
    <script src="./js/general.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/userfriends.js"></script>
</body>
</html>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <!-- Instagram-Style Post Modal -->
    <div id="postViewModal" class="post-view-modal" aria-hidden="true">
        <div class="post-view-overlay"></div>
        <button class="post-view-close" aria-label="Close">
            <i class="uil uil-times"></i>
        </button>
        <button class="post-view-prev" aria-label="Previous post">
            <i class="uil uil-angle-left"></i>
        </button>
        <button class="post-view-next" aria-label="Next post">
            <i class="uil uil-angle-right"></i>
        </button>
        <div class="post-view-content">
            <div class="post-view-image">
                <img src="" alt="Post image" id="postViewImage">
                <div class="post-view-text-content" id="postViewTextContent"></div>
            </div>
            <div class="post-view-sidebar">
                <div class="post-view-header">
                    <div class="user">
                        <div class="profile-picture">
                            <img src="" alt="Profile" id="postViewAvatar">
                        </div>
                        <div class="info">
                            <h3 id="postViewUsername"></h3>
                            <small id="postViewDate"></small>
                        </div>
                    </div>
                </div>
                <div class="post-view-caption">
                    <p id="postViewCaption"></p>
                </div>
                <div class="post-view-actions">
                    <div class="modal-interactions">
                        <button type="button" class="modal-vote" id="postViewUpvoteBtn" data-vote-type="upvote" aria-label="Upvote">
                            <i class="uil uil-arrow-up"></i>
                            <span id="postViewUpvoteCount">0</span>
                        </button>
                        <button type="button" class="modal-vote" id="postViewDownvoteBtn" data-vote-type="downvote" aria-label="Downvote">
                            <i class="uil uil-arrow-down"></i>
                            <span id="postViewDownvoteCount">0</span>
                        </button>
                        <button type="button" class="modal-comments-toggle" id="postViewCommentToggle" aria-label="View comments">
                            <i class="uil uil-comment-dots"></i>
                            <span id="postViewCommentCount">0</span>
                        </button>
                    </div>
                </div>
                <div class="post-view-comments" id="postViewCommentsPanel">
                    <div class="comments-header">
                        <h4>Comments</h4>
                        <span id="postViewCommentBadge">0</span>
                    </div>
                    <div class="comments-list" id="postViewCommentsList">
                        <div class="comments-loading">Loading comments...</div>
                    </div>
                    <form id="postViewCommentForm" class="comment-form hf-form hf-inline">
                        <textarea id="postViewCommentInput" placeholder="Add a comment..." rows="2"></textarea>
                        <button type="submit" id="postViewCommentSubmit">Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const VIEWER_USER_ID = <?php echo $viewerId; ?>;
        const PROFILE_USER_ID = <?php echo $profileUserId; ?>;
        const POSTS_ARE_PRIVATE = <?php echo $postsArePrivate ? 'true' : 'false'; ?>;
        const IS_OWNER = <?php echo $isOwner ? 'true' : 'false'; ?>;
        window.PERSONAL_POSTS = <?php echo json_encode($personalPosts ?? []); ?>;
        window.GROUP_POSTS = <?php echo json_encode($groupPosts ?? []); ?>;
    </script>
    <script id="profilePostPayload" type="application/json">
        <?php echo json_encode([
            'personal' => $personalPosts ?? [],
            'group' => $groupPosts ?? []
        ], JSON_UNESCAPED_SLASHES); ?>
    </script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/userprofileview.js"></script>
</body>
</html>
