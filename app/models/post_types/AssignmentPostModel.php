<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class AssignmentPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'assignment';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        $form = $data['request'] ?? [];
        $metadata = [
            'title' => $form['assignment_title'] ?? 'Assignment',
            'deadline' => $form['assignment_deadline'] ?? '',
            'points' => isset($form['assignment_points']) ? (int)$form['assignment_points'] : null
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
