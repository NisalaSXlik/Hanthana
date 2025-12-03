<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class ResourcePostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'resource';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        $form = $data['request'] ?? [];
        $filePath = $data['file_path'] ?? null;
        $resourceType = strtolower(trim((string)($form['resource_type'] ?? 'document')));
        if ($resourceType === '') {
            $resourceType = 'document';
        }
        $resourceLink = trim((string)($form['resource_link'] ?? ''));

        $metadata = [
            'title' => $form['resource_title'] ?? 'Shared Resource',
            'resource_type' => $resourceType,
            'resource_link' => $resourceLink,
            'file_path' => $filePath,
            // legacy keys kept for backwards compatibility with earlier code paths
            'type' => $resourceType,
            'link' => $resourceLink
        ];

        return $this->persistPost([
            'user_id' => $userId,
            'group_id' => $groupId,
            'content' => $content,
            'group_post_type' => $this->getType(),
            'metadata' => $metadata,
            'image_path' => $data['image_path'] ?? null,
            'document_path' => $filePath
        ]);
    }
}
