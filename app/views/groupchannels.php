<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/group-right.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/groupchannels.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle" style="width: 100%;">
                <?php
                    $channelGroupId = 0;
                    if (isset($groupId) && (int)$groupId > 0) {
                        $channelGroupId = (int)$groupId;
                    } elseif (isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
                        $channelGroupId = (int)$_GET['group_id'];
                    } elseif (isset($_SESSION['current_group_id']) && (int)$_SESSION['current_group_id'] > 0) {
                        $channelGroupId = (int)$_SESSION['current_group_id'];
                    }

                    if ($channelGroupId > 0) {
                        $_SESSION['current_group_id'] = $channelGroupId;
                    }

                    $channelGroupName = isset($group['name']) ? $group['name'] : 'this group';
                ?>

                <div class="channel-page-shell" data-group-id="<?php echo (int)$channelGroupId; ?>" data-group-name="<?php echo htmlspecialchars($channelGroupName, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="channel-header">
                        <div class="channel-header-top">
                            <div class="channel-title-block">
                                <h2><i class="uil uil-channel"></i> Channels</h2>
                                <p>Small chats inside <?php echo htmlspecialchars($channelGroupName); ?></p>
                            </div>
                            <div class="channel-header-actions">
                                <button class="btn btn-primary" id="openCreateChannelBtn" type="button">
                                    <i class="uil uil-plus"></i> Create Channel
                                </button>
                            </div>
                        </div>

                        <div class="channel-toolbar">
                            <div class="search-bar" style="max-width: 26rem; width: 100%;">
                                <i class="uil uil-search"></i>
                                <input type="search" id="channelSearchInput" placeholder="Search channels by name or description" autocomplete="off">
                            </div>

                            <div class="filter-tabs channel-filter-tabs" role="tablist" aria-label="Channel filters">
                                <button class="filter-tab channel-filter active" type="button" data-filter="all">
                                    <i class="uil uil-list-ul"></i> All
                                </button>
                                <button class="filter-tab channel-filter" type="button" data-filter="joined">
                                    <i class="uil uil-check-circle"></i> Joined
                                </button>
                                <button class="filter-tab channel-filter" type="button" data-filter="available">
                                    <i class="uil uil-user-plus"></i> Available
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="channelsContainer" class="channel-grid"></div>

                    <div id="createChannelModal" class="channel-modal-overlay" aria-hidden="true">
                        <div class="channel-modal-content" role="dialog" aria-modal="true" aria-labelledby="createChannelTitle">
                            <div class="channel-modal-header">
                                <h3 id="createChannelTitle"><i class="uil uil-plus-circle"></i> Create Channel</h3>
                                <button class="modal-close" id="closeChannelModalBtn" type="button" aria-label="Close">
                                    <i class="uil uil-times"></i>
                                </button>
                            </div>

                            <form id="createChannelForm" class="channel-modal-body">
                                <p class="channel-modal-note">This creates a small chat room inside the current group.</p>
                                <input type="hidden" name="group_id" id="channelGroupId" value="<?php echo (int)$channelGroupId; ?>">

                                <div class="channel-form-grid">
                                    <div class="form-group">
                                        <label for="channelName">Channel Name</label>
                                        <input type="text" id="channelName" maxlength="100" placeholder="e.g. Study Lounge" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="channelDescription">Description</label>
                                        <textarea id="channelDescription" rows="4" placeholder="A short description of what this chat is for"></textarea>
                                    </div>

                                    <div class="form-group channel-upload">
                                        <label>Display Picture</label>
                                        <label for="channelDpInput" class="channel-upload-label">
                                            <i class="uil uil-image"></i> Choose Image
                                        </label>
                                        <input type="file" id="channelDpInput" accept="image/*" style="display:none;">
                                        <div class="channel-preview" id="channelDpPreviewWrap">
                                            <img id="channelDpPreviewImg" alt="Channel preview">
                                            <div>
                                                <strong id="channelDpName">Preview</strong>
                                                <div style="color: var(--color-gray); font-size: 0.85rem;">Visible in the channel list</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="channel-modal-footer">
                                    <button type="button" class="btn btn-secondary" id="cancelChannelBtn">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create Channel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="toast-container" id="toastContainer"></div>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        window.CURRENT_GROUP_ID = <?php echo (int) $channelGroupId; ?>;
        window.CHANNEL_GROUP_ID = <?php echo (int) $channelGroupId; ?>;
        window.CHANNEL_CURRENT_USER_ID = <?php echo (int) ($currentUserId ?? ($_SESSION['user_id'] ?? 0)); ?>;
    </script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/report.js"></script>
    <script type="module" src="./js/groupchannels.js"></script>
</body>
</html>