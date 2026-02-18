<?php
class FeedController extends BaseController
{
    private UserModel $userModel;
    private PostModel $postModel;
    private GroupModel $groupModel;
    private FriendModel $friendModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();

        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->groupModel = new GroupModel();
        $this->friendModel = new FriendModel();
    }

    public function index()
    {
        $currentUser = $this->userModel->findByField('user_id', $_SESSION['user_id']);
        $friendRequests = $friendRequests ?? [];

        // if (!isset($posts)) {
        //     header('Location: ../controllers/FeedController.php');
        //     exit();
        // }

        $this->render('Feed', [$currentUser, $friendRequests]);
    }
}