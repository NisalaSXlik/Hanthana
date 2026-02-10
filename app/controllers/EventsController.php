<?php
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/CalendarReminderModel.php';

class EventsController {
    private $postModel;
    private $calendarModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->calendarModel = new CalendarReminderModel();
    }

    public function index() {
        if (isset($_GET['ajax_action'])) {
            $this->handleAjaxAction($_GET['ajax_action']);
            return;
        }
        require_once __DIR__ . '/../views/events.php';
    }

    private function handleAjaxAction($action) {
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'getEvents':
                $this->getEvents();
                break;
            case 'createEvent':
                $this->createEvent();
                break;
            case 'addToCalendar':
                $this->addToCalendar();
                break;
            case 'getMostGoingEvents':
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'events' => $this->getMostGoingEvents()]);
                exit;   
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
        exit;
    }

    private function getEvents() {
        $filter = $_GET['filter'] ?? 'recent';
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $posts = $this->postModel->getFeedPosts($userId, false);

        $events = array_values(array_filter($posts, function($post) {
            $type = !empty($post['group_id'])
                ? ($post['group_post_type'] ?? 'discussion')
                : ($post['post_type'] ?? 'text');
            return $type === 'event';
        }));

        $toEventTimestamp = function(array $event): int {
            $eventDate = trim((string)($event['event_date'] ?? ''));
            if ($eventDate === '') return 0;

            // event_date may already include time in some rows
            if (preg_match('/\d{1,2}:\d{2}/', $eventDate)) {
                $ts = strtotime($eventDate);
                return $ts !== false ? $ts : 0;
            }

            $eventTime = trim((string)($event['event_time'] ?? ''));
            $dateTime = $eventDate . ' ' . ($eventTime !== '' ? $eventTime : '00:00:00');
            $ts = strtotime($dateTime);
            return $ts !== false ? $ts : 0;
        };

        $toCreatedTimestamp = function(array $row): int {
            $ts = strtotime((string)($row['created_at'] ?? ''));
            return $ts !== false ? $ts : 0;
        };

        $filteredEvents = [];

        if ($filter === 'my_events') {
            foreach ($events as $event) {
                $authorId = (int)($event['author_id'] ?? $event['user_id'] ?? 0);
                if ($authorId === $userId) {
                    $filteredEvents[] = $event;
                }
            }

            usort($filteredEvents, function($a, $b) use ($toCreatedTimestamp) {
                return $toCreatedTimestamp($b) <=> $toCreatedTimestamp($a); // newest created first
            });

        } elseif ($filter === 'added_to_calendar') {
            $reminders = $this->calendarModel->listReminders($userId);

            // Keep latest reminder timestamp per post_id
            $reminderByPost = [];
            foreach ($reminders as $reminder) {
                $postId = (int)($reminder['post_id'] ?? 0);
                if ($postId <= 0) continue;

                $addedAtRaw = $reminder['updated_at'] ?? $reminder['created_at'] ?? null;
                $addedAtTs = $addedAtRaw ? strtotime($addedAtRaw) : 0;
                if ($addedAtTs === false) $addedAtTs = 0;

                if (!isset($reminderByPost[$postId]) || $addedAtTs > $reminderByPost[$postId]) {
                    $reminderByPost[$postId] = $addedAtTs;
                }
            }

            foreach ($events as $event) {
                $postId = (int)($event['post_id'] ?? $event['event_id'] ?? 0);
                if ($postId > 0 && isset($reminderByPost[$postId])) {
                    $event['reminder_added_at_ts'] = $reminderByPost[$postId];
                    $filteredEvents[] = $event;
                }
            }

            usort($filteredEvents, function($a, $b) {
                return (int)($b['reminder_added_at_ts'] ?? 0) <=> (int)($a['reminder_added_at_ts'] ?? 0); // most recently added first
            });

        } else { // default: recent (also supports legacy upcoming filter)
            $filteredEvents = $events;

            // Keep recent by post creation time (newest first)
            usort($filteredEvents, function($a, $b) use ($toCreatedTimestamp) {
                return $toCreatedTimestamp($b) <=> $toCreatedTimestamp($a);
            });
        }

        echo json_encode(['success' => true, 'events' => array_values($filteredEvents)]);
    }

    private function createEvent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $imagePath = null;
        $imageName = null;
        $imageSize = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/post_media/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
            $destinationPath = $uploadDir . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $destinationPath)) {
                echo json_encode(['success' => false, 'error' => 'Image upload failed']);
                return;
            }

            $imagePath = 'uploads/post_media/' . $imageName;
            $imageSize = $_FILES['image']['size'] ?? null;
        }

        $data = [
            'content' => $_POST['description'] ?? '',
            'post_type' => 'event',
            'visibility' => 'public', // Default to public for now
            'event_title' => $_POST['title'] ?? '',
            'event_date' => $_POST['date'] ?? null,
            'event_time' => $_POST['time'] ?? null,
            'event_location' => $_POST['location'] ?? '',
            'author_id' => $userId,
            'is_group_post' => 0,
            'image_path' => $imagePath,
            'image_name' => $imageName,
            'image_size' => $imageSize
        ];

        $result = $this->postModel->createPost($data);
        echo json_encode($result);
    }

    private function addToCalendar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['post_id'] ?? 0;
        
        if (!$postId) {
            echo json_encode(['success' => false, 'error' => 'Missing post ID']);
            return;
        }

        // Fetch event details to ensure it exists and get details
        // We can use getFeedPosts and find the post, or add getPostById to PostModel.
        // Assuming we have the details from the frontend or we trust the ID.
        // Better to fetch. But PostModel doesn't have getPostById visible in snippet.
        // I'll assume the frontend sends necessary details or I'll just use what I have.
        // CalendarReminderModel::upsertReminder needs title, date, etc.
        
        $title = $input['title'] ?? 'Event';
        $date = $input['event_date'] ?? date('Y-m-d');
        $time = $input['event_time'] ?? '00:00:00';
        $location = $input['location'] ?? '';
        $description = $input['description'] ?? '';

        $success = $this->calendarModel->upsertReminder(
            $userId, 
            null, 
            $postId, 
            [
                'title' => $title,
                'event_date' => $date,
                'event_time' => $time,
                'location' => $location,
                'description' => $description,
                'metadata' => json_encode(['source' => 'event_page'])
            ]
        );

        $goingCount = $this->calendarModel->getGoingCount($postId);

        echo json_encode(['success' => $success, 'going_count' => $goingCount]);
    }

    public function getMostGoingEvents() {
        $userId = $_SESSION['user_id'] ?? 0;
        $posts = $this->postModel->getFeedPosts($userId, false);

        $events = array_filter($posts, function($post) {
            $type = !empty($post['group_id']) ? ($post['group_post_type'] ?? 'discussion') : ($post['post_type'] ?? 'text');
            return $type === 'event';
        });

        $filteredEvents = [];
        $now = date('Y-m-d H:i:s');

        foreach ($events as $event) {
            $eventDateTime = trim(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00'));
            if ($eventDateTime >= $now) {
                $postId = (int)($event['post_id'] ?? $event['id'] ?? 0);
                if ($postId <= 0) continue;

                $event['post_id'] = $postId;
                $event['going_count'] = $this->calendarModel->getGoingCount($postId);
                $event['is_going'] = $this->calendarModel->getReminderForPost($userId, $postId) ? 1 : 0;
                $filteredEvents[] = $event;
            }
        }

        usort($filteredEvents, fn($a, $b) => ($b['going_count'] ?? 0) <=> ($a['going_count'] ?? 0));
        return array_slice($filteredEvents, 0, 5);
    }
}

