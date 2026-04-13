<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/FriendModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/NotificationsModel.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../../config/config.php';

class AdminController {
    private UserModel $userModel;
    private PostModel $postModel;
    private GroupModel $groupModel;
    private FriendModel $friendModel;
    private SettingsModel $settingsModel;
    private ReportModel $reportModel;
    private NotificationsModel $notificationsModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit;
        }

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }

        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->groupModel = new GroupModel();
        $this->friendModel = new FriendModel();
        $this->settingsModel = new SettingsModel();
        $this->reportModel = new ReportModel();
        $this->notificationsModel = new NotificationsModel();
    }

    public function index() {
        $userStats = $this->userModel->getStats();
        $recentUsers = $this->userModel->getRecentUsers(6);
        $dailyActiveUsers = $this->userModel->getDailyActiveUsers(7);
        $postStats = $this->postModel->getStats();
        $groupStats = $this->groupModel->getStats();
        $friendStats = $this->friendModel->getStats();
        $recentGroups = $this->groupModel->getRecentGroups(5);
        $trendingGroups = $this->groupModel->getTrendingGroups(4);
        $groupReviewSnapshot = $this->groupModel->getReviewSnapshot();
        $trendingPosts = $this->postModel->getTrendingPosts(5, (int)$_SESSION['user_id']);
        $moderationSnapshot = $this->postModel->getModerationSnapshot();
        $complaintStats = $this->reportModel->getComplaintStats(7);
        $recentComplaints = $this->reportModel->getRecentComplaints(6);
        $complaintsByStatus = [
            'received' => $this->reportModel->getComplaintsByStatus('received', 15),
            'pending' => $this->reportModel->getComplaintsByStatus('pending', 15),
            'resolved' => $this->reportModel->getComplaintsByStatus('resolved', 15)
        ];
        $settingsSummary = $this->settingsModel->getSettingsSummary();

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function generateReport() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $rawType = strtolower(trim((string)($_GET['type'] ?? '')));
        $reportType = str_replace('-', '_', $rawType);

        $builders = [
            'user_activity' => 'buildUserActivityReportCsv',
            'moderation' => 'buildModerationReportCsv',
            'group_health' => 'buildGroupHealthReportCsv',
            'content_engagement' => 'buildContentEngagementReportCsv'
        ];

        if (!isset($builders[$reportType])) {
            http_response_code(400);
            echo 'Unknown report type';
            return;
        }

        try {
            $builder = $builders[$reportType];
            $report = $this->{$builder}();
            $filename = (string)($report['filename'] ?? ('admin-report-' . date('Ymd-His') . '.csv'));
            $rows = (array)($report['rows'] ?? []);
            $this->streamCsvDownload($filename, $rows);
        } catch (Throwable $e) {
            error_log('generateReport error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Unable to generate report at this time';
        }
    }

    public function generateReportPdf() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $rawType = strtolower(trim((string)($_GET['type'] ?? '')));
        $reportType = str_replace('-', '_', $rawType);
        $autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';

        $builders = [
            'user_activity' => 'buildUserActivityReportPayload',
            'moderation' => 'buildModerationReportPayload',
            'group_health' => 'buildGroupHealthReportPayload',
            'content_engagement' => 'buildContentEngagementReportPayload'
        ];

        if (!isset($builders[$reportType])) {
            http_response_code(400);
            echo 'Unknown report type';
            return;
        }

        try {
            $builder = $builders[$reportType];
            $report = $this->{$builder}();
            $report['generated_at'] = date('Y-m-d H:i:s');
            $report['generated_by'] = trim((string)($_SESSION['first_name'] ?? '') . ' ' . (string)($_SESSION['last_name'] ?? ''));
            if ($report['generated_by'] === '') {
                $report['generated_by'] = (string)($_SESSION['username'] ?? 'Admin');
            }
            $report['type'] = $reportType;
            $report['csv_export_url'] = BASE_PATH . 'index.php?controller=Admin&action=generateReport&type=' . urlencode($reportType);

            require __DIR__ . '/../views/admin/report-template.php';
        } catch (Throwable $e) {
            error_log('generateReportPdf error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Unable to generate PDF report at this time';
        }
    }

    private function buildUserActivityReportPayload(): array {
        $userStats = $this->userModel->getStats();
        $dailyActive = $this->userModel->getDailyActiveUsers(14);
        $recentUsers = $this->userModel->getRecentUsers(20);

        $trendRows = [];
        $labels = $dailyActive['labels'] ?? [];
        $counts = $dailyActive['counts'] ?? [];
        $max = min(count($labels), count($counts));
        for ($i = 0; $i < $max; $i++) {
            $trendRows[] = [(string)$labels[$i], (int)$counts[$i]];
        }

        $signupRows = [];
        foreach ($recentUsers as $user) {
            $fullName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
            $signupRows[] = [
                $fullName !== '' ? $fullName : '—',
                '@' . (string)($user['username'] ?? ''),
                (string)($user['email'] ?? ''),
                ucfirst((string)($user['role'] ?? 'user')),
                $this->formatReportDate($user['created_at'] ?? null)
            ];
        }

        return [
            'title' => 'User Activity Overview',
            'subtitle' => '14-day behavior snapshot for growth and retention monitoring.',
            'summary' => [
                ['label' => 'Total users', 'value' => (int)($userStats['total_users'] ?? 0)],
                ['label' => 'Active users', 'value' => (int)($userStats['active_users'] ?? 0)],
                ['label' => 'New users (7d)', 'value' => (int)($userStats['new_users_last_7'] ?? 0)],
                ['label' => 'Latest daily active', 'value' => (int)($dailyActive['latest_count'] ?? 0)]
            ],
            'sections' => [
                [
                    'title' => 'Daily active trend (last 14 days)',
                    'columns' => ['Day', 'Active users'],
                    'rows' => $trendRows
                ],
                [
                    'title' => 'Recent signups',
                    'columns' => ['Name', 'Username', 'Email', 'Role', 'Joined at'],
                    'rows' => $signupRows
                ]
            ]
        ];
    }

    private function buildModerationReportPayload(): array {
        $stats = $this->reportModel->getComplaintStats(14);
        $pending = $this->reportModel->getComplaintsByStatus('pending', 30);

        $typeRows = [];
        foreach (($stats['type_breakdown'] ?? []) as $typeRow) {
            $typeRows[] = [
                (string)($typeRow['label'] ?? 'Other'),
                (int)($typeRow['count'] ?? 0)
            ];
        }

        $trendRows = [];
        $trendLabels = $stats['trend']['labels'] ?? [];
        $trendCounts = $stats['trend']['counts'] ?? [];
        $trendMax = min(count($trendLabels), count($trendCounts));
        for ($i = 0; $i < $trendMax; $i++) {
            $trendRows[] = [(string)$trendLabels[$i], (int)$trendCounts[$i]];
        }

        $queueRows = [];
        foreach ($pending as $item) {
            $queueRows[] = [
                (int)($item['report_id'] ?? 0),
                ucfirst((string)($item['report_type'] ?? 'other')),
                (string)($item['target_label'] ?? 'General'),
                (string)($item['reporter_username'] ?? 'unknown'),
                ucfirst((string)($item['status'] ?? 'pending')),
                $this->formatReportDate($item['created_at'] ?? null),
                $this->compactReportText((string)($item['description'] ?? ''), 140)
            ];
        }

        return [
            'title' => 'Moderation and Complaints',
            'subtitle' => 'Operational risk view with queue pressure and complaint composition.',
            'summary' => [
                ['label' => 'Total complaints', 'value' => (int)($stats['total_reports'] ?? 0)],
                ['label' => 'Pending', 'value' => (int)($stats['pending_reports'] ?? 0)],
                ['label' => 'Reviewed', 'value' => (int)($stats['reviewed_reports'] ?? 0)],
                ['label' => 'Resolved', 'value' => (int)($stats['resolved_reports'] ?? 0)],
                ['label' => 'New complaints (7d)', 'value' => (int)($stats['recent_reports'] ?? 0)]
            ],
            'sections' => [
                [
                    'title' => 'Complaint type breakdown',
                    'columns' => ['Type', 'Count'],
                    'rows' => $typeRows
                ],
                [
                    'title' => 'Complaint trend (14 days)',
                    'columns' => ['Day', 'Count'],
                    'rows' => $trendRows
                ],
                [
                    'title' => 'Pending queue',
                    'columns' => ['Report ID', 'Type', 'Target', 'Reporter', 'Status', 'Created at', 'Description'],
                    'rows' => $queueRows
                ]
            ]
        ];
    }

    private function buildGroupHealthReportPayload(): array {
        $groupStats = $this->groupModel->getStats();
        $snapshot = $this->groupModel->getReviewSnapshot();
        $trendingGroups = $this->groupModel->getTrendingGroups(20, (int)($_SESSION['user_id'] ?? 0));
        $recentGroups = $this->groupModel->getRecentGroups(20);

        $trendingRows = [];
        foreach ($trendingGroups as $group) {
            $trendingRows[] = [
                (string)($group['name'] ?? 'Group'),
                ucfirst((string)($group['privacy_status'] ?? 'public')),
                (int)($group['member_count'] ?? 0),
                (int)($group['recent_joins'] ?? 0),
                (int)($group['recent_posts'] ?? 0),
                (int)($group['recent_comments'] ?? 0),
                round((float)($group['engagement_score'] ?? 0), 2)
            ];
        }

        $recentRows = [];
        foreach ($recentGroups as $group) {
            $recentRows[] = [
                (string)($group['name'] ?? 'Group'),
                ucfirst((string)($group['privacy_status'] ?? 'public')),
                (int)($group['member_count'] ?? 0),
                (string)($group['focus'] ?? '—'),
                (string)($group['tag'] ?? '—'),
                $this->formatReportDate($group['created_at'] ?? null)
            ];
        }

        return [
            'title' => 'Group Health and Governance',
            'subtitle' => 'Community structure quality and collaboration velocity snapshot.',
            'summary' => [
                ['label' => 'Total groups', 'value' => (int)($groupStats['total_groups'] ?? 0)],
                ['label' => 'Active groups', 'value' => (int)($groupStats['active_groups'] ?? 0)],
                ['label' => 'Disabled groups', 'value' => (int)($groupStats['inactive_groups'] ?? 0)],
                ['label' => 'New groups (7d)', 'value' => (int)($snapshot['new_last_7'] ?? 0)],
                ['label' => 'Pending join requests', 'value' => (int)($snapshot['pending_requests'] ?? 0)],
                ['label' => 'Avg members/group', 'value' => (float)($snapshot['avg_members'] ?? 0)]
            ],
            'sections' => [
                [
                    'title' => 'Top trending groups',
                    'columns' => ['Group', 'Privacy', 'Members', 'Joins (7d)', 'Posts (7d)', 'Comments (7d)', 'Engagement score'],
                    'rows' => $trendingRows
                ],
                [
                    'title' => 'Recently created groups',
                    'columns' => ['Group', 'Privacy', 'Members', 'Focus', 'Tag', 'Created at'],
                    'rows' => $recentRows
                ]
            ]
        ];
    }

    private function buildContentEngagementReportPayload(): array {
        $postStats = $this->postModel->getStats();
        $moderation = $this->postModel->getModerationSnapshot();
        $trendingPosts = $this->postModel->getTrendingPosts(25, (int)($_SESSION['user_id'] ?? 0));

        $postRows = [];
        foreach ($trendingPosts as $post) {
            $author = trim((string)($post['first_name'] ?? '') . ' ' . (string)($post['last_name'] ?? ''));
            if ($author === '') {
                $author = (string)($post['username'] ?? 'Unknown');
            }

            $postRows[] = [
                (int)($post['post_id'] ?? 0),
                $author,
                (string)($post['group_name'] ?? '—'),
                (int)($post['upvote_count'] ?? 0),
                (int)($post['comment_count'] ?? 0),
                (int)($post['engagement_score'] ?? 0),
                $this->formatReportDate($post['created_at'] ?? null),
                $this->compactReportText((string)($post['content'] ?? ''), 120)
            ];
        }

        return [
            'title' => 'Content Engagement and Risk',
            'subtitle' => 'Feed performance indicators connected to moderation signals.',
            'summary' => [
                ['label' => 'Total posts', 'value' => (int)($postStats['total_posts'] ?? 0)],
                ['label' => 'Group posts', 'value' => (int)($postStats['group_posts'] ?? 0)],
                ['label' => 'Event posts', 'value' => (int)($postStats['event_posts'] ?? 0)],
                ['label' => 'Total reports', 'value' => (int)($moderation['total_reports'] ?? 0)],
                ['label' => 'Reports (7d)', 'value' => (int)($moderation['reports_last_7'] ?? 0)],
                ['label' => 'Post reports', 'value' => (int)($moderation['post_reports'] ?? 0)]
            ],
            'sections' => [
                [
                    'title' => 'Top engagement posts',
                    'columns' => ['Post ID', 'Author', 'Group', 'Upvotes', 'Comments', 'Engagement', 'Created at', 'Excerpt'],
                    'rows' => $postRows
                ]
            ]
        ];
    }

    private function buildUserActivityReportCsv(): array {
        $userStats = $this->userModel->getStats();
        $dailyActive = $this->userModel->getDailyActiveUsers(14);
        $recentUsers = $this->userModel->getRecentUsers(20);

        $rows = [
            ['Hanthana Admin Report', 'User Activity Overview'],
            ['Generated at', date('Y-m-d H:i:s')],
            ['Coverage', 'Last 14 days'],
            [],
            ['Metric', 'Value'],
            ['Total users', (int)($userStats['total_users'] ?? 0)],
            ['Active users', (int)($userStats['active_users'] ?? 0)],
            ['New users (last 7 days)', (int)($userStats['new_users_last_7'] ?? 0)],
            ['Latest daily active users', (int)($dailyActive['latest_count'] ?? 0)],
            [],
            ['Daily active users trend'],
            ['Label', 'Active users']
        ];

        $labels = $dailyActive['labels'] ?? [];
        $counts = $dailyActive['counts'] ?? [];
        $max = min(count($labels), count($counts));
        for ($i = 0; $i < $max; $i++) {
            $rows[] = [(string)$labels[$i], (int)$counts[$i]];
        }

        $rows[] = [];
        $rows[] = ['Recent signups'];
        $rows[] = ['User ID', 'Name', 'Username', 'Email', 'Role', 'Joined at'];

        foreach ($recentUsers as $user) {
            $fullName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
            $rows[] = [
                (int)($user['user_id'] ?? 0),
                $fullName !== '' ? $fullName : '—',
                (string)($user['username'] ?? ''),
                (string)($user['email'] ?? ''),
                (string)($user['role'] ?? 'user'),
                $this->formatReportDate($user['created_at'] ?? null)
            ];
        }

        return [
            'filename' => 'user-activity-report-' . date('Ymd-His') . '.csv',
            'rows' => $rows
        ];
    }

    private function buildModerationReportCsv(): array {
        $stats = $this->reportModel->getComplaintStats(14);
        $pending = $this->reportModel->getComplaintsByStatus('pending', 30);
        $recent = $this->reportModel->getRecentComplaints(30);

        $rows = [
            ['Hanthana Admin Report', 'Moderation and Complaints'],
            ['Generated at', date('Y-m-d H:i:s')],
            ['Coverage', 'Last 14 days trend + latest queue snapshot'],
            [],
            ['Metric', 'Value'],
            ['Total complaints', (int)($stats['total_reports'] ?? 0)],
            ['Pending complaints', (int)($stats['pending_reports'] ?? 0)],
            ['Reviewed complaints', (int)($stats['reviewed_reports'] ?? 0)],
            ['Resolved complaints', (int)($stats['resolved_reports'] ?? 0)],
            ['New complaints (last 7 days)', (int)($stats['recent_reports'] ?? 0)],
            []
        ];

        $rows[] = ['Complaint type breakdown'];
        $rows[] = ['Type', 'Count'];
        foreach (($stats['type_breakdown'] ?? []) as $typeRow) {
            $rows[] = [
                (string)($typeRow['label'] ?? 'Other'),
                (int)($typeRow['count'] ?? 0)
            ];
        }

        $rows[] = [];
        $rows[] = ['Complaint trend'];
        $rows[] = ['Label', 'Count'];
        $trendLabels = $stats['trend']['labels'] ?? [];
        $trendCounts = $stats['trend']['counts'] ?? [];
        $trendMax = min(count($trendLabels), count($trendCounts));
        for ($i = 0; $i < $trendMax; $i++) {
            $rows[] = [(string)$trendLabels[$i], (int)$trendCounts[$i]];
        }

        $rows[] = [];
        $rows[] = ['Pending complaint queue'];
        $rows[] = ['Report ID', 'Type', 'Target', 'Reporter', 'Status', 'Created at', 'Description'];
        foreach ($pending as $item) {
            $rows[] = [
                (int)($item['report_id'] ?? 0),
                (string)($item['report_type'] ?? 'other'),
                (string)($item['target_label'] ?? 'General'),
                (string)($item['reporter_username'] ?? 'unknown'),
                (string)($item['status'] ?? 'pending'),
                $this->formatReportDate($item['created_at'] ?? null),
                $this->compactReportText((string)($item['description'] ?? ''), 220)
            ];
        }

        $rows[] = [];
        $rows[] = ['Most recent complaints'];
        $rows[] = ['Report ID', 'Type', 'Target', 'Reporter', 'Status', 'Created at'];
        foreach ($recent as $item) {
            $rows[] = [
                (int)($item['report_id'] ?? 0),
                (string)($item['report_type'] ?? 'other'),
                (string)($item['target_label'] ?? 'General'),
                (string)($item['reporter_username'] ?? 'unknown'),
                (string)($item['status'] ?? 'pending'),
                $this->formatReportDate($item['created_at'] ?? null)
            ];
        }

        return [
            'filename' => 'moderation-report-' . date('Ymd-His') . '.csv',
            'rows' => $rows
        ];
    }

    private function buildGroupHealthReportCsv(): array {
        $groupStats = $this->groupModel->getStats();
        $reviewSnapshot = $this->groupModel->getReviewSnapshot();
        $trendingGroups = $this->groupModel->getTrendingGroups(20, (int)($_SESSION['user_id'] ?? 0));
        $recentGroups = $this->groupModel->getRecentGroups(20);

        $rows = [
            ['Hanthana Admin Report', 'Group Health and Governance'],
            ['Generated at', date('Y-m-d H:i:s')],
            ['Coverage', 'Current snapshot + weekly activity indicators'],
            [],
            ['Metric', 'Value'],
            ['Total groups', (int)($groupStats['total_groups'] ?? 0)],
            ['Active groups', (int)($groupStats['active_groups'] ?? 0)],
            ['Disabled groups', (int)($groupStats['inactive_groups'] ?? 0)],
            ['Public groups', (int)($groupStats['public_groups'] ?? 0)],
            ['Private/secret groups', (int)($groupStats['private_groups'] ?? 0)],
            ['New groups (last 7 days)', (int)($reviewSnapshot['new_last_7'] ?? 0)],
            ['Pending join requests', (int)($reviewSnapshot['pending_requests'] ?? 0)],
            ['Average members per active group', (float)($reviewSnapshot['avg_members'] ?? 0)],
            [],
            ['Top trending groups'],
            ['Group ID', 'Name', 'Privacy', 'Members', 'Recent joins', 'Recent posts', 'Recent comments', 'Engagement score']
        ];

        foreach ($trendingGroups as $group) {
            $rows[] = [
                (int)($group['group_id'] ?? 0),
                (string)($group['name'] ?? 'Group'),
                (string)($group['privacy_status'] ?? 'public'),
                (int)($group['member_count'] ?? 0),
                (int)($group['recent_joins'] ?? 0),
                (int)($group['recent_posts'] ?? 0),
                (int)($group['recent_comments'] ?? 0),
                round((float)($group['engagement_score'] ?? 0), 2)
            ];
        }

        $rows[] = [];
        $rows[] = ['Recently created groups'];
        $rows[] = ['Group ID', 'Name', 'Privacy', 'Members', 'Focus', 'Tag', 'Created at'];
        foreach ($recentGroups as $group) {
            $rows[] = [
                (int)($group['group_id'] ?? 0),
                (string)($group['name'] ?? 'Group'),
                (string)($group['privacy_status'] ?? 'public'),
                (int)($group['member_count'] ?? 0),
                (string)($group['focus'] ?? ''),
                (string)($group['tag'] ?? ''),
                $this->formatReportDate($group['created_at'] ?? null)
            ];
        }

        return [
            'filename' => 'group-health-report-' . date('Ymd-His') . '.csv',
            'rows' => $rows
        ];
    }

    private function buildContentEngagementReportCsv(): array {
        $postStats = $this->postModel->getStats();
        $moderation = $this->postModel->getModerationSnapshot();
        $trendingPosts = $this->postModel->getTrendingPosts(25, (int)($_SESSION['user_id'] ?? 0));

        $rows = [
            ['Hanthana Admin Report', 'Content Engagement and Risk'],
            ['Generated at', date('Y-m-d H:i:s')],
            ['Coverage', 'Top 25 trending posts + moderation signals'],
            [],
            ['Metric', 'Value'],
            ['Total posts', (int)($postStats['total_posts'] ?? 0)],
            ['Group posts', (int)($postStats['group_posts'] ?? 0)],
            ['Event posts', (int)($postStats['event_posts'] ?? 0)],
            ['Total reports', (int)($moderation['total_reports'] ?? 0)],
            ['Reports (last 7 days)', (int)($moderation['reports_last_7'] ?? 0)],
            ['Post reports', (int)($moderation['post_reports'] ?? 0)],
            ['Comment reports', (int)($moderation['comment_reports'] ?? 0)],
            ['Group reports', (int)($moderation['group_reports'] ?? 0)],
            [],
            ['Top engagement posts'],
            ['Post ID', 'Author', 'Group', 'Upvotes', 'Comments', 'Engagement score', 'Created at', 'Excerpt']
        ];

        foreach ($trendingPosts as $post) {
            $author = trim((string)($post['first_name'] ?? '') . ' ' . (string)($post['last_name'] ?? ''));
            if ($author === '') {
                $author = (string)($post['username'] ?? 'Unknown');
            }

            $rows[] = [
                (int)($post['post_id'] ?? 0),
                $author,
                (string)($post['group_name'] ?? '—'),
                (int)($post['upvote_count'] ?? 0),
                (int)($post['comment_count'] ?? 0),
                (int)($post['engagement_score'] ?? 0),
                $this->formatReportDate($post['created_at'] ?? null),
                $this->compactReportText((string)($post['content'] ?? ''), 180)
            ];
        }

        return [
            'filename' => 'content-engagement-report-' . date('Ymd-His') . '.csv',
            'rows' => $rows
        ];
    }

    private function streamCsvDownload(string $filename, array $rows): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) ?: ('admin-report-' . date('Ymd-His') . '.csv');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            http_response_code(500);
            echo 'Unable to open export stream';
            return;
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $row = [(string)$row];
            }

            $normalized = array_map(static function($value): string {
                if ($value === null) {
                    return '';
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                if (is_scalar($value)) {
                    return (string)$value;
                }
                return json_encode($value) ?: '';
            }, $row);

            fputcsv($output, $normalized);
        }

        fclose($output);
        exit;
    }

    private function compactReportText(string $value, int $maxLength = 180): string {
        $clean = trim(preg_replace('/\s+/', ' ', $value));
        if ($clean === '') {
            return '—';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($clean) > $maxLength ? mb_substr($clean, 0, $maxLength - 1) . '…' : $clean;
        }

        return strlen($clean) > $maxLength ? substr($clean, 0, $maxLength - 1) . '…' : $clean;
    }

    private function formatReportDate(?string $value): string {
        if (!$value) {
            return '—';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return (string)$value;
        }

        return date('Y-m-d H:i', $timestamp);
    }

    public function banUser() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $customUntil = trim($_POST['custom_until'] ?? '');
        $reason = trim($_POST['reason'] ?? 'Policy violation');
        $notes = trim($_POST['notes'] ?? '');
        $reportId = (int)($_POST['report_id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user id']);
            return;
        }

        if ($userId === (int)$_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
            return;
        }

        $banUntil = $this->resolveBanUntil($duration, $customUntil);

        if (!$banUntil) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid duration']);
            return;
        }

        $banUntilFormatted = $banUntil->format('Y-m-d H:i:s');
        $result = $this->userModel->banUser($userId, $banUntilFormatted, $reason, (int)$_SESSION['user_id'], $notes);

        if ($result) {
            if ($reportId > 0) {
                $this->reportModel->resolveReport($reportId, (int)$_SESSION['user_id']);
            }
            $this->logAdminAction('user_ban', $userId, null, null, $reason);
            echo json_encode([
                'success' => true,
                'message' => 'User banned until ' . $banUntil->format('M d, Y H:i')
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to ban user']);
        }
    }

    public function previewPost() {
        header('Content-Type: application/json');
        $postId = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing post id']);
            return;
        }

        $post = $this->postModel->getPostById($postId);
        if ($post) {
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
        }
    }

    public function removePost() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        $reportId = (int)($_POST['report_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post id']);
            return;
        }

        $success = $this->postModel->removePostByAdmin($postId);
        if ($success) {
            if ($reportId > 0) {
                $this->reportModel->resolveReport($reportId, (int)$_SESSION['user_id']);
            }
            $this->logAdminAction('post_remove', null, $postId, null, 'Post removed from admin dashboard');
            echo json_encode(['success' => true, 'message' => 'Post removed successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to remove post.']);
        }
    }

    public function disableGroup() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = (int)($_POST['group_id'] ?? 0);
        $reportId = (int)($_POST['report_id'] ?? 0);
        $duration = trim($_POST['duration'] ?? '24h');
        $customUntil = trim($_POST['custom_until'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($groupId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid group id']);
            return;
        }

        if ($reason === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reason is required']);
            return;
        }

        $groupRecord = $this->groupModel->getById($groupId);
        $groupAdmins = $this->groupModel->getGroupAdmins($groupId);

        $disableUntil = $this->resolveBanUntil($duration, $customUntil ?: null);
        if (!$disableUntil) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Select a valid duration']);
            return;
        }

        $disableUntilFormatted = $disableUntil->format('Y-m-d H:i:s');
        $success = $this->groupModel->disableGroup(
            $groupId,
            $disableUntilFormatted,
            $reason,
            (int)$_SESSION['user_id'],
            $notes !== '' ? $notes : null
        );
        if ($success) {
            if ($reportId > 0) {
                $this->reportModel->resolveReport($reportId, (int)$_SESSION['user_id']);
            }
            $this->logAdminAction('group_remove', null, null, $groupId, $reason);
            $durationLabels = [
                '24h' => '24 hours',
                '72h' => '3 days',
                '1w' => '1 week',
                '2w' => '2 weeks',
                '1m' => '1 month',
                'custom' => $disableUntil->format('M d, Y H:i')
            ];
            $durationLabel = $durationLabels[$duration] ?? $disableUntil->format('M d, Y H:i');

            $this->notifyGroupAdminsAboutDisable(
                $groupId,
                $groupRecord,
                $groupAdmins,
                $reason,
                $durationLabel,
                $disableUntil
            );

            $message = sprintf('Group disabled. Duration logged: %s. Reason: %s', $durationLabel, $reason);
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to disable group.']);
        }
    }

    private function notifyGroupAdminsAboutDisable(int $groupId, ?array $groupRecord, array $groupAdmins, string $reason, string $durationLabel, DateTime $disableUntil): void {
        $recipientIds = [];
        foreach ($groupAdmins as $admin) {
            $adminId = (int)($admin['user_id'] ?? 0);
            if ($adminId > 0) {
                $recipientIds[] = $adminId;
            }
        }

        $creatorId = (int)($groupRecord['created_by'] ?? 0);
        if ($creatorId > 0) {
            $recipientIds[] = $creatorId;
        }

        $recipientIds = array_values(array_unique($recipientIds));
        if (empty($recipientIds)) {
            return;
        }

        $groupName = trim((string)($groupRecord['name'] ?? ''));
        if ($groupName === '') {
            $groupName = 'Group #' . $groupId;
        }

        $actingAdminId = (int)($_SESSION['user_id'] ?? 0);
        $disableUntilLabel = $disableUntil->format('M d, Y H:i');
        $safeReason = trim($reason) !== '' ? trim($reason) : 'Policy violation';
        $groupActionUrl = BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . $groupId;

        $title = 'Group disabled by moderation';
        $message = sprintf(
            'Your group "%s" has been disabled for %s (until %s). Reason: %s.',
            $groupName,
            $durationLabel,
            $disableUntilLabel,
            $safeReason
        );

        foreach ($recipientIds as $recipientId) {
            if ($recipientId <= 0) {
                continue;
            }

            $created = $this->notificationsModel->createNotification(
                $recipientId,
                $actingAdminId > 0 ? $actingAdminId : null,
                'system_alert',
                $title,
                $message,
                $groupActionUrl,
                'high',
                $groupId,
                'group'
            );

            // Backward-compat fallback for deployments where enum values differ from latest schema.
            if (!$created) {
                $fallbackCreated = $this->notificationsModel->createNotification(
                    $recipientId,
                    $actingAdminId > 0 ? $actingAdminId : null,
                    'message',
                    $title,
                    $message,
                    $groupActionUrl,
                    'high',
                    null,
                    null
                );

                if (!$fallbackCreated) {
                    error_log('AdminController:disableGroup notification insert failed for recipient ' . $recipientId . ' and group ' . $groupId);
                }
            }
        }
    }

    public function markReportReviewed() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid report id']);
            return;
        }

        $adminId = (int)$_SESSION['user_id'];
        $result = $this->reportModel->markReviewed($reportId, $adminId);
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to update complaint']);
        }
    }

    public function complaintsBoardData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $complaintStats = $this->reportModel->getComplaintStats(7);
        $recentComplaints = $this->reportModel->getRecentComplaints(6);
        $complaintsByStatus = [
            'received' => $this->reportModel->getComplaintsByStatus('received', 15),
            'pending' => $this->reportModel->getComplaintsByStatus('pending', 15),
            'resolved' => $this->reportModel->getComplaintsByStatus('resolved', 15)
        ];

        echo json_encode([
            'success' => true,
            'stats' => $complaintStats,
            'recent' => $recentComplaints,
            'byStatus' => $complaintsByStatus
        ]);
    }

    public function quickTrendingData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $limit = (int)($_GET['limit'] ?? 15);
        $limit = max(5, min(50, $limit));
        $search = strtolower(trim((string)($_GET['q'] ?? '')));

        $moderation = $this->postModel->getModerationSnapshot();
        $posts = $this->postModel->getTrendingPosts($limit * 2, (int)($_SESSION['user_id'] ?? 0));

        if ($search !== '') {
            $posts = array_values(array_filter($posts, static function($post) use ($search) {
                $author = strtolower(trim((string)($post['first_name'] ?? '') . ' ' . (string)($post['last_name'] ?? '')));
                if ($author === '') {
                    $author = strtolower((string)($post['username'] ?? ''));
                }
                $content = strtolower((string)($post['content'] ?? ''));
                return strpos($author, $search) !== false || strpos($content, $search) !== false;
            }));
        }

        $posts = array_slice($posts, 0, $limit);

        echo json_encode([
            'success' => true,
            'moderation' => $moderation,
            'posts' => $posts
        ]);
    }

    public function groupsDirectoryData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $status = trim((string)($_GET['status'] ?? 'all'));
        $search = trim((string)($_GET['q'] ?? ''));
        $limit = (int)($_GET['limit'] ?? 400);

        $groups = $this->groupModel->getAdminGroupDirectory($status, $search, $limit);
        $groupStats = $this->groupModel->getStats();

        echo json_encode([
            'success' => true,
            'groups' => $groups,
            'summary' => [
                'total_groups' => (int)($groupStats['total_groups'] ?? 0),
                'active_groups' => (int)($groupStats['active_groups'] ?? 0),
                'inactive_groups' => (int)($groupStats['inactive_groups'] ?? 0)
            ]
        ]);
    }

    public function enableGroup() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = (int)($_POST['group_id'] ?? 0);
        if ($groupId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid group id']);
            return;
        }

        $enabled = $this->groupModel->enableGroup($groupId);
        if ($enabled) {
            echo json_encode(['success' => true, 'message' => 'Group re-enabled successfully.']);
        } else {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Group is already active or cannot be enabled.']);
        }
    }

    private function logAdminAction(string $actionType, ?int $targetUserId = null, ?int $targetPostId = null, ?int $targetGroupId = null, ?string $reason = null): void {
        try {
            $db = new Database();
            $connection = $db->getConnection();

            $existsStmt = $connection->prepare("SHOW TABLES LIKE 'AdminActions'");
            $existsStmt->execute();
            if (!$existsStmt->fetchColumn()) {
                return;
            }

            $sql = "INSERT INTO AdminActions
                        (admin_id, action_type, target_user_id, target_post_id, target_group_id, reason)
                    VALUES
                        (:admin_id, :action_type, :target_user_id, :target_post_id, :target_group_id, :reason)";

            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':admin_id' => (int)$_SESSION['user_id'],
                ':action_type' => $actionType,
                ':target_user_id' => $targetUserId,
                ':target_post_id' => $targetPostId,
                ':target_group_id' => $targetGroupId,
                ':reason' => ($reason !== null && trim($reason) !== '') ? trim($reason) : null
            ]);
        } catch (Throwable $e) {
            error_log('logAdminAction error: ' . $e->getMessage());
        }
    }

    private function resolveBanUntil(string $duration, ?string $customUntil): ?DateTime {
        $duration = strtolower($duration);
        $now = new DateTime();

        $map = [
            '24h' => 'PT24H',
            '72h' => 'PT72H',
            '1w' => 'P7D',
            '2w' => 'P14D',
            '1m' => 'P1M'
        ];

        if (isset($map[$duration])) {
            try {
                $now->add(new DateInterval($map[$duration]));
                return $now;
            } catch (Exception $e) {
                return null;
            }
        }

        if ($duration === 'custom' && !empty($customUntil)) {
            try {
                $customDate = new DateTime($customUntil);
                if ($customDate <= new DateTime()) {
                    return null;
                }
                return $customDate;
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }
}
?>
