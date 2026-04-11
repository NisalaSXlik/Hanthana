<?php
require_once __DIR__ . '/../core/Database.php';

class QuestionModel {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Get questions feed with filters
    public function getQuestionsFeed($userId, $filters = []) {
        $personalSql = "SELECT 
                    q.question_id,
                    q.title,
                    q.content,
                    q.category,
                    q.views,
                    q.created_at,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    (SELECT COUNT(*) FROM Answers a WHERE a.question_id = q.question_id AND a.is_deleted = FALSE) as answer_count,
                    (SELECT COUNT(*) FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.vote_type = 'upvote') as upvote_count,
                    (SELECT COUNT(*) FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.vote_type = 'downvote') as downvote_count,
                    (SELECT vote_type FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.user_id = ?) as user_vote,
                    GROUP_CONCAT(DISTINCT qt.topic_name) as topics,
                    'question' AS source_type,
                    NULL AS group_id,
                    NULL AS group_name
                FROM Questions q
                INNER JOIN Users u ON q.user_id = u.user_id
                LEFT JOIN QuestionTopics qt ON q.question_id = qt.question_id
                WHERE q.is_deleted = FALSE";
        $personalParams = [$userId];
        
        if (!empty($filters['category'])) {
            $personalSql .= " AND q.category = ?";
            $personalParams[] = $filters['category'];
        }
        
        if (!empty($filters['topic'])) {
            $personalSql .= " AND qt.topic_name = ?";
            $personalParams[] = $filters['topic'];
        }
        
        if (!empty($filters['search'])) {
            $personalSql .= " AND MATCH(q.title, q.content) AGAINST(? IN NATURAL LANGUAGE MODE)";
            $personalParams[] = $filters['search'];
        }

        if (!empty($filters['mine']) || (($filters['sort'] ?? '') === 'my_questions')) {
            $personalSql .= " AND q.user_id = ?";
            $personalParams[] = (int)$userId;
        }
        
        $personalSql .= " GROUP BY q.question_id";

        $sortBy = $filters['sort'] ?? 'recent';
        $includeGroupQuestions = !(!empty($filters['mine']) || $sortBy === 'my_questions');
        $groupRows = $includeGroupQuestions ? $this->getGroupQuestionsFeed($userId, $filters) : [];
        $questions = $this->runQuestionsQuery($personalSql, $personalParams);

        $merged = array_merge($questions, $groupRows);

        usort($merged, function (array $a, array $b) use ($sortBy) {
            $timeA = isset($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
            $timeB = isset($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
            $upA = (int)($a['upvote_count'] ?? 0);
            $upB = (int)($b['upvote_count'] ?? 0);
            $ansA = (int)($a['answer_count'] ?? 0);
            $ansB = (int)($b['answer_count'] ?? 0);

            switch ($sortBy) {
                case 'popular':
                    if ($upA !== $upB) return $upB <=> $upA;
                    if ($ansA !== $ansB) return $ansB <=> $ansA;
                    return $timeB <=> $timeA;
                case 'answered':
                    if ($ansA !== $ansB) return $ansB <=> $ansA;
                    return $timeB <=> $timeA;
                case 'unanswered':
                    if ($ansA === 0 && $ansB > 0) return -1;
                    if ($ansA > 0 && $ansB === 0) return 1;
                    return $timeB <=> $timeA;
                default:
                    return $timeB <=> $timeA;
            }
        });

        $limit = (int)($filters['limit'] ?? 20);
        $offset = (int)($filters['offset'] ?? 0);
        $merged = array_slice($merged, $offset, $limit);

        return $merged;
    }

    private function runQuestionsQuery(string $sql, array $params): array {
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getGroupQuestionsFeed($userId, $filters = []): array {
        $sql = "SELECT 
                    p.post_id AS question_id,
                    JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.title')) AS title,
                    p.content,
                    JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.category')) AS category,
                    NULL AS attachment_name,
                    NULL AS attachment_path,
                    NULL AS attachment_type,
                    NULL AS attachment_size,
                    0 AS views,
                    p.created_at,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    COALESCE(p.comment_count, 0) AS answer_count,
                    COALESCE((SELECT COUNT(*) FROM Vote v WHERE v.post_id = p.post_id AND v.vote_type = 'upvote'), 0) AS upvote_count,
                    COALESCE((SELECT COUNT(*) FROM Vote v WHERE v.post_id = p.post_id AND v.vote_type = 'downvote'), 0) AS downvote_count,
                    (SELECT vote_type FROM Vote v WHERE v.post_id = p.post_id AND v.user_id = ? LIMIT 1) AS user_vote,
                    JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.topics')) AS topics,
                    'group_question' AS source_type,
                    p.group_id AS group_id,
                    g.name AS group_name
                FROM Post p
                INNER JOIN Users u ON p.author_id = u.user_id
                INNER JOIN GroupsTable g ON g.group_id = p.group_id
                INNER JOIN GroupMember gm ON gm.group_id = p.group_id AND gm.user_id = ? AND gm.status = 'active'
                WHERE p.is_group_post = 1
                    AND COALESCE(g.is_active, 1) = 1
                    AND p.group_post_type = 'question'";

        $params = [$userId, $userId];

        if (!empty($filters['category'])) {
            $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.category')) = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['topic'])) {
            $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.topics')) LIKE ?";
            $params[] = '%' . $filters['topic'] . '%';
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                        JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.title')) LIKE ?
                        OR p.content LIKE ?
                        OR JSON_UNQUOTE(JSON_EXTRACT(p.metadata, '$.topics')) LIKE ?
                    )";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $stmt = $this->db->getConnection()->prepare($sql . " GROUP BY p.post_id");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new question
    public function createQuestion($userId, $data) {
        $sql = "INSERT INTO Questions (
                    user_id,
                    title,
                    content,
                    category
                ) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $userId,
            $data['title'],
            $data['content'] ?? '',
            $data['category'] ?? 'General'
        ]);
        
        $questionId = $this->db->getConnection()->lastInsertId();
        
        // Add topics if provided
        if (!empty($data['topics'])) {
            $this->addTopics($questionId, $data['topics']);
        }
        
        return $questionId;
    }
    
    // Get single question with details
    public function getQuestion($questionId, $userId) {
        $sql = "SELECT 
                    q.*,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    (SELECT COUNT(*) FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.vote_type = 'upvote') as upvote_count,
                    (SELECT COUNT(*) FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.vote_type = 'downvote') as downvote_count,
                    (SELECT vote_type FROM QuestionVotes qv WHERE qv.question_id = q.question_id AND qv.user_id = ?) as user_vote,
                    GROUP_CONCAT(DISTINCT qt.topic_name) as topics
                FROM Questions q
                INNER JOIN Users u ON q.user_id = u.user_id
                LEFT JOIN QuestionTopics qt ON q.question_id = qt.question_id
                WHERE q.question_id = ? AND q.is_deleted = FALSE
                GROUP BY q.question_id";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get answers for a question
    public function getAnswers($questionId, $userId) {
        $sql = "SELECT 
                    a.*,
                    q.user_id AS question_user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'upvote') as upvote_count,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'downvote') as downvote_count,
                    (SELECT vote_type FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.user_id = ?) as user_vote
                FROM Answers a
                INNER JOIN Questions q ON a.question_id = q.question_id
                INNER JOIN Users u ON a.user_id = u.user_id
                WHERE a.question_id = ? AND a.is_deleted = FALSE
                ORDER BY a.is_accepted DESC, upvote_count DESC, a.created_at ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->buildAnswerTree($rows);
    }
    
