<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/FriendModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/CalendarReminderModel.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class SearchController
{
    private UserModel $userModel;
    private GroupModel $groupModel;
    private FriendModel $friendModel;
    private PostModel $postModel;
    private QuestionModel $questionModel;
    private CalendarReminderModel $calendarModel;
    private PDO $db;
    private array $publicGroupCache = [];
    private array $publicUserCache = [];

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->groupModel = new GroupModel();
        $this->friendModel = new FriendModel();
        $this->postModel = new PostModel();
        $this->questionModel = new QuestionModel();
        $this->calendarModel = new CalendarReminderModel();
        $this->db = (new Database())->getConnection();
    }

    private function mapFriendshipStateForButton(string $state): string
    {
        switch ($state) {
            case 'friends':
                return 'friends';
            case 'pending_them':
                return 'pending_outgoing';
            case 'pending_me':
                return 'incoming_pending';
            case 'blocked':
                return 'blocked';
            case 'none':
            default:
                return 'none';
        }
    }

    private function limitText(string $value, int $max = 140): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        return mb_strimwidth($clean, 0, $max, '...');
    }

    private function buildSearchTokens(string $query): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($query));
        if ($normalized === null || $normalized === '') {
            return [];
        }

        $tokens = [];
        foreach (explode(' ', $normalized) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $lower = mb_strtolower($part);
            $tokens[$lower] = true;

            if (mb_substr($lower, 0, 1) === '#') {
                $withoutHash = ltrim($lower, '#');
                if ($withoutHash !== '') {
                    $tokens[$withoutHash] = true;
                }
            } else {
                $tokens['#' . $lower] = true;
            }
        }

        return array_keys($tokens);
    }

    private function haystackContainsAnyToken(string $haystack, array $tokens): bool
    {
        if ($haystack === '') {
            return false;
        }

        if (empty($tokens)) {
            return true;
        }

        foreach ($tokens as $token) {
            if ($token !== '' && mb_stripos($haystack, $token) !== false) {
                return true;
            }
        }

        return false;
    }

    private function getMutualFriendCount(int $viewerId, int $targetUserId): int
    {
        if ($viewerId <= 0 || $targetUserId <= 0 || $viewerId === $targetUserId) {
            return 0;
        }

        $sql = "SELECT COUNT(*)
                FROM (
                    SELECT CASE WHEN f.user_id = :viewer THEN f.friend_id ELSE f.user_id END AS friend_id
                    FROM Friends f
                    WHERE f.status = 'accepted' AND (f.user_id = :viewer OR f.friend_id = :viewer)
                ) viewer_friends
                INNER JOIN (
                    SELECT CASE WHEN f.user_id = :target THEN f.friend_id ELSE f.user_id END AS friend_id
                    FROM Friends f
                    WHERE f.status = 'accepted' AND (f.user_id = :target OR f.friend_id = :target)
                ) target_friends
                ON viewer_friends.friend_id = target_friends.friend_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':viewer' => $viewerId,
            ':target' => $targetUserId,
        ]);

        return (int)$stmt->fetchColumn();
    }

    private function searchPeople(string $query, int $viewerId, int $limit = 8): array
    {
        $matches = $this->userModel->searchUsersWithPrivacy($query, $viewerId, $limit);
        $results = [];

        foreach ($matches as $user) {
            $userId = (int)($user['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $avatar = MediaHelper::resolveMediaPath($user['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');
            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if ($name === '') {
                $name = (string)($user['username'] ?? 'User');
            }

            $friendState = $userId === $viewerId
                ? 'self'
                : $this->mapFriendshipStateForButton($this->friendModel->getFriendshipStatus($viewerId, $userId));

            $results[] = [
                'user_id' => $userId,
                'name' => $name,
                'username' => (string)($user['username'] ?? ''),
                'avatar' => $avatar,
                'mutual_friends' => $this->getMutualFriendCount($viewerId, $userId),
                'friend_state' => $friendState,
                'profile_url' => BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $userId,
            ];
        }

        return $results;
    }

    private function searchPosts(string $query, int $viewerId, int $limit = 8): array
    {
        $posts = $this->postModel->getFeedPosts($viewerId, false);
        $queryTokens = $this->buildSearchTokens($query);
        $results = [];
        $postIds = [];
        foreach ($posts as $row) {
            $id = (int)($row['post_id'] ?? 0);
            if ($id > 0) {
                $postIds[] = $id;
            }
        }
        $fileNameMap = $this->getPostFileNamesMap($postIds);

        foreach ($posts as $post) {
            if (!empty($post['is_deleted'])) {
                continue;
            }

            $authorName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
            if ($authorName === '') {
                $authorName = (string)($post['username'] ?? 'Unknown');
            }

            $authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
            $postId = (int)($post['post_id'] ?? 0);
            $isGroupPost = !empty($post['group_id']);
            $fileNames = trim((string)($fileNameMap[$postId] ?? ''));

            $metadataRaw = $post['metadata'] ?? null;
            if (is_array($metadataRaw)) {
                $metadataText = json_encode($metadataRaw) ?: '';
            } else {
                $metadataText = (string)$metadataRaw;
            }

            $haystack = mb_strtolower(implode(' ', [
                (string)($post['content'] ?? ''),
                (string)($post['event_title'] ?? ''),
                (string)($post['event_location'] ?? ''),
                (string)($post['username'] ?? ''),
                $authorName,
                (string)($post['group_name'] ?? ''),
                $fileNames,
                $metadataText,
            ]));

            if (!$this->haystackContainsAnyToken($haystack, $queryTokens)) {
                continue;
            }

            $postUrl = $isGroupPost
                ? BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#group-post-' . $postId
                : BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#personal-post-' . $postId;

            $results[] = [
                'post_id' => $postId,
                'author_name' => $authorName,
                'author_username' => (string)($post['username'] ?? ''),
                'author_avatar' => MediaHelper::resolveMediaPath($post['profile_picture'] ?? '', 'uploads/user_dp/default.png'),
                'snippet' => $this->limitText((string)($post['content'] ?? ($fileNames !== '' ? $fileNames : 'No preview available')), 190),
                'thumbnail' => !empty($post['image_url']) ? MediaHelper::resolveMediaPath((string)$post['image_url'], '') : '',
                'source' => !empty($post['group_name']) ? (string)$post['group_name'] : 'Public feed',
                'likes' => (int)($post['upvote_count'] ?? 0),
                'comments' => (int)($post['comment_count'] ?? 0),
                'posted_at' => (string)($post['created_at'] ?? ''),
                'file_names' => $fileNames,
                'url' => $postUrl,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private function classifyStreamPost(array $post): string
    {
        $isGroupPost = !empty($post['group_id']);
        $type = $isGroupPost
            ? strtolower((string)($post['group_post_type'] ?? 'discussion'))
            : strtolower((string)($post['post_type'] ?? 'text'));

        if ($type === 'event') {
            return 'events';
        }

        if ($type === 'question') {
            return 'qna';
        }

        if ($type === 'resource') {
            return 'resources';
        }

        return 'posts';
    }

    private function searchStreamPosts(string $query, int $viewerId, int $limit = 100): array
    {
        $posts = $this->postModel->getFeedPosts($viewerId, false);
        $queryTokens = $this->buildSearchTokens($query);
        $results = [];
        $postIds = [];

        foreach ($posts as $row) {
            $id = (int)($row['post_id'] ?? 0);
            if ($id > 0) {
                $postIds[] = $id;
            }
        }

    $fileNameMap = $this->getPostFileNamesMap($postIds);

        foreach ($posts as $post) {
            if (!empty($post['is_deleted'])) {
                continue;
            }

            $postId = (int)($post['post_id'] ?? 0);
            $authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
            $isGroupPost = !empty($post['group_id']);
            $category = $this->classifyStreamPost($post);
            $fileNames = trim((string)($fileNameMap[$postId] ?? ''));


            $authorName = trim((string)($post['first_name'] ?? '') . ' ' . (string)($post['last_name'] ?? ''));
            if ($authorName === '') {
                $authorName = (string)($post['username'] ?? 'Unknown');
            }

            $metadataRaw = $post['metadata'] ?? null;
            if (is_array($metadataRaw)) {
                $metadataText = json_encode($metadataRaw) ?: '';
            } else {
                $metadataText = (string)$metadataRaw;
            }

            $haystack = mb_strtolower(implode(' ', [
                (string)($post['content'] ?? ''),
                (string)($post['event_title'] ?? ''),
                (string)($post['event_location'] ?? ''),
                (string)($post['username'] ?? ''),
                $authorName,
                (string)($post['group_name'] ?? ''),
                $fileNames,
                $metadataText,
            ]));

            if (!$this->haystackContainsAnyToken($haystack, $queryTokens)) {
                continue;
            }

            $postUrl = $isGroupPost
                ? BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#group-post-' . $postId
                : BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#personal-post-' . $postId;

            $post['stream_category'] = $category;
            $post['stream_url'] = $postUrl;
            $post['stream_author_name'] = $authorName;
            $post['stream_author_avatar'] = MediaHelper::resolveMediaPath($post['profile_picture'] ?? '', 'uploads/user_dp/default.png');
            $post['stream_file_names'] = $fileNames;
            $post['stream_image_url'] = !empty($post['image_url']) ? MediaHelper::resolveMediaPath((string)$post['image_url'], '') : '';

            $results[] = $post;
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private function getPostFileNamesMap(array $postIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static function (int $id): bool {
            return $id > 0;
        })));

        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "SELECT post_id, GROUP_CONCAT(file_name ORDER BY postmedia_id SEPARATOR ' • ') AS file_names
                FROM PostMedia
                WHERE post_id IN ($placeholders)
                GROUP BY post_id";

        $stmt = $this->db->prepare($sql);
        foreach ($cleanIds as $idx => $id) {
            $stmt->bindValue($idx + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $postId = (int)($row['post_id'] ?? 0);
            if ($postId > 0) {
                $map[$postId] = (string)($row['file_names'] ?? '');
            }
        }

        return $map;
    }

    private function isPublicGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        if (array_key_exists($groupId, $this->publicGroupCache)) {
            return $this->publicGroupCache[$groupId];
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM GroupsTable
             WHERE group_id = ?
               AND COALESCE(is_active, 1) = 1
               AND LOWER(TRIM(COALESCE(privacy_status, 'public'))) = 'public'
             LIMIT 1"
        );
        $stmt->execute([$groupId]);
        $isPublic = (bool)$stmt->fetchColumn();
        $this->publicGroupCache[$groupId] = $isPublic;

        return $isPublic;
    }

    private function isPublicUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (array_key_exists($userId, $this->publicUserCache)) {
            return $this->publicUserCache[$userId];
        }

        $stmt = $this->db->prepare(
            "SELECT
                CASE
                    WHEN LOWER(TRIM(COALESCE(us.profile_visibility, 'friends'))) = 'everyone' THEN 1
                    ELSE 0
                END AS is_public
             FROM Users u
             LEFT JOIN UserSettings us ON us.user_id = u.user_id
             WHERE u.user_id = ?
               AND COALESCE(u.is_active, 1) = 1
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $isPublic = (int)$stmt->fetchColumn() === 1;
        $this->publicUserCache[$userId] = $isPublic;

        return $isPublic;
    }

    private function isPublicPostVisible(array $post): bool
    {
        $groupId = (int)($post['group_id'] ?? 0);
        if ($groupId > 0) {
            return $this->isPublicGroup($groupId);
        }

        $visibility = strtolower(trim((string)($post['visibility'] ?? '')));
        if ($visibility !== 'public') {
            return false;
        }

        $authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
        return $this->isPublicUser($authorUserId);
    }

    private function searchGroupsPage(string $query, int $viewerId, int $limit = 8): array
    {
        $groupMatches = $this->groupModel->searchGroups($query, $viewerId);
        $results = [];

        foreach ($groupMatches as $group) {
            $groupId = (int)($group['group_id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $results[] = [
                'group_id' => $groupId,
                'name' => (string)($group['name'] ?? 'Group'),
                'description' => $this->limitText((string)($group['description'] ?? ''), 150),
                'member_count' => (int)($group['member_count'] ?? 0),
                'privacy' => strtolower((string)($group['privacy_status'] ?? 'public')),
                'avatar' => MediaHelper::resolveMediaPath($group['display_picture'] ?? '', 'uploads/group_dp/default_group_dp.jpg'),
                'is_member' => !empty($group['is_member']),
                'group_url' => BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . $groupId,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private function searchEvents(string $query, int $viewerId, int $limit = 8): array
    {
        $posts = $this->postModel->getFeedPosts($viewerId, false);
        $results = [];

        foreach ($posts as $post) {
            $type = !empty($post['group_id'])
                ? strtolower((string)($post['group_post_type'] ?? 'discussion'))
                : strtolower((string)($post['post_type'] ?? 'text'));

            if ($type !== 'event') {
                continue;
            }

            $title = trim((string)($post['event_title'] ?? ''));
            if ($title === '') {
                $title = $this->limitText((string)($post['content'] ?? 'Untitled event'), 100);
            }

            $haystack = mb_strtolower(implode(' ', [
                $title,
                (string)($post['content'] ?? ''),
                (string)($post['event_location'] ?? ''),
                (string)($post['group_name'] ?? ''),
            ]));

            if (mb_stripos($haystack, mb_strtolower($query)) === false) {
                continue;
            }

            $postId = (int)($post['post_id'] ?? 0);
            $authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
            $eventDate = trim((string)($post['event_date'] ?? ''));
            $eventTime = trim((string)($post['event_time'] ?? ''));

            $results[] = [
                'post_id' => $postId,
                'title' => $title,
                'date_time' => trim($eventDate . ' ' . $eventTime),
                'location' => (string)($post['event_location'] ?? 'Location not provided'),
                'going_count' => $postId > 0 ? $this->calendarModel->getGoingCount($postId) : 0,
                'created_at' => (string)($post['created_at'] ?? ''),
                'author_name' => trim((string)($post['first_name'] ?? '') . ' ' . (string)($post['last_name'] ?? '')),
                'author_username' => (string)($post['username'] ?? ''),
                'author_avatar' => MediaHelper::resolveMediaPath($post['profile_picture'] ?? '', 'uploads/user_dp/default.png'),
                'snippet' => $this->limitText((string)($post['content'] ?? ''), 180),
                'url' => BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#personal-post-' . $postId,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private function searchQuestions(string $query, int $viewerId, int $limit = 8): array
    {
        $questions = $this->questionModel->getQuestionsFeed($viewerId, [
            'search' => $query,
            'sort' => 'recent',
            'limit' => $limit,
            'offset' => 0,
        ]);

        $results = [];
        foreach ($questions as $question) {
            $questionId = (int)($question['question_id'] ?? 0);
            if ($questionId <= 0) {
                continue;
            }

            $authorUserId = (int)($question['user_id'] ?? 0);
            if (!$this->isPublicUser($authorUserId)) {
                continue;
            }

            $upvotes = (int)($question['upvote_count'] ?? 0);
            $downvotes = (int)($question['downvote_count'] ?? 0);

            $results[] = [
                'question_id' => $questionId,
                'title' => (string)($question['title'] ?? 'Untitled question'),
                'excerpt' => $this->limitText((string)($question['content'] ?? ''), 170),
                'answer_count' => (int)($question['answer_count'] ?? 0),
                'votes' => $upvotes - $downvotes,
                'created_at' => (string)($question['created_at'] ?? ''),
                'author_name' => trim((string)($question['first_name'] ?? '') . ' ' . (string)($question['last_name'] ?? '')),
                'author_username' => (string)($question['username'] ?? ''),
                'author_avatar' => MediaHelper::resolveMediaPath($question['profile_picture'] ?? '', 'uploads/user_dp/default.png'),
                'url' => BASE_PATH . 'index.php?controller=QnA&action=view&id=' . $questionId,
            ];
        }

        return $results;
    }

    private function searchFilesAndResources(string $query, int $viewerId, int $limit = 8): array
    {
        $term = '%' . $query . '%';
        $results = [];

        $sql = "SELECT
                    pm.file_name,
                    pm.file_type,
                    pm.file_url,
                    pm.uploaded_at,
                    u.username,
                    g.name AS group_name
                FROM PostMedia pm
                INNER JOIN Post p ON p.post_id = pm.post_id
                INNER JOIN Users u ON u.user_id = pm.uploader_id
                LEFT JOIN GroupsTable g ON g.group_id = p.group_id
                WHERE
                    pm.file_name LIKE :term
                    OR pm.file_url LIKE :term
                    OR p.content LIKE :term
                    OR COALESCE(g.name, '') LIKE :term
                ORDER BY pm.uploaded_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term', $term, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'name' => (string)($row['file_name'] ?? 'File'),
                'type' => (string)($row['file_type'] ?? 'other'),
                'uploader_or_group' => !empty($row['group_name'])
                    ? (string)$row['group_name']
                    : '@' . (string)($row['username'] ?? 'unknown'),
                'uploaded_at' => (string)($row['uploaded_at'] ?? ''),
                'url' => MediaHelper::resolveMediaPath((string)($row['file_url'] ?? ''), ''),
            ];
        }

        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $qSql = "SELECT
                        q.question_id,
                        q.title,
                        q.attachment_name,
                        q.attachment_type,
                        q.attachment_path,
                        q.attachment_size,
                        q.created_at,
                        u.username
                    FROM Questions q
                    INNER JOIN Users u ON u.user_id = q.user_id
                    WHERE q.is_deleted = FALSE
                      AND q.attachment_path IS NOT NULL
                      AND q.attachment_path != ''
                      AND (
                            q.attachment_name LIKE :term
                            OR q.title LIKE :term
                            OR q.content LIKE :term
                      )
                    ORDER BY q.created_at DESC
                    LIMIT :limit";

            $qStmt = $this->db->prepare($qSql);
            $qStmt->bindValue(':term', $term, PDO::PARAM_STR);
            $qStmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
            $qStmt->execute();

            foreach ($qStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'name' => (string)($row['attachment_name'] ?? ($row['title'] ?? ('Question attachment #' . (int)($row['question_id'] ?? 0)))),
                    'type' => (string)($row['attachment_type'] ?? 'attachment'),
                    'uploader_or_group' => '@' . (string)($row['username'] ?? 'unknown'),
                    'uploaded_at' => (string)($row['created_at'] ?? ''),
                    'url' => MediaHelper::resolveMediaPath((string)($row['attachment_path'] ?? ''), ''),
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $viewerId = (int)$_SESSION['user_id'];
        $currentUser = $this->userModel->findById($viewerId);
        $query = trim((string)($_GET['query'] ?? ''));

        $peopleResults = [];
        $postResults = [];
        $groupResults = [];
        $eventResults = [];
        $questionResults = [];
        $resourceResults = [];
        $streamPosts = [];

        if ($query !== '') {
            $peopleResults = $this->searchPeople($query, $viewerId, 8);
            $postResults = $this->searchPosts($query, $viewerId, 8);
            $groupResults = $this->searchGroupsPage($query, $viewerId, 8);
            $eventResults = $this->searchEvents($query, $viewerId, 8);
            $questionResults = $this->searchQuestions($query, $viewerId, 8);
            $resourceResults = $this->searchFilesAndResources($query, $viewerId, 8);
            $streamPosts = $this->searchStreamPosts($query, $viewerId, 120);
        }

        require_once __DIR__ . '/../views/search.php';
    }

    private function formatUserResult(array $user): array
    {
        $avatar = MediaHelper::resolveMediaPath($user['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');
        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = $user['username'];
        }

        return [
            'id' => (int)$user['user_id'],
            'name' => $fullName,
            'username' => $user['username'],
            'avatar' => $avatar,
            'profileUrl' => BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . (int)$user['user_id'],
        ];
    }

    private function formatGroupResult(array $group): array
    {
        $avatar = MediaHelper::resolveMediaPath($group['display_picture'] ?? '', 'uploads/group_dp/default_group_dp.jpg');
        $memberCount = (int)($group['member_count'] ?? 0);
        $privacy = $group['privacy_status'] ?? 'public';
        $isMember = !empty($group['is_member']);
        $hasPending = !empty($group['has_pending_request']);
        $ownerId = isset($group['created_by']) ? (int)$group['created_by'] : null;
        $viewerId = (int)($_SESSION['user_id'] ?? 0);

        return [
            'id' => (int)$group['group_id'],
            'name' => $group['name'] ?? 'Group',
            'tag' => $group['tag'] ?? null,
            'description' => $group['description'] ?? null,
            'avatar' => $avatar,
            'memberCount' => $memberCount,
            'privacy' => $privacy,
            'isMember' => $isMember,
            'hasPendingRequest' => $hasPending,
            'isOwner' => $ownerId !== null && $ownerId === $viewerId,
            'groupUrl' => BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . (int)$group['group_id'],
        ];
    }

    private function ensureAuthenticated(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return false;
        }

        return true;
    }

    public function users(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->ensureAuthenticated()) {
            return;
        }

        $query = isset($_GET['query']) ? trim((string)$_GET['query']) : '';
        if ($query === '') {
            echo json_encode(['success' => true, 'results' => []]);
            return;
        }

        $matches = $this->userModel->searchUsers($query, 8);
        $results = array_map([$this, 'formatUserResult'], $matches);

        echo json_encode(['success' => true, 'results' => $results]);
    }

    public function all(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->ensureAuthenticated()) {
            return;
        }

        $query = isset($_GET['query']) ? trim((string)$_GET['query']) : '';
        if ($query === '') {
            echo json_encode(['success' => true, 'users' => [], 'groups' => []]);
            return;
        }

        $userMatches = $this->userModel->searchUsers($query, 6);
        $users = array_map([$this, 'formatUserResult'], $userMatches);

        $groupMatches = $this->groupModel->searchGroups($query, (int)$_SESSION['user_id'], 6);
        $groups = array_map([$this, 'formatGroupResult'], $groupMatches);

        echo json_encode([
            'success' => true,
            'users' => $users,
            'groups' => $groups,
        ]);
    }
}
