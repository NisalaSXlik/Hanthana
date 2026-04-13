<?php

class GroupSettingsController extends BaseController
{
    private GroupModel $groupModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->groupModel = new GroupModel();
        $this->userModel = new UserModel();
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

        $_SESSION['current_group_id'] = $groupId;

        $currentUserId = (int)$_SESSION['user_id'];
        $currentUser = $this->userModel->findById($currentUserId);
        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $currentUserId);
        $canModerateFileBank = $isAdmin;
        $membershipState = $this->groupModel->getUserMembershipState($groupId, $currentUserId);
        $isJoined = ($membershipState === 'active');

        if (!$isAdmin) {
            header('Location: ' . rtrim(BASE_PATH, '/') . '/index.php?controller=Group&action=index&group_id=' . $groupId);
            exit;
        }

        require_once __DIR__ . '/../views/groupsettings.php';
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
            $groupId = (int)$candidate;
            if ($groupId > 0) {
                return $groupId;
            }
        }

        $joinedGroups = $this->groupModel->getGroupsJoinedBy((int)$_SESSION['user_id']);
        if (!empty($joinedGroups) && !empty($joinedGroups[0]['group_id'])) {
            return (int)$joinedGroups[0]['group_id'];
        }

        return 0;
    }
}
