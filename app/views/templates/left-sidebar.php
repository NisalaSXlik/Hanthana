<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/MediaHelper.php';
require_once __DIR__ . '/../../models/UserModel.php';

$userModel = new UserModel;
$currentUser = $userModel->findById($currentUser['user_id']);
$currentUserAvatar = MediaHelper::resolveMediaPath($currentUser['profile_picture'], 'uploads/user_dp/default.png');

$explicitSidebarKey = isset($activeSidebar) && is_string($activeSidebar) ? strtolower($activeSidebar) : null;

$currentController = strtolower($_GET['controller'] ?? '');
if ($currentController === '') {
    $scriptName = strtolower(pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME));
    if ($scriptName === 'index' || $scriptName === '' || $scriptName === 'myfeed') {
        $currentController = 'home';
    } else {
        $currentController = $scriptName;
    }
}

$menuActiveMap = [
    'feed' => ['home', 'feed'],
    'discover' => ['discover'],
    'events' => ['events'],
    'popular' => ['popular']
];

$resolvedMenuKey = $explicitSidebarKey;
if ($resolvedMenuKey === null) {
    foreach ($menuActiveMap as $key => $controllers) {
        if (in_array($currentController, $controllers, true)) {
            $resolvedMenuKey = $key;
            break;
        }
    }
}

