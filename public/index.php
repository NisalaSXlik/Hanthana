<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/helpers/MediaHelper.php';
session_start();

spl_autoload_register(function ($class)
{
    $controllerPath[] =__DIR__ . '/../app/controllers/api/' . $class . '.php';
    $controllerPath[] = __DIR__ . '/../app/controllers/web/' . $class . '.php';
    $modelPath = __DIR__ . '/../app/models/' . $class . '.php';
    
    $paths = array_merge($controllerPath, [$modelPath]);

    foreach ($paths as $path)
    {
        if (file_exists($path))
        {
            require_once $path;
            return;
        }
    }
});

// Parse controller and action from query params (fallback routing)
$controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Feed';
$actionName = isset($_GET['action']) ? $_GET['action'] : 'index';

$controllerClass = $controllerName . 'Controller'; // Build controller class name

if (class_exists($controllerClass)) // Check if controller exists
{
    $controller = new $controllerClass();
    if (method_exists($controller, $actionName))
    {
        $controller->$actionName(); // Call action
    }
    else
    {
        http_response_code(404);
        echo json_encode(['error' => "Action '$actionName' not found"]);
    }
}
else
{
    http_response_code(404);
    echo json_encode(['error' => "Controller '$controllerClass' not found"]);
}
