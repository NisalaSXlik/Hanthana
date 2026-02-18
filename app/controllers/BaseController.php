<?php
class BaseController
{
    protected $data;

    public function __construct()
    {
        $raw = file_get_contents('php://input');
        $this->data = json_decode($raw, true) ?? [];
    }

    protected function requireAuth()
    {
        if (!isset($_SESSION['user_id']))
        {
            $this->response(['error' => 'Unauthorized'], 401);
            $this->redirect('Login');
        }
    }

    protected function response($data, $status = 200)
    {
        if (isset($data['redirect']))
            $data['redirect'] = 'index.php?controller='. $data['redirect'] .'&action=index';
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function render($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/'. $view .'.php';
    }

    protected function redirect($page)
    {
        header('Location: index.php?controller='. $page .'&action=index');
        exit;
    }
}
