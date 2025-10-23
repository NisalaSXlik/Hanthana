<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

$viewerId = (int)($_SESSION['user_id'] ?? 0);
$profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $viewerId;

$userModel = new User();
$profileUser = $userModel->findById($profileUserId);

if (!$profileUser) {
    header('Location: myFeed.php?error=user_not_found');
    exit();
}

$isOwner = $viewerId === $profileUserId;

$postModel = new PostModel();
$friendModel = new FriendModel();
$feedPosts = $postModel->getFeedPosts($viewerId);
$userPosts = array_values(array_filter($feedPosts, function ($post) use ($profileUserId) {
    return isset($post['user_id']) && (int)$post['user_id'] === $profileUserId;
}));

$photoPosts = array_values(array_filter($userPosts, function ($post) {
    return !empty($post['image_url']);
}));

$publicBase = rtrim(BASE_PATH, '/') . '/public/';

if (!function_exists('resolveMediaPath')) {
    function resolveMediaPath(?string $raw, string $default, string $subDir): string {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return $default;
        }
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        $normalized = ltrim(str_replace('\\', '/', $raw), '/');
        $base = rtrim(BASE_PATH, '/');

        if (strpos($normalized, 'public/') === 0) {
            return $base . '/' . $normalized;
        }

        if (strpos($normalized, 'images/') === 0 || strpos($normalized, 'uploads/') === 0) {
            return $base . '/public/' . $normalized;
        }

        return $base . '/public/' . trim($subDir, '/') . '/' . basename($normalized);
    }
}

$profilePicture = resolveMediaPath($profileUser['profile_picture'] ?? '', $publicBase . 'images/avatars/defaultProfilePic.png', 'images/avatars');
$coverPhoto = resolveMediaPath($profileUser['cover_photo'] ?? '', $publicBase . 'images/default_cover.jpg', 'images/userCover');

$displayName = trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''));
$displayHandle = !empty($profileUser['username']) ? '@' . $profileUser['username'] : '';
$postCount = count($userPosts);
$friendsCount = (int)($profileUser['friends_count'] ?? 0);
$bio = $profileUser['bio'] ?? 'No bio provided yet.';
$location = $profileUser['location'] ?? 'Location not set';
$university = $profileUser['university'] ?? 'University not set';
$joinedAt = !empty($profileUser['created_at']) ? date('F Y', strtotime($profileUser['created_at'])) : 'Unknown';
$email = $profileUser['email'] ?? null;
$phone = $profileUser['phone_number'] ?? null;
$dob = !empty($profileUser['date_of_birth']) ? date('F j, Y', strtotime($profileUser['date_of_birth'])) : null;
$dobValue = !empty($profileUser['date_of_birth']) ? date('Y-m-d', strtotime($profileUser['date_of_birth'])) : '';
$interestTags = !empty($profileUser['interests']) ? array_map('trim', explode(',', $profileUser['interests'])) : [];
$friendListLimit = 50;
$friendList = $friendModel->getAcceptedFriends($profileUserId, $friendListLimit);
$friendListCount = $friendsCount;
$hasMoreFriends = $friendsCount > count($friendList);

$friendButtonState = 'none';
$friendButtonLabel = 'Add Friend';
$friendButtonIcon = 'uil uil-user-plus';
$friendButtonDisabled = false;
$friendButtonVariant = 'btn-primary';

