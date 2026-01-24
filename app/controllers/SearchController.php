<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class SearchController
{
    private UserModel $userModel;
    private GroupModel $groupModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->groupModel = new GroupModel();
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
