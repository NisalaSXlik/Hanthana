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

<body>
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
                                    <option value="all">All</option>
                                    <option value="current">Latest</option>
                                    <option value="past">Past</option>
                                </select>
                            </div>
                        </div>

                        <div class="governance-events-list" id="governanceEventsList">
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
                                $inFavor = (int)($event['in_favor'] ?? 0);
                                $against = (int)($event['against'] ?? 0);
                                $totalVotes = max(1, $inFavor + $against);
                                $inFavorPercent = (int)round(($inFavor / $totalVotes) * 100);
                                $againstPercent = 100 - $inFavorPercent;
                                ?>
                                <article class="governance-event-item" data-type="<?php echo htmlspecialchars($typeKey); ?>" data-time="<?php echo htmlspecialchars($timeState); ?>" data-event-id="<?php echo $eventId; ?>">
                                    <div class="governance-event-top">
                                        <span class="vote-type-pill vote-type-<?php echo htmlspecialchars((string)($event['tone'] ?? 'role')); ?>"><?php echo htmlspecialchars((string)($event['type'] ?? 'Vote Event')); ?></span>
                                        <span class="vote-status-chip vote-status-<?php echo htmlspecialchars($statusTone); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                    </div>

                                    <p class="governance-event-reason"><?php echo htmlspecialchars((string)($event['reason'] ?? 'No reason provided.')); ?></p>

                                    <div class="governance-event-meta-lines">
                                        <span><strong>Started by:</strong> <?php echo htmlspecialchars((string)($event['started_by_name'] ?? $event['started_by_username'] ?? 'Admin')); ?></span>
                                        <span><strong>Target:</strong> <?php echo htmlspecialchars((string)($event['target_type'] ?? 'group')); ?> #<?php echo (int)($event['target_id'] ?? $group['group_id']); ?></span>
                                        <?php if (!empty($event['from_role']) || !empty($event['to_role'])): ?>
                                            <span><strong>Role:</strong> <?php echo htmlspecialchars((string)($event['from_role'] ?? 'member')); ?> to <?php echo htmlspecialchars((string)($event['to_role'] ?? 'member')); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($event['from_visibility']) || !empty($event['to_visibility'])): ?>
                                            <span><strong>Visibility:</strong> <?php echo htmlspecialchars((string)($event['from_visibility'] ?? 'unknown')); ?> to <?php echo htmlspecialchars((string)($event['to_visibility'] ?? 'unknown')); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="governance-vote-meter-row">
                                        <div class="governance-vote-meter-stack">
                                            <div class="governance-vote-line governance-vote-line-up">
                                                <label class="vote-choice-label" for="vote-in-favor-<?php echo (int)$eventIndex; ?>">
                                                    <input type="checkbox" id="vote-in-favor-<?php echo (int)$eventIndex; ?>" class="vote-choice-checkbox" data-vote-choice="in_favor" data-event-index="<?php echo (int)$eventIndex; ?>" data-event-id="<?php echo $eventId; ?>" <?php echo (($event['viewer_vote'] ?? '') === 'in_favor') ? 'checked' : ''; ?>>
                                                    <span>In favor</span>
                                                </label>
                                                <div class="governance-progress-track">
                                                    <div class="governance-progress-fill governance-progress-fill-up" style="width: <?php echo $inFavorPercent; ?>%;"></div>
                                                </div>
                                                <strong class="vote-line-count vote-line-count-up"><?php echo $inFavor; ?></strong>
                                            </div>
                                            <div class="governance-vote-line governance-vote-line-down">
                                                <label class="vote-choice-label" for="vote-not-favor-<?php echo (int)$eventIndex; ?>">
                                                    <input type="checkbox" id="vote-not-favor-<?php echo (int)$eventIndex; ?>" class="vote-choice-checkbox" data-vote-choice="not_in_favor" data-event-index="<?php echo (int)$eventIndex; ?>" data-event-id="<?php echo $eventId; ?>" <?php echo (($event['viewer_vote'] ?? '') === 'not_in_favor') ? 'checked' : ''; ?>>
                                                    <span>Not in favor</span>
                                                </label>
                                                <div class="governance-progress-track">
                                                    <div class="governance-progress-fill governance-progress-fill-down" style="width: <?php echo $againstPercent; ?>%;"></div>
                                                </div>
                                                <strong class="vote-line-count vote-line-count-down"><?php echo $against; ?></strong>
                                            </div>
                                        </div>

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
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>

        <?php include __DIR__ . '/templates/chat-clean.php'; ?>
        <?php include __DIR__ . '/templates/report-modal.php'; ?>
    </main>

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const GROUP_ID = <?php echo (int)$group['group_id']; ?>;
        window.GOVERNANCE_VOTE_EVENTS = <?php echo $voteEventsJson ?: '[]'; ?>;
    </script>
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