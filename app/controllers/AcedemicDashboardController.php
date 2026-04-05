<?php

require_once __DIR__ . '/../models/QuestionModel.php';

class AcedemicDashboardController
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $questionModel = new QuestionModel();
        $myQuestionAnswers = $questionModel->getMyQuestionsLatestAnswers((int)$_SESSION['user_id'], 5);

        require_once __DIR__ . '/../views/acedemicdashboard.php';
    }
}
