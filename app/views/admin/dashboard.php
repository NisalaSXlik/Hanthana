<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/MediaHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? 'user') !== 'admin')) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit;
}
$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById($_SESSION['user_id']);

$adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: ($_SESSION['username'] ?? 'Admin');
$profilePicture = MediaHelper::resolveMediaPath($_SESSION['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');

$number = fn($value) => number_format((int)($value ?? 0));
$percentOf = function($count, $total) {
    $total = max(1, (int)$total);
    return round(((int)$count / $total) * 100);
};
$hidePostModal = true;

$dailyActiveUsers = $dailyActiveUsers ?? ['labels' => [], 'counts' => [], 'latest_count' => 0];
$complaintStats = $complaintStats ?? [
    'total_reports' => 0,
    'pending_reports' => 0,
    'resolved_reports' => 0,
    'reviewed_reports' => 0,
    'recent_reports' => 0,
    'type_breakdown' => [],
    'trend' => ['labels' => [], 'counts' => []]
];
$trendingGroups = $trendingGroups ?? [];
$recentComplaints = $recentComplaints ?? [];
$complaintsByStatus = $complaintsByStatus ?? [
    'received' => [],
    'pending' => [],
    'resolved' => []
];
$complaintTypeLabels = array_map(fn($row) => $row['label'] ?? 'Other', $complaintStats['type_breakdown']);
$complaintTypeCounts = array_map(fn($row) => (int)($row['count'] ?? 0), $complaintStats['type_breakdown']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel · Hanthana</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/admin.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
</head>
<body class="admin-page">
<?php include __DIR__ . '/../templates/navbar.php'; ?>

<main class="admin-container">
    <header class="admin-header">
        <div class="admin-heading">
            <p class="eyebrow">System overview</p>
            <h1>Welcome back, <?php echo htmlspecialchars($adminName); ?> 👋</h1>
            <p class="subtext">Monitor community health, track growth, and keep Hanthana running smoothly.</p>
        </div>
        <div class="admin-profile-chip">
            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Admin avatar">
            <div>
                <span><?php echo htmlspecialchars($adminName); ?></span>
                <small>System Admin</small>
            </div>
        </div>
    </header>

    <section class="admin-grid">
        <article class="admin-card">
            <div class="card-label">
                <span class="icon pill users"><i class="uil uil-users-alt"></i></span>
                <span>Users</span>
            </div>
            <h2><?php echo $number($userStats['total_users'] ?? 0); ?></h2>
            <p class="muted">Active: <?php echo $number($userStats['active_users'] ?? 0); ?> · New (7d): <?php echo $number($userStats['new_users_last_7'] ?? 0); ?></p>
        </article>
        <article class="admin-card">
            <div class="card-label">
                <span class="icon pill posts"><i class="uil uil-file-alt"></i></span>
                <span>Posts</span>
            </div>
            <h2><?php echo $number($postStats['total_posts'] ?? 0); ?></h2>
            <p class="muted">Group: <?php echo $number($postStats['group_posts'] ?? 0); ?> · Events: <?php echo $number($postStats['event_posts'] ?? 0); ?></p>
        </article>
        <article class="admin-card">
            <div class="card-label">
                <span class="icon pill groups"><i class="uil uil-layer-group"></i></span>
                <span>Groups</span>
            </div>
            <h2><?php echo $number($groupStats['total_groups'] ?? 0); ?></h2>
            <p class="muted">Public: <?php echo $number($groupStats['public_groups'] ?? 0); ?> · Private/Secret: <?php echo $number($groupStats['private_groups'] ?? 0); ?></p>
        </article>
        <article class="admin-card">
            <div class="card-label">
                <span class="icon pill friends"><i class="uil uil-user-plus"></i></span>
                <span>Friendships</span>
            </div>
            <h2><?php echo $number($friendStats['accepted_friendships'] ?? 0); ?></h2>
            <p class="muted">Pending requests: <?php echo $number($friendStats['pending_requests'] ?? 0); ?></p>
        </article>
    </section>

    <section class="admin-analytics">
        <article class="admin-panel analytics-card">
            <div class="panel-header">
                <div>
                    <h3>Daily active users</h3>
                    <p class="muted">Logins tracked over the past week.</p>
                </div>
                <span class="metric-badge"><?php echo $number($dailyActiveUsers['latest_count'] ?? 0); ?> today</span>
            </div>
            <div class="metric-highlight">
                <h2><?php echo $number($dailyActiveUsers['latest_count'] ?? 0); ?></h2>
                <p class="muted">Users active in the last 24 hours.</p>
            </div>
            <div class="chart-wrapper">
                <canvas id="dailyActiveChart" height="140"></canvas>
            </div>
        </article>

        <article class="admin-panel analytics-card">
            <div class="panel-header">
                <div>
                    <h3>Complaints overview</h3>
                    <p class="muted">Pending moderation load and recent trend.</p>
                </div>
                <span class="metric-badge warning" data-complaint-pending><?php echo $number($complaintStats['pending_reports'] ?? 0); ?> pending</span>
            </div>
            <div class="metric-highlight">
                <div>
                    <strong data-complaint-total><?php echo $number($complaintStats['total_reports'] ?? 0); ?></strong>
                    <p class="muted">Total complaints logged</p>
                </div>
                <ul class="metric-breakdown">
                    <li>Resolved: <span data-complaint-resolved><?php echo $number($complaintStats['resolved_reports'] ?? 0); ?></span></li>
                    <li>Reviewed: <span data-complaint-reviewed><?php echo $number($complaintStats['reviewed_reports'] ?? 0); ?></span></li>
                    <li>New (7d): <span data-complaint-recent><?php echo $number($complaintStats['recent_reports'] ?? 0); ?></span></li>
                </ul>
            </div>
            <div class="chart-wrapper">
                <canvas id="complaintTypeChart" height="140"></canvas>
            </div>
        </article>
    </section>

    <section class="admin-secondary">
        <article class="admin-panel">
            <div class="panel-header">
                <div>
                    <h3>Recent signups</h3>
                    <p class="muted">Latest users to join the community</p>
                </div>
                <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Discover&action=index">View community</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $recent): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(trim(($recent['first_name'] ?? '') . ' ' . ($recent['last_name'] ?? '')) ?: '—'); ?></td>
                                    <td>@<?php echo htmlspecialchars($recent['username'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($recent['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($recent['created_at'] ?? 'now'))); ?></td>
                                    <td><span class="role-pill <?php echo ($recent['role'] ?? 'user') === 'admin' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars(ucfirst($recent['role'] ?? 'User')); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="muted" style="text-align:center;">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-header">
                <div>
                    <h3>Quick actions</h3>
                    <p class="muted">Resolve pending tasks faster</p>
                </div>
            </div>
            <div class="action-list" id="quickActionButtons">
                <button type="button" class="action-item" data-action-target="quick-review-groups">
                    <div>
                        <h4>Review groups</h4>
                        <p>Scan for inactive or reported groups.</p>
                    </div>
                    <i class="uil uil-angle-right"></i>
                </button>
                <button type="button" class="action-item" data-action-target="quick-trending-posts">
                    <div>
                        <h4>Moderate trending posts</h4>
                        <p>Keep the feed healthy and respectful.</p>
                    </div>
                    <i class="uil uil-angle-right"></i>
                </button>
                <button type="button" class="action-item" data-action-target="quick-system-settings">
                    <div>
                        <h4>Update system settings</h4>
                        <p>Manage profile & security preferences.</p>
                    </div>
                    <i class="uil uil-angle-right"></i>
                </button>
            </div>

            <p class="quick-hint muted">Select an action to open its dashboard in a popup.</p>

            <div class="quick-pane-templates" aria-hidden="true">
                <section id="quick-review-groups" class="quick-pane-template">
                    <header class="quick-pane-header">
                        <div>
                            <h4>Recently created groups</h4>
                            <p class="muted">Newest communities that may need review.</p>
                        </div>
                        <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=discover">View all</a>
                    </header>
                    <?php 
                        $groupSnapshot = $groupReviewSnapshot ?? [];
                        $groupTotalBase = max(1, (int)($groupSnapshot['total_groups'] ?? ($groupStats['total_groups'] ?? 1)));
                        $groupNew = (int)($groupSnapshot['new_last_7'] ?? 0);
                        $groupPending = (int)($groupSnapshot['pending_requests'] ?? 0);
                        $groupInactive = (int)($groupSnapshot['inactive_groups'] ?? 0);
                        $groupAvgMembers = number_format((float)($groupSnapshot['avg_members'] ?? 0), 1);
                    ?>
                    <div class="quick-stats">
                        <div class="stat-card">
                            <p class="label">New this week</p>
                            <strong><?php echo $number($groupNew); ?></strong>
                            <div class="stat-bar">
                                <span style="width: <?php echo min(100, $percentOf($groupNew, $groupTotalBase)); ?>%"></span>
                            </div>
                            <small><?php echo $percentOf($groupNew, $groupTotalBase); ?>% of active groups</small>
                        </div>
                        <div class="stat-card">
                            <p class="label">Pending joins</p>
                            <strong><?php echo $number($groupPending); ?></strong>
                            <div class="stat-bar warning">
                                <span style="width: <?php echo min(100, $percentOf($groupPending, $groupTotalBase)); ?>%"></span>
                            </div>
                            <small><?php echo $groupPending ? 'Needs review' : 'Queue clear'; ?></small>
                        </div>
                        <div class="stat-card">
                            <p class="label">Inactive groups</p>
                            <strong><?php echo $number($groupInactive); ?></strong>
                            <div class="stat-bar danger">
                                <span style="width: <?php echo min(100, $percentOf($groupInactive, $groupTotalBase)); ?>%"></span>
                            </div>
                            <small><?php echo $percentOf($groupInactive, $groupTotalBase); ?>% paused</small>
                        </div>
                        <div class="stat-card compact">
                            <p class="label">Avg members/group</p>
                            <strong><?php echo $groupAvgMembers; ?></strong>
                            <small>Engagement depth snapshot</small>
                        </div>
                    </div>
                    <?php if (!empty($recentGroups)): ?>
                        <ul class="quick-list" data-see-more-list>
                            <?php foreach ($recentGroups as $group): ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                        <p class="muted">
                                            <?php echo ucfirst($group['privacy_status']); ?> · <?php echo (int)($group['member_count'] ?? 0); ?> members
                                        </p>
                                    </div>
                                    <div class="quick-meta">
                                        <span><?php echo htmlspecialchars(date('M d', strtotime($group['created_at'] ?? 'now'))); ?></span>
                                        <a class="quick-link" href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>">Inspect</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">No active groups found.</p>
                    <?php endif; ?>
                </section>

                <section id="quick-trending-posts" class="quick-pane-template">
                    <header class="quick-pane-header">
                        <div>
                            <h4>Trending posts</h4>
                            <p class="muted">Top content past 7 days, sorted by engagement.</p>
                        </div>
                        <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Popular&action=index">Review feed</a>
                    </header>
                    <?php 
                        $moderation = $moderationSnapshot ?? [];
                        $reportsTotal = max(1, (int)($moderation['total_reports'] ?? 0));
                        $reportsRecent = (int)($moderation['reports_last_7'] ?? 0);
                        $postReports = (int)($moderation['post_reports'] ?? 0);
                        $commentReports = (int)($moderation['comment_reports'] ?? 0);
                        $groupReports = (int)($moderation['group_reports'] ?? 0);
                        $distributionBase = max(1, $postReports + $commentReports + $groupReports);
                    ?>
                    <div class="quick-stats">
                        <div class="stat-card">
                            <p class="label">Reports (7d)</p>
                            <strong><?php echo $number($reportsRecent); ?></strong>
                            <div class="stat-bar danger">
                                <span style="width: <?php echo min(100, $percentOf($reportsRecent, $reportsTotal)); ?>%"></span>
                            </div>
                            <small><?php echo $number($moderation['total_reports'] ?? 0); ?> total pending</small>
                        </div>
                        <div class="stat-card">
                            <p class="label">Report distribution</p>
                            <div class="stacked-bar">
                                <span class="segment posts" style="width: <?php echo $percentOf($postReports, $distributionBase); ?>%"></span>
                                <span class="segment comments" style="width: <?php echo $percentOf($commentReports, $distributionBase); ?>%"></span>
                                <span class="segment groups" style="width: <?php echo $percentOf($groupReports, $distributionBase); ?>%"></span>
                            </div>
                            <ul class="stacked-legend">
                                <li><span class="dot posts"></span>Posts (<?php echo $postReports; ?>)</li>
                                <li><span class="dot comments"></span>Comments (<?php echo $commentReports; ?>)</li>
                                <li><span class="dot groups"></span>Groups (<?php echo $groupReports; ?>)</li>
                            </ul>
                        </div>
                        <div class="stat-card compact">
                            <p class="label">Actionable tips</p>
                            <ul class="stat-checklist">
                                <li>Prioritize repeat reporters</li>
                                <li>Escalate posts with >20 comments</li>
                                <li>Archive spam-heavy groups</li>
                            </ul>
                        </div>
                    </div>
                    <?php if (!empty($trendingPosts)): ?>
                        <?php 
                            $maxEngagement = 1;
                            foreach ($trendingPosts as $post) {
                                $score = (int)($post['engagement_score'] ?? 0);
                                if ($score > $maxEngagement) {
                                    $maxEngagement = $score;
                                }
                            }
                        ?>
                        <ul class="quick-list" data-see-more-list>
                            <?php foreach ($trendingPosts as $post): ?>
                                <?php
                                    $authorName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')) ?: ($post['username'] ?? 'Unknown');
                                    $rawContent = $post['content'] ?? '—';
                                    if (function_exists('mb_strimwidth')) {
                                        $excerpt = mb_strimwidth($rawContent, 0, 70, '…');
                                    } else {
                                        $excerpt = strlen($rawContent) > 70 ? substr($rawContent, 0, 67) . '…' : $rawContent;
                                    }
                                ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars($authorName); ?></strong>
                                        <p class="muted"><?php echo htmlspecialchars($excerpt); ?></p>
                                        <?php
                                            $engagementScore = (int)($post['engagement_score'] ?? 0);
                                            $engagementPercent = $maxEngagement ? round(($engagementScore / $maxEngagement) * 100) : 0;
                                        ?>
                                        <div class="engagement-bar" aria-label="Engagement score">
                                            <span style="width: <?php echo min(100, $engagementPercent); ?>%"></span>
                                        </div>
                                    </div>
                                    <div class="quick-meta">
                                        <span><?php echo (int)($post['upvote_count'] ?? 0); ?> ▲ · <?php echo (int)($post['comment_count'] ?? 0); ?> 💬</span>
                                        <div class="quick-meta__actions">
                                            <button type="button" class="link-btn" data-preview-post="<?php echo (int)$post['post_id']; ?>">Preview</button>
                                            <a class="quick-link" href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)$post['user_id']; ?>">Profile</a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">No trending posts detected this week.</p>
                    <?php endif; ?>
                </section>

                <section id="quick-system-settings" class="quick-pane-template">
                    <header class="quick-pane-header">
                        <div>
                            <h4>System settings snapshot</h4>
                            <p class="muted">Monitor privacy and appearance preferences.</p>
                        </div>
                        <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Settings&action=index">Go to settings</a>
                    </header>
                    <?php if (!empty($settingsSummary['total_rows'])): ?>
                        <div class="settings-summary">
                            <div>
                                <h5>Profile visibility</h5>
                                <ul>
                                    <li>
                                        <div class="summary-line">
                                            <span>Public</span>
                                            <strong><?php echo (int)$settingsSummary['public_profiles']; ?></strong>
                                        </div>
                                        <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['public_profiles']; ?>%"></span></div>
                                    </li>
                                    <li>
                                        <div class="summary-line">
                                            <span>Friends only</span>
                                            <strong><?php echo (int)$settingsSummary['friends_only_profiles']; ?></strong>
                                        </div>
                                        <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['friends_only_profiles']; ?>%"></span></div>
                                    </li>
                                    <li>
                                        <div class="summary-line">
                                            <span>Private</span>
                                            <strong><?php echo (int)$settingsSummary['private_profiles']; ?></strong>
                                        </div>
                                        <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['private_profiles']; ?>%"></span></div>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h5>Theme preference</h5>
                                <?php if (!empty($settingsSummary['has_theme'])): ?>
                                    <ul>
                                        <li>
                                            <div class="summary-line">
                                                <span>Light</span>
                                                <strong><?php echo (int)$settingsSummary['light_theme']; ?></strong>
                                            </div>
                                            <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['light_theme']; ?>%"></span></div>
                                        </li>
                                        <li>
                                            <div class="summary-line">
                                                <span>Dark</span>
                                                <strong><?php echo (int)$settingsSummary['dark_theme']; ?></strong>
                                            </div>
                                            <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['dark_theme']; ?>%"></span></div>
                                        </li>
                                        <li>
                                            <div class="summary-line">
                                                <span>Auto</span>
                                                <strong><?php echo (int)$settingsSummary['auto_theme']; ?></strong>
                                            </div>
                                            <div class="progress-line"><span style="width: <?php echo $settingsSummary['percent']['auto_theme']; ?>%"></span></div>
                                        </li>
                                    </ul>
                                <?php else: ?>
                                    <p class="muted">Theme data not tracked on this install.</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5>Notifications</h5>
                                <?php if (!empty($settingsSummary['has_push'])): ?>
                                    <div class="summary-line">
                                        <span>Push enabled</span>
                                        <strong><?php echo (int)$settingsSummary['push_enabled_users']; ?></strong>
                                    </div>
                                    <div class="progress-line"><span style="width: <?php echo $percentOf($settingsSummary['push_enabled_users'], $settingsSummary['total_rows']); ?>%"></span></div>
                                <?php else: ?>
                                    <p class="muted">Push preference not tracked on this install.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="muted">Settings data not available yet.</p>
                    <?php endif; ?>
                </section>
            </div>

            <div id="quickModal" class="quick-modal" aria-hidden="true">
                <div class="quick-modal__dialog" role="dialog" aria-modal="true" aria-label="Quick action details">
                    <button type="button" class="quick-modal__close" data-modal-close aria-label="Close quick action">
                        <i class="uil uil-multiply"></i>
                    </button>
                    <div class="quick-modal__body"></div>
                </div>
            </div>
        </article>
    </section>

    <section class="admin-trending-grid">
        <article class="admin-panel">
            <div class="panel-header">
                <div>
                    <h3>Most trending posts</h3>
                    <p class="muted">High engagement across the network.</p>
                </div>
                <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Popular&action=index">View feed</a>
            </div>
            <?php $topTrendingPosts = array_slice($trendingPosts ?? [], 0, 3); ?>
            <?php if (!empty($topTrendingPosts)): ?>
                <ul class="trend-list" data-see-more-list>
                    <?php foreach ($topTrendingPosts as $post): ?>
                        <?php
                            $rawContent = trim(strip_tags($post['content'] ?? ''));
                            if ($rawContent === '') {
                                $rawContent = 'No caption provided.';
                            }
                            $previewText = function_exists('mb_strimwidth')
                                ? mb_strimwidth($rawContent, 0, 90, '…')
                                : (strlen($rawContent) > 90 ? substr($rawContent, 0, 87) . '…' : $rawContent);
                        ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars(($post['username'] ?? 'unknown')); ?></strong>
                                <p class="muted trend-snippet">"<?php echo htmlspecialchars($previewText); ?>"</p>
                                <span class="metric-pill">Engagement score · <?php echo (int)($post['engagement_score'] ?? 0); ?></span>
                            </div>
                            <div class="trend-metrics">
                                <div class="metric-pill"><?php echo (int)($post['upvote_count'] ?? 0); ?> upvotes</div>
                                <div class="metric-pill"><?php echo (int)($post['comment_count'] ?? 0); ?> comments</div>
                                <div class="trend-actions">
                                    <button type="button" class="link-btn" data-preview-post="<?php echo (int)$post['post_id']; ?>">Preview</button>
                                    <a class="quick-link" href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)$post['user_id']; ?>">Profile</a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">No trending posts detected.</p>
            <?php endif; ?>
        </article>

        <article class="admin-panel">
            <div class="panel-header">
                <div>
                    <h3>Trending groups</h3>
                    <p class="muted">Ranked by weekly activity, showing lifetime totals.</p>
                </div>
                <a class="text-link" href="<?php echo BASE_PATH; ?>index.php?controller=Discover&action=index">Explore</a>
            </div>
            <?php if (!empty($trendingGroups)): ?>
                <ul class="trend-list groups" data-see-more-list>
                    <?php foreach ($trendingGroups as $group): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($group['name'] ?? 'Group'); ?></strong>
                                <p class="muted"><?php echo ucfirst($group['privacy_status'] ?? 'public'); ?> · <?php echo (int)($group['member_count'] ?? 0); ?> members</p>
                                <span class="metric-pill">Engagement score · <?php echo (int)($group['engagement_score'] ?? 0); ?></span>
                            </div>
                            <div class="trend-metrics">
                                <div class="metric-pill"><?php echo (int)($group['total_posts'] ?? $group['posts_last_7'] ?? 0); ?> posts</div>
                                <div class="metric-pill"><?php echo (int)($group['total_comments'] ?? $group['comments_last_7'] ?? 0); ?> comments</div>
                                <a class="quick-link" href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>">Inspect</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">Not enough group activity yet.</p>
            <?php endif; ?>
        </article>

        <article class="admin-panel">
            <div class="panel-header">
                <div>
                    <h3>Complaints queue</h3>
                    <p class="muted">Latest reports awaiting review.</p>
                </div>
                <button type="button" class="text-link link-btn" data-open-complaints>Open board</button>
            </div>
            <div data-recent-complaints data-empty-message="<?php echo htmlspecialchars('No complaints logged.'); ?>">
                <?php if (!empty($recentComplaints)): ?>
                    <ul class="complaint-list" data-see-more-list>
                        <?php foreach ($recentComplaints as $complaint): ?>
                            <?php
                                $reportId = (int)($complaint['report_id'] ?? 0);
                                $targetLabel = $complaint['target_label'] ?? 'General';
                                $reportedPostId = (int)($complaint['reported_post_id'] ?? 0);
                                $reportedGroupId = (int)($complaint['reported_group_id'] ?? 0);
                                $reportedUserId = (int)($complaint['reported_user_id'] ?? 0);
                            ?>
                            <li data-report-id="<?php echo $reportId; ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($complaint['report_type'] ?? 'Complaint'); ?></strong>
                                    <p class="muted">
                                        <?php echo htmlspecialchars($targetLabel); ?> ·
                                        <?php echo htmlspecialchars($complaint['reporter_username'] ?? 'Unknown'); ?>
                                    </p>
                                </div>
                                <div class="trend-metrics">
                                    <div class="trend-actions">
                                        <?php if ($reportedPostId): ?>
                                            <button type="button" class="link-btn" data-preview-post="<?php echo $reportedPostId; ?>">Preview</button>
                                            <button type="button" class="link-btn danger" data-remove-post="<?php echo $reportedPostId; ?>" data-target-label="<?php echo htmlspecialchars($targetLabel); ?>">Remove</button>
                                        <?php endif; ?>
                                        <?php if ($reportedGroupId): ?>
                                            <button type="button" class="link-btn danger" data-disable-group="<?php echo $reportedGroupId; ?>" data-target-label="<?php echo htmlspecialchars($targetLabel); ?>">Disable group</button>
                                        <?php endif; ?>
                                        <?php if ($reportedUserId): ?>
                                            <button type="button" class="link-btn danger" data-ban-user="<?php echo $reportedUserId; ?>" data-ban-username="<?php echo htmlspecialchars($complaint['reported_username'] ?? 'User'); ?>">Ban</button>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-pill status-<?php echo htmlspecialchars(strtolower($complaint['status'] ?? 'pending')); ?>"><?php echo ucfirst($complaint['status'] ?? 'pending'); ?></span>
                                    <small><?php echo htmlspecialchars(date('M d, H:i', strtotime($complaint['created_at'] ?? 'now'))); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No complaints logged.</p>
                <?php endif; ?>
            </div>
        </article>
    </section>
</main>

<?php
$complaintTabs = [
    'received' => [
        'label' => 'Received (48h)',
        'description' => 'Newest complaints captured within the last 48 hours.',
        'items' => $complaintsByStatus['received'] ?? [],
        'count' => count($complaintsByStatus['received'] ?? []),
        'empty' => 'No new complaints recorded in the past 48 hours.'
    ],
    'pending' => [
        'label' => 'Pending',
        'description' => 'Complaints awaiting moderator action.',
        'items' => $complaintsByStatus['pending'] ?? [],
        'count' => count($complaintsByStatus['pending'] ?? []),
        'empty' => 'Great news — there are no pending complaints.'
    ],
    'resolved' => [
        'label' => 'Resolved',
        'description' => 'Recently resolved or reviewed complaints.',
        'items' => $complaintsByStatus['resolved'] ?? [],
        'count' => count($complaintsByStatus['resolved'] ?? []),
        'empty' => 'No resolved complaints to show yet.'
    ]
];

$renderComplaintItems = function(array $items, string $emptyMessage) {
    if (empty($items)) {
        echo '<p class="muted empty-state">' . htmlspecialchars($emptyMessage) . '</p>';
        return;
    }

    echo '<ul class="complaint-board-list" data-see-more-list>';
    foreach ($items as $item) {
        $reportType = ucfirst($item['report_type'] ?? 'Complaint');
        $status = strtolower($item['status'] ?? 'pending');
        $reportId = (int)($item['report_id'] ?? 0);
        $reportedUserId = (int)($item['reported_user_id'] ?? 0);
        $reportedPostId = (int)($item['reported_post_id'] ?? 0);
        $reportedGroupId = (int)($item['reported_group_id'] ?? 0);
        $reportedCommentId = (int)($item['reported_comment_id'] ?? 0);
        $reporter = $item['reporter_username'] ?? 'Anonymous';
        $reportedUsername = $item['reported_username'] ?? null;
        $createdAt = $item['created_at'] ?? '';

        $targetLabel = 'General';
        if ($reportedPostId) {
            $targetLabel = 'Post #' . $reportedPostId;
        } elseif ($reportedCommentId) {
            $targetLabel = 'Comment #' . $reportedCommentId;
        } elseif ($reportedGroupId) {
            $targetLabel = 'Group #' . $reportedGroupId;
        } elseif ($reportedUserId) {
            $targetLabel = 'User #' . $reportedUserId;
        }

        $description = trim($item['description'] ?? '');
        if ($description !== '') {
            $description = function_exists('mb_strimwidth') ? mb_strimwidth($description, 0, 140, '…') : (strlen($description) > 140 ? substr($description, 0, 137) . '…' : $description);
        }

        echo '<li class="complaint-board-item" data-report-id="' . $reportId . '">';
        echo '<div class="complaint-summary">';
        echo '<strong>' . htmlspecialchars($reportType) . '</strong>';
        echo '<p class="muted">' . htmlspecialchars($targetLabel) . ' · Reported by ' . htmlspecialchars($reporter) . '</p>';
        if ($description) {
            echo '<p class="complaint-description">' . htmlspecialchars($description) . '</p>';
        }
        echo '</div>';
        echo '<div class="complaint-actions">';
        if ($reportedPostId) {
            echo '<button type="button" class="link-btn" data-preview-post="' . $reportedPostId . '">View post</button>';
            echo '<button type="button" class="link-btn danger" data-remove-post="' . $reportedPostId . '" data-target-label="' . htmlspecialchars($targetLabel) . '">Remove post</button>';
        }

        if ($reportedGroupId) {
            echo '<a class="link-btn" href="' . BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . $reportedGroupId . '">Open group</a>';
            echo '<button type="button" class="link-btn danger" data-disable-group="' . $reportedGroupId . '" data-target-label="' . htmlspecialchars($targetLabel) . '">Disable group</button>';
        }

        if ($reportedUserId) {
            $usernameAttr = htmlspecialchars($reportedUsername ?? ('User #' . $reportedUserId));
            echo '<button type="button" class="link-btn danger" data-ban-user="' . $reportedUserId . '" data-ban-username="' . $usernameAttr . '">Ban user</button>';
        }

        echo '<span class="status-pill status-' . htmlspecialchars($status) . '">' . ucfirst($status) . '</span>';
        if ($createdAt) {
            echo '<small>' . htmlspecialchars(date('M d, H:i', strtotime($createdAt))) . '</small>';
        }
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
};
?>

<div id="complaintsModal" class="admin-overlay" aria-hidden="true">
    <div class="admin-overlay__dialog" role="dialog" aria-modal="true" aria-label="Complaints board">
        <button type="button" class="admin-overlay__close" data-overlay-close="complaints" aria-label="Close complaints board">
            <i class="uil uil-multiply"></i>
        </button>
        <header class="overlay-header">
            <div>
                <p class="eyebrow">Moderation</p>
                <h2>Complaints board</h2>
                <p class="muted">Browse complaints by lifecycle stage and take immediate action.</p>
            </div>
        </header>
        <div class="complaint-tabs" role="tablist">
            <?php foreach ($complaintTabs as $key => $tab): ?>
                <button type="button" role="tab" class="tab-button<?php echo $key === 'received' ? ' active' : ''; ?>" data-complaint-tab="<?php echo $key; ?>">
                    <span><?php echo htmlspecialchars($tab['label']); ?></span>
                    <span class="tab-count" data-complaint-tab-count="<?php echo $key; ?>"><?php echo $number($tab['count'] ?? 0); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <?php foreach ($complaintTabs as $key => $tab): ?>
            <section class="complaint-tabpanel<?php echo $key === 'received' ? ' active' : ''; ?>" data-complaint-panel="<?php echo $key; ?>" data-empty-message="<?php echo htmlspecialchars($tab['empty']); ?>" role="tabpanel">
                <p class="muted"><?php echo htmlspecialchars($tab['description']); ?></p>
                <div class="complaint-panel-body" data-complaint-body>
                    <?php $renderComplaintItems($tab['items'], $tab['empty']); ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<div id="banUserModal" class="admin-overlay" aria-hidden="true">
    <div class="admin-overlay__dialog" role="dialog" aria-modal="true" aria-label="Ban user">
        <button type="button" class="admin-overlay__close" data-overlay-close="ban" aria-label="Close ban modal">
            <i class="uil uil-multiply"></i>
        </button>
        <header class="overlay-header">
            <div>
                <p class="eyebrow">Enforcement</p>
                <h2>Ban account</h2>
                <p class="muted">Suspend problematic accounts for a set duration.</p>
            </div>
            <div class="ban-target" data-ban-target-label>—</div>
        </header>
        <form id="banUserForm" class="ban-form">
            <input type="hidden" name="user_id" data-ban-user-id>
            <label>
                <span>Duration</span>
                <div class="ban-duration-options">
                    <label><input type="radio" name="duration" value="24h" required checked>24 hours</label>
                    <label><input type="radio" name="duration" value="72h">3 days</label>
                    <label><input type="radio" name="duration" value="1w">1 week</label>
                    <label><input type="radio" name="duration" value="2w">2 weeks</label>
                    <label><input type="radio" name="duration" value="1m">1 month</label>
                    <label><input type="radio" name="duration" value="custom">Custom</label>
                </div>
            </label>
            <label class="custom-until" data-custom-until hidden>
                <span>Custom end time</span>
                <input type="datetime-local" name="custom_until">
            </label>
            <label>
                <span>Reason</span>
                <input type="text" name="reason" placeholder="Reason shown to the user" required>
            </label>
            <label>
                <span>Internal notes</span>
                <textarea name="notes" rows="3" placeholder="Visible to admins only"></textarea>
            </label>
            <div class="ban-form__actions">
                <button type="button" class="link-btn" data-overlay-close="ban">Cancel</button>
                <button type="submit" class="primary-btn">Ban user</button>
            </div>
            <p class="ban-feedback" data-ban-feedback></p>
        </form>
    </div>
</div>

<div id="disableGroupModal" class="admin-overlay" aria-hidden="true">
    <div class="admin-overlay__dialog" role="dialog" aria-modal="true" aria-label="Disable group">
        <button type="button" class="admin-overlay__close" data-overlay-close="disable" aria-label="Close disable group modal">
            <i class="uil uil-multiply"></i>
        </button>
        <header class="overlay-header">
            <div>
                <p class="eyebrow">Group enforcement</p>
                <h2>Disable group</h2>
                <p class="muted">Take a problematic community offline.</p>
            </div>
            <div class="ban-target" data-disable-target-label>—</div>
        </header>
        <form id="disableGroupForm" class="ban-form">
            <input type="hidden" name="group_id" data-disable-group-id>
            <input type="hidden" name="report_id" data-disable-report-id>
            <label>
                <span>Duration</span>
                <div class="ban-duration-options">
                    <label><input type="radio" name="duration" value="24h" required checked>24 hours</label>
                    <label><input type="radio" name="duration" value="72h">3 days</label>
                    <label><input type="radio" name="duration" value="1w">1 week</label>
                    <label><input type="radio" name="duration" value="2w">2 weeks</label>
                    <label><input type="radio" name="duration" value="1m">1 month</label>
                    <label><input type="radio" name="duration" value="custom">Custom</label>
                </div>
            </label>
            <label class="custom-until" data-disable-custom hidden>
                <span>Custom end time</span>
                <input type="datetime-local" name="custom_until" step="60">
            </label>
            <label>
                <span>Reason</span>
                <input type="text" name="reason" placeholder="Reason shown to the user" required>
            </label>
            <label>
                <span>Internal notes</span>
                <textarea name="notes" rows="3" placeholder="Visible to admins only"></textarea>
            </label>
            <p class="form-feedback" data-disable-feedback></p>
            <div class="ban-form__actions">
                <button type="submit" class="btn danger">Disable group</button>
                <button type="button" class="btn ghost" data-overlay-close="disable">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="postPreviewModal" class="admin-overlay" aria-hidden="true">
    <div class="admin-overlay__dialog" role="dialog" aria-modal="true" aria-label="Post preview">
        <button type="button" class="admin-overlay__close" data-overlay-close="preview" aria-label="Close post preview">
            <i class="uil uil-multiply"></i>
        </button>
        <div class="post-preview" data-post-preview>
            <p class="muted">Select an engagement to preview the full post.</p>
        </div>
    </div>
</div>

<script src="./js/navbar.js"></script>
<script src="./js/notificationpopup.js"></script>
<script src="./js/calender.js"></script>
<script src="./js/general.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const adminDashboardData = <?php echo json_encode([
    'dailyActive' => [
        'labels' => $dailyActiveUsers['labels'] ?? [],
        'counts' => $dailyActiveUsers['counts'] ?? []
    ],
    'complaintTypes' => [
        'labels' => $complaintTypeLabels,
        'counts' => $complaintTypeCounts
    ]
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

document.addEventListener('DOMContentLoaded', () => {
    if (window.Chart) {
        const palette = ['#1877f2', '#8e44ad', '#ff7a18', '#30c48d'];
        const dailyCtx = document.getElementById('dailyActiveChart');
        if (dailyCtx && adminDashboardData.dailyActive.labels.length) {
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: adminDashboardData.dailyActive.labels,
                    datasets: [{
                        label: 'Active users',
                        data: adminDashboardData.dailyActive.counts,
                        borderColor: '#1877f2',
                        backgroundColor: 'rgba(24, 119, 242, 0.15)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        const complaintCtx = document.getElementById('complaintTypeChart');
        if (complaintCtx && adminDashboardData.complaintTypes.labels.length) {
            new Chart(complaintCtx, {
                type: 'doughnut',
                data: {
                    labels: adminDashboardData.complaintTypes.labels,
                    datasets: [{
                        data: adminDashboardData.complaintTypes.counts,
                        backgroundColor: palette,
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } },
                    cutout: '68%'
                }
            });
        }
    }

    const SEE_MORE_LIMIT = 3;

    const applySeeMoreGroup = container => {
        if (!container) return;
        const limitAttr = parseInt(container.getAttribute('data-see-more-list'), 10);
        const limit = Number.isFinite(limitAttr) && limitAttr > 0 ? limitAttr : SEE_MORE_LIMIT;
        const items = Array.from(container.children).filter(child => child.nodeType === 1);
        const existingToggle = container.nextElementSibling && container.nextElementSibling.matches('[data-see-more-toggle]')
            ? container.nextElementSibling
            : null;

        if (items.length <= limit) {
            items.forEach(item => item.removeAttribute('data-see-more-hidden'));
            container.removeAttribute('data-see-more-expanded');
            existingToggle?.remove();
            return;
        }

        if (existingToggle) {
            existingToggle.remove();
        }

        if (!container.hasAttribute('data-see-more-expanded')) {
            container.setAttribute('data-see-more-expanded', 'false');
        }

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'link-btn see-more-toggle';
        toggle.setAttribute('data-see-more-toggle', 'true');
        container.after(toggle);

        const updateState = () => {
            const expanded = container.getAttribute('data-see-more-expanded') === 'true';
            items.forEach((item, index) => {
                if (index >= limit) {
                    item.toggleAttribute('data-see-more-hidden', !expanded);
                } else {
                    item.removeAttribute('data-see-more-hidden');
                }
            });
            const hiddenCount = Math.max(0, items.length - limit);
            toggle.textContent = expanded ? 'Show less' : `See more (${hiddenCount})`;
        };

        toggle.addEventListener('click', () => {
            const expanded = container.getAttribute('data-see-more-expanded') === 'true';
            container.setAttribute('data-see-more-expanded', expanded ? 'false' : 'true');
            updateState();
        });

        updateState();
    };

    const refreshSeeMoreGroups = (scope = document) => {
        scope.querySelectorAll('[data-see-more-list]').forEach(applySeeMoreGroup);
    };

    refreshSeeMoreGroups();

    const quickModal = document.getElementById('quickModal');
    const quickButtons = document.querySelectorAll('[data-action-target]');
    const quickTemplates = {};
    let lastFocusedButton = null;
    let closeQuickModal = () => {};

    if (quickModal) {
        const modalBody = quickModal.querySelector('.quick-modal__body');
        const closeButtons = quickModal.querySelectorAll('[data-modal-close]');
        document.querySelectorAll('.quick-pane-template').forEach(node => {
            if (node.id) {
                quickTemplates[node.id] = node;
            }
        });

        closeQuickModal = () => {
            quickModal.classList.remove('open');
            quickModal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.admin-overlay.open')) {
                document.body.classList.remove('modal-open');
            }
            if (modalBody) {
                modalBody.innerHTML = '';
            }
            if (lastFocusedButton) {
                lastFocusedButton.focus();
            }
        };

        const openQuickModal = (targetId, triggerBtn) => {
            if (!modalBody || !quickTemplates[targetId]) return;
            const clone = quickTemplates[targetId].cloneNode(true);
            clone.removeAttribute('id');
            modalBody.innerHTML = '';
            modalBody.appendChild(clone);
            refreshSeeMoreGroups(modalBody);
            quickModal.classList.add('open');
            quickModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            lastFocusedButton = triggerBtn;
            const closeBtn = quickModal.querySelector('[data-modal-close]');
            closeBtn?.focus();
        };

        quickButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                quickButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const targetId = btn.dataset.actionTarget;
                if (targetId) {
                    openQuickModal(targetId, btn);
                }
            });
        });

        closeButtons.forEach(button => {
            button.addEventListener('click', closeQuickModal);
        });

        quickModal.addEventListener('click', event => {
            if (event.target === quickModal) {
                closeQuickModal();
            }
        });
    }

    const overlays = {
        complaints: document.getElementById('complaintsModal'),
        ban: document.getElementById('banUserModal'),
        disable: document.getElementById('disableGroupModal'),
        preview: document.getElementById('postPreviewModal')
    };

    const syncBodyScrollLock = () => {
        if (!document.querySelector('.admin-overlay.open') && !document.querySelector('.quick-modal.open')) {
            document.body.classList.remove('modal-open');
        }
    };

    const openOverlay = modal => {
        if (!modal) return;
        modal.classList.add('open');
        document.body.classList.add('modal-open');
    };

    const closeOverlayEl = modal => {
        if (!modal) return;
        modal.classList.remove('open');
        syncBodyScrollLock();
    };

    document.querySelectorAll('[data-open-complaints]').forEach(btn => {
        btn.addEventListener('click', () => {
            openOverlay(overlays.complaints);
            refreshComplaintsBoard();
        });
    });

    document.querySelectorAll('[data-overlay-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.getAttribute('data-overlay-close');
            closeOverlayEl(overlays[key]);
        });
    });

    Object.values(overlays).forEach(modal => {
        modal?.addEventListener('click', event => {
            if (event.target === modal) {
                closeOverlayEl(modal);
            }
        });
    });

    const complaintsModal = overlays.complaints;
    if (complaintsModal) {
        const tabButtons = complaintsModal.querySelectorAll('[data-complaint-tab]');
        const tabPanels = complaintsModal.querySelectorAll('[data-complaint-panel]');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const target = button.dataset.complaintTab;
                tabButtons.forEach(b => b.classList.toggle('active', b === button));
                tabPanels.forEach(panel => {
                    panel.classList.toggle('active', panel.dataset.complaintPanel === target);
                });
            });
        });
    }

    const complaintSummary = {
        pending: document.querySelector('[data-complaint-pending]'),
        total: document.querySelector('[data-complaint-total]'),
        resolved: document.querySelector('[data-complaint-resolved]'),
        reviewed: document.querySelector('[data-complaint-reviewed]'),
        recent: document.querySelector('[data-complaint-recent]')
    };

    const recentComplaintsContainer = document.querySelector('[data-recent-complaints]');
    const recentComplaintsEmptyMessage = recentComplaintsContainer?.getAttribute('data-empty-message') || 'No complaints logged.';

    const complaintBoardPanels = {};
    if (complaintsModal) {
        complaintsModal.querySelectorAll('[data-complaint-panel]').forEach(panel => {
            const key = panel.getAttribute('data-complaint-panel');
            complaintBoardPanels[key] = {
                body: panel.querySelector('[data-complaint-body]'),
                emptyMessage: panel.getAttribute('data-empty-message') || 'No complaints to show.'
            };
        });
    }

    let complaintsRefreshInFlight = false;
    let complaintsRefreshQueued = false;

    const buildAssetUrl = path => {
        if (!path) return '';
        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        const base = BASE_PATH.endsWith('/') ? BASE_PATH : `${BASE_PATH}/`;
        const cleanedPath = path.startsWith('/') ? path.slice(1) : path;
        return base + cleanedPath;
    };

    const escapeHtml = (str = '') => str.toString().replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[ch] || ch));

    const formatDateTime = value => {
        if (!value) return '';
        try {
            return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        } catch (err) {
            return value;
        }
    };

    const truncateText = (value = '', limit = 140) => {
        const str = value.toString();
        if (str.length <= limit) {
            return str;
        }
        return `${str.slice(0, limit - 1)}…`;
    };

    const buildComplaintTargetLabel = complaint => {
        if (complaint.reported_post_id) {
            return `Post #${complaint.reported_post_id}`;
        }
        if (complaint.reported_comment_id) {
            return `Comment #${complaint.reported_comment_id}`;
        }
        if (complaint.reported_group_id) {
            return `Group #${complaint.reported_group_id}`;
        }
        if (complaint.reported_user_id) {
            return `User #${complaint.reported_user_id}`;
        }
        return 'General';
    };

    const renderComplaintBoardPanel = (key, items = []) => {
        const slot = complaintBoardPanels[key];
        if (!slot?.body) {
            return;
        }

        if (!items.length) {
            slot.body.innerHTML = `<p class="muted empty-state">${escapeHtml(slot.emptyMessage || 'No complaints to show.')}</p>`;
            return;
        }

        const markup = items.map(item => {
            const reportId = parseInt(item.report_id, 10) || 0;
            const status = (item.status || 'pending').toLowerCase();
            const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
            const description = item.description ? `<p class="complaint-description">${escapeHtml(truncateText(item.description))}</p>` : '';
            const targetName = escapeHtml(item.target_label || buildComplaintTargetLabel(item));
            const targetLabel = `${targetName} · Reported by ${escapeHtml(item.reporter_username || 'Anonymous')}`;

            const actionButtons = [];
            const reportedPostId = parseInt(item.reported_post_id, 10) || 0;
            const reportedGroupId = parseInt(item.reported_group_id, 10) || 0;
            const reportedUserId = parseInt(item.reported_user_id, 10) || 0;

            if (reportedPostId) {
                actionButtons.push(`<button type="button" class="link-btn" data-preview-post="${reportedPostId}">View post</button>`);
                actionButtons.push(`<button type="button" class="link-btn danger" data-remove-post="${reportedPostId}" data-target-label="${targetName}">Remove post</button>`);
            }

            if (reportedGroupId) {
                actionButtons.push(`<a class="link-btn" href="${BASE_PATH}index.php?controller=Group&action=index&group_id=${reportedGroupId}">Open group</a>`);
                actionButtons.push(`<button type="button" class="link-btn danger" data-disable-group="${reportedGroupId}" data-target-label="${targetName}">Disable group</button>`);
            }

            if (reportedUserId) {
                const username = escapeHtml(item.reported_username || `User #${reportedUserId}`);
                actionButtons.push(`<button type="button" class="link-btn danger" data-ban-user="${reportedUserId}" data-ban-username="${username}">Ban user</button>`);
            }

            const createdAt = item.created_at ? `<small>${escapeHtml(formatDateTime(item.created_at))}</small>` : '';

            return `
                <li class="complaint-board-item" data-report-id="${reportId}">
                    <div class="complaint-summary">
                        <strong>${escapeHtml(item.report_type ? item.report_type.charAt(0).toUpperCase() + item.report_type.slice(1) : 'Complaint')}</strong>
                        <p class="muted">${targetLabel}</p>
                        ${description}
                    </div>
                    <div class="complaint-actions">
                        ${actionButtons.join('')}
                        <span class="status-pill status-${escapeHtml(status)}">${escapeHtml(statusLabel)}</span>
                        ${createdAt}
                    </div>
                </li>
            `;
        }).join('');

        slot.body.innerHTML = `<ul class="complaint-board-list" data-see-more-list>${markup}</ul>`;
        const list = slot.body.querySelector('[data-see-more-list]');
        applySeeMoreGroup(list);
    };

    const renderRecentComplaints = complaints => {
        if (!recentComplaintsContainer) {
            return;
        }

        if (!complaints?.length) {
            recentComplaintsContainer.innerHTML = `<p class="muted">${escapeHtml(recentComplaintsEmptyMessage)}</p>`;
            return;
        }

        const markup = complaints.map(item => {
            const reportId = parseInt(item.report_id, 10) || 0;
            const targetLabel = escapeHtml(item.target_label || buildComplaintTargetLabel(item));
            const actions = [];

            if (item.reported_post_id) {
                const postId = parseInt(item.reported_post_id, 10) || 0;
                actions.push(`<button type="button" class="link-btn" data-preview-post="${postId}">Preview</button>`);
                actions.push(`<button type="button" class="link-btn danger" data-remove-post="${postId}" data-target-label="${targetLabel}">Remove</button>`);
            }

            if (item.reported_group_id) {
                const groupId = parseInt(item.reported_group_id, 10) || 0;
                actions.push(`<button type="button" class="link-btn danger" data-disable-group="${groupId}" data-target-label="${targetLabel}">Disable group</button>`);
            }

            if (item.reported_user_id) {
                const username = escapeHtml(item.reported_username || 'User');
                actions.push(`<button type="button" class="link-btn danger" data-ban-user="${parseInt(item.reported_user_id, 10)}" data-ban-username="${username}">Ban</button>`);
            }

            const status = (item.status || 'pending').toLowerCase();
            return `
                <li data-report-id="${reportId}">
                    <div>
                        <strong>${escapeHtml(item.report_type || 'Complaint')}</strong>
                        <p class="muted">${targetLabel} · ${escapeHtml(item.reporter_username || 'Unknown')}</p>
                    </div>
                    <div class="trend-metrics">
                        <div class="trend-actions">${actions.join('')}</div>
                        <span class="status-pill status-${escapeHtml(status)}">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span>
                        <small>${escapeHtml(formatDateTime(item.created_at))}</small>
                    </div>
                </li>
            `;
        }).join('');

        recentComplaintsContainer.innerHTML = `<ul class="complaint-list" data-see-more-list>${markup}</ul>`;
        applySeeMoreGroup(recentComplaintsContainer.querySelector('[data-see-more-list]'));
    };

    const numberFormatter = new Intl.NumberFormat();
    const formatCount = value => numberFormatter.format(Math.max(0, Number(value) || 0));

    const updateComplaintStats = stats => {
        if (!stats) {
            return;
        }
        if (complaintSummary.pending) {
            complaintSummary.pending.textContent = `${formatCount(stats.pending_reports)} pending`;
        }
        if (complaintSummary.total) {
            complaintSummary.total.textContent = formatCount(stats.total_reports);
        }
        if (complaintSummary.resolved) {
            complaintSummary.resolved.textContent = formatCount(stats.resolved_reports);
        }
        if (complaintSummary.reviewed) {
            complaintSummary.reviewed.textContent = formatCount(stats.reviewed_reports);
        }
        if (complaintSummary.recent) {
            complaintSummary.recent.textContent = formatCount(stats.recent_reports);
        }
    };

    const updateComplaintTabCounts = counts => {
        if (!counts) return;
        Object.entries(counts).forEach(([key, value]) => {
            const target = document.querySelector(`[data-complaint-tab-count="${key}"]`);
            if (target) {
                target.textContent = formatCount(value);
            }
        });
    };

    const refreshComplaintsBoard = async () => {
        if (complaintsRefreshInFlight) {
            complaintsRefreshQueued = true;
            return;
        }

        complaintsRefreshInFlight = true;
        try {
            const response = await fetch(`${BASE_PATH}index.php?controller=Admin&action=complaintsBoardData`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Unable to refresh complaints');
            }
            updateComplaintStats(data.stats || {});
            renderRecentComplaints(data.recent || []);
            const panels = data.byStatus || {};
            Object.keys(panels).forEach(key => renderComplaintBoardPanel(key, panels[key] || []));
            updateComplaintTabCounts({
                received: (panels.received || []).length,
                pending: (panels.pending || []).length,
                resolved: (panels.resolved || []).length
            });
        } catch (error) {
            console.error('Unable to refresh complaints board', error);
        } finally {
            complaintsRefreshInFlight = false;
            if (complaintsRefreshQueued) {
                complaintsRefreshQueued = false;
                refreshComplaintsBoard();
            }
        }
    };

    const previewModal = overlays.preview;
    const previewBody = previewModal?.querySelector('[data-post-preview]');

    const renderPostPreview = post => {
        if (!previewBody) return;
        const authorName = `${(post.first_name || '')} ${(post.last_name || '')}`.trim() || post.username || 'User';
        const avatar = buildAssetUrl(post.profile_picture);
        const imageUrl = buildAssetUrl(post.image_url);
        const groupLabel = post.group_name ? `<small class="muted">Shared in ${escapeHtml(post.group_name)}</small>` : '';
        previewBody.innerHTML = `
            <div class="post-preview__header">
                ${avatar ? `<img src="${avatar}" alt="${escapeHtml(authorName)}">` : ''}
                <div>
                    <strong>${escapeHtml(authorName)}</strong>
                    <p class="muted">${formatDateTime(post.created_at)}</p>
                    ${groupLabel}
                </div>
            </div>
            <p>${escapeHtml(post.content ?? '')}</p>
            ${imageUrl ? `<div class="post-preview__media"><img src="${imageUrl}" alt="Post media"></div>` : ''}
            <p class="muted">${post.upvote_count || 0} ▲ · ${post.comment_count || 0} 💬</p>
        `;
    };

    const markReportReviewed = async (reportId, itemEl) => {
        if (!reportId) return;
        if (itemEl?.dataset.reviewed === 'true') {
            return;
        }

        try {
            const response = await fetch(`${BASE_PATH}index.php?controller=Admin&action=markReportReviewed`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ report_id: reportId }).toString()
            });
            const data = await response.json();
            if (data.success) {
                if (itemEl) {
                    itemEl.dataset.reviewed = 'true';
                    const pill = itemEl.querySelector('.status-pill');
                    if (pill) {
                        pill.textContent = 'Reviewed';
                        pill.classList.remove('status-pending');
                        pill.classList.add('status-reviewed');
                    }
                }
                refreshComplaintsBoard();
            }
        } catch (error) {
            console.error('Unable to mark complaint reviewed', error);
        }
    };

    const handlePostRemoval = async (postId, reportId, targetLabel, sourceElement) => {
        if (!postId) return;
        const label = targetLabel || `Post #${postId}`;
        if (!window.confirm(`Remove ${label}? This action cannot be undone.`)) {
            return;
        }

        try {
            const params = new URLSearchParams({ post_id: postId });
            if (reportId) {
                params.append('report_id', reportId);
            }

            const response = await fetch(`${BASE_PATH}index.php?controller=Admin&action=removePost`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Unable to remove post.');
            }

            if (reportId) {
                await markReportReviewed(reportId, sourceElement);
            } else {
                refreshComplaintsBoard();
            }
        } catch (error) {
            alert(error.message || 'Unable to remove post.');
        }
    };

    const openPostPreview = async (postId, sourceComplaint) => {
        if (!postId || !previewModal) return;
        if (sourceComplaint) {
            markReportReviewed(sourceComplaint.reportId, sourceComplaint.element);
        }
        openOverlay(previewModal);
        if (previewBody) {
            previewBody.innerHTML = '<p class="muted">Loading preview…</p>';
        }
        try {
            const response = await fetch(`${BASE_PATH}index.php?controller=Admin&action=previewPost&post_id=${postId}`);
            const data = await response.json();
            if (data.success) {
                renderPostPreview(data.post);
            } else if (previewBody) {
                previewBody.innerHTML = `<p class="muted">${escapeHtml(data.message || 'Unable to load post.')}</p>`;
            }
        } catch (error) {
            if (previewBody) {
                previewBody.innerHTML = '<p class="muted">Network error. Try again.</p>';
            }
        }
    };

    const banModal = overlays.ban;
    const banForm = document.getElementById('banUserForm');
    const banTargetLabel = banModal?.querySelector('[data-ban-target-label]');
    const banUserIdInput = banModal?.querySelector('[data-ban-user-id]');
    const banFeedback = banModal?.querySelector('[data-ban-feedback]');
    const customWrapper = banModal?.querySelector('[data-custom-until]');
    const customInput = customWrapper?.querySelector('input[name="custom_until"]');

    const toggleCustomField = show => {
        if (!customWrapper) return;
        customWrapper.hidden = !show;
        if (!show && customInput) {
            customInput.value = '';
        }
    };

    const openBanForm = (userId, username, sourceComplaintEl) => {
        if (!banModal || !banForm || !banUserIdInput) return;
        banForm.reset();
        banUserIdInput.value = userId;
        
        // Store the source complaint element for later marking as reviewed
        if (sourceComplaintEl) {
            banForm.dataset.sourceReportId = sourceComplaintEl.getAttribute('data-report-id') || '';
        } else {
            delete banForm.dataset.sourceReportId;
        }
        
        if (banTargetLabel) {
            banTargetLabel.textContent = username;
        }
        if (banFeedback) {
            banFeedback.textContent = '';
            banFeedback.style.color = '';
        }
        toggleCustomField(false);
        const defaultDuration = banForm.querySelector('input[name="duration"][value="24h"]');
        if (defaultDuration) {
            defaultDuration.checked = true;
        }
        openOverlay(banModal);
    };

    if (banForm) {
        banForm.addEventListener('change', event => {
            if (event.target.name === 'duration') {
                toggleCustomField(event.target.value === 'custom');
            }
        });

        banForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (!banUserIdInput?.value) {
                if (banFeedback) {
                    banFeedback.textContent = 'Select a user to ban.';
                    banFeedback.style.color = '#ff5c5c';
                }
                return;
            }

            const formData = new FormData(banForm);
            const durationValue = formData.get('duration');
            if (durationValue === 'custom') {
                if (!customInput?.value) {
                    if (banFeedback) {
                        banFeedback.textContent = 'Select a custom end time.';
                        banFeedback.style.color = '#ff5c5c';
                    }
                    return;
                }
                formData.set('custom_until', customInput.value.replace('T', ' ') + ':00');
            } else {
                formData.delete('custom_until');
            }

            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Admin&action=banUser', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (banFeedback) {
                    banFeedback.textContent = data.message || 'Unable to ban user.';
                    banFeedback.style.color = data.success ? '#30c48d' : '#ff5c5c';
                }
                if (data.success) {
                    // Mark the source complaint as reviewed if it exists
                    const sourceReportId = banForm.dataset.sourceReportId;
                    if (sourceReportId) {
                        const complaintItem = document.querySelector(`[data-report-id="${sourceReportId}"]`);
                        if (complaintItem) {
                            await markReportReviewed(parseInt(sourceReportId, 10), complaintItem);
                        }
                    }
                    
                    // Refresh the complaints board
                    refreshComplaintsBoard();
                    
                    setTimeout(() => closeOverlayEl(banModal), 1200);
                }
            } catch (error) {
                if (banFeedback) {
                    banFeedback.textContent = 'Network error. Please retry.';
                    banFeedback.style.color = '#ff5c5c';
                }
            }
        });
    }

    const disableModal = overlays.disable;
    const disableForm = document.getElementById('disableGroupForm');
    const disableTargetLabel = disableModal?.querySelector('[data-disable-target-label]');
    const disableGroupIdInput = disableModal?.querySelector('[data-disable-group-id]');
    const disableReportIdInput = disableModal?.querySelector('[data-disable-report-id]');
    const disableFeedback = disableModal?.querySelector('[data-disable-feedback]');
    const disableReasonInput = disableForm?.querySelector('[name="reason"]');
    const disableNotesInput = disableForm?.querySelector('textarea[name="notes"]');
    const disableDurationInputs = disableForm ? Array.from(disableForm.querySelectorAll('input[name="duration"]')) : [];
    const disableCustomWrapper = disableForm?.querySelector('[data-disable-custom]');
    const disableCustomInput = disableCustomWrapper?.querySelector('input[name="custom_until"]');
    let disableSourceElement = null;

    const toggleDisableCustom = show => {
        if (!disableCustomWrapper) return;
        disableCustomWrapper.hidden = !show;
        if (!show && disableCustomInput) {
            disableCustomInput.value = '';
        }
    };

    const openDisableGroupForm = (groupId, targetLabel, reportId, sourceElement) => {
        if (!disableModal || !disableForm || !disableGroupIdInput) return;
        disableForm.reset();
        disableGroupIdInput.value = groupId;
        if (disableReportIdInput) {
            disableReportIdInput.value = reportId && reportId > 0 ? reportId : '';
        }
        disableForm.dataset.sourceReportId = reportId && reportId > 0 ? String(reportId) : '';
        disableSourceElement = sourceElement || null;
        if (disableTargetLabel) {
            disableTargetLabel.textContent = targetLabel || `Group #${groupId}`;
        }
        if (disableFeedback) {
            disableFeedback.textContent = '';
            disableFeedback.style.color = '';
        }
        toggleDisableCustom(false);
        if (disableDurationInputs.length) {
            disableDurationInputs.forEach(input => {
                input.checked = input.value === '24h';
            });
        }
        openOverlay(disableModal);
    };

    if (disableForm) {
        disableForm.addEventListener('change', event => {
            if (event.target.name === 'duration') {
                toggleDisableCustom(event.target.value === 'custom');
            }
        });

        disableForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (!disableGroupIdInput?.value) {
                if (disableFeedback) {
                    disableFeedback.textContent = 'Select a group first.';
                    disableFeedback.style.color = '#ff5c5c';
                }
                return;
            }

            const reason = (disableReasonInput?.value || '').trim();
            if (!reason) {
                if (disableFeedback) {
                    disableFeedback.textContent = 'Reason is required.';
                    disableFeedback.style.color = '#ff5c5c';
                }
                return;
            }

            const formData = new FormData(disableForm);
            formData.set('group_id', disableGroupIdInput.value);
            formData.set('reason', reason);

            const durationValue = formData.get('duration') || '24h';
            if (durationValue === 'custom') {
                if (!disableCustomInput?.value) {
                    if (disableFeedback) {
                        disableFeedback.textContent = 'Select a custom end time.';
                        disableFeedback.style.color = '#ff5c5c';
                    }
                    return;
                }
                formData.set('custom_until', disableCustomInput.value.replace('T', ' ') + ':00');
            } else {
                formData.delete('custom_until');
            }

            const notes = (disableNotesInput?.value || '').trim();
            if (notes) {
                formData.set('notes', notes);
            } else {
                formData.delete('notes');
            }

            if (disableReportIdInput?.value) {
                formData.set('report_id', disableReportIdInput.value);
            } else {
                formData.delete('report_id');
            }

            const payload = new URLSearchParams();
            formData.forEach((value, key) => {
                payload.append(key, value);
            });

            try {
                const response = await fetch(`${BASE_PATH}index.php?controller=Admin&action=disableGroup`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString()
                });
                const data = await response.json();
                if (disableFeedback) {
                    disableFeedback.textContent = data.message || 'Unable to disable group.';
                    disableFeedback.style.color = data.success ? '#30c48d' : '#ff5c5c';
                }
                if (data.success) {
                    const sourceReportId = parseInt(disableForm.dataset.sourceReportId || '0', 10);
                    if (sourceReportId) {
                        const sourceEl = disableSourceElement || document.querySelector(`[data-report-id="${sourceReportId}"]`);
                        await markReportReviewed(sourceReportId, sourceEl || null);
                    } else {
                        refreshComplaintsBoard();
                    }
                    setTimeout(() => {
                        closeOverlayEl(disableModal);
                        disableSourceElement = null;
                    }, 1200);
                }
            } catch (error) {
                if (disableFeedback) {
                    disableFeedback.textContent = 'Network error. Please retry.';
                    disableFeedback.style.color = '#ff5c5c';
                }
            }
        });
    }

    document.addEventListener('click', event => {
        const previewTrigger = event.target.closest('[data-preview-post]');
        if (previewTrigger) {
            event.preventDefault();
            const postId = parseInt(previewTrigger.getAttribute('data-preview-post'), 10);
            if (postId) {
                const complaintItem = previewTrigger.closest('[data-report-id]');
                const reportId = complaintItem ? parseInt(complaintItem.getAttribute('data-report-id'), 10) : 0;
                openPostPreview(postId, {
                    reportId,
                    element: complaintItem || null
                });
            }
            return;
        }

        const banTrigger = event.target.closest('[data-ban-user]');
        if (banTrigger) {
            event.preventDefault();
            const userId = parseInt(banTrigger.getAttribute('data-ban-user'), 10);
            if (userId) {
                const username = banTrigger.getAttribute('data-ban-username') || `User #${userId}`;
                const complaintItem = banTrigger.closest('[data-report-id]');
                openBanForm(userId, username, complaintItem);
            }
            return;
        }

        const removeTrigger = event.target.closest('[data-remove-post]');
        if (removeTrigger) {
            event.preventDefault();
            const postId = parseInt(removeTrigger.getAttribute('data-remove-post'), 10);
            if (postId) {
                const complaintItem = removeTrigger.closest('[data-report-id]');
                const reportId = complaintItem ? parseInt(complaintItem.getAttribute('data-report-id'), 10) : 0;
                const label = removeTrigger.getAttribute('data-target-label') || `Post #${postId}`;
                handlePostRemoval(postId, reportId, label, complaintItem || null);
            }
            return;
        }

        const disableTrigger = event.target.closest('[data-disable-group]');
        if (disableTrigger) {
            event.preventDefault();
            const groupId = parseInt(disableTrigger.getAttribute('data-disable-group'), 10);
            if (groupId) {
                const complaintItem = disableTrigger.closest('[data-report-id]');
                const reportId = complaintItem ? parseInt(complaintItem.getAttribute('data-report-id'), 10) : 0;
                const label = disableTrigger.getAttribute('data-target-label') || `Group #${groupId}`;
                openDisableGroupForm(groupId, label, reportId, complaintItem || null);
            }
            return;
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if (overlays.preview?.classList.contains('open')) {
            closeOverlayEl(overlays.preview);
            return;
        }
        if (overlays.ban?.classList.contains('open')) {
            closeOverlayEl(overlays.ban);
            return;
        }
        if (overlays.complaints?.classList.contains('open')) {
            closeOverlayEl(overlays.complaints);
            return;
        }
        if (quickModal?.classList.contains('open')) {
            closeQuickModal();
        }
    });
});
</script>
</body>
</html>
