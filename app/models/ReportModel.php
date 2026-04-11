<?php
require_once __DIR__ . '/../core/Database.php';

class ReportModel {
    private Database $db;
    private string $table = 'Reports';

    public function __construct() {
        $this->db = new Database();
    }

    public function getComplaintStats(int $days = 7): array {
        $days = max(1, min(30, $days));
        $connection = $this->db->getConnection();

        $summarySql = "SELECT 
                COUNT(*) AS total_reports,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_reports,
                SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed_reports,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_reports,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS recent_reports
            FROM {$this->table}";

        $summaryStmt = $connection->prepare($summarySql);
        $summaryStmt->execute();
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $typesSql = "SELECT report_type, COUNT(*) AS count
            FROM {$this->table}
            GROUP BY report_type";
        $typesStmt = $connection->prepare($typesSql);
        $typesStmt->execute();
        $typeRows = $typesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $defaultTypes = ['spam', 'harassment', 'inappropriate', 'other'];
        $typeMap = array_fill_keys($defaultTypes, 0);
        foreach ($typeRows as $row) {
            $type = $row['report_type'] ?? 'other';
            $typeMap[$type] = (int)($row['count'] ?? 0);
        }

        $start = new DateTime('today');
        $start->modify('-' . ($days - 1) . ' days');
        $timelineSql = "SELECT DATE(created_at) AS day, COUNT(*) AS count
            FROM {$this->table}
            WHERE created_at >= :start_date
            GROUP BY DATE(created_at)
            ORDER BY day ASC";
        $timelineStmt = $connection->prepare($timelineSql);
        $timelineStmt->execute([':start_date' => $start->format('Y-m-d 00:00:00')]);
        $timelineRows = $timelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $timelineMap = [];
        foreach ($timelineRows as $row) {
            $timelineMap[$row['day']] = (int)($row['count'] ?? 0);
        }

        $labels = [];
        $counts = [];
        $cursor = clone $start;
        for ($i = 0; $i < $days; $i++) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M d');
            $counts[] = $timelineMap[$key] ?? 0;
            $cursor->modify('+1 day');
        }

        $typeBreakdown = [];
        foreach ($typeMap as $label => $count) {
            $typeBreakdown[] = [
                'label' => ucfirst($label),
                'count' => $count
            ];
        }

