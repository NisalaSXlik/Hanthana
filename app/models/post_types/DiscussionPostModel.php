<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class DiscussionPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'discussion';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        return $this->persistPost([
            'user_id' => $userId,
            'group_id' => $groupId,
            'content' => $content,
            'group_post_type' => $this->getType(),
            'image_path' => $data['image_path'] ?? null
        ]);
    }
}
