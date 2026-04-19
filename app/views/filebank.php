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

$resolvedGroupId = isset($groupId) ? (int)$groupId : 0;
if ($resolvedGroupId <= 0 && isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
    $resolvedGroupId = (int)$_GET['group_id'];
} elseif ($resolvedGroupId <= 0 && isset($_GET['groupId']) && (int)$_GET['groupId'] > 0) {
    $resolvedGroupId = (int)$_GET['groupId'];
} elseif ($resolvedGroupId <= 0 && isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $resolvedGroupId = (int)$_GET['id'];
} elseif ($resolvedGroupId <= 0 && isset($_SESSION['current_group_id']) && (int)$_SESSION['current_group_id'] > 0) {
    $resolvedGroupId = (int)$_SESSION['current_group_id'];
}

$groupId = $resolvedGroupId;
if ($groupId > 0) {
    $_SESSION['current_group_id'] = $groupId;
}

$fileBankGroupName = trim((string)($group['name'] ?? 'Group'));
if ($fileBankGroupName === '') {
    $fileBankGroupName = 'Group';
}

$fileBankGroupDp = MediaHelper::resolveMediaPath(
    (string)($group['display_picture'] ?? ''),
    'images/default_group.png'
);

$isAdmin = isset($isAdmin) ? (bool)$isAdmin : false;
$isJoined = isset($isJoined) ? (bool)$isJoined : false;
$isPublicGroup = strtolower(trim((string)($group['privacy_status'] ?? 'public'))) === 'public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Bank - Hanthane</title>
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
    <link rel="stylesheet" href="./css/filebank.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <!-- ══════════════════════════════════════════════════════
                 MIDDLE — FILE BANK
            ══════════════════════════════════════════════════════════ -->
            <div class="middle">
                <div class="filebank-shell"
                    data-is-admin="<?php echo $isAdmin ? '1' : '0'; ?>"
                    data-is-joined="<?php echo $isJoined ? '1' : '0'; ?>"
                    data-is-public-group="<?php echo $isPublicGroup ? '1' : '0'; ?>">

                    <!-- Title bar -->
                    <div class="fb-titlebar">
                        <div class="fb-titlebar-left">
                            <img class="fb-group-dp" src="<?php echo htmlspecialchars($fileBankGroupDp, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($fileBankGroupName, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="uil uil-folder-open"></i>
                            <span><?php echo htmlspecialchars($fileBankGroupName, ENT_QUOTES, 'UTF-8'); ?>'s File Bank</span>
                        </div>
                    </div>

                    <!-- Address / breadcrumb + search -->
                    <div class="fb-addressbar">
                        <div class="fb-breadcrumb" id="fbBreadcrumb">
                            <i class="uil uil-folder"></i>
                            <span class="fb-breadcrumb-part active" data-path="root">File Bank</span>
                        </div>
                        <button class="btn btn-primary" id="addBinBtn">
                            <i class="uil uil-folder-plus"></i> New Bin
                        </button>
                        <div class="fb-search-box">
                            <i class="uil uil-search"></i>
                            <input type="search" id="fileSearchInput" placeholder="Search File Bank" autocomplete="off">
                        </div>
                    </div>

                    <!-- Bin list -->
                    <div class="fb-body" id="fileBankBody">

                        <div class="fb-bin-nav-row" id="fbBinNavRow">
                            <button type="button" class="fb-back-btn" id="fbBackBtn">
                                <i class="uil uil-arrow-left"></i>
                                <span>Back</span>
                            </button>
                            <span class="fb-current-bin" id="fbCurrentBinLabel"></span>
                        </div>
                        <div id="fbDataContainer"></div>

                    </div><!-- /fb-body -->

                    <!-- Status bar -->
                    <div class="fb-statusbar">
                        <span id="fb-status-text">4 bins</span>
                    </div>

                </div><!-- /filebank-shell -->
            </div><!-- /middle -->

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <!-- ════════════════════════════════════════════════════════════════
         MODAL: Create / Edit Bin  (shared for both new & edit)
    ════════════════════════════════════════════════════════════════════ -->
    <div id="createBinModal" class="modal-overlay">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header">
                <h3 id="binModalTitle">Create New Bin</h3>
                <button class="modal-close" id="closeBinModal"><i class="uil uil-times"></i></button>
            </div>
            <form id="createBinForm" class="modal-body">
                <input type="hidden" id="binEditId" value="">
                <input type="hidden" id="binGroupId" name="group_id" value="<?php echo $groupId > 0 ? $groupId : ''; ?>">
                <div id="binErrorMsg" style="display:none;color:var(--color-danger);font-weight:600;margin-bottom:0.75rem;font-size:0.88rem;"></div>
                <div class="form-group">
                    <label for="binName">Bin Name <span class="required">*</span></label>
                    <input type="text" id="binName" name="name" required maxlength="255" placeholder="Enter bin name">
                </div>
                <div class="form-group">
                    <label for="binDescription">Description</label>
                    <textarea id="binDescription" name="description" rows="3" placeholder="Describe this bin..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelBinBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBinBtn">Create Bin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════
         MODAL: Add / Edit File
    ════════════════════════════════════════════════════════════════════ -->
    <div id="fileModal" class="modal-overlay">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h3 id="fileModalTitle">Add File</h3>
                <button class="modal-close" id="closeFileModal"><i class="uil uil-times"></i></button>
            </div>
            <form id="fileForm" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" id="fileEditId" value="">
                <input type="hidden" id="fileBinId" value="">
                <div class="form-group">
                    <label for="fileName">File Name <span class="required">*</span></label>
                    <input type="text" id="fileName" name="name" placeholder="e.g., Project Proposal" required>
                </div>
                <div class="form-group">
                    <label for="fileUpload">Upload File</label>
                    <input type="file" id="fileUpload" name="file_data" accept=".pdf,.doc,.docx,.txt,.jpg,.png,.zip,.xlsx">
                    <small id="existingFileInfo" style="display:none;color:var(--color-gray);">Leave empty to keep existing file</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelFileBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitFileBtn">Save File</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════
         MODAL: Delete Confirmation (shared for bins & files)
    ════════════════════════════════════════════════════════════════════ -->
    <div id="deleteConfirmModal" class="confirm-modal">
        <div class="modal-content" style="max-width:400px;padding:2rem;">
            <h3 style="color:var(--color-danger);margin-bottom:0.75rem;font-size:1.1rem;">Confirm Delete</h3>
            <p id="deleteConfirmText" style="margin-bottom:1.5rem;color:var(--color-gray);font-size:0.9rem;line-height:1.6;">
                Are you sure you want to delete this item? This action cannot be undone.
            </p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        window.IS_ADMIN = <?php echo json_encode($isAdmin); ?>;
        window.IS_JOINED = <?php echo json_encode($isJoined); ?>;
        window.IS_PUBLIC_GROUP = <?php echo json_encode($isPublicGroup); ?>;
        window.FILEBANK_GROUP_ID = <?php echo (int) $groupId; ?>;
        window.CURRENT_GROUP_ID = <?php echo (int) $groupId; ?>;
        window.FILEBANK_CURRENT_USER_ID = <?php echo (int) ($currentUserId ?? ($_SESSION['user_id'] ?? 0)); ?>;
    </script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/groupprofileview.js"></script>
    <script src="./js/group-post-create.js"></script>
    <script src="./js/group-poll-voting.js"></script>
    <script src="./js/group-post-interactions.js"></script>
    <script type="module" src="./js/filebank.js"></script>
</body>
</html>