<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class QuestionPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'question';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $form = $data['request'] ?? [];
        $content = trim($data['content'] ?? ($form['problem_statement'] ?? ''));
        if ($content === '') {
            return null;
        }

        $metadata = [
            'title' => trim((string)($form['title'] ?? '')),
            'category' => $form['question_category'] ?? ($form['category'] ?? 'General'),
            'topics' => $form['topics'] ?? ($form['tags'] ?? '')
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
