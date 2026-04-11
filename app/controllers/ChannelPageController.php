<?php

class ChannelPageController extends BaseController
{
    private GroupModel $groupModel;
    private UserModel $userModel;
    private ChannelModel $channelModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->groupModel = new GroupModel();
        $this->userModel = new UserModel();
        $this->channelModel = new ChannelModel();
    }

    public function index()
    {
        $groupId = $this->resolveGroupId();
        if ($groupId <= 0) {
            $this->redirect('Feed');
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            $this->redirect('Feed');
        }

        $userId = (int) $_SESSION['user_id'];
        $groupPrivacy = strtolower(trim((string)($group['privacy_status'] ?? 'public')));
        if ($groupPrivacy !== 'public' && !$this->channelModel->isActiveGroupMember($groupId, $userId)) {
            $this->redirect('Feed');
        }

        $_SESSION['current_group_id'] = $groupId;
        $currentUserId = $userId;
        $currentUser = $this->userModel->findById($currentUserId);
        $isCreator = (int)($group['created_by'] ?? 0) === $userId;
        $isGroupAdmin = $this->groupModel->isGroupAdmin($groupId, $userId);
        $isAdmin = $isCreator || $isGroupAdmin;

        require_once __DIR__ . '/../views/groupchannels.php';
    }

    public function listChannels()
    {
        $payload = $this->requestData();
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : $this->resolveGroupId();
        $userId = (int) $_SESSION['user_id'];

        if ($groupId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Valid group ID is required.']], 400);
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            return $this->response(['status' => 'error', 'errors' => ['Group not found.']], 404);
        }

        $groupPrivacy = strtolower(trim((string)($group['privacy_status'] ?? 'public')));
        if ($groupPrivacy !== 'public' && !$this->channelModel->isActiveGroupMember($groupId, $userId)) {
            return $this->response(['status' => 'error', 'errors' => ['You are not a member of this group.']], 403);
        }

        $channels = $this->channelModel->listChannelsForUser($groupId, $userId);

        return $this->response([
            'status' => 'success',
            'data' => [
                'channels' => $channels,
            ],
        ]);
    }

    public function createChannel()
    {
        $payload = $this->requestData();
        $userId = (int) $_SESSION['user_id'];
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        $errors = [];
        if ($groupId <= 0) {
            $errors[] = 'Valid group ID is required.';
        }
        if ($name === '') {
            $errors[] = 'Channel name is required.';
        }
        if (strcasecmp($name, 'Main') === 0) {
            $errors[] = 'Main channel is reserved and created automatically.';
        }

        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors], 400);
        }

        if (!$this->channelModel->isActiveGroupMember($groupId, $userId)) {
            return $this->response(['status' => 'error', 'errors' => ['You are not a member of this group.']], 403);
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            return $this->response(['status' => 'error', 'errors' => ['Group not found.']], 404);
        }

        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $userId)
            || (int)($group['created_by'] ?? 0) === $userId;

        if ($this->channelModel->isChannelNameTaken($groupId, $name)) {
            return $this->response(['status' => 'error', 'errors' => ['A channel with this name already exists.']], 400);
        }

        $displayPicture = $this->handleDisplayPictureUpload();
        if (isset($displayPicture['errors'])) {
            return $this->response(['status' => 'error', 'errors' => $displayPicture['errors']], 400);
        }

        if (!$isAdmin) {
            $queued = $this->groupModel->queueChannelCreationRequest($groupId, $userId, [
                'name' => $name,
                'description' => $description,
                'display_picture' => $displayPicture['path'] ?? 'uploads/channel_dp/default.png',
            ]);

            if (!$queued) {
                return $this->response(['status' => 'error', 'errors' => ['Failed to submit channel request.']], 500);
            }

            return $this->response([
                'status' => 'success',
                'queued' => true,
                'message' => 'Channel request submitted for admin approval.',
            ]);
        }

        $created = $this->channelModel->createChannel([
            'group_id' => $groupId,
            'name' => $name,
            'description' => $description,
            'display_picture' => $displayPicture['path'] ?? 'uploads/channel_dp/default.png',
        ], $userId);

        if (!$created) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to create channel.']], 500);
        }

        return $this->response([
            'status' => 'success',
            'message' => 'Channel created successfully.',
            'data' => ['channel' => $created],
        ]);
    }

    public function joinChannel()
    {
        $payload = $this->requestData();
        $channelId = isset($payload['channel_id']) ? (int) $payload['channel_id'] : 0;
        $userId = (int) $_SESSION['user_id'];

        if ($channelId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Valid channel ID is required.']], 400);
        }

        $channel = $this->channelModel->getChannelById($channelId);
        if (!$channel) {
            return $this->response(['status' => 'error', 'errors' => ['Channel not found.']], 404);
        }

        $groupId = (int) ($channel['group_id'] ?? 0);
        if (!$this->channelModel->isActiveGroupMember($groupId, $userId)) {
            return $this->response(['status' => 'error', 'errors' => ['You must join the group before joining channels.']], 403);
        }

        if (!$this->channelModel->joinChannel($channelId, $userId)) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to join channel.']], 500);
        }

        return $this->response([
            'status' => 'success',
            'message' => 'Joined channel successfully.',
        ]);
    }

    private function requestData(): array
    {
        if (!empty($this->data) && is_array($this->data)) {
            return $this->data;
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return [];
    }

    private function resolveGroupId(): int
    {
        $candidates = [
            $_GET['group_id'] ?? null,
            $_GET['groupId'] ?? null,
            $_GET['id'] ?? null,
            $_SESSION['current_group_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $groupId = (int) $candidate;
            if ($groupId > 0) {
                return $groupId;
            }
        }

        $joinedGroups = $this->groupModel->getGroupsJoinedBy((int) $_SESSION['user_id']);
        if (!empty($joinedGroups) && !empty($joinedGroups[0]['group_id'])) {
            return (int) $joinedGroups[0]['group_id'];
        }

        return 0;
    }

    private function handleDisplayPictureUpload(): array
    {
        if (!isset($_FILES['display_picture']) || !isset($_FILES['display_picture']['tmp_name']) || !is_uploaded_file($_FILES['display_picture']['tmp_name'])) {
            return ['path' => 'uploads/channel_dp/default.png'];
        }

        $file = $_FILES['display_picture'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['errors' => ['Display picture upload failed.']];
        }

        $projectRoot = dirname(__DIR__, 2);
        $targetDir = $projectRoot . '/public/uploads/channel_dp';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['errors' => ['Could not prepare display picture upload directory.']];
        }

        $originalName = (string) ($file['name'] ?? 'channel.png');
        $cleanOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $cleanOriginalName = $cleanOriginalName ?: 'channel.png';

        $storedName = uniqid('ch_', true) . '_' . $cleanOriginalName;
        $absolutePath = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['errors' => ['Could not save uploaded display picture.']];
        }

        return ['path' => 'uploads/channel_dp/' . $storedName];
    }
}