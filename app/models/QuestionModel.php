<?php
class QuestionModel {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Get questions feed with filters
    public function getQuestionsFeed($userId, $filters = []) {
        $sql = "SELECT 
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
                    GROUP_CONCAT(DISTINCT qt.topic_name) as topics
                FROM Questions q
                INNER JOIN Users u ON q.user_id = u.user_id
                LEFT JOIN QuestionTopics qt ON q.question_id = qt.question_id
                WHERE q.is_deleted = FALSE";
        
        $params = [$userId];
        
        if (!empty($filters['category'])) {
            $sql .= " AND q.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['topic'])) {
            $sql .= " AND qt.topic_name = ?";
            $params[] = $filters['topic'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND MATCH(q.title, q.content) AGAINST(? IN NATURAL LANGUAGE MODE)";
            $params[] = $filters['search'];
        }
        
        $sql .= " GROUP BY q.question_id";
        
        // Sorting
        $sortBy = $filters['sort'] ?? 'recent';
        switch ($sortBy) {
            case 'popular':
                $sql .= " ORDER BY upvote_count DESC, views DESC";
                break;
            case 'answered':
                $sql .= " ORDER BY answer_count DESC";
                break;
            case 'unanswered':
                $sql .= " HAVING answer_count = 0 ORDER BY q.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY q.created_at DESC";
        }
        
        $limit = $filters['limit'] ?? 20;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new question
    public function createQuestion($userId, $data) {
        $sql = "INSERT INTO Questions (user_id, title, content, category) VALUES (?, ?, ?, ?)";
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
        // Increment view count
        $this->incrementViews($questionId);
        
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
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'upvote') as upvote_count,
                    (SELECT COUNT(*) FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.vote_type = 'downvote') as downvote_count,
                    (SELECT vote_type FROM AnswerVotes av WHERE av.answer_id = a.answer_id AND av.user_id = ?) as user_vote
                FROM Answers a
                INNER JOIN Users u ON a.user_id = u.user_id
                WHERE a.question_id = ? AND a.is_deleted = FALSE
                ORDER BY a.is_accepted DESC, upvote_count DESC, a.created_at ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Post an answer
    public function createAnswer($userId, $questionId, $content) {
        $sql = "INSERT INTO Answers (user_id, question_id, content) VALUES (?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId, $questionId, $content]);
        return $this->db->getConnection()->lastInsertId();
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
    private function incrementViews($questionId) {
        $sql = "UPDATE Questions SET views = views + 1 WHERE question_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$questionId]);
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
}