    // Post an answer
    public function createAnswer($userId, $questionId, $content, $parentAnswerId = null) {
        $sql = "INSERT INTO Answers (user_id, question_id, parent_answer_id, content) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId, $parentAnswerId ?: null, $content]);
        $answerId = (int)$this->db->getConnection()->lastInsertId();
        return $this->getAnswerById($answerId, $userId);
    }

    public function editAnswer(int $answerId, int $userId, string $content): array {
        $ownerSql = "SELECT a.user_id, q.user_id AS question_user_id 
                    FROM Answers a 
                    INNER JOIN Questions q ON a.question_id = q.question_id 
                    WHERE a.answer_id = ? AND a.is_deleted = FALSE";
        $ownerStmt = $this->db->getConnection()->prepare($ownerSql);
        $ownerStmt->execute([$answerId]);
        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner || ((int)$owner['user_id'] !== $userId && (int)$owner['question_user_id'] !== $userId)) {
            return ['success' => false, 'message' => 'Not allowed'];
        }

        $sql = "UPDATE Answers SET content = ? WHERE answer_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$content, $answerId]);

        return ['success' => true, 'message' => 'Answer updated'];
    }

    public function deleteAnswer(int $answerId, int $userId): array {
        $ownerSql = "SELECT a.user_id, q.user_id AS question_user_id 
                    FROM Answers a 
                    INNER JOIN Questions q ON a.question_id = q.question_id 
                    WHERE a.answer_id = ? AND a.is_deleted = FALSE";
        $ownerStmt = $this->db->getConnection()->prepare($ownerSql);
        $ownerStmt->execute([$answerId]);
        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner || ((int)$owner['user_id'] !== $userId && (int)$owner['question_user_id'] !== $userId)) {
            return ['success' => false, 'message' => 'Not allowed'];
        }

        $sql = "UPDATE Answers SET is_deleted = TRUE WHERE answer_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$answerId]);

        return ['success' => true, 'message' => 'Answer deleted'];
    }

    public function getAnswerById(int $answerId, int $userId): ?array {
        $sql = "SELECT 
                    a.*,
                    q.user_id AS question_user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'upvote') as upvote_count,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'downvote') as downvote_count,
                    (SELECT vote_type FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.user_id = ?) as user_vote
                FROM Answers a
                INNER JOIN Questions q ON a.question_id = q.question_id
                INNER JOIN Users u ON a.user_id = u.user_id
                WHERE a.answer_id = ? AND a.is_deleted = FALSE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $answerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['replies'] = [];
        return $row;
    }
    
    // Vote on question
    public function voteQuestion($userId, $questionId, $voteType) {
        // Check if user already voted
        $sql = "SELECT vote_type FROM QuestionVotes WHERE user_id = ? AND question_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['vote_type'] === $voteType) {
                // Remove vote if clicking same button
                $sql = "DELETE FROM QuestionVotes WHERE user_id = ? AND question_id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->execute([$userId, $questionId]);
                return 'removed';
            } else {
                // Update vote type
                $sql = "UPDATE QuestionVotes SET vote_type = ? WHERE user_id = ? AND question_id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->execute([$voteType, $userId, $questionId]);
                return 'updated';
            }
        } else {
            // Insert new vote
            $sql = "INSERT INTO QuestionVotes (user_id, question_id, vote_type) VALUES (?, ?, ?)";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$userId, $questionId, $voteType]);
            return 'added';
        }
    }
    
    // Vote on answer
    public function voteAnswer($userId, $answerId, $voteType) {
        $sql = "SELECT vote_type FROM AnswerVotes WHERE user_id = ? AND answer_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $answerId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['vote_type'] === $voteType) {
                $sql = "DELETE FROM AnswerVotes WHERE user_id = ? AND answer_id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->execute([$userId, $answerId]);
                return 'removed';
            } else {
                $sql = "UPDATE AnswerVotes SET vote_type = ? WHERE user_id = ? AND answer_id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->execute([$voteType, $userId, $answerId]);
                return 'updated';
            }
        } else {
            $sql = "INSERT INTO AnswerVotes (user_id, answer_id, vote_type) VALUES (?, ?, ?)";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$userId, $answerId, $voteType]);
            return 'added';
        }
    }
    
    // Helper functions
    public function incrementViews($questionId) {
        $sql = "UPDATE Questions SET views = views + 1 WHERE question_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$questionId]);
    }

    private function buildAnswerTree(array $rows): array {
        $byId = [];
        foreach ($rows as $row) {
            $row['answer_id'] = (int)$row['answer_id'];
            $row['question_id'] = (int)$row['question_id'];
            $row['user_id'] = (int)$row['user_id'];
            $row['parent_answer_id'] = isset($row['parent_answer_id']) ? (int)$row['parent_answer_id'] : null;
            $row['upvote_count'] = (int)($row['upvote_count'] ?? 0);
            $row['downvote_count'] = (int)($row['downvote_count'] ?? 0);
            $row['replies'] = [];
            $byId[$row['answer_id']] = $row;
        }

        $tree = [];
        foreach ($byId as $id => &$node) {
            $parentId = $node['parent_answer_id'] ?? null;
            if (!empty($parentId) && isset($byId[$parentId])) {
                $byId[$parentId]['replies'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }
    
    private function addTopics($questionId, $topics) {
        $sql = "INSERT INTO QuestionTopics (question_id, topic_name) VALUES (?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($topics as $topic) {
            $stmt->execute([$questionId, trim($topic)]);
        }
    }
    
    public function getCategories() {
        return [
            'General',
            'Technology',
            'Science',
            'Education',
            'Health',
            'Lifestyle',
            'Travel',
            'Finance',
            'Career',
            'Relationships',
            'Entertainment',
            'Sports',
            'Politics',
            'Other'
        ];
    }

    public function getMyQuestionsLatestAnswers(int $userId, int $limit = 8): array {
        try {
            $sql = "SELECT
                        q.question_id,
                        q.title,
                        a.answer_id,
                        a.content AS latest_answer_content,
                        a.created_at AS latest_answer_at,
                        u.first_name,
                        u.last_name,
                        (
                            SELECT GROUP_CONCAT(DISTINCT qt.topic_name ORDER BY qt.topic_name SEPARATOR ', ')
                            FROM QuestionTopics qt
                            WHERE qt.question_id = q.question_id
                        ) AS question_topics
                    FROM Questions q
                    INNER JOIN Answers a
                        ON a.question_id = q.question_id
                        AND a.is_deleted = FALSE
                        AND a.user_id <> ?
                    INNER JOIN Users u ON u.user_id = a.user_id
                    WHERE q.user_id = ?
                        AND q.is_deleted = FALSE
                        AND a.answer_id = (
                            SELECT a3.answer_id
                            FROM Answers a3
                            WHERE a3.question_id = q.question_id
                                AND a3.is_deleted = FALSE
                                AND a3.user_id <> ?
                            ORDER BY a3.created_at DESC, a3.answer_id DESC
                            LIMIT 1
                        )
                    ORDER BY a.created_at DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$userId, $userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('getMyQuestionsLatestAnswers error: ' . $e->getMessage());
            return [];
        }
    }

    public function updateQuestion(int $questionId, int $userId, array $data): array {
        $ownerSql = "SELECT user_id FROM Questions WHERE question_id = ? AND is_deleted = FALSE";
        $ownerStmt = $this->db->getConnection()->prepare($ownerSql);
        $ownerStmt->execute([$questionId]);
        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner || (int)$owner['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Not allowed'];
        }

        $sql = "UPDATE Questions SET title = ?, content = ?, category = ? WHERE question_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['category'] ?? 'General',
            $questionId
        ]);

        $this->db->getConnection()->prepare("DELETE FROM QuestionTopics WHERE question_id = ?")->execute([$questionId]);

        if (!empty($data['topics'])) {
            $this->addTopics($questionId, $data['topics']);
        }

        return ['success' => true, 'message' => 'Question updated'];
    }

    public function deleteQuestion(int $questionId, int $userId): array {
        $ownerSql = "SELECT user_id FROM Questions WHERE question_id = ? AND is_deleted = FALSE";
        $ownerStmt = $this->db->getConnection()->prepare($ownerSql);
        $ownerStmt->execute([$questionId]);
        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner || (int)$owner['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Not allowed'];
        }

        $sql = "UPDATE Questions SET is_deleted = TRUE WHERE question_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$questionId]);

        return ['success' => true, 'message' => 'Question deleted'];
    }
}
