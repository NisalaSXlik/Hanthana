<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/EventModel.php';

class EventsController {
    private $eventModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->eventModel = new EventModel();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        require_once __DIR__ . '/../views/events.php';
    }

    /**
     * Get events via AJAX
     */
    public function getEvents() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';
            
            switch ($filter) {
                case 'upcoming':
                    $events = $this->eventModel->getUpcomingEvents($userId);
                    break;
                case 'my_events':
                    $events = $this->eventModel->getUserEvents($userId);
                    break;
                case 'past':
                    $events = $this->eventModel->getPastEvents($userId);
                    break;
                default:
                    $events = $this->eventModel->getUpcomingEvents($userId);
            }
            
            echo json_encode([
                'success' => true,
                'events' => $events
            ]);
        } catch (Exception $e) {
            error_log('Get events error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error loading events'
            ]);
        }
        exit;
    }

    /**
     * Create event via AJAX
     */
    public function createEvent() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $input = json_decode(file_get_contents('php://input'), true);
            
            $eventData = [
                'title' => trim($input['title'] ?? ''),
                'description' => trim($input['description'] ?? ''),
                'event_date' => $input['event_date'] ?? '',
                'event_time' => $input['event_time'] ?? '',
                'location' => trim($input['location'] ?? ''),
                'group_id' => isset($input['group_id']) ? (int)$input['group_id'] : null,
                'created_by' => $userId
            ];
            
            // Validate required fields
            if (empty($eventData['title'])) {
                echo json_encode(['success' => false, 'message' => 'Event title is required']);
                exit;
            }
            
            if (empty($eventData['event_date'])) {
                echo json_encode(['success' => false, 'message' => 'Event date is required']);
                exit;
            }
            
            $eventId = $this->eventModel->createEvent($eventData);
            
            if ($eventId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'event_id' => $eventId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create event'
                ]);
            }
        } catch (Exception $e) {
            error_log('Create event error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * RSVP to event via AJAX
     */
    public function rsvpEvent() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $input = json_decode(file_get_contents('php://input'), true);
            
            $eventId = isset($input['event_id']) ? (int)$input['event_id'] : 0;
            $status = $input['status'] ?? 'interested'; // interested, going, not_going
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'Event ID required']);
                exit;
            }
            
            $result = $this->eventModel->setUserRSVP($eventId, $userId, $status);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'RSVP updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update RSVP'
                ]);
            }
        } catch (Exception $e) {
            error_log('RSVP event error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
    ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_action']))) {
    $controller = new EventsController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
    } else {
        $action = $_GET['ajax_action'] ?? '';
    }
    
    if ($action === 'getEvents') {
        $controller->getEvents();
    } elseif ($action === 'createEvent') {
        $controller->createEvent();
    } elseif ($action === 'rsvpEvent') {
        $controller->rsvpEvent();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
