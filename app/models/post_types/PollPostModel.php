<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class PollPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'poll';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        $form = $data['request'] ?? [];
        $options = [];
        for ($i = 1; $i <= 4; $i++) {
            $label = trim($form['poll_option_' . $i] ?? '');
            if ($label !== '') {
                $options[] = $label;
            }
        }

        if (count($options) < 2) {
            return null;
        }

        $metadata = [
            'question' => $form['poll_question'] ?? 'Poll',
            'options' => $options,
            'duration' => (int)($form['poll_duration'] ?? 7),
            'votes' => array_fill(0, count($options), 0)
        ];

        return $this->persistPost([
            'user_id' => $userId,
            'group_id' => $groupId,
            'content' => $content,
            'group_post_type' => $this->getType(),
            'metadata' => $metadata,
            'image_path' => $data['image_path'] ?? null
        ]);
    }

    public function recordVote(int $postId, int $userId, int $optionIndex, int $optionCount): array {
        $optionCount = max($optionCount, 0);
        if ($optionCount === 0) {
            return [];
        }

        if ($this->hasPollVoteTable) {
            $counts = $this->recordVoteWithTable($postId, $userId, $optionIndex, $optionCount);
            if (!empty(array_sum($counts))) {
                return $counts;
            }
            // If table logic failed or returned no counts (possible schema issues), fall back to metadata
            error_log('PollPostModel: Falling back to metadata for poll votes.');
        }

        return $this->recordVoteWithMetadata($postId, $userId, $optionIndex, $optionCount);
    }

    private function recordVoteWithTable(int $postId, int $userId, int $optionIndex, int $optionCount): array {
        $conn = $this->getConnection();
        $counts = array_fill(0, $optionCount, 0);

        try {
            $conn->beginTransaction();

            $checkStmt = $conn->prepare("SELECT option_index FROM GroupPostPollVote WHERE post_id = ? AND user_id = ?");
            $checkStmt->execute([$postId, $userId]);
            $existing = $checkStmt->fetchColumn();

            if ($existing === false) {
                $insertStmt = $conn->prepare("INSERT INTO GroupPostPollVote (post_id, user_id, option_index) VALUES (?, ?, ?)");
                $insertStmt->execute([$postId, $userId, $optionIndex]);
            } elseif ((int)$existing !== $optionIndex) {
                $updateStmt = $conn->prepare("UPDATE GroupPostPollVote SET option_index = ?, voted_at = NOW() WHERE post_id = ? AND user_id = ?");
                $updateStmt->execute([$optionIndex, $postId, $userId]);
            }

            $countStmt = $conn->prepare("SELECT option_index, COUNT(*) AS total FROM GroupPostPollVote WHERE post_id = ? GROUP BY option_index");
            $countStmt->execute([$postId]);
            while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = (int)$row['option_index'];
                if ($idx >= 0 && $idx < count($counts)) {
                    $counts[$idx] = (int)$row['total'];
                }
            }

            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log('PollPostModel recordVoteWithTable error: ' . $e->getMessage());
        }

        return $counts;
    }

    private function recordVoteWithMetadata(int $postId, int $userId, int $optionIndex, int $optionCount): array {
        $conn = $this->getConnection();
        $counts = array_fill(0, $optionCount, 0);

        try {
            $stmt = $conn->prepare("SELECT metadata FROM Post WHERE post_id = ?");
            $stmt->execute([$postId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $metadata = [];
            if ($result && !empty($result['metadata'])) {
                $decoded = json_decode($result['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            $userVotes = $metadata['user_votes'] ?? [];
            if (!is_array($userVotes)) {
                $userVotes = [];
            }

            $userIdStr = (string)$userId;
            $oldIndex = null;
            if (isset($userVotes[$userId])) {
                $oldIndex = (int)$userVotes[$userId];
            } elseif (isset($userVotes[$userIdStr])) {
                $oldIndex = (int)$userVotes[$userIdStr];
            }

            if ($optionIndex >= 0 && $optionIndex < $optionCount) {
                $userVotes[$userIdStr] = $optionIndex;
            }

            // Rebuild votes array to ensure only one active vote per user
            $votes = array_fill(0, $optionCount, 0);
            foreach ($userVotes as $voteIdx) {
                $idx = (int)$voteIdx;
                if ($idx >= 0 && $idx < $optionCount) {
                    $votes[$idx]++;
                }
            }

            $metadata['votes'] = $votes;
            $metadata['user_votes'] = $userVotes;

            $updateStmt = $conn->prepare("UPDATE Post SET metadata = ? WHERE post_id = ?");
            $updateStmt->execute([json_encode($metadata), $postId]);

            $counts = $votes;
        } catch (PDOException $e) {
            error_log('PollPostModel recordVoteWithMetadata error: ' . $e->getMessage());
        }

        return $counts;
    }

    public function updateMetadata(int $postId, array $metadata): bool {
        return $this->updateMetadataColumn($postId, $metadata);
    }
}
