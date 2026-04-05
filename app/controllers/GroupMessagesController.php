<?php
class GroupMessagesController
{
    public function index()
    {
        header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
        exit();
    }
}
