<?php

class FileBankController extends BaseController
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

        $groupPrivacy = strtolower(trim((string)($group['privacy_status'] ?? 'public')));
        if ($groupPrivacy !== 'public' && !$this->groupModel->isMember($groupId, (int)$_SESSION['user_id']) && !$this->groupModel->isGroupAdmin($groupId, (int)$_SESSION['user_id'])) {
            $this->redirect('Feed');
        }

        $_SESSION['current_group_id'] = $groupId;

        $currentUserId = $_SESSION['user_id'];
        $currentUser = $this->userModel->findById($currentUserId);
        $isAdmin = $this->groupModel->isGroupAdmin($groupId, (int)$currentUserId);
        $canModerateFileBank = $isAdmin;
        $membershipState = $this->groupModel->getUserMembershipState($groupId, $currentUserId);
        $isJoined = ($membershipState === 'active') || $isAdmin;

        require_once __DIR__ . '/../views/filebank.php';
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
}