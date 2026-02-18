<?php
class SignupController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // if (isset($_SESSION['user_id']))
        // {
        //     $this->redirect('Feed');
        //     exit();
        // }

        $this->render('Signup');
    }
}