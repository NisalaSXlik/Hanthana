<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class QuestionPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'question';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        $form = $data['request'] ?? [];
        $metadata = [
            'category' => $form['question_category'] ?? 'general',
            'tags' => $form['tags'] ?? ''
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
}
