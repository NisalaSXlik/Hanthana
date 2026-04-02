<?php

require_once __DIR__ . '/DiscussionPostModel.php';
require_once __DIR__ . '/QuestionPostModel.php';
require_once __DIR__ . '/ResourcePostModel.php';
require_once __DIR__ . '/PollPostModel.php';
require_once __DIR__ . '/EventPostModel.php';
require_once __DIR__ . '/AssignmentPostModel.php';

class PostTypeFactory {
    public static function make(string $type): BaseGroupPostModel {
        switch ($type) {
            case 'question':
                return new QuestionPostModel();
            case 'resource':
                return new ResourcePostModel();
            case 'poll':
                return new PollPostModel();
            case 'event':
                return new EventPostModel();
            case 'assignment':
                return new AssignmentPostModel();
            case 'discussion':
            default:
                return new DiscussionPostModel();
        }
    }
}
