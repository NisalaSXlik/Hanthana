<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$userModel = new UserModel();
$currentUser = $userModel->findById($currentUserId);

$resolvedGroupId = isset($groupId) ? (int)$groupId : 0;
if ($resolvedGroupId <= 0 && isset($group['group_id'])) {
    $resolvedGroupId = (int)$group['group_id'];
}

$groupId = $resolvedGroupId;
if ($groupId > 0) {
    $_SESSION['current_group_id'] = $groupId;
}

$groupCover = MediaHelper::resolveMediaPath((string)($group['cover_image'] ?? ''), 'uploads/group_cover/default.png');
$groupDp = MediaHelper::resolveMediaPath((string)($group['display_picture'] ?? ''), 'uploads/group_dp/default.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Settings - <?php echo htmlspecialchars($group['name'] ?? 'Group'); ?></title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/group-right.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/groupsettings.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="group-settings-page">
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="group-settings-header">
                    <h1><i class="uil uil-cog"></i> Group Settings</h1>
                    <p>Manage <?php echo htmlspecialchars($group['name'] ?? 'your group'); ?> details and moderation-ready configuration.</p>
                </div>

                <div class="gs-card">
                    <h3>Manage Group Details</h3>
                    <p>Update the same settings available from the group profile settings popup.</p>

                    <form id="groupSettingsForm" class="hf-form" enctype="multipart/form-data">
                        <input type="hidden" name="group_id" value="<?php echo (int)$groupId; ?>">

                        <div class="gs-section">
                            <div class="gs-form-grid">
                                <div class="form-group">
                                    <label for="groupName">Group Name</label>
                                    <input type="text" id="groupName" name="name" maxlength="255" value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="groupTag">Group Tag</label>
                                    <input type="text" id="groupTag" name="tag" maxlength="50" value="<?php echo htmlspecialchars($group['tag'] ?? ''); ?>">
                                </div>

                                <div class="form-group gs-full">
                                    <label for="groupDescription">Description</label>
                                    <textarea id="groupDescription" name="description" rows="3"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="groupFocus">Focus or Category</label>
                                    <input type="text" id="groupFocus" name="focus" maxlength="100" value="<?php echo htmlspecialchars($group['focus'] ?? ''); ?>">
                                </div>

                                <div class="form-group gs-full">
                                    <label for="groupRules">Group Rules</label>
                                    <textarea id="groupRules" name="rules" rows="3"><?php echo htmlspecialchars($group['rules'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="groupCoverInput">Cover Photo</label>
                                    <input type="file" id="groupCoverInput" name="cover_image" accept="image/*">
                                    <img id="groupCoverPreview" class="gs-preview" src="<?php echo htmlspecialchars($groupCover); ?>" alt="Group cover preview">
                                </div>

                                <div class="form-group">
                                    <label for="groupDpInput">Display Picture</label>
                                    <input type="file" id="groupDpInput" name="display_picture" accept="image/*">
                                    <img id="groupDpPreview" class="gs-preview dp" src="<?php echo htmlspecialchars($groupDp); ?>" alt="Group display preview">
                                </div>
                            </div>
                            
                            <div class="gs-submit-row">
                                <button type="submit" class="btn btn-primary">Save Group Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="gs-card">
                    <h3>Advanced Settings</h3>
                    <p>These changes require admin approval through governance votes.</p>

                    <section class="settings-card" id="privacySection">
                        <div class="card-heading">
                            <h4>Change Group Privacy</h4>
                            <p>Modify who can see and join this group. This starts a governance vote.</p>
                        </div>
                        <div class="settings-form">
                            <div class="settings-inline-info">Current visibility: <strong><?php echo ucfirst((string)($group['privacy_status'] ?? 'public')); ?></strong></div>
                            <button type="button" class="btn-primary" id="openPrivacyVoteModalBtn">Change Visibility</button>
                        </div>
                    </section>

                    <section class="settings-card settings-card-danger" id="deleteGroupSection">
                        <div class="card-heading">
                            <h4>Delete Group</h4>
                            <p>This action requires approval from all group admins through a governance vote.</p>
                        </div>
                        <div class="settings-form">
                            <div class="settings-inline-info settings-inline-danger">Deletion vote only. Group is removed only after governance approval.</div>
                            <button type="button" class="btn-danger" id="openDeleteVoteModalBtn">Start Delete Vote</button>
                        </div>
                    </section>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <div class="toast-container" id="toastContainer"></div>

    <div id="privacyVoteModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content gs-vote-modal" role="dialog" aria-modal="true" aria-labelledby="privacyVoteTitle">
            <div class="modal-header">
                <h3 id="privacyVoteTitle">Change Visibility</h3>
                <button class="modal-close" id="closePrivacyVoteModalBtn" type="button" aria-label="Close">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="privacyVoteForm" class="modal-body hf-form">
                <input type="hidden" name="group_id" value="<?php echo (int)$groupId; ?>">
                <div class="form-group">
                    <label for="groupPrivacy">New Visibility</label>
                    <select id="groupPrivacy" name="to_visibility" required>
                        <option value="public" <?php echo (($group['privacy_status'] ?? 'public') === 'public') ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?php echo (($group['privacy_status'] ?? 'public') === 'private') ? 'selected' : ''; ?>>Private</option>
                        <option value="secret" <?php echo (($group['privacy_status'] ?? 'public') === 'secret') ? 'selected' : ''; ?>>Secret</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="privacyReason">Reason for Change</label>
                    <textarea id="privacyReason" name="reason" rows="3" placeholder="Explain why this visibility change is needed..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelPrivacyVoteBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPrivacyVoteBtn">Start Vote</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteVoteModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content gs-vote-modal gs-vote-modal-danger" role="dialog" aria-modal="true" aria-labelledby="deleteVoteTitle">
            <div class="modal-header">
                <h3 id="deleteVoteTitle">Start Group Deletion Vote</h3>
                <button class="modal-close" id="closeDeleteVoteModalBtn" type="button" aria-label="Close">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="deleteVoteForm" class="modal-body hf-form">
                <input type="hidden" name="group_id" value="<?php echo (int)$groupId; ?>">
                <div class="form-group">
                    <label for="deleteReason">Reason for Deletion</label>
                    <textarea id="deleteReason" name="reason" rows="3" placeholder="Explain why this group should be deleted..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="deleteConfirmText">Confirm keyword</label>
                    <input type="text" id="deleteConfirmText" name="confirm_text" placeholder="Type DELETE" required>
                    <small>Type DELETE to confirm starting this vote.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelDeleteVoteBtn">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="submitDeleteVoteBtn">Start Delete Vote</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const GROUP_ID = <?php echo (int)$groupId; ?>;
        window.GROUP_ID = GROUP_ID;
        window.GROUP_PRIVACY_STATUS = '<?php echo addslashes(strtolower((string)($group['privacy_status'] ?? 'public'))); ?>';
    </script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/groupsettings.js"></script>
</body>
</html>
