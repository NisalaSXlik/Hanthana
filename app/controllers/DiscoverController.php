<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/GroupPostModel.php';
require_once __DIR__ . '/../models/GroupModel.php';

class DiscoverController {
    private $postModel;
    private $groupPostModel;
    private $groupModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->groupPostModel = new GroupPostModel();
        $this->groupModel = new GroupModel();
    }


    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_PATH . "index.php?controller=Login&action=index");
            exit();
        }

        $userId = $_SESSION['user_id'];

        // Use old working methods (don't merge)
        $allPosts = $this->postModel->getTrendingPosts(60, $userId);

        // Mark group posts
        foreach ($allPosts as &$post) {
            $post['is_group_post'] = !empty($post['group_id']);
        }

        // Filter to show only user-created general image/text posts (exclude events, questions, group posts)
        $allPosts = $this->filterDiscoverPosts($allPosts);

        // Sidebar data
        $trendingHashtags = $this->postModel->getTrendingHashtags(10, 7);
        $popularGroups = $this->groupModel->getPopularGroups(8, $userId);

        $joinedGroups = $this->groupModel->getGroupsJoinedBy((int)$userId);
        $joinedMap = [];
        foreach ($joinedGroups as $joinedGroup) {
            $joinedMap[(int)($joinedGroup['group_id'] ?? 0)] = true;
        }

        require_once __DIR__ . '/../helpers/MediaHelper.php';
        foreach ($popularGroups as &$group) {
            $group['display_picture'] = !empty($group['display_picture'])
                ? MediaHelper::resolveMediaPath($group['display_picture'], 'images/default_group.png')
                : MediaHelper::resolveMediaPath('', 'images/default_group.png');

            $groupId = (int)($group['group_id'] ?? 0);
            $group['is_member'] = !empty($joinedMap[$groupId]);
        }
        unset($group);

        require_once __DIR__ . '/../views/discover.php';
    }

    
    
    public function feed() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_PATH . "index.php?controller=Login&action=index");
            exit();
        }

        $userId = $_SESSION['user_id'];
        $clickedPostId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

        // Get all ranked posts
        $allRankedPosts = $this->postModel->getTrendingPosts(60, $userId);

        // Mark group posts and filter to show only user-created general image/text posts
        foreach ($allRankedPosts as &$post) {
            $post['is_group_post'] = !empty($post['group_id']);
        }
        $allRankedPosts = $this->filterDiscoverPosts($allRankedPosts);

        $posts = [];

        if ($clickedPostId > 0) {
            // Find clicked post and all posts after it
            $foundClicked = false;
            $clickedPost = null;

            foreach ($allRankedPosts as $post) {
                if ((int)$post['post_id'] === $clickedPostId) {
                    $clickedPost = $post;
                    $foundClicked = true;
                } else if ($foundClicked) {
                    $posts[] = $post;
                }
            }

            // Put clicked post at front
            if ($clickedPost) {
                array_unshift($posts, $clickedPost);
            } else {
                $posts = $allRankedPosts;
            }
        } else {
            $posts = $allRankedPosts;
        }

        $trendingHashtags = $this->postModel->getTrendingHashtags(10, 7);
        $popularGroups = $this->groupModel->getPopularGroups(8, $userId);

        // Get joined groups for sidebar display
        $joinedGroups = $this->groupModel->getGroupsJoinedBy((int)$userId);
        $joinedMap = [];
        foreach ($joinedGroups as $joinedGroup) {
            $joinedMap[(int)($joinedGroup['group_id'] ?? 0)] = true;
        }

        require_once __DIR__ . '/../helpers/MediaHelper.php';
        foreach ($popularGroups as &$group) {
            $group['display_picture'] = !empty($group['display_picture'])
                ? MediaHelper::resolveMediaPath($group['display_picture'], 'images/default_group.png')
                : MediaHelper::resolveMediaPath('', 'images/default_group.png');

            $groupId = (int)($group['group_id'] ?? 0);
            $group['is_member'] = !empty($joinedMap[$groupId]);
        }
        unset($group);

        require_once __DIR__ . '/../views/discover-feed.php';
    }

    /**
     * Filter posts to show user-created general posts and group discussion posts
     * Excludes: events, questions, polls, assignments, resources from groups
     */
    private function filterDiscoverPosts($posts) {
        return array_values(array_filter($posts, function ($post) {
            $isGroupPost = (int)($post['is_group_post'] ?? 0) === 1;
            $postType = strtolower((string)($post['post_type'] ?? ''));
            $groupPostType = strtolower((string)($post['group_post_type'] ?? ''));
            
            // Allow: User-created general posts (post_type='image', is_group_post=0)
            if (!$isGroupPost && $postType === 'image') {
                return true;
            }
            
            // Allow: Group discussion posts (image/text based)
            if ($isGroupPost && $groupPostType === 'discussion') {
                return true;
            }
            
            return false;
        }));
    }

    // AJAX endpoint to load more posts
    public function loadMorePosts() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = 20;

        $posts = $this->postModel->getTrendingPosts($limit, $userId);
        
        // Mark group posts and apply filter
        foreach ($posts as &$post) {
            $post['is_group_post'] = !empty($post['group_id']);
        }
        $posts = $this->filterDiscoverPosts($posts);
        
        echo json_encode(['success' => true, 'posts' => $posts]);
        exit();
    }


    public function loadMore() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = 12;

        $posts = $this->postModel->getTrendingPosts($limit, $userId);

        // Mark group posts and apply filter
        foreach ($posts as &$post) {
            $post['is_group_post'] = !empty($post['group_id']);
        }
        $posts = $this->filterDiscoverPosts($posts);

        echo json_encode(['success' => true, 'posts' => $posts, 'count' => count($posts)]);
        exit();
    }
    public function getPost() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    if ($postId <= 0) {
        echo json_encode(['error' => 'Invalid post']);
        exit();
    }

    $post = $this->postModel->getPostById($postId);

    if (!$post) {
        echo json_encode(['error' => 'Not found']);
        exit();
    }

    require_once __DIR__ . '/../helpers/MediaHelper.php';
    if (!empty($post['image_url'])) {
        $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
    }
    if (!empty($post['profile_picture'])) {
        $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
    }

    echo json_encode(['success' => true, 'post' => $post]);
    exit();
    }
}