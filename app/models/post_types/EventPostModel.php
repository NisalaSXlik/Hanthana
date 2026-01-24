<?php

require_once __DIR__ . '/BaseGroupPostModel.php';

class EventPostModel extends BaseGroupPostModel {
    public function getType(): string {
        return 'event';
    }

    public function create(int $userId, int $groupId, array $data): ?int {
        $content = trim($data['content'] ?? '');
        if ($content === '') {
            return null;
        }

        $form = $data['request'] ?? [];
        $event = [
            'title' => $form['event_title'] ?? ($form['event_name'] ?? ''),
            'date' => $form['event_date'] ?? '',
            'time' => $form['event_time'] ?? '',
            'location' => $form['event_location'] ?? ''
        ];

        $metadata = array_merge($event, [
            'description' => $form['event_description'] ?? ''
        ]);

        return $this->persistPost([
            'user_id' => $userId,
            'group_id' => $groupId,
            'content' => $content,
            'group_post_type' => $this->getType(),
            'metadata' => $metadata,
            'event' => $event,
            'image_path' => $data['image_path'] ?? null
        ]);
    }
}
