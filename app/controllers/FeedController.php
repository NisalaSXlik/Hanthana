<?php
session_start();
require_once __DIR__ . '/../models/PostModel.php';

class FeedController {
    public function index() {
        $posts = (new PostModel())->getFeedPosts();
        require __DIR__ . '/../views/myFeed.php';
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    (new FeedController())->index();
}