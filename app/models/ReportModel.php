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
                    r.reported_user_id,
                    u.username AS reporter_username,
                    reported.username AS reported_username,
                    CASE 
                        WHEN r.reported_post_id IS NOT NULL THEN CONCAT('Post #', r.reported_post_id)
                        WHEN r.reported_comment_id IS NOT NULL THEN CONCAT('Comment #', r.reported_comment_id)
                        WHEN r.reported_group_id IS NOT NULL THEN CONCAT('Group #', r.reported_group_id)
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
                    r.reported_user_id,
                    u.user_id AS reporter_id,
                    u.username AS reporter_username,
                    reported.username AS reported_username,
                    reported.user_id AS reported_id,
                    CASE 
                        WHEN r.reported_post_id IS NOT NULL THEN CONCAT('Post #', r.reported_post_id)
                        WHEN r.reported_comment_id IS NOT NULL THEN CONCAT('Comment #', r.reported_comment_id)
                        WHEN r.reported_group_id IS NOT NULL THEN CONCAT('Group #', r.reported_group_id)
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
                (reporter_id, report_type, description, reported_post_id, reported_comment_id, reported_group_id, reported_user_id)
                VALUES (:reporter_id, :report_type, :description, :reported_post_id, :reported_comment_id, :reported_group_id, :reported_user_id)";

        $params = [
            ':reporter_id' => $reporterId,
            ':report_type' => $reportType,
            ':description' => $description !== '' ? $description : null,
            ':reported_post_id' => $payload['reported_post_id'] ?? null,
            ':reported_comment_id' => $payload['reported_comment_id'] ?? null,
            ':reported_group_id' => $payload['reported_group_id'] ?? null,
            ':reported_user_id' => $payload['reported_user_id'] ?? null
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
}
?>