if (!function_exists('menuActiveClass')) {
    function menuActiveClass(string $key, ?string $resolvedKey): string {
        return ($resolvedKey !== null && $key === $resolvedKey) ? ' active' : '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Popup with Media</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    
    <script> const BASE_PATH = '<?php echo BASE_PATH; ?>'; </script>
</head>
<body>
<div class="left">
    <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Profile&action=view<?php echo isset($_SESSION['user_id']) ? '&user_id=' . (int)$_SESSION['user_id'] : ''; ?>" class="profile-button">
        <div class="profile">
            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your profile picture">
            </div>
            <div class="handle">
                <h4><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></h4>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
            </div>
        </div>
    </a>
    <div class="side-bar">
        <button type="button" class="menu-item<?php echo menuActiveClass('feed', $resolvedMenuKey); ?>" data-target="feed" data-url="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Home&action=index" onclick="window.location.href=this.getAttribute('data-url')">
            <i class="uil uil-home"></i>
            <h3>My Feed</h3>
        </button>
        <button type="button" class="menu-item<?php echo menuActiveClass('discover', $resolvedMenuKey); ?>" data-target="discover" data-url="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Discover&action=index" onclick="window.location.href=this.getAttribute('data-url')">
            <i class="uil uil-compass"></i>
            <h3>Discover</h3>
        </button>
        <button type="button" class="menu-item<?php echo menuActiveClass('events', $resolvedMenuKey); ?>" data-target="events" data-url="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Events&action=index" onclick="window.location.href=this.getAttribute('data-url')">
            <i class="uil uil-calendar-alt"></i>
            <h3>Events</h3>
        </button>
        <button type="button" class="menu-item<?php echo menuActiveClass('popular', $resolvedMenuKey); ?>" data-target="popular" data-url="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Popular&action=index" onclick="window.location.href=this.getAttribute('data-url')">
            <i class="uil uil-fire"></i>
            <h3>Popular</h3>
        </button>
    </div>

    <?php
    require_once __DIR__ . '/../../models/GroupModel.php';
    $groupModel = new GroupModel();
    $userId = $_SESSION['user_id'] ?? null;
    $createdGroups = $userId ? $groupModel->getGroupsCreatedBy($userId) : [];
    $joinedGroups = $userId ? $groupModel->getGroupsJoinedBy($userId) : [];
    $createdGroupIds = array_column($createdGroups, 'group_id');
    $joinedOnlyGroups = array_values(array_filter($joinedGroups, function ($group) use ($createdGroupIds) {
        return isset($group['group_id']) && !in_array($group['group_id'], $createdGroupIds, true);
    }));
    $totalUserGroups = count($createdGroups) + count($joinedOnlyGroups);
    $currentGroupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
    ?>
    <div class="joined-groups">
        <div class="joined-groups-header">
            <h4>Groups</h4>
            <button class="btn-add-group" title="Create Group">
                <i class="uil uil-plus"></i>
            </button>
        </div>
        <div class="group-list">
            <?php if (!empty($createdGroups)) : ?>
                <div class="sidebar-subsection">
                    <strong style="font-size:13px;">Created by you</strong>
                    <?php foreach ($createdGroups as $sidebarGroup): ?>
                        <?php
                        $displayUrl = MediaHelper::resolveMediaPath($sidebarGroup['display_picture'] ?? '', 'uploads/group_dp/default.png');
                        ?>
                        <div class="group <?php echo ($currentGroupId === $sidebarGroup['group_id']) ? 'active' : ''; ?>" data-group-id="<?php echo $sidebarGroup['group_id']; ?>" onclick="window.location.href='<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Group&action=index&group_id=<?php echo $sidebarGroup['group_id']; ?>'">
                            <div class="group-icon">
                                <img src="<?php echo htmlspecialchars($displayUrl); ?>" alt="<?php echo htmlspecialchars($sidebarGroup['name']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            </div>
                            <div class="group-info">
                                <h5><?php echo htmlspecialchars($sidebarGroup['name']); ?></h5>
                                <p class="group-member-count"><?php echo htmlspecialchars($sidebarGroup['member_count'] ?? '0'); ?> members</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($joinedOnlyGroups)) : ?>
                <div class="sidebar-subsection">
                    <strong style="font-size:13px;">Joined Groups</strong>
                    <?php foreach ($joinedOnlyGroups as $sidebarGroup): ?>
                        <?php 
                        $displayUrl = MediaHelper::resolveMediaPath($sidebarGroup['display_picture'] ?? '', 'uploads/group_dp/default.png');
                        ?>
                        <div class="group <?php echo ($currentGroupId === $sidebarGroup['group_id']) ? 'active' : ''; ?>" data-group-id="<?php echo $sidebarGroup['group_id']; ?>" onclick="window.location.href='<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=Group&action=index&group_id=<?php echo $sidebarGroup['group_id']; ?>'">
                            <div class="group-icon">
                                <img src="<?php echo htmlspecialchars($displayUrl); ?>" alt="<?php echo htmlspecialchars($sidebarGroup['name']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            </div>
                            <div class="group-info">
                                <h5><?php echo htmlspecialchars($sidebarGroup['name']); ?></h5>
                                <p class="group-member-count"><?php echo htmlspecialchars($sidebarGroup['member_count'] ?? '0'); ?> members</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (empty($createdGroups) && empty($joinedGroups)) : ?>
                <p style="padding:10px 0 0 10px;">You haven't joined or created any groups yet.</p>
            <?php endif; ?>
        </div>
        <button class="btn btn-secondary" id="seeAllGroupsBtn">See All Groups</button>
    </div>
</div>

<?php
$baseGroupUrl = rtrim(BASE_PATH, '/');
?>
<div id="allGroupsModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-content groups-modal" role="dialog" aria-modal="true" aria-labelledby="allGroupsModalTitle">
        <div class="modal-header">
            <h3 id="allGroupsModalTitle">Your groups (<?php echo $totalUserGroups; ?>)</h3>
            <button type="button" class="modal-close" id="closeAllGroupsModal" aria-label="Close groups overview">
                <i class="uil uil-times"></i>
            </button>
        </div>
        <div class="groups-modal__body">
            <?php if ($totalUserGroups === 0): ?>
                <div class="groups-modal__empty">
                    <i class="uil uil-sad"></i>
                    <p>You haven't joined or created any groups yet.</p>
                    <small>Start by discovering new communities or creating your own.</small>
                </div>
            <?php else: ?>
                <?php if (!empty($createdGroups)): ?>
                    <section class="groups-modal__section">
                        <header>
                            <h4>Created by you</h4>
                            <p><?php echo count($createdGroups); ?> group<?php echo count($createdGroups) !== 1 ? 's' : ''; ?></p>
                        </header>
                        <ul class="groups-modal__list">
                            <?php foreach ($createdGroups as $group): ?>
                                <?php
                                    $groupUrl = $baseGroupUrl . '/index.php?controller=Group&action=index&group_id=' . (int)($group['group_id'] ?? 0);
                                    $avatar = MediaHelper::resolveMediaPath($group['display_picture'] ?? '', 'uploads/group_dp/default.png');
                                    $memberCount = (int)($group['member_count'] ?? 0);
                                ?>
                                <li class="groups-modal__item">
                                    <div class="groups-modal__details">
                                        <div class="groups-modal__avatar">
                                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($group['name'] ?? 'Group'); ?>">
                                        </div>
                                        <div class="groups-modal__info">
                                            <h5><?php echo htmlspecialchars($group['name'] ?? 'Untitled group'); ?></h5>
                                            <p class="groups-modal__meta">Created · <?php echo $memberCount; ?> member<?php echo $memberCount !== 1 ? 's' : ''; ?></p>
                                        </div>
                                    </div>
                                    <a class="groups-modal__link" href="<?php echo htmlspecialchars($groupUrl); ?>">Open</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if (!empty($joinedOnlyGroups)): ?>
                    <section class="groups-modal__section">
                        <header>
                            <h4>Joined groups</h4>
                            <p><?php echo count($joinedOnlyGroups); ?> group<?php echo count($joinedOnlyGroups) !== 1 ? 's' : ''; ?></p>
                        </header>
                        <ul class="groups-modal__list">
                            <?php foreach ($joinedOnlyGroups as $group): ?>
                                <?php
                                    $groupUrl = $baseGroupUrl . '/index.php?controller=Group&action=index&group_id=' . (int)($group['group_id'] ?? 0);
                                    $avatar = MediaHelper::resolveMediaPath($group['display_picture'] ?? '', 'uploads/group_dp/default.png');
                                    $memberCount = (int)($group['member_count'] ?? 0);
                                    $privacy = ucfirst($group['privacy_status'] ?? 'public');
                                ?>
                                <li class="groups-modal__item">
                                    <div class="groups-modal__details">
                                        <div class="groups-modal__avatar">
                                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($group['name'] ?? 'Group'); ?>">
                                        </div>
                                        <div class="groups-modal__info">
                                            <h5><?php echo htmlspecialchars($group['name'] ?? 'Untitled group'); ?></h5>
                                            <p class="groups-modal__meta">Joined · <?php echo $memberCount; ?> member<?php echo $memberCount !== 1 ? 's' : ''; ?> · <?php echo htmlspecialchars($privacy); ?></p>
                                        </div>
                                    </div>
                                    <a class="groups-modal__link" href="<?php echo htmlspecialchars($groupUrl); ?>">Open</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div id="createGroupModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Group</h3>
            <button class="modal-close" id="closeGroupModal">
                <i class="uil uil-times"></i>
            </button>
        </div>
        <form id="createGroupForm" class="modal-body">
            <div id="groupErrorMsg" style="display:none;color:#d32f2f;font-weight:bold;margin-bottom:10px;"></div>
            <div class="form-group">
                <label for="groupName">Group Name <span class="required">*</span></label>
                <input type="text" id="groupName" name="name" required maxlength="255" placeholder="Enter group name">
            </div>

            <div class="form-group">
                <label for="groupTag">Group Tag <span class="required">*</span></label>
                <input type="text" id="groupTag" name="tag" maxlength="50" placeholder="@unique-tag">
                <small>Must be unique (e.g., @colombo-foodies)</small>
            </div>

            <div class="form-group">
                <label for="groupDescription">Description</label>
                <textarea id="groupDescription" name="description" rows="3" placeholder="Describe what your group is about..."></textarea>
            </div>

            <div class="form-group">
                <label for="groupFocus">Focus/Category</label>
                <input type="text" id="groupFocus" name="focus" maxlength="100" placeholder="e.g., Photography, Food, Travel">
            </div>

            <div class="form-group">
                <label for="groupPrivacy">Privacy <span class="required">*</span></label>
                <select id="groupPrivacy" name="privacy_status" required>
                    <option value="public">Public - Anyone can see and join</option>
                    <option value="private">Private - Anyone can see, must request to join</option>
                    <option value="secret">Secret - Only members can see</option>
                </select>
            </div>

            <div class="form-group">
                <label for="groupRules">Group Rules (Optional)</label>
                <textarea id="groupRules" name="rules" rows="3" placeholder="Set guidelines for members..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelGroupBtn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Group</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

