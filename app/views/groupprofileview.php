<?php
require_once __DIR__ . '/../../config/config.php'; // Include config to get BASE_PATH

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$userId = $_SESSION['user_id'];

// Debug: Log what we're getting
error_log("GroupProfileView - Requested group_id: " . ($groupId ?? 'NULL'));
error_log("GroupProfileView - GET params: " . print_r($_GET, true));

if (!$groupId) {
    header('Location: myfeed.php');
    exit();
}

$groupModel = new GroupModel();
$group = $groupModel->getById($groupId);

error_log("GroupProfileView - Loaded group: " . ($group ? $group['name'] : 'NULL'));

$friendModel = new FriendModel();
$incomingFriendRequests = $friendModel->getIncomingRequests($userId);

if (!$group) {
    header('Location: myfeed.php?error=group_not_found');
    exit();
}

// Check permissions
$isJoined = false;
$isCreator = isset($group['created_by']) && (int)$group['created_by'] === $userId;
$isAdmin = $groupModel->isGroupAdmin($groupId, $userId);

$joinedGroups = $groupModel->getGroupsJoinedBy($userId);
foreach ($joinedGroups as $g) {
    if ($g['group_id'] == $groupId) {
        $isJoined = true;
        break;
    }
}

$sharedFiles = [
    [
        'name' => 'Anime_Tutorial.pdf',
        'uploader' => 'Lahiru F.',
        'url' => '#'
    ],
    [
        'name' => 'prize_list.docx',
        'uploader' => 'Minthaka J.',
        'url' => '#'
    ],
];

$upcomingEvents = [
    [
        'title' => 'Hayao Miyazaki - Conveying taste through anime art',
        'datetime' => '2025-08-20 18:00:00',
        'location' => 'B.M.I.C.H',
        'cta' => 'Interested'
    ],
];

$memberSpotlight = [
    [
        'name' => 'Lithmal Perera',
        'role' => 'Admin',
        'avatar' => '../../public/images/4.jpg',
    ],
    [
        'name' => 'Minthaka Jayawardena',
        'role' => 'Member',
        'avatar' => '../../public/images/6.jpg',
    ],
];

