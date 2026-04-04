<?php
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

class GroupMessagesController
{
    private $groupModel;
    private $userModel;
    private $friendModel;

    public function __construct()
    {
        $this->groupModel = new GroupModel();
        $this->userModel = new UserModel();
        $this->friendModel = new FriendModel();
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $currentUserId = (int)$_SESSION['user_id'];
        $currentUser = $this->userModel->findById($currentUserId);
        $incomingFriendRequests = $this->friendModel->getIncomingRequests($currentUserId);

        $createdGroups = $this->groupModel->getGroupsCreatedBy($currentUserId);
        $joinedGroups = $this->groupModel->getGroupsJoinedBy($currentUserId);

        $createdGroupIds = array_column($createdGroups, 'group_id');
        $joinedOnlyGroups = array_values(array_filter($joinedGroups, function ($group) use ($createdGroupIds) {
            return isset($group['group_id']) && !in_array($group['group_id'], $createdGroupIds, true);
        }));

        $allGroups = array_values(array_merge($createdGroups, $joinedOnlyGroups));
        $groupNameFallbacks = ['Campus Explorers', 'Weekend Hikers', 'Foodies Circle', 'Photo Walk Crew', 'Study Circle'];
        $senderFallbacks = ['Nethmi', 'Kavindu', 'Ishara', 'Pabasara', 'Tharindu'];
        $messageFallbacks = [
            'New meetup details were shared. Check the pinned update when free.',
            'Reminder: tonight we vote on the next group event location.',
            'A new photo dump was posted in the group album.',
            'Quick update: route and start time changed for tomorrow.',
            'Admin note: please confirm attendance before 6 PM.'
        ];
        $timeFallbacks = ['2 min ago', '12 min ago', '35 min ago', '1 hr ago', '3 hr ago'];
        $unreadFallbacks = [3, 1, 2, 4, 1];

        $groupMessages = [];
        for ($i = 0; $i < 10; $i++) {
            $groupRow = $allGroups[$i % max(1, count($allGroups))] ?? null;
            $groupMessages[] = [
                'group_id' => isset($groupRow['group_id']) ? (int)$groupRow['group_id'] : null,
                'group_name' => $groupRow['name'] ?? $groupNameFallbacks[$i % count($groupNameFallbacks)],
                'sender' => $senderFallbacks[$i % count($senderFallbacks)],
                'message' => $messageFallbacks[$i % count($messageFallbacks)],
                'time' => $timeFallbacks[$i % count($timeFallbacks)],
                'unread_count' => $unreadFallbacks[$i % count($unreadFallbacks)],
                'priority' => ($i % 3 === 0) ? 'high' : 'normal'
            ];
        }

        $unreadTotal = array_sum(array_column($groupMessages, 'unread_count'));
        $highPriorityCount = count(array_filter($groupMessages, function ($item) {
            return ($item['priority'] ?? 'normal') === 'high';
        }));

        $groupLatestUpdates = [];
        foreach ($groupMessages as $message) {
            $groupKey = $message['group_id'] !== null
                ? 'gid_' . (int)$message['group_id']
                : 'gname_' . strtolower((string)$message['group_name']);

            if (!isset($groupLatestUpdates[$groupKey])) {
                $groupLatestUpdates[$groupKey] = [
                    'group_id' => $message['group_id'],
                    'group_name' => $message['group_name'],
                    'latest_sender' => $message['sender'],
                    'latest_message' => $message['message'],
                    'latest_time' => $message['time'],
                    'unread_count' => (int)$message['unread_count']
                ];
            } else {
                $groupLatestUpdates[$groupKey]['unread_count'] += (int)$message['unread_count'];
            }
        }
        $groupLatestUpdates = array_values($groupLatestUpdates);

        require_once __DIR__ . '/../views/group-messages.php';
    }
}
