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

    $reportsByTab = $reportsByTab ?? [
        'content' => [],
        'filebank' => [],
        'messages' => [],
        'channels' => [],
    ];
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
    <link rel="stylesheet" href="./css/group-right.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/admin.css">
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

                <div class="reports-container">
                    <div class="profile-tabs">
                        <ul>
                            <li class="active"><a href="#" data-tab="content">Content</a></li>
                            <li><a href="#" data-tab="filebank">File Bank</a></li>
                            <li><a href="#" data-tab="messages">Messages</a></li>
                            <li><a href="#" data-tab="channels">Channels</a></li>
                        </ul>
                    </div>

                    <div class="group-content moderation-content">
                        <div class="tab-content active" id="content-content">
                            <div class="mod-section-card">
                                <table class="mod-table moderation-queue-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Target</th>
                                            <th>Owner</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reportsByTab['content'])): ?>
                                            <?php foreach ($reportsByTab['content'] as $row): ?>
                                                <?php
                                                    $status = strtolower((string)($row['status'] ?? 'pending'));
                                                    $statusClass = $status === 'resolved' ? 'status-resolved' : ($status === 'reviewed' ? 'status-reviewed' : 'status-pending');
                                                    $detailPayload = htmlspecialchars(json_encode([
                                                        'report_id' => (int)($row['report_id'] ?? 0),
                                                        'target_type' => (string)($row['target_type'] ?? ''),
                                                        'target_label' => (string)($row['target_label'] ?? ''),
                                                        'target_url' => (string)($row['target_url'] ?? '#'),
                                                        'owner_label' => (string)($row['owner_label'] ?? 'N/A'),
                                                        'context_label' => (string)($row['context_label'] ?? 'System'),
                                                        'reason' => (string)($row['report_type'] ?? 'other'),
                                                        'status' => $status,
                                                        'target_id' => (int)($row['target_id'] ?? 0),
                                                        'group_id' => (int)($row['group_id'] ?? 0),
                                                        'reported_user_id' => (int)($row['reported_user_id'] ?? 0),
                                                        'report_type' => (string)($row['report_type'] ?? 'other'),
                                                        'action_taken' => (string)($row['action_taken'] ?? 'none'),
                                                        'reviewer_note' => (string)($row['reviewer_note'] ?? ''),
                                                        'description' => (string)($row['description'] ?? ''),
                                                        'reporter' => (string)($row['reporter_username'] ?? 'Unknown'),
                                                        'created_at' => (string)($row['created_at'] ?? ''),
                                                        'reviewed_by' => (string)($row['reviewed_by_username'] ?? ''),
                                                        'reviewed_at' => (string)($row['reviewed_at'] ?? '')
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$row['target_type']))); ?></td>
                                                    <td><a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars((string)$row['target_url']); ?>">View</a></td>
                                                    <td><?php echo htmlspecialchars((string)$row['owner_label']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst((string)$row['report_type'])); ?></td>
                                                    <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                                    <td><button class="btn btn-secondary btn-sm js-open-report-edit" data-report="<?php echo $detailPayload; ?>" type="button">Edit</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="muted">No content reports for this group yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-content" id="filebank-content">
                            <div class="mod-section-card">
                                <table class="mod-table moderation-queue-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Target</th>
                                            <th>Owner</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reportsByTab['filebank'])): ?>
                                            <?php foreach ($reportsByTab['filebank'] as $row): ?>
                                                <?php
                                                    $status = strtolower((string)($row['status'] ?? 'pending'));
                                                    $statusClass = $status === 'resolved' ? 'status-resolved' : ($status === 'reviewed' ? 'status-reviewed' : 'status-pending');
                                                    $detailPayload = htmlspecialchars(json_encode([
                                                        'report_id' => (int)($row['report_id'] ?? 0),
                                                        'target_type' => (string)($row['target_type'] ?? ''),
                                                        'target_label' => (string)($row['target_label'] ?? ''),
                                                        'target_url' => (string)($row['target_url'] ?? '#'),
                                                        'owner_label' => (string)($row['owner_label'] ?? 'N/A'),
                                                        'context_label' => (string)($row['context_label'] ?? 'System'),
                                                        'reason' => (string)($row['report_type'] ?? 'other'),
                                                        'status' => $status,
                                                        'target_id' => (int)($row['target_id'] ?? 0),
                                                        'group_id' => (int)($row['group_id'] ?? 0),
                                                        'reported_user_id' => (int)($row['reported_user_id'] ?? 0),
                                                        'report_type' => (string)($row['report_type'] ?? 'other'),
                                                        'action_taken' => (string)($row['action_taken'] ?? 'none'),
                                                        'reviewer_note' => (string)($row['reviewer_note'] ?? ''),
                                                        'description' => (string)($row['description'] ?? ''),
                                                        'reporter' => (string)($row['reporter_username'] ?? 'Unknown'),
                                                        'created_at' => (string)($row['created_at'] ?? ''),
                                                        'reviewed_by' => (string)($row['reviewed_by_username'] ?? ''),
                                                        'reviewed_at' => (string)($row['reviewed_at'] ?? '')
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$row['target_type']))); ?></td>
                                                    <td><a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars((string)$row['target_url']); ?>">View</a></td>
                                                    <td><?php echo htmlspecialchars((string)$row['owner_label']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst((string)$row['report_type'])); ?></td>
                                                    <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                                    <td><button class="btn btn-secondary btn-sm js-open-report-edit" data-report="<?php echo $detailPayload; ?>" type="button">Edit</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="muted">No file bank reports for this group yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-content" id="messages-content">
                            <div class="mod-section-card">
                                <table class="mod-table moderation-queue-table" style="margin-top: 1rem;">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Target</th>
                                            <th>Owner</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reportsByTab['messages'])): ?>
                                            <?php foreach ($reportsByTab['messages'] as $row): ?>
                                                <?php
                                                    $status = strtolower((string)($row['status'] ?? 'pending'));
                                                    $statusClass = $status === 'resolved' ? 'status-resolved' : ($status === 'reviewed' ? 'status-reviewed' : 'status-pending');
                                                    $detailPayload = htmlspecialchars(json_encode([
                                                        'report_id' => (int)($row['report_id'] ?? 0),
                                                        'target_type' => (string)($row['target_type'] ?? ''),
                                                        'target_label' => (string)($row['target_label'] ?? ''),
                                                        'target_url' => (string)($row['target_url'] ?? '#'),
                                                        'owner_label' => (string)($row['owner_label'] ?? 'N/A'),
                                                        'context_label' => (string)($row['context_label'] ?? 'System'),
                                                        'reason' => (string)($row['report_type'] ?? 'other'),
                                                        'status' => $status,
                                                        'target_id' => (int)($row['target_id'] ?? 0),
                                                        'group_id' => (int)($row['group_id'] ?? 0),
                                                        'reported_user_id' => (int)($row['reported_user_id'] ?? 0),
                                                        'report_type' => (string)($row['report_type'] ?? 'other'),
                                                        'action_taken' => (string)($row['action_taken'] ?? 'none'),
                                                        'reviewer_note' => (string)($row['reviewer_note'] ?? ''),
                                                        'description' => (string)($row['description'] ?? ''),
                                                        'reporter' => (string)($row['reporter_username'] ?? 'Unknown'),
                                                        'created_at' => (string)($row['created_at'] ?? ''),
                                                        'reviewed_by' => (string)($row['reviewed_by_username'] ?? ''),
                                                        'reviewed_at' => (string)($row['reviewed_at'] ?? '')
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$row['target_type']))); ?></td>
                                                    <td><a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars((string)$row['target_url']); ?>">View</a></td>
                                                    <td><?php echo htmlspecialchars((string)$row['owner_label']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst((string)$row['report_type'])); ?></td>
                                                    <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                                    <td><button class="btn btn-secondary btn-sm js-open-report-edit" data-report="<?php echo $detailPayload; ?>" type="button">Edit</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="muted">No message reports for this group yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-content" id="channels-content">
                            <div class="mod-section-card">
                                <table class="mod-table moderation-queue-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Target</th>
                                            <th>Owner</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reportsByTab['channels'])): ?>
                                            <?php foreach ($reportsByTab['channels'] as $row): ?>
                                                <?php
                                                    $status = strtolower((string)($row['status'] ?? 'pending'));
                                                    $statusClass = $status === 'resolved' ? 'status-resolved' : ($status === 'reviewed' ? 'status-reviewed' : 'status-pending');
                                                    $detailPayload = htmlspecialchars(json_encode([
                                                        'report_id' => (int)($row['report_id'] ?? 0),
                                                        'target_type' => (string)($row['target_type'] ?? ''),
                                                        'target_label' => (string)($row['target_label'] ?? ''),
                                                        'target_url' => (string)($row['target_url'] ?? '#'),
                                                        'owner_label' => (string)($row['owner_label'] ?? 'N/A'),
                                                        'context_label' => (string)($row['context_label'] ?? 'System'),
                                                        'reason' => (string)($row['report_type'] ?? 'other'),
                                                        'status' => $status,
                                                        'target_id' => (int)($row['target_id'] ?? 0),
                                                        'group_id' => (int)($row['group_id'] ?? 0),
                                                        'reported_user_id' => (int)($row['reported_user_id'] ?? 0),
                                                        'report_type' => (string)($row['report_type'] ?? 'other'),
                                                        'action_taken' => (string)($row['action_taken'] ?? 'none'),
                                                        'reviewer_note' => (string)($row['reviewer_note'] ?? ''),
                                                        'description' => (string)($row['description'] ?? ''),
                                                        'reporter' => (string)($row['reporter_username'] ?? 'Unknown'),
                                                        'created_at' => (string)($row['created_at'] ?? ''),
                                                        'reviewed_by' => (string)($row['reviewed_by_username'] ?? ''),
                                                        'reviewed_at' => (string)($row['reviewed_at'] ?? '')
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$row['target_type']))); ?></td>
                                                    <td><a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars((string)$row['target_url']); ?>">View</a></td>
                                                    <td><?php echo htmlspecialchars((string)$row['owner_label']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst((string)$row['report_type'])); ?></td>
                                                    <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                                    <td><button class="btn btn-secondary btn-sm js-open-report-edit" data-report="<?php echo $detailPayload; ?>" type="button">Edit</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="muted">No channel reports for this group yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <div id="groupReportEditModal" class="admin-overlay" aria-hidden="true">
        <div class="admin-overlay__dialog" role="dialog" aria-modal="true" aria-label="Edit report">
            <button type="button" class="admin-overlay__close" data-overlay-close="group-report" aria-label="Close edit form">
                <i class="uil uil-multiply"></i>
            </button>
            <header class="overlay-header">
                <div>
                    <p class="eyebrow">Moderation</p>
                    <h2>Edit Report</h2>
                    <p class="muted" id="reportEditTargetLabel">Target</p>
                </div>
            </header>
            <div class="complaint-tabpanel active" style="display:block;">
                <form id="groupReportEditForm" class="hf-form">
                    <input type="hidden" id="reportEditReportId" name="report_id" value="0">
                    <div class="report-detail-grid">
                        <div>
                            <strong>Target Type</strong>
                            <p class="muted" id="reportEditTargetType">-</p>
                        </div>
                        <div>
                            <strong>Report Type</strong>
                            <p class="muted" id="reportEditReportType">-</p>
                        </div>
                        <div>
                            <strong>Status</strong>
                            <select id="reportEditStatus" name="status">
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div>
                            <strong>Action Taken</strong>
                            <select id="reportEditActionTaken" name="action_taken">
                                <option value="none">None</option>
                                <option value="delete_content">Delete content</option>
                                <option value="warn_user">Warn user</option>
                                <option value="kick_user">Kick user</option>
                                <option value="remove_file">Remove file</option>
                                <option value="remove_folder">Remove folder</option>
                                <option value="delete_channel">Delete channel</option>
                                <option value="clear_channel">Clear channel</option>
                                <option value="false_positive">False positive</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:1rem;">
                        <strong>Description</strong>
                        <textarea id="reportEditDescription" name="description" rows="3" placeholder="Moderation description"></textarea>
                    </div>
                    <div style="margin-top:1rem;">
                        <strong>Reviewer Note</strong>
                        <textarea id="reportEditReviewerNote" name="reviewer_note" rows="3" placeholder="Add moderation notes"></textarea>
                    </div>
                    <div class="complaint-actions" style="margin-top:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <a class="link-btn" id="reportEditViewTarget" href="#" target="_blank" rel="noopener noreferrer">View target</a>
                        <button type="submit" class="link-btn">Save changes</button>
                    </div>
                </form>
                </div>
        </div>
    </div>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('groupReportEditModal');
            const closeEditBtn = editModal?.querySelector('[data-overlay-close="group-report"]');
            const editForm = document.getElementById('groupReportEditForm');

            const editTargetLabel = document.getElementById('reportEditTargetLabel');
            const editReportId = document.getElementById('reportEditReportId');
            const editTargetType = document.getElementById('reportEditTargetType');
            const editReportType = document.getElementById('reportEditReportType');
            const editStatus = document.getElementById('reportEditStatus');
            const editActionTaken = document.getElementById('reportEditActionTaken');
            const editDescription = document.getElementById('reportEditDescription');
            const editReviewerNote = document.getElementById('reportEditReviewerNote');
            const editViewTarget = document.getElementById('reportEditViewTarget');

            const closeDetail = () => {
                if (!editModal) return;
                editModal.classList.remove('open');
                document.body.classList.remove('modal-open');
            };

            const openDetail = () => {
                if (!editModal) return;
                editModal.classList.add('open');
                document.body.classList.add('modal-open');
            };

            const submitUpdate = async () => {
                const formData = new URLSearchParams();
                formData.append('report_id', String(editReportId.value || '0'));
                formData.append('status', String(editStatus.value || 'pending'));
                formData.append('action_taken', String(editActionTaken.value || 'none'));
                formData.append('description', String(editDescription.value || ''));
                formData.append('reviewer_note', String(editReviewerNote.value || ''));

                const response = await fetch(`${BASE_PATH}index.php?controller=GroupReports&action=updateReport&group_id=<?php echo (int)$groupId; ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString(),
                    credentials: 'same-origin'
                });

                const data = await response.json().catch(() => ({ success: false, message: 'Request failed' }));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to update report');
                }
            };

            document.querySelectorAll('.js-open-report-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const payload = JSON.parse(btn.getAttribute('data-report') || '{}');

                        editTargetLabel.textContent = payload.target_label || 'Target';
                        editReportId.value = String(payload.report_id || 0);
                        editTargetType.textContent = String(payload.target_type || '-');
                        editReportType.textContent = String(payload.report_type || payload.reason || 'other');
                        editStatus.value = String(payload.status || 'pending');
                        editActionTaken.value = String(payload.action_taken || 'none');
                        editDescription.value = String(payload.description || '');
                        editReviewerNote.value = String(payload.reviewer_note || '');
                        editViewTarget.href = payload.target_url || '#';

                        openDetail();
                    } catch (err) {
                        console.error('Invalid report payload', err);
                    }
                });
            });

            editForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!editReportId.value || editReportId.value === '0') {
                    return;
                }
                try {
                    await submitUpdate();
                    window.location.reload();
                } catch (error) {
                    alert(error.message);
                }
            });

            closeEditBtn?.addEventListener('click', closeDetail);
            editModal?.addEventListener('click', event => {
                if (event.target === editModal) {
                    closeDetail();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && editModal?.classList.contains('open')) {
                    closeDetail();
                }
            });
        });
    </script>
</body>
</html>