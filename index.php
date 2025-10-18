<?php
require_once __DIR__ . '/config/config.php';  // Load config to define BASE_PATH

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/controllers/' . $class . '.php',
        __DIR__ . '/app/models/' . $class . '.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Parse controller and action from query params (fallback routing)
$controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Home';
$actionName = isset($_GET['action']) ? $_GET['action'] : 'index';

// Build controller class name
$controllerClass = $controllerName . 'Controller';

// Check if controller exists
if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    if (method_exists($controller, $actionName)) {
        // Call action
        $controller->$actionName();
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Action '$actionName' not found"]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => "Controller '$controllerClass' not found"]);
}