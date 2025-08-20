<?php
// Autoload classes (simple example)
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

// Parse controller and action from URL, default to 'Home' and 'index'
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
        // Action not found
        echo "Action '$actionName' not found in controller '$controllerClass'.";
    }
} else {
    // Controller not found
    echo "Controller '$controllerClass' not found.";
}