        return [
            'total_reports' => (int)($summary['total_reports'] ?? 0),
            'pending_reports' => (int)($summary['pending_reports'] ?? 0),
            'reviewed_reports' => (int)($summary['reviewed_reports'] ?? 0),
            'resolved_reports' => (int)($summary['resolved_reports'] ?? 0),
            'recent_reports' => (int)($summary['recent_reports'] ?? 0),
            'type_breakdown' => $typeBreakdown,
            'trend' => [
                'labels' => $labels,
                'counts' => $counts
            ]
        ];
    }

    public function getRecentComplaints(int $limit = 6): array {
        $sql = "SELECT 
                    r.report_id,
                    r.report_type,
                    r.status,
                    r.description,
                    r.created_at,
                    r.reported_post_id,
                    r.reported_comment_id,
                    r.reported_group_id,
                    r.reported_media_id,
                    r.reported_user_id,
                    u.username AS reporter_username,
                    reported.username AS reported_username,
                    CASE 
                        WHEN r.reported_post_id IS NOT NULL THEN CONCAT('Post #', r.reported_post_id)
                        WHEN r.reported_comment_id IS NOT NULL THEN CONCAT('Comment #', r.reported_comment_id)
                        WHEN r.reported_group_id IS NOT NULL THEN CONCAT('Group #', r.reported_group_id)
                        WHEN r.reported_media_id IS NOT NULL THEN CONCAT('File #', r.reported_media_id)
                        WHEN r.reported_user_id IS NOT NULL THEN CONCAT('User #', r.reported_user_id)
                        ELSE 'General'
                    END AS target_label
                FROM {$this->table} r
                LEFT JOIN Users u ON u.user_id = r.reporter_id
                LEFT JOIN Users reported ON reported.user_id = r.reported_user_id
                ORDER BY r.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getComplaintsByStatus(?string $status = null, int $limit = 15): array {
        $limit = max(1, min(100, $limit));
        $statusFilter = $status ? strtolower(trim($status)) : null;

        $sql = "SELECT 
                    r.report_id,
                    r.report_type,
                    r.status,
                    r.description,
                    r.created_at,
                    r.reported_post_id,
                    r.reported_comment_id,
                    r.reported_group_id,
                    r.reported_media_id,
                    r.reported_user_id,
                    u.user_id AS reporter_id,
                    u.username AS reporter_username,
                    reported.username AS reported_username,
                    reported.user_id AS reported_id,
                    CASE 
                        WHEN r.reported_post_id IS NOT NULL THEN CONCAT('Post #', r.reported_post_id)
                        WHEN r.reported_comment_id IS NOT NULL THEN CONCAT('Comment #', r.reported_comment_id)
                        WHEN r.reported_group_id IS NOT NULL THEN CONCAT('Group #', r.reported_group_id)
                        WHEN r.reported_media_id IS NOT NULL THEN CONCAT('File #', r.reported_media_id)
                        WHEN r.reported_user_id IS NOT NULL THEN CONCAT('User #', r.reported_user_id)
                        ELSE 'General'
                    END AS target_label
                FROM {$this->table} r
                LEFT JOIN Users u ON u.user_id = r.reporter_id
                LEFT JOIN Users reported ON reported.user_id = r.reported_user_id";

        $conditions = [];
        $params = [];

        if ($statusFilter === 'pending' || $statusFilter === 'resolved' || $statusFilter === 'reviewed') {
            $conditions[] = 'LOWER(r.status) = :status';
            $params[':status'] = $statusFilter;
        } elseif ($statusFilter === 'received') {
            $conditions[] = 'r.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)';
            $conditions[] = "LOWER(r.status) = 'pending'";
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY r.created_at DESC LIMIT :limit';

        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportById(int $reportId): ?array {
        if ($reportId <= 0) {
            return null;
        }

        $sql = "SELECT r.*, 
                    reporter.username AS reporter_username,
                    owner.username AS owner_username,
                    reviewer.username AS reviewed_by_username
                FROM {$this->table} r
                LEFT JOIN Users reporter ON reporter.user_id = r.reporter_id
                LEFT JOIN Users owner ON owner.user_id = r.reported_user_id
                LEFT JOIN Users reviewer ON reviewer.user_id = r.reviewed_by
                WHERE r.report_id = :report_id
                LIMIT 1";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getGroupModerationReports(int $groupId, int $limit = 200): array {
        if ($groupId <= 0) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $sql = "SELECT
                    r.report_id,
                    r.reporter_id,
                    r.target_type,
                    r.target_id,
                    r.group_id,
                    r.reported_user_id,
                    r.report_type,
                    r.description,
                    r.status,
                    r.created_at,
                    r.reviewed_by,
                    r.reviewed_at,
                    r.action_taken,
                    r.reviewer_note,
                    reporter.username AS reporter_username,
                    owner.username AS owner_username,
                    reviewer.username AS reviewed_by_username,
                    g.name AS group_name
                FROM {$this->table} r
                LEFT JOIN Users reporter ON reporter.user_id = r.reporter_id
                LEFT JOIN Users owner ON owner.user_id = r.reported_user_id
                LEFT JOIN Users reviewer ON reviewer.user_id = r.reviewed_by
                LEFT JOIN GroupsTable g ON g.group_id = r.group_id
                WHERE r.group_id = :group_id
                   OR (r.target_type = 'group' AND r.target_id = :group_target_id)
                ORDER BY
                    CASE r.status
                        WHEN 'pending' THEN 1
                        WHEN 'reviewed' THEN 2
                        ELSE 3
                    END,
                    r.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':group_target_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function resolveReport(int $reportId, int $adminId): bool {
        if ($reportId <= 0) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET status = 'resolved',
                    reviewed_by = :admin_id,
                    reviewed_at = NOW()
                WHERE report_id = :report_id";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':report_id' => $reportId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('resolveReport error: ' . $e->getMessage());
            return false;
        }
    }

    public function createReport(array $payload): array {
        $reporterId = (int)($payload['reporter_id'] ?? 0);
        if ($reporterId <= 0) {
            return ['success' => false, 'message' => 'Reporter required'];
        }

        $targetType = strtolower(trim((string)($payload['target_type'] ?? '')));
        $allowedTargetTypes = ['user', 'post', 'comment', 'group', 'bin', 'bin_media', 'question', 'answer', 'channel', 'message'];
        if (!in_array($targetType, $allowedTargetTypes, true)) {
            return ['success' => false, 'message' => 'Invalid report target'];
        }

        $targetId = (int)($payload['target_id'] ?? 0);
        if ($targetId <= 0) {
            return ['success' => false, 'message' => 'Target required'];
        }

        $allowedTypes = ['spam', 'harassment', 'inappropriate', 'other'];
        $reportType = strtolower(trim($payload['report_type'] ?? 'other'));
        if (!in_array($reportType, $allowedTypes, true)) {
            $reportType = 'other';
        }

        $description = trim($payload['description'] ?? '');
        if ($description !== '') {
            $limit = 1000;
            $length = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
            if ($length > $limit) {
                $description = function_exists('mb_substr') ? mb_substr($description, 0, $limit) : substr($description, 0, $limit);
            }
        }

        $connection = $this->db->getConnection();
        $sql = "INSERT INTO {$this->table}
            (reporter_id, target_type, target_id, group_id, reported_user_id, report_type, description)
            VALUES (:reporter_id, :target_type, :target_id, :group_id, :reported_user_id, :report_type, :description)";

        $params = [
            ':reporter_id' => $reporterId,
            ':target_type' => $targetType,
            ':target_id' => $targetId,
            ':group_id' => $payload['group_id'] ?? null,
            ':reported_user_id' => $payload['reported_user_id'] ?? null,
            ':report_type' => $reportType,
            ':description' => $description !== '' ? $description : null
        ];

        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);
            return [
                'success' => true,
                'report_id' => (int)$connection->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log('createReport error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to file report at the moment'];
        }
    }

    public function markReviewed(int $reportId, int $adminId): bool {
        if ($reportId <= 0) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET status = CASE WHEN status = 'resolved' THEN status ELSE 'reviewed' END,
                    reviewed_by = :admin_id,
                    reviewed_at = NOW()
                WHERE report_id = :report_id";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':report_id' => $reportId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('markReviewed error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateModerationReport(int $reportId, int $adminId, array $updates): bool {
        if ($reportId <= 0 || $adminId <= 0) {
            return false;
        }

        $allowedStatuses = ['pending', 'reviewed', 'resolved'];
        $allowedActions = [
            'none',
            'delete_content',
            'warn_user',
            'kick_user',
            'remove_file',
            'remove_folder',
            'delete_channel',
            'clear_channel',
            'false_positive',
            'other',
        ];

        $status = strtolower(trim((string)($updates['status'] ?? 'pending')));
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $actionTaken = strtolower(trim((string)($updates['action_taken'] ?? 'none')));
        if (!in_array($actionTaken, $allowedActions, true)) {
            $actionTaken = 'none';
        }

        $reviewerNote = trim((string)($updates['reviewer_note'] ?? ''));
        if ($reviewerNote !== '') {
            $maxLength = 2000;
            $length = function_exists('mb_strlen') ? mb_strlen($reviewerNote) : strlen($reviewerNote);
            if ($length > $maxLength) {
                $reviewerNote = function_exists('mb_substr')
                    ? mb_substr($reviewerNote, 0, $maxLength)
                    : substr($reviewerNote, 0, $maxLength);
            }
        }

        $description = trim((string)($updates['description'] ?? ''));
        if ($description !== '') {
            $maxLength = 1000;
            $length = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
            if ($length > $maxLength) {
                $description = function_exists('mb_substr')
                    ? mb_substr($description, 0, $maxLength)
                    : substr($description, 0, $maxLength);
            }
        }

        $sql = "UPDATE {$this->table}
                SET status = :status,
                    action_taken = :action_taken,
                    description = :description,
                    reviewer_note = :reviewer_note,
                    reviewed_by = :admin_id,
                    reviewed_at = NOW()
                WHERE report_id = :report_id";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':action_taken' => $actionTaken,
                ':description' => $description !== '' ? $description : null,
                ':reviewer_note' => $reviewerNote !== '' ? $reviewerNote : null,
                ':admin_id' => $adminId,
                ':report_id' => $reportId,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('updateModerationReport error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