$groupRulesRaw = (string)($group['rules'] ?? '');
$groupRulesList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $groupRulesRaw))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Hanthane</title>
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/general.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/groupprofileview.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/navbar.css"> 
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/mediaquery.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/calender.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/post.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/myfeed.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container">
            <?php 
            // Isolate include to avoid variable collisions (e.g., $group) from the sidebar template
            (function() {
                include __DIR__ . '/templates/left-sidebar.php';
            })();
            ?>

            <div class="middle">
                <!-- Group Header -->
                <div class="profile-header">
                    <div class="profile-cover">
                        <img id="groupCoverImage" src="<?php
                            if ($group && !empty($group['cover_image'])) {
                                $coverPath = htmlspecialchars($group['cover_image']);
                                if (filter_var($coverPath, FILTER_VALIDATE_URL)) {
                                    echo $coverPath;
                                } elseif (strpos($coverPath, '/') === 0) {
                                    echo $coverPath;
                                } elseif (strpos($coverPath, 'public/') === 0) {
                                    echo rtrim(BASE_PATH, '/') . '/' . ltrim($coverPath, '/');
                                } else {
                                    echo rtrim(BASE_PATH, '/') . '/public/' . ltrim($coverPath, '/');
                                }
                            } else {
                                echo rtrim(BASE_PATH, '/') . '/public/images/default_cover.jpg';
                            }
                        ?>" alt="Profile Cover">
                        <?php if ($isCreator || $isAdmin): ?>
                        <button class="edit-cover-btn">
                            <i class="uil uil-camera"></i> Edit Cover
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-dp-container">
                            <div class="profile-dp">
                                <img id="groupDpImage" src="<?php
                                    if ($group && !empty($group['display_picture'])) {
                                        $dpPath = htmlspecialchars($group['display_picture']);
                                        if (filter_var($dpPath, FILTER_VALIDATE_URL)) {
                                            echo $dpPath;
                                        } elseif (strpos($dpPath, '/') === 0) {
                                            echo $dpPath;
                                        } elseif (strpos($dpPath, 'public/') === 0) {
                                            echo rtrim(BASE_PATH, '/') . '/' . ltrim($dpPath, '/');
                                        } else {
                                            echo rtrim(BASE_PATH, '/') . '/public/' . ltrim($dpPath, '/');
                                        }
                                    } else {
                                        echo rtrim(BASE_PATH, '/') . '/public/images/default_dp.jpg';
                                    }
                                ?>" alt="Profile DP">
                                <?php if ($isCreator || $isAdmin): ?>
                                <button class="edit-dp-btn">
                                    <i class="uil uil-camera"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-name"><?php echo htmlspecialchars($group['name']); ?></p>
                            <p class="profile-handle">@<?php echo htmlspecialchars($group['tag']); ?></p>
                            <div class="profile-stats">
                                <div class="stat">
                                    <strong><?php echo htmlspecialchars($group['post_count'] ?? '0'); ?></strong>
                                    <span>Posts</span>
                                </div>
                                <div class="stat">
                                    <strong><?php echo htmlspecialchars($group['member_count'] ?? '0'); ?></strong>
                                    <span>Members</span>
                                </div>
                            </div>
                            <p class="profile-bio"><?php echo htmlspecialchars($group['description']); ?></p>
                            <div class="profile-actions">
                                <?php if (!$isCreator): ?>
                                    <?php if ($isJoined): ?>
                                        <button class="btn btn-danger leave-btn">Leave</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary join-btn">Join</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn btn-secondary invite-btn">Invite</button>
                                
                                <!-- Dropdown menu for creator/admin -->
                                <?php if ($isCreator || $isAdmin): ?>
                                <div class="dropdown-container">
                                    <button class="btn btn-icon" id="groupOptionsBtn">
                                        <i class="uil uil-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu" id="groupOptionsMenu" style="display: none;">
                                        <a href="#" class="dropdown-item" id="editGroupOption">
                                            <i class="uil uil-edit"></i>
                                            <span>Edit Group</span>
                                        </a>
                                        <?php if ($isCreator): ?>
                                        <a href="#" class="dropdown-item delete-option" id="deleteGroupOption">
                                            <i class="uil uil-trash-alt"></i>
                                            <span>Delete Group</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="profile-tabs">
                        <ul>
                            <li class="active">
                                <a href="#" data-tab="post">Posts</a>
                            </li>
                            <li>
                                <a href="#" data-tab="about">About</a>
                            </li>
                            <li>
                                <a href="#" data-tab="files">Files</a>
                            </li>
                            <li>
                                <a href="#" data-tab="events">Events</a>
                            </li>
                            <li>
                                <a href="#" data-tab="members">Members</a>
                            </li>
                            <li>
                                <a href="#" data-tab="photos">Photos</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Profile Content Area -->
                <div class="group-content">
                    <!-- Posts Tab Content -->
                    <div class="tab-content active" id="posts-content">
                        <!-- Post Creation -->
                        <div class="create-post">
                            <div class="post-input">
                                <img src="../../public/images/4.jpg" alt="Your profile">
                                <input type="text" placeholder="What's on your mind, Lithmal?">
                            </div>
                            <div class="post-options">
                                <button class="option">
                                    <i class="uil uil-image"></i>
                                    <span>Photo</span>
                                </button>
                                <button class="option">
                                    <i class="uil uil-video"></i>
                                    <span>Video</span>
                                </button>
                                <button class="option">
                                    <i class="uil uil-calendar-alt"></i>
                                    <span>Event</span>
                                </button>
                            </div>
                        </div>

                        <!-- FEED: Default Active Tab -->
                        <div class="posts-feed">
                            <div class="feed">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-picture">
                                            <img src="../../public/images/gpvpostTY_dp.jpg">
                                        </div>
                                        <div class="info">
                                            <h3>Tachi Yamamoto</h3>
                                            <small>Colombo, 15 mins ago</small>
                                        </div>
                                    </div>
                                    <i class="uil uil-ellipsis-h"></i>
                                </div>
                                <div class="photo">
                                    <img src="../../public/images/gpvpost_content1.jpg">
                                </div>
                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <i class="uil uil-heart"></i>
                                        <i class="uil uil-comment"></i>
                                        <i class="uil uil-share-alt"></i>
                                        <button class="add-to-calendar-btn"
                                            data-event='{"title":"Temple Visit","date":"2023-11-15T09:00:00","location":"Temple of the Tooth, Kandy","image":"../../public/images/2.jpg"}'>
                                            <i class="uil uil-calendar-alt"></i> Add to Calendar</button>
                                    </div>
                                    <i class="uil uil-bookmark"></i>
                                </div>
                                <div class="liked-by">
                                    <div class="liked-users">
                                        <img src="../../public/images/gpvpostNJ_dp.jpg">
                                        <img src="../../public/images/gpvpostfun_dp1.jpg">
                                        <img src="../../public/images/gpvpostfun_dp2.jpg">
                                    </div>
                                    <p>Liked by <b>Zanka</b> and <b>187 others</b></p>
                                </div>
                                <div class="caption">
                                    <p><b>Tachi Yamamoto</b> Yuji Itadori drip! Slapped a supreme drip on yuji😎! Inspiration for Supreme-level meme fits
                                        <br><p class="post-tags">#photoshop #itadori #dripvibes #funedit #animefit #justforfun</p></p>
                                </div>
                                <div class="comments">View all 42 comments</div>
                            </div>

                            <div class="feed">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-picture">
                                            <img src="../../public/images/gpvpostNJ_dp.jpg">
                                        </div>
                                        <div class="info">
                                            <h3>Nijou-Jou</h3>
                                            <small>Kandy, 1 hour ago</small>
                                        </div>
                                    </div>
                                    <i class="uil uil-ellipsis-h"></i>
                                </div>
                                <div class="photo">
                                    <img src="../../public/images/gpvpost_content2.jpg">
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
                                        <img src="../../public/images/gpvpostTY_dp.jpg">
                                        <img src="../../public/images/gpvpostfun_dp3.jpg">
                                        <img src="../../public/images/gpvpostfun_dp4.jpg">
                                    </div>
                                    <p>Liked by <b>Tachi</b> and <b>243 others</b></p>
                                </div>
                                <div class="caption">
                                    <p><b>Nijou-Jou</b> Quick sketch practice ✏️
                                        <br><p class="post-tags">#drawing-wramup #schoolrumble</p></p>
                                </div>
                                <div class="comments">View all 56 comments</div>
                            </div>
                        </div>
                        <!-- More posts... -->
                    </div>

                    <!-- ABOUT TAB -->
                    <div class="tab-content" id="about-content">
                        <div class="about-grid">
                            <div class="about-card about-overview">
                                <h3>About This Group</h3>
                                <p>
                                    <?php
                                    $aboutDescription = trim((string)($group['description'] ?? ''));
                                    echo htmlspecialchars($aboutDescription !== '' ? $aboutDescription : 'This group does not have a description yet.');
                                    ?>
                                </p>
                            </div>

                            <div class="about-card about-details">
                                <h4>Key Details</h4>
                                <ul class="about-detail-list">
                                    <li>
                                        <i class="uil uil-shield-check"></i>
                                        <span>Privacy</span>
                                        <strong><?php echo ucfirst(htmlspecialchars($group['privacy_status'] ?? 'public')); ?></strong>
                                    </li>
                                    <li>
                                        <i class="uil uil-calendar-alt"></i>
                                        <span>Created</span>
                                        <strong><?php echo htmlspecialchars(date('F Y', strtotime($group['created_at']))); ?></strong>
                                    </li>
                                    <li>
                                        <i class="uil uil-users-alt"></i>
                                        <span>Members</span>
                                        <strong><?php echo (int)($group['member_count'] ?? 0); ?></strong>
                                    </li>
                                    <li>
                                        <i class="uil uil-compass"></i>
                                        <span>Focus</span>
                                        <strong><?php echo htmlspecialchars($group['focus'] ?? 'General'); ?></strong>
                                    </li>
                                    <li>
                                        <i class="uil uil-tag"></i>
                                        <span>Group Tag</span>
                                        <strong><?php echo htmlspecialchars($group['tag'] ?? 'N/A'); ?></strong>
                                    </li>
                                </ul>
                            </div>

                            <?php if (!empty($groupRulesList)): ?>
                            <div class="about-card about-rules">
                                <h4>Group Rules</h4>
                                <ul class="rules-list">
                                    <?php foreach ($groupRulesList as $rule): ?>
                                        <li><i class="uil uil-check-circle"></i><?php echo htmlspecialchars($rule); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FILES TAB -->
                    <div class="tab-content" id="files-content">
                        <div class="section-header">
                            <h3>Shared Files</h3>
                            <button class="btn btn-secondary" type="button"><i class="uil uil-upload"></i> Upload</button>
                        </div>
                        <div class="files-grid">
                            <?php foreach ($sharedFiles as $file): ?>
                                <?php
                                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $extensionLabel = $extension !== '' ? strtoupper(substr($extension, 0, 3)) : 'FILE';
                                ?>
                                <div class="file-card" data-file-type="<?php echo htmlspecialchars($extension); ?>">
                                    <div class="file-icon">
                                        <span><?php echo htmlspecialchars($extensionLabel); ?></span>
                                    </div>
                                    <div class="file-info">
                                        <a href="<?php echo htmlspecialchars($file['url']); ?>"><?php echo htmlspecialchars($file['name']); ?></a>
                                        <span class="file-meta">Uploaded by <?php echo htmlspecialchars($file['uploader']); ?></span>
                                    </div>
                                    <button class="btn btn-primary" type="button">Download</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- EVENTS TAB -->
                    <div class="tab-content" id="events-content">
                        <div class="section-header">
                            <h3>Upcoming Events</h3>
                            <a href="#" class="see-all-events">View calendar</a>
                        </div>
                        <div class="events-grid">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <?php
                                $dateTime = new DateTime($event['datetime']);
                                $monthLabel = strtoupper($dateTime->format('M'));
                                $dayLabel = $dateTime->format('d');
                                ?>
                                <div class="event-card">
                                    <div class="event-date">
                                        <span class="month"><?php echo htmlspecialchars($monthLabel); ?></span>
                                        <span class="date-number"><?php echo htmlspecialchars($dayLabel); ?></span>
                                    </div>
                                    <div class="event-info">
                                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                        <p><i class="uil uil-schedule"></i><?php echo htmlspecialchars($dateTime->format('l, g:i A')); ?></p>
                                        <p><i class="uil uil-map-marker"></i><?php echo htmlspecialchars($event['location']); ?></p>
                                    </div>
                                    <button
                                        class="btn btn-primary event-interest-btn"
                                        type="button"
                                        data-default-text="<?php echo htmlspecialchars($event['cta']); ?>"
                                        data-active-text="Interested"
                                        aria-pressed="false"
                                    ><?php echo htmlspecialchars($event['cta']); ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- MEMBERS TAB -->
                    <div class="tab-content" id="members-content">
                        <div class="section-header">
                            <h3>Members</h3>
                            <a href="#" class="see-all-members">See all members</a>
                        </div>
                        <div class="members-grid">
                            <?php foreach ($memberSpotlight as $member): ?>
                                <div class="member-card">
                                    <div class="member-avatar">
                                        <img src="<?php echo htmlspecialchars($member['avatar']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                    </div>
                                    <div class="member-info">
                                        <h5><?php echo htmlspecialchars($member['name']); ?></h5>
                                        <?php if (!empty($member['role'])): ?>
                                            <span class="member-role"><?php echo htmlspecialchars($member['role']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-secondary" type="button">Message</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- PHOTOS TAB -->
                    <div class="tab-content" id="photos-content">
                        <div class="photo-grid">
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content1.jpg" alt="Photo 1">
                            </div>
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content2.jpg" alt="Photo 2">
                            </div>
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content3.jpg" alt="Photo 3">
                            </div>
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content4.jpg" alt="Photo 4">
                            </div>
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content5.jpg" alt="Photo 5">
                            </div>
                            <div class="photo-item">
                                <img src="../../public/images/gpvpost_content6.jpg" alt="Photo 6">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <!-- Group Details -->
                <div class="group-details">
                    <h4>Group Details</h4>
                    <div class="detail-list">
                        <div class="detail-item">
                            <i class="uil uil-user"></i>
                            <span><?php echo ucfirst(htmlspecialchars($group['privacy_status'])) . ' Group'; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-compass"></i>
                            <span><?php echo htmlspecialchars($group['focus'] ?? 'No focus'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-home"></i>
                            <span><?php echo htmlspecialchars($group['tag'] ?? 'No tag'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span>Created <?php echo date('F Y', strtotime($group['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <?php
                    $friendRequests = $incomingFriendRequests ?? [];
                    include __DIR__ . '/templates/friend-requests.php';
                ?>

                <!-- Top Collaborators -->
                <div class="top-collaborators">
                    <div class="heading">
                        <h4>Top Collaborators</h4>
                        <a href="#" class="see-all">See all</a>
                    </div>
                    <div class="creator-list">
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp1.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Goth bunny</h5>
                                    <p class="creator-bio">12 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>

                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp4.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Naruuuuto</h5>
                                    <p class="creator-bio">8 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>

                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp3.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Ozamu Dazai</h5>
                                    <p class="creator-bio">15 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Group Modal -->
    <div id="editGroupModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Group</h3>
                <button class="modal-close" id="closeEditGroupModal">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="editGroupForm" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                <div class="form-group">
                    <label for="editGroupName">Group Name</label>
                    <input type="text" id="editGroupName" name="name" maxlength="255" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editGroupTag">Group Tag</label>
                    <input type="text" id="editGroupTag" name="tag" maxlength="50" value="<?php echo htmlspecialchars($group['tag']); ?>">
                </div>
                <div class="form-group">
                    <label for="editGroupDescription">Description</label>
                    <textarea id="editGroupDescription" name="description" rows="3"><?php echo htmlspecialchars($group['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="editGroupFocus">Focus/Category</label>
                    <input type="text" id="editGroupFocus" name="focus" maxlength="100" value="<?php echo htmlspecialchars($group['focus']); ?>">
                </div>
                <div class="form-group">
                    <label for="editGroupPrivacy">Privacy</label>
                    <select id="editGroupPrivacy" name="privacy_status">
                        <option value="public" <?php if ($group['privacy_status'] === 'public') echo 'selected'; ?>>Public</option>
                        <option value="private" <?php if ($group['privacy_status'] === 'private') echo 'selected'; ?>>Private</option>
                        <option value="secret" <?php if ($group['privacy_status'] === 'secret') echo 'selected'; ?>>Secret</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editGroupRules">Group Rules</label>
                    <textarea id="editGroupRules" name="rules" rows="3"><?php echo htmlspecialchars($group['rules']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Cover Photo</label><br>
                    <label for="editGroupCover" class="image-upload-label"><i class="uil uil-image"></i> Choose Cover Photo</label>
                    <input type="file" id="editGroupCover" name="cover_image" accept="image/*">
                    <img id="coverPreview" class="image-preview" src="<?php
                        if (!empty($group['cover_image'])) {
                            $coverPath = htmlspecialchars($group['cover_image']);
                            echo '../../public/' . ltrim($coverPath, '/');
                        } else {
                            echo '';
                        }
                    ?>" alt="Cover Preview" <?php if (empty($group['cover_image'])) echo 'style="display:none;"'; ?> >
                </div>
                <div class="form-group">
                    <label>Display Picture</label><br>
                    <label for="editGroupDP" class="image-upload-label"><i class="uil uil-user"></i> Choose Display Picture</label>
                    <input type="file" id="editGroupDP" name="display_picture" accept="image/*">
                    <img id="dpPreview" class="image-preview" src="<?php
                        if (!empty($group['display_picture'])) {
                            $dpPath = htmlspecialchars($group['display_picture']);
                            echo '../../public/' . ltrim($dpPath, '/');
                        } else {
                            echo '';
                        }
                    ?>" alt="DP Preview" <?php if (empty($group['display_picture'])) echo 'style="display:none;"'; ?> >
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEditGroupBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteGroupModal" class="confirm-modal">
        <div class="modal-content">
            <h3>Delete Group?</h3>
            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($group['name']); ?></strong>? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

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

    <!-- Add this script to expose BASE_PATH to JS -->
    <script> const BASE_PATH = '<?php echo BASE_PATH; ?>'; </script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/calender.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/feed.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/friends.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/general.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/notificationpopup.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/navbar.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/post.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/comment.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/groupprofileview.js"></script>
    <script>
        const GROUP_ID = <?php echo $groupId; ?>;
        const IS_CREATOR = <?php echo $isCreator ? 'true' : 'false'; ?>;
        const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        
        // Debug: Log the loaded group info
        console.log('Group Profile View - GROUP_ID:', GROUP_ID);
        console.log('Group Profile View - URL:', window.location.href);
        console.log('Group Profile View - Search params:', window.location.search);
    </script>
</body>
</html>