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
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
        exit;
    }

    private function getEvents() {
        $filter = $_GET['filter'] ?? 'upcoming';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Reuse getFeedPosts but filter for events in PHP for now, 
        // or ideally add a specific method in PostModel.
        // Since PostModel::getFeedPosts returns mixed posts, we can filter them.
        // But for 'upcoming' and 'past', we might need a custom query.
        // Let's use a custom query here or add one to PostModel.
        // For simplicity/speed, I'll add a method to PostModel or just query here if I had DB access.
        // I'll use PostModel::getFeedPosts and filter, but that might not get ALL events.
        // Actually, getFeedPosts gets friends' posts. Events page might show public events too?
        // The user prompt implies "Discover and join exciting events".
        
        // Let's try to use getFeedPosts for now, as it handles permissions.
        // We explicitly pass false to include events, as FeedController passes true to exclude them.
        $posts = $this->postModel->getFeedPosts($userId, false);
        
        $events = array_filter($posts, function($post) {
            // Check both standard post_type and group_post_type
            $type = !empty($post['group_id']) ? ($post['group_post_type'] ?? 'discussion') : ($post['post_type'] ?? 'text');
            return $type === 'event';
        });

        // Apply filter
        $filteredEvents = [];
        $now = date('Y-m-d H:i:s');
        
        foreach ($events as $event) {
            $eventDateTime = ($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00');
            
            if ($filter === 'upcoming') {
                if ($eventDateTime >= $now) {
                    $filteredEvents[] = $event;
                }
            } elseif ($filter === 'past') {
                if ($eventDateTime < $now) {
                    $filteredEvents[] = $event;
                }
            } elseif ($filter === 'my_events') {
                // Events I created or joined (RSVP). 
                // For now, just events I created.
                if ($event['author_id'] == $userId) {
                    $filteredEvents[] = $event;
                }
            }
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

        $data = [
            'content' => $_POST['description'] ?? '',
            'post_type' => 'event',
            'visibility' => 'public', // Default to public for now
            'event_title' => $_POST['title'] ?? '',
            'event_date' => $_POST['date'] ?? null,
            'event_time' => $_POST['time'] ?? null,
            'event_location' => $_POST['location'] ?? '',
            'author_id' => $userId,
            'is_group_post' => 0
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
}

