<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$userModel = new UserModel;
$currentUser = $userModel->findById((int)$_SESSION['user_id']);

// Resolve/validate group ID (controller should have passed it, but ensure fallback)
$resolvedGroupId = isset($groupId) ? (int)$groupId : 0;
if ($resolvedGroupId <= 0 && isset($group['group_id'])) {
    $resolvedGroupId = (int)$group['group_id'];
}
if ($resolvedGroupId <= 0 && isset($_SESSION['current_group_id'])) {
    $resolvedGroupId = (int)$_SESSION['current_group_id'];
}
$groupId = $resolvedGroupId;
if ($groupId > 0) {
    $_SESSION['current_group_id'] = $groupId;
}

$voteEvents = isset($voteEvents) && is_array($voteEvents) ? $voteEvents : [];

$voteEventsJson = json_encode($voteEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Group - <?php echo htmlspecialchars($group['name'] ?? 'Group'); ?></title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/group-right.css">
    <link rel="stylesheet" href="./css/groupgovernance.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>

<body data-base-path="<?php echo htmlspecialchars((string)BASE_PATH, ENT_QUOTES, 'UTF-8'); ?>" data-group-id="<?php echo (int)$groupId; ?>">
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="governance-header">
                    <h2><i class="uil uil-sliders-v-alt"></i>Group Governance</h2>
                    <p>Review delete votes, role changes, and visibility decisions for <?php echo htmlspecialchars($group['name']); ?>.</p>
                </div>

                <div class="governance-shell">
                    <section class="governance-events-container">
                        <div class="governance-events-head">
                            <h4>Governance Vote Events</h4>
                            <div class="governance-events-filters">
                                <select id="eventTypeFilter" aria-label="Filter by event type">
                                    <option value="all">All event types</option>
                                    <option value="role">Member role change</option>
                                    <option value="delete">Group deletion</option>
                                    <option value="visibility">Visibility change</option>
                                </select>
                                <select id="eventTimeFilter" aria-label="Filter by time state">
                                    <option value="current" selected>Current</option>
                                    <option value="past">Past</option>
                                </select>
                            </div>
                        </div>

                        <div class="governance-events-list" id="governanceEventsList" data-vote-events="<?php echo htmlspecialchars($voteEventsJson ?: '[]', ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (empty($voteEvents)): ?>
                                <div class="governance-empty-inline">No governance vote events yet.</div>
                            <?php endif; ?>
                            <?php foreach ($voteEvents as $eventIndex => $event): ?>
                                <?php
                                $eventId = (int)($event['event_id'] ?? ($eventIndex + 1));
                                $typeKey = (string)($event['type_key'] ?? 'role');
                                $status = strtolower((string)($event['status'] ?? 'pending'));
                                $statusTone = in_array($status, ['approved', 'passed', 'accepted'], true) ? 'success' : (in_array($status, ['rejected', 'declined', 'expired'], true) ? 'danger' : 'warning');
                                $timeState = in_array($status, ['approved', 'passed', 'accepted', 'rejected', 'declined', 'expired'], true) ? 'past' : 'current';
                                $statusLabel = ucwords(str_replace('_', ' ', $status));
                                $isClosed = $timeState === 'past';
                                $inFavor = (int)($event['in_favor'] ?? 0);
                                $against = (int)($event['against'] ?? 0);
                                $adminCount = max(1, (int)($event['admin_count'] ?? 1));
                                $transitionLabel = '';

                                $eventTitle = (string)($event['type'] ?? 'Governance Vote');
                                if ($typeKey === 'role') {
                                    $fromRole = ucfirst(strtolower((string)($event['from_role'] ?? 'member')));
                                    $toRole = ucfirst(strtolower((string)($event['to_role'] ?? $event['requested_role'] ?? 'member')));
                                    $eventTitle = 'Member Role Change';
                                    $transitionLabel = $fromRole . ' -> ' . $toRole;
                                } elseif ($typeKey === 'visibility') {
                                    $eventTitle = 'Visibility Change';
                                    $fromVisibility = ucfirst(strtolower((string)($event['from_visibility'] ?? 'unknown')));
                                    $toVisibility = ucfirst(strtolower((string)($event['to_visibility'] ?? 'unknown')));
                                    $transitionLabel = $fromVisibility . ' -> ' . $toVisibility;
                                } elseif ($typeKey === 'delete') {
                                    $eventTitle = 'Group Deletion Proposal';
                                }

                                $starterUserId = (int)($event['started_by_user_id'] ?? 0);
                                $starterProfileHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $starterUserId;
                                $starterName = trim((string)($event['started_by_name'] ?? ''));
                                if ($starterName === '') {
                                    $starterName = (string)($event['started_by_username'] ?? 'Admin');
                                }
                                $starterUsername = (string)($event['started_by_username'] ?? 'admin');
                                $starterAvatar = MediaHelper::resolveMediaPath((string)($event['started_by_avatar'] ?? ''), 'uploads/user_dp/default.png');

                                $targetUserName = trim((string)($event['target_username'] ?? ''));
                                $targetUserId = (int)($event['target_user_id'] ?? 0);
                                $targetProfileHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $targetUserId;
                                $targetDisplayName = trim((string)($event['target_first_name'] ?? '') . ' ' . (string)($event['target_last_name'] ?? ''));
                                if ($targetDisplayName === '') {
                                    $targetDisplayName = ($targetUserName !== '') ? $targetUserName : 'Member';
                                }
                                $targetAvatar = MediaHelper::resolveMediaPath((string)($event['target_avatar'] ?? ''), 'uploads/user_dp/default.png');
                                $startedAtRaw = (string)($event['created_at'] ?? '');
                                $expiresAtRaw = (string)($event['expires_at'] ?? '');
                                $closedAtRaw = (string)($event['closed_at'] ?? '');
                                $startedAtLabel = $startedAtRaw !== '' ? date('Y-m-d H:i', strtotime($startedAtRaw)) : '--';
                                $closingAtLabel = $isClosed
                                    ? (($closedAtRaw !== '' ? date('Y-m-d H:i', strtotime($closedAtRaw)) : ($expiresAtRaw !== '' ? date('Y-m-d H:i', strtotime($expiresAtRaw)) : '--')))
                                    : ($expiresAtRaw !== '' ? date('Y-m-d H:i', strtotime($expiresAtRaw)) : '--');
                                $targetTag = $targetUserName !== '' ? $targetUserName : (($targetDisplayName !== '') ? $targetDisplayName : 'member');
                                ?>
                                <article class="governance-event-item governance-event-item" data-type="<?php echo htmlspecialchars($typeKey); ?>" data-time="<?php echo htmlspecialchars($timeState); ?>" data-event-id="<?php echo $eventId; ?>">
                                    <div class="governance-event-top">
                                        <span class="vote-type-pill governance-top-action vote-type-<?php echo htmlspecialchars((string)($event['tone'] ?? 'role')); ?>"><?php echo htmlspecialchars($eventTitle); ?></span>
                                        <?php if ($transitionLabel !== ''): ?>
                                            <?php
                                            $transitionParts = explode('->', $transitionLabel);
                                            $fromTransition = trim((string)($transitionParts[0] ?? 'From'));
                                            $toTransition = trim((string)($transitionParts[1] ?? 'To'));
                                            ?>
                                            <span class="governance-transition-pill governance-top-transition">
                                                <span><?php echo htmlspecialchars($fromTransition); ?></span>
                                                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                    <path d="M2 8h9M8 4l4 4-4 4" />
                                                </svg>
                                                <span><?php echo htmlspecialchars($toTransition); ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <span class="vote-status-chip governance-top-status vote-status-<?php echo htmlspecialchars($statusTone); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                    </div>

                                    <div class="governance-event-meta-grid">
                                        <div class="governance-user-stack governance-user-stack--fixed governance-user-row">
                                            <span class="governance-inline-label governance-inline-label--inline">Started by:</span>
                                            
                                            <?php if ($starterUserId > 0): ?>
                                                <a class="governance-user-chip" href="<?php echo htmlspecialchars($starterProfileHref); ?>" title="View starter profile">
                                                    <img src="<?php echo htmlspecialchars($starterAvatar); ?>" alt="<?php echo htmlspecialchars($starterName); ?>">
                                                    <span class="governance-user-chip-text">@<?php echo htmlspecialchars($starterUsername); ?></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="governance-user-chip governance-user-chip--plain">
                                                    <img src="<?php echo htmlspecialchars($starterAvatar); ?>" alt="<?php echo htmlspecialchars($starterName); ?>">
                                                    <span class="governance-user-chip-text">@<?php echo htmlspecialchars($starterUsername); ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($typeKey === 'role' && $targetUserId > 0): ?>
                                            <div class="governance-user-stack governance-user-stack--fixed governance-user-stack--target governance-user-row">
                                                <span class="governance-inline-label governance-inline-label--inline">Target User:</span>
                                                <a class="governance-user-chip" href="<?php echo htmlspecialchars($targetProfileHref); ?>" title="View target profile">
                                                    <img src="<?php echo htmlspecialchars($targetAvatar); ?>" alt="<?php echo htmlspecialchars($targetDisplayName); ?>">
                                                    <span class="governance-user-chip-text">@<?php echo htmlspecialchars($targetTag); ?></span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="governance-event-meta-grid">
                                        <div class="governance-user-stack governance-user-stack--fixed governance-user-row">
                                            <span class="governance-inline-label governance-inline-label--inline">Started at:</span>
                                            <span class="governance-inline-label governance-inline-label--inline"><?php echo htmlspecialchars($startedAtLabel); ?></span>
                                        </div>
                                        
                                        <div class="governance-user-stack governance-user-stack--fixed governance-user-stack--target governance-user-row">
                                            <span class="governance-inline-label governance-inline-label--inline"><?php echo $isClosed ? 'Closed at:' : 'Closing at:'; ?></span>
                                            <span class="governance-inline-label governance-inline-label--inline"><?php echo htmlspecialchars($closingAtLabel); ?></span>
                                        </div>
                                    </div>

                                    <div class="governance-reason-block">
                                        <span class="governance-inline-label">Reason</span>
                                        <p class="governance-event-reason"><?php echo htmlspecialchars((string)($event['reason'] ?? 'No reason provided.')); ?></p>
                                    </div>

                                    <div class="governance-vote-row">
                                        <label class="vote-item vote-item-up vote-choice-label" for="vote-in-favor-<?php echo (int)$eventIndex; ?>">
                                            <?php if (!$isClosed): ?>
                                                <input type="checkbox" id="vote-in-favor-<?php echo (int)$eventIndex; ?>" class="vote-choice-checkbox" data-vote-choice="in_favor" data-event-index="<?php echo (int)$eventIndex; ?>" data-event-id="<?php echo $eventId; ?>" <?php echo (($event['viewer_vote'] ?? '') === 'in_favor') ? 'checked' : ''; ?>>
                                            <?php else: ?>
                                                <span class="vote-checkbox-slot" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="vote-icon vote-line-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="currentColor" focusable="false">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 10h3v2h-3v3h-2v-3H7v-2h3V7h2v5z" />
                                                </svg>
                                            </span>
                                            <span class="vote-item-text">In favor</span>
                                            <strong class="vote-count vote-count-up" data-event-id="<?php echo $eventId; ?>" data-vote-choice="in_favor"><?php echo $inFavor; ?></strong>
                                        </label>

                                        <label class="vote-item vote-item-down vote-choice-label" for="vote-not-favor-<?php echo (int)$eventIndex; ?>">
                                            <?php if (!$isClosed): ?>
                                                <input type="checkbox" id="vote-not-favor-<?php echo (int)$eventIndex; ?>" class="vote-choice-checkbox" data-vote-choice="not_in_favor" data-event-index="<?php echo (int)$eventIndex; ?>" data-event-id="<?php echo $eventId; ?>" <?php echo (($event['viewer_vote'] ?? '') === 'not_in_favor') ? 'checked' : ''; ?>>
                                            <?php else: ?>
                                                <span class="vote-checkbox-slot" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="vote-icon vote-line-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="currentColor" focusable="false">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 10H7v-2h3V7h2v5h3v2h-3v3h-2v-3z" />
                                                </svg>
                                            </span>
                                            <span class="vote-item-text">Not in favor</span>
                                            <strong class="vote-count vote-count-down" data-event-id="<?php echo $eventId; ?>" data-vote-choice="not_in_favor"><?php echo $against; ?></strong>
                                        </label>

                                        <button class="see-votes-pill see-votes-popup-trigger" type="button" data-event-index="<?php echo (int)$eventIndex; ?>" title="See votes">
                                            <span class="see-votes-icon"><i class="uil uil-info-circle"></i></span>
                                            <span>See votes</span>
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>

            <div id="seeVotesModal" class="governance-modal-overlay" aria-hidden="true">
                <div class="governance-modal-content governance-votes-modal" role="dialog" aria-modal="true" aria-labelledby="seeVotesTitle">
                    <div class="governance-modal-header">
                        <h3 id="seeVotesTitle"><i class="uil uil-users-alt"></i> Vote Details</h3>
                        <button class="modal-close" type="button" id="closeSeeVotesModal" aria-label="Close vote details popup">
                            <i class="uil uil-times"></i>
                        </button>
                    </div>
                    <div class="governance-modal-body">
                        <div class="votes-popup-columns">
                            <section class="votes-popup-column votes-popup-column-up">
                                <div class="votes-popup-column-head">
                                    <span>In Favor</span>
                                </div>
                                <ul class="votes-popup-list" id="votesPopupInFavorList"></ul>
                            </section>
                            <section class="votes-popup-column votes-popup-column-down">
                                <div class="votes-popup-column-head">
                                    <span>Not in Favor</span>
                                </div>
                                <ul class="votes-popup-list" id="votesPopupAgainstList"></ul>
                            </section>
                            <section class="votes-popup-column votes-popup-column-pending">
                                <div class="votes-popup-column-head">
                                    <span id="votesPopupPendingTitle">Didn't Vote</span>
                                </div>
                                <ul class="votes-popup-list" id="votesPopupPendingList"></ul>
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>

        <?php include __DIR__ . '/templates/chat-clean.php'; ?>
        <?php include __DIR__ . '/templates/report-modal.php'; ?>
    </main>

    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/report.js"></script>
    
    <script src="./js/groupgovernance.js"></script>
    <script src="./js/groupprofileview.js"></script>
</body>

</html>