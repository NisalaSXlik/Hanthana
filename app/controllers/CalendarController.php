<?php
require_once __DIR__ . '/../models/CalendarReminderModel.php';

class CalendarController {
    private $calendarModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->calendarModel = new CalendarReminderModel();
    }

    public function handleAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if ($decoded) {
                    $input = $decoded;
                }
            }
        }
        $subAction = $input['sub_action'] ?? ($_GET['sub_action'] ?? 'list');

        switch ($subAction) {
            case 'list':
                $this->listReminders($userId);
                break;
            case 'delete':
                $this->deleteReminder($userId, $input);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown calendar action']);
        }
    }

    private function listReminders(int $userId): void {
        try {
            $reminders = $this->calendarModel->listReminders($userId);
            echo json_encode([
                'success' => true,
                'events' => array_map([$this, 'formatReminder'], $reminders)
            ]);
        } catch (Exception $e) {
            error_log('Calendar list error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to fetch reminders']);
        }
    }

    private function deleteReminder(int $userId, array $input): void {
        $postId = isset($input['post_id']) ? (int)$input['post_id'] : 0;
        if ($postId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post']);
            return;
        }

        try {
            $deleted = $this->calendarModel->deleteReminder($userId, $postId);
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Reminder removed' : 'Nothing to remove'
            ]);
        } catch (Exception $e) {
            error_log('Calendar delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to remove reminder']);
        }
    }

    private function formatReminder(array $reminder): array {
        return [
            'post_id' => (int)$reminder['post_id'],
            'title' => $reminder['title'] ?? 'Untitled Event',
            'event_date' => $reminder['event_date'],
            'event_time' => $reminder['event_time'],
            'location' => $reminder['location'],
            'description' => $reminder['description'],
            'group_id' => isset($reminder['group_id']) ? (int)$reminder['group_id'] : null,
            'metadata' => $reminder['metadata'] ?? [],
            'created_at' => $reminder['created_at'] ?? null
        ];
    }
}
