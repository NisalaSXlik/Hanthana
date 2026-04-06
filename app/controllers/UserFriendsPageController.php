<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/FriendModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/GroupPostModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class UserFriendsPageController {
    private $userModel;
    private $postModel;
    private $friendModel;
    private $groupModel;
    private $groupPostModel;
    private $settingsModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->friendModel = new FriendModel();
        $this->groupModel = new GroupModel();
        $this->groupPostModel = new GroupPostModel();
        $this->settingsModel = new SettingsModel();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $viewerId = (int)$_SESSION['user_id'];
        $profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $viewerId;

        $profileUser = $this->userModel->findById($profileUserId);
        if (!$profileUser) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index&error=user_not_found');
            exit();
        }

        $isOwner = $viewerId === $profileUserId;

        $displayName = trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''));
        if ($displayName === '') {
            $displayName = $profileUser['username'] ?? 'Unknown User';
        }
        $displayHandle = !empty($profileUser['username']) ? '@' . $profileUser['username'] : '';

        $bio = $profileUser['bio'] ?? '';
        $location = $profileUser['location'] ?? 'Not specified';
        $university = $profileUser['university'] ?? 'Not specified';

        $rawEmail = $profileUser['email'] ?? '';
        $rawPhone = $profileUser['phone_number'] ?? '';
        $canViewEmail = $this->settingsModel->shouldShowEmail($profileUserId, $viewerId);
        $canViewPhone = $this->settingsModel->shouldShowPhone($profileUserId, $viewerId);
        $email = $canViewEmail ? $rawEmail : '';
        $phone = $canViewPhone ? $rawPhone : '';

        $dob = '';
        $dobValue = '';
        if (!empty($profileUser['date_of_birth'])) {
            $dob = date('F j, Y', strtotime($profileUser['date_of_birth']));
            $dobValue = $profileUser['date_of_birth'];
        }

        $joinedAt = date('F Y', strtotime($profileUser['created_at'] ?? 'now'));

        $interestTags = [];
        if (!empty($profileUser['interests'])) {
            $interestTags = array_filter(array_map('trim', explode(',', $profileUser['interests'])));
        }

        $friendsCount = $this->friendModel->getFriendsCount($profileUserId);
        $friendListLimit = 50;
        $friendList = $this->friendModel->getAcceptedFriends($profileUserId, $friendListLimit);
        $friendListCount = count($friendList);
        $hasMoreFriends = $friendsCount > $friendListLimit;
        $pendingRequests = $isOwner ? $this->friendModel->getIncomingRequests($viewerId) : [];

        $postVisibility = $profileUser['post_visibility'] ?? 'public';
        $profileVisibility = $profileUser['profile_visibility'] ?? 'public';

        $postsArePrivate = false;
        $personalPosts = [];
        $personalPostCount = 0;

        if ($isOwner) {
            $personalPosts = $this->postModel->getUserPosts($profileUserId);
            $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
        } else {
            if ($postVisibility === 'only_me') {
                $postsArePrivate = true;
            } elseif ($postVisibility === 'friends_only') {
                $areFriends = $this->friendModel->areFriends($viewerId, $profileUserId);
                if ($areFriends) {
                    $personalPosts = $this->postModel->getUserPosts($profileUserId);
                    $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
                } else {
                    $postsArePrivate = true;
                }
            } else {
                $personalPosts = $this->postModel->getUserPosts($profileUserId);
                $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
            }
        }

        $groupPosts = [];
        $groupPostCount = 0;
        if (!$postsArePrivate || $isOwner) {
            $groupPosts = $this->groupPostModel->getUserGroupPosts($profileUserId);
            $groupPostCount = count($groupPosts);
        }

        $totalPostsCount = $personalPostCount + $groupPostCount;

        $photoPosts = [];
        if (!$postsArePrivate || $isOwner) {
            $photoPosts = $this->postModel->getUserPhotoPosts($profileUserId);
        }

        $incomingFriendRequests = $this->friendModel->getIncomingRequests($viewerId);
        $joinedGroupsCount = $this->groupModel->getUserJoinedGroupsCount($profileUserId);
        $userGroups = $this->groupModel->getUserGroupsWithDetails($profileUserId, 5);

        require __DIR__ . '/../views/userfriendspage.php';
    }
}