if (!$isOwner) {
    $friendship = $friendModel->getFriendship($viewerId, $profileUserId);

    if ($friendship) {
        switch ($friendship['status']) {
            case 'pending':
                if ((int)$friendship['user_id'] === $viewerId) {
                    $friendButtonState = 'pending_outgoing';
                    $friendButtonLabel = 'Request Sent';
                    $friendButtonIcon = 'uil uil-clock';
                    $friendButtonDisabled = true;
                    $friendButtonVariant = 'btn-secondary';
                } else {
                    $friendButtonState = 'incoming_pending';
                    $friendButtonLabel = 'Request Pending';
                    $friendButtonIcon = 'uil uil-user-plus';
                    $friendButtonDisabled = true;
                    $friendButtonVariant = 'btn-secondary';
                }
                break;
            case 'accepted':
                $friendButtonState = 'friends';
                $friendButtonLabel = 'Friends';
                $friendButtonIcon = 'uil uil-user-check';
                $friendButtonDisabled = false;
                $friendButtonVariant = 'btn-secondary';
                break;
            case 'blocked':
                $friendButtonState = 'blocked';
                $friendButtonLabel = 'Unavailable';
                $friendButtonIcon = 'uil uil-ban';
                $friendButtonDisabled = true;
                $friendButtonVariant = 'btn-secondary';
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($displayName !== '' ? $displayName : $displayHandle); ?> - Profile</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/general.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/groupprofileview.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/mediaquery.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/calender.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/post.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/myfeed.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/notificationpopup.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicBase . 'css/userprofileview.css'); ?>">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="profile-header">
                    <div class="profile-cover">
                        <img src="<?php echo htmlspecialchars($coverPhoto); ?>" alt="Profile Cover" id="profileCoverImage">
                        <?php if ($isOwner): ?>
                        <button type="button" class="btn btn-primary edit-cover-btn" id="triggerEditCover">
                            <i class="uil uil-camera"></i> Edit Cover
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-dp-container">
                            <div class="profile-dp">
                                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" id="profileAvatarImage">
                                <?php if ($isOwner): ?>
                                <button type="button" class="edit-dp-btn" id="triggerEditAvatar">
                                    <i class="uil uil-camera"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-name"><?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Unknown User'); ?></p>
                            <?php if ($displayHandle !== ''): ?>
                                <p class="profile-handle"><?php echo htmlspecialchars($displayHandle); ?></p>
                            <?php endif; ?>
                            <div class="profile-stats">
                                <button type="button" class="stat stat-link" data-tab-target="posts" aria-label="View posts section">
                                    <strong><?php echo $postCount; ?></strong>
                                    <span>Posts</span>
                                </button>
                                <div class="stat friend-stat">
                                    <button type="button" class="friend-count-trigger" data-friend-count-trigger>
                                        <strong data-friend-count="<?php echo $friendsCount; ?>"><?php echo $friendsCount; ?></strong>
                                        <span>Friends</span>
                                    </button>
                                </div>
                                <button type="button" class="stat stat-link" data-tab-target="photos" aria-label="View photos section">
                                    <strong><?php echo count($photoPosts); ?></strong>
                                    <span>Photos</span>
                                </button>
                            </div>
                            <p class="profile-bio"><?php echo htmlspecialchars($bio); ?></p>
                            <div class="profile-meta">
                                <span><i class="uil uil-location-point"></i> <?php echo htmlspecialchars($location); ?></span>
                                <span><i class="uil uil-university"></i> <?php echo htmlspecialchars($university); ?></span>
                                <span><i class="uil uil-calendar-alt"></i> Joined <?php echo htmlspecialchars($joinedAt); ?></span>
                            </div>
                            <div class="profile-actions">
                                <?php if ($isOwner): ?>
                                    <button type="button" class="btn btn-primary" id="editProfileBtn"><i class="uil uil-edit"></i> Edit Profile</button>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn <?php echo $friendButtonVariant; ?> add-friend-btn"
                                        data-user-id="<?php echo $profileUserId; ?>"
                                        data-state="<?php echo htmlspecialchars($friendButtonState); ?>"
                                        <?php echo $friendButtonDisabled ? 'disabled' : ''; ?>
                                    >
                                        <i class="<?php echo htmlspecialchars($friendButtonIcon); ?>"></i>
                                        <span><?php echo htmlspecialchars($friendButtonLabel); ?></span>
                                    </button>
                                    <button class="btn btn-secondary" type="button"><i class="uil uil-message"></i> Message</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-tabs">
                        <ul>
                            <li class="active"><a href="#" data-tab="posts">Posts</a></li>
                            <li><a href="#" data-tab="about">About</a></li>
                            <li><a href="#" data-tab="photos">Photos</a></li>
                        </ul>
                    </div>
                </div>

                <div class="group-content">
                    <div class="tab-content active" id="tab-posts">
                        <?php if (!empty($userPosts)): ?>
                            <div class="posts-feed">
                                <?php foreach ($userPosts as $post): ?>
                                    <div class="feed" id="post-<?php echo (int)$post['post_id']; ?>" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                        <div class="head">
                                            <div class="user">
                                                <div class="profile-picture">
                                                    <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? $profilePicture); ?>" alt="<?php echo htmlspecialchars($displayName); ?>">
                                                </div>
                                                <div class="info">
                                                    <h3><?php echo htmlspecialchars($displayName !== '' ? $displayName : $displayHandle); ?></h3>
                                                    <small><?php echo htmlspecialchars($post['created_at'] ?? ''); ?></small>
                                                </div>
                                            </div>
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
                                            <div class="photo">
                                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image">
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($post['content'])): ?>
                                            <div class="caption">
                                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="action-buttons">
                                            <div class="interaction-buttons">
                                                <?php
                                                $upClass = ($post['user_vote'] ?? null) === 'upvote' ? 'liked' : '';
                                                $downClass = ($post['user_vote'] ?? null) === 'downvote' ? 'liked' : '';
                                                ?>
                                                <i class="uil uil-arrow-up <?php echo $upClass; ?>" data-vote-type="upvote"></i>
                                                <small><?php echo (int)($post['upvote_count'] ?? 0); ?></small>
                                                <i class="uil uil-arrow-down <?php echo $downClass; ?>" data-vote-type="downvote"></i>
                                                <small><?php echo (int)($post['downvote_count'] ?? 0); ?></small>
                                                <i class="uil uil-comment"></i>
                                            </div>
                                            <i class="uil uil-bookmark"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="feed">
                                <div class="caption"><p>No posts yet.</p></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-content" id="tab-about">
                        <div class="about-section">
                            <h3>About</h3>
                            <p><?php echo htmlspecialchars($bio); ?></p>
                        </div>
                        <div class="about-section">
                            <h3>Details</h3>
                            <ul class="detail-list">
                                <?php if ($email): ?>
                                    <li><i class="uil uil-envelope"></i> <?php echo htmlspecialchars($email); ?></li>
                                <?php endif; ?>
                                <?php if ($phone): ?>
                                    <li><i class="uil uil-phone"></i> <?php echo htmlspecialchars($phone); ?></li>
                                <?php endif; ?>
                                <?php if ($dob): ?>
                                    <li><i class="uil uil-gift"></i> Born on <?php echo htmlspecialchars($dob); ?></li>
                                <?php endif; ?>
                                <li><i class="uil uil-location-point"></i> <?php echo htmlspecialchars($location); ?></li>
                                <li><i class="uil uil-university"></i> <?php echo htmlspecialchars($university); ?></li>
                                <li><i class="uil uil-calendar-alt"></i> Member since <?php echo htmlspecialchars($joinedAt); ?></li>
                            </ul>
                        </div>
                        <?php if (!empty($interestTags)): ?>
                            <div class="about-section">
                                <h3>Interests</h3>
                                <div class="interest-tags">
                                    <?php foreach ($interestTags as $tag): ?>
                                        <?php if ($tag !== ''): ?>
                                            <span class="interest-tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-content" id="tab-photos">
                        <div class="photo-grid">
                            <?php if (!empty($photoPosts)): ?>
                                <?php foreach ($photoPosts as $post): ?>
                                    <div class="photo-item">
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Photo from post <?php echo (int)$post['post_id']; ?>">
                                        <?php if (!empty($post['created_at'])): ?>
                                            <span><?php echo htmlspecialchars(date('M j, Y', strtotime($post['created_at']))); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-message">No photos yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right profile-sidebar">
                <div class="group-details">
                    <h4>Profile Snapshot</h4>
                    <div class="detail-list">
                        <div class="detail-item">
                            <i class="uil uil-user"></i>
                            <span><?php echo htmlspecialchars($displayName !== '' ? $displayName : $displayHandle); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-location-point"></i>
                            <span><?php echo htmlspecialchars($location); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span>Member since <?php echo htmlspecialchars($joinedAt); ?></span>
                        </div>
                    </div>
                </div>

                <div class="top-collaborators">
                    <div class="heading">
                        <h4>Contact</h4>
                    </div>
                    <div class="creator-list">
                        <?php if ($email): ?>
                        <div class="creator-card">
                            <div class="creator-info">
                                <i class="uil uil-envelope"></i>
                                <div class="creator-details">
                                    <h5>Email</h5>
                                    <p class="creator-bio"><?php echo htmlspecialchars($email); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                        <div class="creator-card">
                            <div class="creator-info">
                                <i class="uil uil-phone"></i>
                                <div class="creator-details">
                                    <h5>Phone</h5>
                                    <p class="creator-bio"><?php echo htmlspecialchars($phone); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="creator-card friend-count-card" data-friend-count-trigger role="button" tabindex="0">
                            <div class="creator-info">
                                <i class="uil uil-users-alt"></i>
                                <div class="creator-details">
                                    <h5>Friends</h5>
                                    <p class="creator-bio" data-friend-count-label><?php echo $friendsCount; ?> total friends</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($isOwner): ?>
        <input type="file" id="directAvatarInput" name="profile_picture" accept="image/*" style="display:none;" aria-hidden="true">
        <input type="file" id="directCoverInput" name="cover_photo" accept="image/*" style="display:none;" aria-hidden="true">
    <?php endif; ?>

    <?php if ($isOwner): ?>
    <div id="editProfileModal" class="profile-edit-modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button type="button" class="modal-close" id="closeEditProfileModal" aria-label="Close">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="editProfileForm" class="modal-body" enctype="multipart/form-data">
                <div id="profileFormMessage" class="form-message" style="display:none;"></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstNameInput">First Name</label>
                        <input type="text" id="firstNameInput" name="first_name" maxlength="100" value="<?php echo htmlspecialchars($profileUser['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastNameInput">Last Name</label>
                        <input type="text" id="lastNameInput" name="last_name" maxlength="100" value="<?php echo htmlspecialchars($profileUser['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="usernameInput">Username</label>
                        <input type="text" id="usernameInput" name="username" maxlength="100" value="<?php echo htmlspecialchars($profileUser['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="emailInput">Email</label>
                        <input type="email" id="emailInput" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phoneInput">Phone Number</label>
                        <input type="text" id="phoneInput" name="phone_number" maxlength="15" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dobInput">Date of Birth</label>
                        <input type="date" id="dobInput" name="date_of_birth" value="<?php echo htmlspecialchars($dobValue); ?>">
                    </div>
                    <div class="form-group">
                        <label for="locationInput">Location</label>
                        <input type="text" id="locationInput" name="location" maxlength="150" value="<?php echo htmlspecialchars($profileUser['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="universityInput">University</label>
                        <input type="text" id="universityInput" name="university" maxlength="150" value="<?php echo htmlspecialchars($profileUser['university'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="bioInput">Bio</label>
                    <textarea id="bioInput" name="bio" rows="3" maxlength="500"><?php echo htmlspecialchars($profileUser['bio'] ?? ''); ?></textarea>
                </div>
                <div class="media-upload-group">
                    <div class="media-field">
                        <label for="profilePictureInput">Profile Picture</label>
                        <div class="media-preview">
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile preview" id="profilePicturePreview">
                        </div>
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                    </div>
                    <div class="media-field">
                        <label for="coverPhotoInput">Cover Photo</label>
                        <div class="media-preview cover">
                            <img src="<?php echo htmlspecialchars($coverPhoto); ?>" alt="Cover preview" id="coverPhotoPreview">
                        </div>
                        <input type="file" id="coverPhotoInput" name="cover_photo" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEditProfileBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="friends-modal" id="friendListModal" aria-hidden="true">
        <div class="friends-modal__content" role="dialog" aria-modal="true" aria-labelledby="friendListTitle">
            <div class="friends-modal__header">
                <h2 id="friendListTitle">Friends <span data-friend-list-count>(<?php echo $friendListCount; ?>)</span></h2>
                <button type="button" class="friends-modal__close" data-close-friends-modal aria-label="Close friend list">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <div class="friends-modal__body">
                <?php if ($hasMoreFriends && !empty($friendList)): ?>
                    <p class="friends-modal__note" data-friends-note>Showing first <?php echo count($friendList); ?> friends.</p>
                <?php else: ?>
                    <p class="friends-modal__note" data-friends-note style="display:none;">Showing first <?php echo count($friendList); ?> friends.</p>
                <?php endif; ?>

                <ul class="friends-modal__list" data-friends-list data-friend-list-limit="<?php echo $friendListLimit; ?>">
                    <?php foreach ($friendList as $friend): ?>
                        <?php
                            $friendUserId = (int)($friend['friend_user_id'] ?? 0);
                            $friendName = trim(($friend['first_name'] ?? '') . ' ' . ($friend['last_name'] ?? ''));
                            if ($friendName === '') {
                                $friendName = $friend['username'] ?? 'Unknown User';
                            }
                            $friendHandle = !empty($friend['username']) ? '@' . $friend['username'] : '';
                            $friendAvatar = resolveMediaPath($friend['profile_picture'] ?? '', $publicBase . 'images/avatars/defaultProfilePic.png', 'images/avatars');
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

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const VIEWER_USER_ID = <?php echo $viewerId; ?>;
        const PROFILE_USER_ID = <?php echo $profileUserId; ?>;
    </script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/calender.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/feed.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/friends.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/general.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/notificationpopup.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/navbar.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/post.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/comment.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicBase . 'js/userprofileview.js'); ?>"></script>
</body>
</html>
