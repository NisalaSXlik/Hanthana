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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Moderation - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/groupmoderation.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="group-reports-page">
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="reports-header">
                    <div class="reports-header-top">
                        <div class="reports-title-block">
                            <h2><i class="uil uil-shield-check"></i> Group Moderation</h2>
                            <p>Review reported content, bin media, and communication spaces in one place.</p>
                        </div>
                    </div>
                </div>

                <div class="profile-tabs">
                    <ul>
                        <li class="active">
                            <a href="#" data-tab="content">Content</a>
                        </li>
                        <li>
                            <a href="#" data-tab="bin">Bin</a>
                        </li>
                        <li>
                            <a href="#" data-tab="messages">Messages</a>
                        </li>
                        <li>
                            <a href="#" data-tab="channels">Channels</a>
                        </li>
                    </ul>
                </div>

                <div class="group-content moderation-content">
                    <div class="tab-content active" id="content-content">
                        <div class="mod-section-card">
                            <h3>Content Report Queue</h3>
                            <div class="content-type-tags">
                                <span class="chip chip-warn">Question</span>
                                <span class="chip chip-warn">Social</span>
                                <span class="chip chip-warn">Event</span>
                                <span class="chip chip-warn">Poll</span>
                                <span class="chip chip-warn">Assignment</span>
                            </div>
                            <table class="mod-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Member Name</th>
                                        <th>Post View</th>
                                        <th>Complaint</th>
                                        <th>Moderation Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Question</td>
                                        <td>Nethmi Perera</td>
                                        <td><a class="btn btn-secondary btn-sm" href="#">View Post</a></td>
                                        <td>Academic misconduct / leaked material request</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" type="button">Remove Content</button>
                                            <button class="btn btn-secondary btn-sm" type="button">Mark as Valid</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Assignment</td>
                                        <td>Kavindu Jay</td>
                                        <td><a class="btn btn-secondary btn-sm" href="#">View Post</a></td>
                                        <td>Unauthorized answer key sharing</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" type="button">Restrict Post</button>
                                            <button class="btn btn-secondary btn-sm" type="button">Dismiss Report</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="bin-content">
                        <div class="mod-section-card">
                            <h3>Bin & Bin Media Reports</h3>
                            <table class="mod-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Member Name</th>
                                        <th>Visibility</th>
                                        <th>Complaint</th>
                                        <th>Moderation Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Bin</td>
                                        <td>Malinda S</td>
                                        <td><span class="chip chip-ok">Public</span></td>
                                        <td>Inappropriate bin title / abuse words</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" type="button">Archive Bin</button>
                                            <button class="btn btn-secondary btn-sm" type="button">Dismiss Report</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bin Media</td>
                                        <td>Isuri Kavya</td>
                                        <td><span class="chip chip-warn">Members Only</span></td>
                                        <td>Copyrighted lecture pack upload</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" type="button">Remove Media</button>
                                            <button class="btn btn-secondary btn-sm" type="button">Mark as Valid</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="messages-content">
                        <div class="mod-section-card">
                            <h3>Conversation Reports</h3>
                            <div class="channel-list message-thread-list">
                                <div class="channel-item">
                                    <div>
                                        <strong>Conversation: #first-year-help</strong>
                                        <p>Click to view reported messages in this conversation.</p>
                                    </div>
                                    <button class="btn btn-secondary btn-sm" type="button">Open Thread</button>
                                </div>
                                <div class="channel-item">
                                    <div>
                                        <strong>Conversation: #internships</strong>
                                        <p>Reported for abusive replies and suspicious links.</p>
                                    </div>
                                    <button class="btn btn-secondary btn-sm" type="button">Open Thread</button>
                                </div>
                            </div>

                            <table class="mod-table" style="margin-top: 1rem;">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Message Complaint</th>
                                        <th>Moderation Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Rashmi D</td>
                                        <td>Personal attack / hate expression</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" type="button">Delete Message</button>
                                            <button class="btn btn-danger btn-sm" type="button">Remove Member</button>
                                            <button class="btn btn-secondary btn-sm" type="button">Dismiss Report</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="channels-content">
                        <div class="mod-section-card">
                            <h3>Channels Moderation</h3>
                            <table class="mod-table">
                                <thead><tr><th>Channel</th><th>Total Reports</th><th>Complaint Trend</th><th>Action</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td>#hostel-talk</td>
                                        <td>18</td>
                                        <td>Repeated abuse and spam</td>
                                        <td><button class="btn btn-danger btn-sm" type="button">Delete Channel</button></td>
                                    </tr>
                                    <tr>
                                        <td>#project-marketplace</td>
                                        <td>6</td>
                                        <td>Scam-like post complaints</td>
                                        <td><button class="btn btn-danger btn-sm" type="button">Delete Channel</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            

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

    <!-- ════════════════════════════════════════════════════════════════
         POST VIEW MODAL — file detail + comment thread
    ════════════════════════════════════════════════════════════════════ -->
    <div id="postViewModal" class="post-view-modal" aria-hidden="true">
        <div class="post-view-overlay"></div>
        <button class="post-view-close" aria-label="Close"><i class="uil uil-times"></i></button>
        <div class="post-view-content">
            <!-- Header: uploader info -->
            <div class="post-view-header">
                <div class="user">
                    <div class="profile-picture">
                        <img src="" alt="Profile" id="postViewAvatar">
                    </div>
                    <div class="info">
                        <h3 id="postViewUsername">Username</h3>
                        <small id="postViewDate"></small>
                    </div>
                </div>
                <div class="post-menu" id="postViewFileMenuWrap">
                    <button type="button" class="menu-trigger" id="postViewFileMenuTrigger" aria-label="File options">
                        <i class="uil uil-ellipsis-h"></i>
                    </button>
                    <div class="menu" id="postViewFileMenu">
                        <button type="button" class="menu-item" id="postViewRenameBtn">
                            <i class="uil uil-pen"></i> Rename
                        </button>
                        <button type="button" class="menu-item" id="postViewDeleteBtn">
                            <i class="uil uil-trash-alt"></i> Delete
                        </button>
                        <button type="button" class="menu-item" id="postViewReportBtn" data-report-type="group" data-target-id="0" data-target-label="file in file bank">
                            <i class="uil uil-exclamation-triangle"></i> Report
                        </button>
                    </div>
                </div>
            </div>
            <!-- File info row -->
            <div class="post-view-file-info">
                <i class="uil uil-file-blank fb-file-icon icon-pdf" id="postViewFileIcon"></i>
                <div>
                    <div class="file-detail-name" id="postViewFileName">File Name</div>
                    <div class="file-detail-meta" id="postViewFileMeta"></div>
                </div>
                <button class="btn btn-primary" id="postViewDownloadBtn">
                    <i class="uil uil-download-alt"></i> Download
                </button>
            </div>
            <!-- Comments thread -->
            <div class="post-view-comments">
                <div class="comments-header">
                    <h4>Questions &amp; Comments</h4>
                    <span id="postViewCommentBadge">0</span>
                </div>
                <div class="comments-list" id="postViewCommentsList">
                    <div class="comments-loading">Loading comments...</div>
                </div>
                <form id="postViewCommentForm" class="comment-form">
                    <textarea id="postViewCommentInput" placeholder="Ask a question or leave a comment..." rows="2"></textarea>
                    <button type="submit" class="btn btn-primary" id="postViewCommentSubmit">Post</button>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        window.FILEBANK_GROUP_ID = <?php echo (int) $groupId; ?>;
        window.CURRENT_GROUP_ID = <?php echo (int) $groupId; ?>;
        window.FILEBANK_CURRENT_USER_ID = <?php echo (int) ($currentUserId ?? ($_SESSION['user_id'] ?? 0)); ?>;
        window.FILEBANK_CAN_MODERATE = <?php echo !empty($canModerateFileBank) ? 'true' : 'false'; ?>;
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
    <script src="./js/groupprofileview.js"></script>
    <script src="./js/group-post-create.js"></script>
    <script src="./js/group-poll-voting.js"></script>
    <script src="./js/group-post-interactions.js"></script>
</body>
</html>